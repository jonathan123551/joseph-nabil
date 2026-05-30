<?php

namespace App\Http\Controllers;

use App\Jobs\SendWhatsAppTicketImageJob;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

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
        //
        // WEBHOOK HIT is the structured (array) form which Monolog will
        // pretty-print and stop recursing at maxNormalizeDepth (9 by
        // default). WEBHOOK RAW is the raw POST body as a string, which
        // sidesteps the depth limit entirely — useful for inspecting
        // delivery-status callbacks whose `statuses[*].errors[*]` objects
        // sit at depth 9 and get replaced by the placeholder string
        // "Over 9 levels deep, aborting normalization".
        Log::info('WEBHOOK HIT', $request->all());
        Log::info('WEBHOOK RAW', ['raw' => $request->getContent()]);

        $message = $request->input('entry.0.changes.0.value.messages.0');

        if (! $message || ! isset($message['from'])) {
            $this->forwardToChatwoot($request);

            return response()->json(['ok' => true]);
        }

        // 🔒 Idempotency: Meta retries a webhook delivery if we don't 2xx
        // fast enough, and a customer can double-tap the button. Dedupe on
        // the inbound message id so the same tap can't enqueue the same
        // tickets twice (and can't double-forward to Chatwoot).
        $messageId = $message['id'] ?? null;
        if ($messageId && ! Cache::add('wa_in:'.$messageId, 1, now()->addMinutes(10))) {
            Log::info('WEBHOOK DEDUPE — already processed', ['message_id' => $messageId]);

            return response()->json(['ok' => true, 'dedupe' => true]);
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
            'text' => $text,
            'type' => $message['type'] ?? 'unknown',
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

            // 🎟 Drain ALL remaining undelivered tickets for this phone in
            // one tap — not just the first one. Previously the handler sent
            // a single ticket per tap, so a 50-ticket booking needed 50 taps
            // and any tickets the customer never tapped for were lost.
            //
            // Scope: only APPROVED bookings are eligible (a ticket's QR is
            // only built at approval, so pending/rejected bookings never
            // leak). Each ticket gets its own queued job; the job's atomic
            // claim guarantees exactly-once delivery even if this same tap
            // arrives twice or collides with a resend.
            $ticketIds = Ticket::where('phone', $phone)
                ->where('whatsapp_sent', false)
                ->whereHas('booking', fn ($q) => $q->where('status', 'approved'))
                ->orderBy('id')
                ->pluck('id');

            if ($ticketIds->isEmpty()) {
                Log::info('NO TICKET FOUND', ['phone' => $phone]);
                $this->forwardToChatwoot($request);

                return response()->json(['status' => 'no ticket']);
            }

            Log::info('QUEUEING TICKETS', [
                'phone' => $phone,
                'count' => $ticketIds->count(),
            ]);

            foreach ($ticketIds as $ticketId) {
                SendWhatsAppTicketImageJob::dispatch($ticketId)->onQueue('high');
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

            if (! $chatwootWebhookUrl) {
                Log::error('Chatwoot webhook URL not set');

                return;
            }

            Http::timeout(10)->post($chatwootWebhookUrl, $request->all());

        } catch (\Exception $e) {
            Log::error('Forward to Chatwoot failed: '.$e->getMessage());
        }
    }
}
