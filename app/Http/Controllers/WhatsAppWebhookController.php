<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Ticket;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class WhatsAppWebhookController extends Controller
{
    /* =======================
       VERIFY WEBHOOK
    ======================= */
    public function verify(Request $request)
    {
        if (
            $request->hub_mode === 'subscribe' &&
            $request->hub_verify_token === env('WHATSAPP_VERIFY_TOKEN')
        ) {
            return response($request->hub_challenge, 200);
        }

        return response('Forbidden', 403);
    }

    /* =======================
       HANDLE INCOMING
    ======================= */
    public function handle(Request $request)
    {
        // Dump the full webhook envelope before any parsing. The strict
        // string match below historically swallowed button replies whose
        // visible label was approved with a different alef variant, with
        // no signal in logs other than an INCOMING MESSAGE line. With the
        // raw payload available we can always see exactly what Meta sent.
        Log::info('WEBHOOK HIT', $request->all());

        $message = $request->input('entry.0.changes.0.value.messages.0');

        if (!$message || !isset($message['from'])) {
            $this->forwardToChatwoot($request);
            return response()->json(['ok' => true]);
        }

        // 📱 Normalize phone
        $phone = preg_replace('/[^0-9]/', '', $message['from']);

        // 📝 Extract message text safely. Read every shape Meta can deliver:
        //   - text.body                          (typed plain text)
        //   - button.text / button.payload       (legacy template button reply)
        //   - interactive.button_reply.title|id  (interactive button reply)
        // The payload/id sources matter because template buttons sometimes
        // return only the developer-defined id, not the visible label.
        $text = $message['text']['body']
            ?? $message['button']['text']
            ?? $message['button']['payload']
            ?? $message['interactive']['button_reply']['title']
            ?? $message['interactive']['button_reply']['id']
            ?? '';

        Log::info('INCOMING MESSAGE', [
            'phone' => $phone,
            'text'  => $text,
            'type'  => $message['type'] ?? 'unknown',
        ]);

        /* ==========================
           🎟 SEND TICKET LOGIC
        ========================== */

        // Accept either Arabic spelling of "receive ticket" plus the canonical
        // button payload id. Meta returns whichever variant the template was
        // approved with — alef-hamza is the formal spelling, bare alef is the
        // more common one — so we have to match both rather than one.
        $triggers = [
            'أستلام التذكرة',  // alef-hamza U+0623
            'استلام التذكرة',  // bare alef  U+0627
            'receive_ticket',  // button payload id
        ];

        if (in_array(trim($text), $triggers, true)) {

            // ✅ نجيب أول تذكرة لنفس الرقم ولسه متبعتتش
            $ticket = Ticket::where('phone', $phone)
                ->whereNotNull('qr_image_path')
                ->where('whatsapp_sent', false)
                ->orderBy('id') // مهم عشان الترتيب
                ->first();

            if (!$ticket) {
                Log::info('NO TICKET FOUND', ['phone' => $phone]);
                return response()->json(['status' => 'no ticket']);
            }

            Log::info('SENDING TICKET', [
                'ticket_id' => $ticket->id,
                'code' => $ticket->ticket_code
            ]);

            try {

                // 🎭 نجيب ميعاد الحفلة
                $showTimeText = '';

                if ($ticket->booking && $ticket->booking->showTime) {
                    $showTime = $ticket->booking->showTime;

                    $showTimeText =
                        $showTime->date->format('d/m/Y') . ' • ' .
                        Carbon::parse($showTime->time)->format('h:i A');
                }

                // 📤 إرسال التذكرة
                app(\App\Http\Controllers\Admin\BookingController::class)
                    ->sendWhatsAppTicket(
                        $ticket->phone,
                        $ticket->qr_image_path,
                        $ticket->ticket_code,
                        $ticket->name,
                        $showTimeText
                    );

                // ✅ تحديث الحالة (دي أهم نقطة)
                $ticket->update([
                    'whatsapp_sent' => true
                ]);

                Log::info('TICKET SENT SUCCESS', [
                    'ticket_id' => $ticket->id
                ]);

            } catch (\Exception $e) {

                Log::error('SEND FAILED', [
                    'error' => $e->getMessage()
                ]);
            }
        }

        /* ==========================
           🔁 FORWARD TO CHATWOOT
        ========================== */

        $this->forwardToChatwoot($request);

        return response()->json(['ok' => true]);
    }

    private function forwardToChatwoot(Request $request)
    {
        try {

            $chatwootWebhookUrl = env('CHATWOOT_WHATSAPP_WEBHOOK_URL');

            if (!$chatwootWebhookUrl) {
                Log::error('Chatwoot webhook URL not set');
                return;
            }

            Http::timeout(10)->post($chatwootWebhookUrl, $request->all());

        } catch (\Exception $e) {
            Log::error('Forward to Chatwoot failed: ' . $e->getMessage());
        }
    }
}