<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Ticket;
use Cloudinary\Api\Upload\UploadApi;
use Cloudinary\Configuration\Configuration;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class BookingController extends Controller
{
    public function __construct()
    {
        Configuration::instance([
            'cloud' => [
                'cloud_name' => env('CLOUDINARY_CLOUD_NAME'),
                'api_key' => env('CLOUDINARY_API_KEY'),
                'api_secret' => env('CLOUDINARY_API_SECRET'),
            ],
            'url' => ['secure' => true],
        ]);
    }

    public function index(Request $request)
    {
        // Eager-load tickets + each ticket's bookingSeat so the admin index
        // can render "name — section row+number" inline without N+1 queries.
        $bookings = Booking::with([
            'showTime.show',
            'tickets.bookingSeat',
        ])
            ->latest()
            ->get();

        return view('admin.bookings.index', compact('bookings'));
    }

    public function show(Booking $booking)
    {
        // Eager-load each ticket's bookingSeat so the per-ticket row in the
        // booking-details view can display the assigned section+seat beside
        // the attendee name.
        $booking->load('showTime.show', 'tickets.bookingSeat');

        return view('admin.bookings.show', compact('booking'));
    }

    /* =======================
     |  APPROVE BOOKING
     ======================= */
    public function approve(Booking $booking)
    {
        if ($booking->status === 'approved') {
            return back()->with('status', 'الحجز معتمد بالفعل');
        }

        $booking->load('showTime.show', 'tickets');

        $show = $booking->showTime?->show;

        if (! $show || ! $show->ticket_template_path) {
            return back()->with('status', 'لا يوجد قالب تذكرة لهذا العرض');
        }

        $booking->update([
            'status' => 'approved',
            'approved_at' => now(),
        ]);

        foreach ($booking->tickets as $ticket) {

            if ($ticket->qr_image_path) {
                continue;
            }

            /* === QR === */
            $qr = Builder::create()
                ->writer(new PngWriter)
                ->data($ticket->ticket_code)
                ->size($show->ticket_qr_size ?? 220)
                ->margin(0)
                ->build();

            $templateImage = imagecreatefromstring(
                file_get_contents($show->ticket_template_path)
            );

            $qrImage = imagecreatefromstring($qr->getString());

            imagecopy(
                $templateImage,
                $qrImage,
                $show->ticket_qr_x ?? 0,
                $show->ticket_qr_y ?? 0,
                0,
                0,
                imagesx($qrImage),
                imagesy($qrImage)
            );

            $tempPath = sys_get_temp_dir().'/'.$ticket->ticket_code.'.png';

            imagepng($templateImage, $tempPath);

            imagedestroy($templateImage);
            imagedestroy($qrImage);

            $upload = (new UploadApi)->upload($tempPath, [
                'folder' => 'tickets/generated',
            ]);

            unlink($tempPath);

            $ticket->update([
                'qr_image_path' => $upload['secure_url'],
                'whatsapp_sent' => false, // مهم جدًا
            ]);
        }

        foreach ($booking->tickets as $ticket) {

            $this->sendTicketTemplate(
                $ticket->phone
            );

            sleep(1);
        }

        return redirect()
            ->route('admin.bookings.show', $booking->id)
            ->with('status', 'تم اعتماد الحجز وإرسال رسالة الاستلام ✅');
    }

    /* =======================
     | TEMPLATE
     ======================= */
    public function sendTicketTemplate($phone)
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);

        $response = Http::withToken(env('WHATSAPP_TOKEN'))->post(
            'https://graph.facebook.com/v23.0/'.env('WHATSAPP_PHONE_ID').'/messages',
            [
                'messaging_product' => 'whatsapp',
                'to' => $phone,
                'type' => 'template',
                'template' => [
                    'name' => 'ticket',
                    'language' => [
                        'code' => 'ar_EG',
                    ],
                    'components' => [], // 🔥 مهم جداً
                ],
            ]
        );

        // Capture Graph API response so silent Meta-side failures (e.g.
        // template not approved, language mismatch, 24h window issues for
        // followup sends) surface in Railway logs instead of being
        // discarded by the fire-and-forget HTTP call.
        //
        // body_raw is the response body as a raw string — needed because
        // Monolog truncates deeply nested error objects to the placeholder
        // "Over 9 levels deep, aborting normalization" when normalizing
        // them into the structured `body` field.
        \Log::info('WA OUTBOUND TEMPLATE', [
            'phone'    => $phone,
            'status'   => $response->status(),
            'ok'       => $response->successful(),
            'body'     => $response->json(),
            'body_raw' => $response->body(),
        ]);
    }

    /* =======================
     | SEND IMAGE
     ======================= */
    public function sendWhatsAppTicket($phone, $imageUrl, $reference, $full_name, $showTimeText)
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);

        // WhatsApp Cloud API rejects images larger than 5 MB. The ticket
        // PNGs we upload to Cloudinary in approve() are commonly 5–6 MB
        // at full resolution (2159×2699), which causes Meta to 200-accept
        // the /messages call and then asynchronously fail delivery with
        // error 131053 ("Media download error") via the statuses webhook.
        // Routing the link through whatsAppMediaUrl() injects a Cloudinary
        // transformation that downsizes the image to JPEG / 2000 px wide /
        // auto-quality — enough to stay well under 5 MB while keeping the
        // QR code legible. The DB still stores the original high-res PNG
        // URL; only the link sent to Meta is rewritten.
        $mediaUrl = $this->whatsAppMediaUrl($imageUrl);

        $response = Http::withToken(env('WHATSAPP_TOKEN'))->post(
            'https://graph.facebook.com/v23.0/'.env('WHATSAPP_PHONE_ID').'/messages',
            [
                'messaging_product' => 'whatsapp',
                'to' => $phone,
                'type' => 'image',
                'image' => [
                    'link' => $mediaUrl,
                    'caption' => "*🎟️ أهلاً {$full_name}*\n\n"
                            ."يسعدنا وجودك معنا،\n"
                            ."أنت الآن جزء من تجربة جديدة نصرخ فيها سويًا…\n\n"
                            ."ليزداد العقل وعيًا.\n\n"
                            ."نتمنى لك أمسية ثرية بالفن ✨\n\n"
                            ."نحن لا نطلب منك سوى حواسك،\n"
                            ."ولا ننتظر منك إلا أن تأتي إلى مصدر الصراخ…\n"
                            ."فهو دائمًا على المسرح 🎭\n\n"
                            ."نلتقي لنصرخ معًا،\n"
                            ."فنغيّر ما فسد،\n"
                            ."ونزرع بدلًا منه ثمرًا صالحًا ❤️\n\n"
                            ."🗓️ *موعد الحفلة:*\n"
                            ."{$showTimeText}\n\n"
                            .'‼️ *يرجى إحضار هذه التذكرة عند الدخول*',
                ],
            ]
        );

        // Capture Graph API response so failures surface in Railway logs.
        // Freeform image sends are gated by the 24h customer service window
        // and will be rejected with code 131047 outside that window — making
        // that visible is the only way to debug silent resend failures.
        //
        // body_raw is the response body as a raw string — needed because
        // Monolog truncates deeply nested error objects to the placeholder
        // "Over 9 levels deep, aborting normalization" when normalizing
        // them into the structured `body` field.
        \Log::info('WA OUTBOUND IMAGE', [
            'phone'    => $phone,
            'status'   => $response->status(),
            'ok'       => $response->successful(),
            'body'     => $response->json(),
            'body_raw' => $response->body(),
            'link'     => $mediaUrl,
        ]);
    }

    /* =======================
     | CLOUDINARY → WHATSAPP-SAFE URL
     |
     | Inject a Cloudinary transformation between "image/upload/" and the
     | version segment so Meta fetches a downsized JPEG instead of the raw
     | high-res PNG. Non-Cloudinary URLs (or already-transformed URLs)
     | pass through untouched.
     |
     |   /image/upload/v123/...png
     |     → /image/upload/q_auto,f_jpg,w_2000,c_limit/v123/...png
     |
     | q_auto:   automatic quality (Cloudinary chooses lossy level)
     | f_jpg:    deliver JPEG (PNG photographic content is much larger)
     | w_2000:   limit width to 2000 px
     | c_limit:  only downscale; never upscale a smaller original
     ======================= */
    private function whatsAppMediaUrl(string $url): string
    {
        if (! preg_match('#/image/upload/v\d+/#', $url)) {
            return $url;
        }

        return preg_replace(
            '#/image/upload/v#',
            '/image/upload/q_auto,f_jpg,w_2000,c_limit/v',
            $url,
            1
        );
    }

    /* =======================
     | WEBHOOK (استلام التذاكر)
     ======================= */
    public function receiveTicket(Request $request)
    {
        $phone = $request['from'];

        // تنظيف الرقم
        $phone = preg_replace('/[^0-9]/', '', $phone);

        \Log::info('USER CLICKED', ['phone' => $phone]);

        // هات كل التذاكر اللي لسه متبعتتش لنفس الرقم
        $tickets = Ticket::where('phone', $phone)
            ->where('whatsapp_sent', false)
            ->get();

        if ($tickets->isEmpty()) {
            return response()->json(['status' => 'no tickets']);
        }

        foreach ($tickets as $ticket) {

            if (! $ticket->qr_image_path) {
                continue;
            }

            $this->sendWhatsAppTicket(
                $ticket->phone,
                $ticket->qr_image_path,
                $ticket->ticket_code,
                $ticket->name,
                ''
            );

            $ticket->update([
                'whatsapp_sent' => true,
            ]);
        }

        return response()->json(['status' => 'sent']);
    }

    /* =======================
     | REJECT
     ======================= */
    public function reject(Booking $booking)
    {
        if ($booking->status === 'rejected') {
            return back()->with('status', 'الحجز مرفوض بالفعل');
        }

        if ($booking->status === 'approved') {
            return back()->with('status', 'لا يمكن رفض حجز تم اعتماده');
        }

        $booking->update([
            'status' => 'rejected',
            'rejected_at' => now(),
        ]);

        return redirect()
            ->route('admin.bookings.index')
            ->with('status', 'تم رفض الحجز بنجاح ❌');
    }

    /* =======================
 | RESEND TICKET
 ======================= */
    public function resendTicket(Request $request, $id)
    {
        $ticket = Ticket::findOrFail($id);

        if (! $ticket->qr_image_path) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['ok' => false, 'reason' => 'no_qr'], 200);
            }

            return back()->with('status', '❌ التذكرة لم يتم إنشاؤها بعد');
        }

        $this->sendWhatsAppTicket(
            $ticket->phone,
            $ticket->qr_image_path,
            $ticket->ticket_code,
            $ticket->name,
            ''
        );

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['ok' => true], 200);
        }

        return back()->with('status', '✅ تم إعادة إرسال التذكرة');
    }

    public function delete($id)
    {
        $booking = Booking::with('tickets')->findOrFail($id);

        // حذف التذاكر
        foreach ($booking->tickets as $ticket) {
            $ticket->delete();
        }

        // حذف الحجز
        $booking->delete();

        return redirect()->route('admin.bookings.index')
            ->with('status', 'تم حذف الحجز بالكامل');
    }

    /* =======================
     | PUBLIC TICKET-BY-REFERENCE LOOKUP
     |
     | Customer-facing self-serve page reachable at GET /ticket/{reference}.
     | Renders one of three variants based on booking status. Intentionally
     | minimal: no admin actions, no payment screenshot, masked phone. The
     | route stays public — same posture as the scanner — so a link sent in
     | a WhatsApp template / email keeps working without auth.
     ======================= */
    public function sendTicketsByReference(string $reference)
    {
        $booking = Booking::with(['tickets', 'seats', 'showTime.show'])
            ->where('reference_code', $reference)
            ->first();

        if (! $booking) {
            // Branded 404 — never throws so we don't leak debug info.
            return response()->view('errors.404', [], 404);
        }

        return view('tickets.show', [
            'booking' => $booking,
            'maskedPhone' => $this->maskPhone($booking->phone),
        ]);
    }

    /* =======================
     | RESEND BY REFERENCE
     |
     | Customer-initiated resend of an approved booking's tickets to the
     | phone already on file. Rate-limited to 1 request / 60s per booking.
     | Never accepts a user-supplied phone number.
     ======================= */
    public function resendByReference(Request $request, string $reference)
    {
        $booking = Booking::with('tickets')
            ->where('reference_code', $reference)
            ->first();

        if (! $booking) {
            return response()->view('errors.404', [], 404);
        }

        // Flash codes (not translated strings) — the view picks the right
        // localized text based on the active language. Keeps this code free
        // of Arabic/English string literals and respects the JS-side i18n
        // system the rest of the app uses.
        $redirect = redirect()->route('tickets.show', ['reference' => $reference]);

        if ($booking->status !== 'approved') {
            return $redirect->with('ticket_lookup_status', 'not_approved');
        }

        $cacheKey = 'ticket_resend:'.$booking->id;
        if (cache()->has($cacheKey)) {
            $secondsLeft = max(1, (int) cache()->get($cacheKey) - now()->timestamp);

            return $redirect
                ->with('ticket_lookup_status', 'cooldown')
                ->with('ticket_lookup_cooldown', $secondsLeft);
        }

        $sent = 0;
        foreach ($booking->tickets as $ticket) {
            if (! $ticket->qr_image_path) {
                continue;
            }

            $this->sendWhatsAppTicket(
                $ticket->phone,
                $ticket->qr_image_path,
                $ticket->ticket_code,
                $ticket->name,
                ''
            );

            $ticket->update(['whatsapp_sent' => true]);
            $sent++;
        }

        // 60-second cooldown — value stored is the unix timestamp at which
        // the cooldown expires, so the UI can show a precise countdown.
        cache()->put($cacheKey, now()->timestamp + 60, now()->addSeconds(60));

        return $redirect->with('ticket_lookup_status', $sent > 0 ? 'success' : 'no_qr');
    }

    /**
     * Mask a phone number to its last 4 digits — `********1234`.
     * Safe to display on a publicly-shareable page.
     */
    private function maskPhone(?string $phone): string
    {
        $digits = preg_replace('/[^0-9]/', '', (string) $phone);
        $len = strlen($digits);
        if ($len <= 4) {
            return $digits;
        }

        return str_repeat('•', max(4, $len - 4)).substr($digits, -4);
    }
}
