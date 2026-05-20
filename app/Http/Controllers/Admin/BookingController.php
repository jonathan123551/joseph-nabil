<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\GenerateTicketImageJob;
use App\Jobs\SendWhatsAppTicketImageJob;
use App\Jobs\SendWhatsAppTicketTemplateJob;
use App\Models\Booking;
use App\Models\Theater;
use App\Models\Ticket;
use Cloudinary\Configuration\Configuration;
use Illuminate\Http\Request;

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
     |
     |  Tier-2: the per-ticket QR build, image composite, Cloudinary
     |  upload and WhatsApp template send used to run inline here — the
     |  admin's browser blocked for 8–17 seconds on a 4-ticket booking,
     |  including an explicit `sleep(1)` between WhatsApp calls to dodge
     |  Meta rate limits.
     |
     |  Now: the Booking::update(status=approved) still commits sync (so
     |  the admin's redirect target shows the new state immediately) and
     |  the heavy work fans out to queued jobs:
     |    • GenerateTicketImageJob — builds QR + composite + uploads
     |      to Cloudinary, then chains SendWhatsAppTicketImageJob for
     |      the same ticket on success.
     |    • SendWhatsAppTicketTemplateJob — the per-phone template
     |      message that used to be guarded by sleep(1). The single
     |      queue worker drains one job at a time, replicating the
     |      pacing without burning admin wall-time.
     |  Rollback to the pre-Tier-2 inline behavior is one env var:
     |  set QUEUE_CONNECTION=sync and every dispatch() above runs
     |  inline on the request thread, byte-identical to today's flow.
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
            // Each ticket gets its own image-generation job. The job
            // is idempotent on qr_image_path: tickets that already
            // have an image (e.g. an admin un-approving and re-
            // approving) skip the Cloudinary upload and chain straight
            // to the WhatsApp send job.
            GenerateTicketImageJob::dispatch($ticket->id)->onQueue('high');

            // Template send (the customer-facing "تم تأكيد حجزك" tap-
            // to-receive template) is independent of the image
            // pipeline — dispatch it directly. Pacing that used to
            // come from in-request sleep(1) now comes from the
            // single-concurrency queue worker draining `high` in
            // order.
            SendWhatsAppTicketTemplateJob::dispatch($ticket->phone)->onQueue('high');
        }

        return redirect()
            ->route('admin.bookings.show', $booking->id)
            ->with('status', 'تم اعتماد الحجز — يتم إرسال التذاكر في الخلفية ✅');
    }

    // Tier-2 note: the previous in-controller helpers (sendTicketTemplate,
    // sendWhatsAppTicket, buildTicketCaption, seatLabelForTicket,
    // formatSeatLabel, whatsAppMediaUrl) all moved verbatim to
    // App\Services\TicketDeliveryService and App\Services\TicketRenderer
    // so the queue jobs can call them without going through `app(...)`.
    // The text, URL transform, network shape, and log lines are identical;
    // only the location changed.

    /* =======================
     | WEBHOOK (استلام التذاكر)
     |
     | Legacy webhook handler — not currently wired to a route (the live
     | webhook is WhatsAppWebhookController::handle). Kept for compatibility
     | with any external integration that may still POST here. Switched to
     | dispatching SendWhatsAppTicketImageJob so it shares the same async
     | path as the live webhook.
     ======================= */
    public function receiveTicket(Request $request)
    {
        $phone = $request['from'];
        $phone = preg_replace('/[^0-9]/', '', $phone);

        \Log::info('USER CLICKED', ['phone' => $phone]);

        $tickets = Ticket::where('phone', $phone)
            ->where('whatsapp_sent', false)
            ->whereNotNull('qr_image_path')
            ->get();

        if ($tickets->isEmpty()) {
            return response()->json(['status' => 'no tickets']);
        }

        foreach ($tickets as $ticket) {
            SendWhatsAppTicketImageJob::dispatch($ticket->id)->onQueue('high');
        }

        return response()->json(['status' => 'queued', 'count' => $tickets->count()]);
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

        // Tier-2: dispatch instead of inline send. The admin's button
        // returns immediately; the worker drains the actual send.
        SendWhatsAppTicketImageJob::dispatch($ticket->id)->onQueue('high');

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

        // Tier-2: dispatch one image-send job per ticket. We no longer
        // mark `whatsapp_sent = true` here — the job is what flips that
        // flag, only when Meta acks the send. The customer-visible
        // cooldown still starts on dispatch, not on delivery, so a slow
        // worker can't block them from re-clicking.
        $queued = 0;
        foreach ($booking->tickets as $ticket) {
            if (! $ticket->qr_image_path) {
                continue;
            }

            SendWhatsAppTicketImageJob::dispatch($ticket->id)->onQueue('high');
            $queued++;
        }

        // 60-second cooldown — value stored is the unix timestamp at which
        // the cooldown expires, so the UI can show a precise countdown.
        cache()->put($cacheKey, now()->timestamp + 60, now()->addSeconds(60));

        return $redirect->with('ticket_lookup_status', $queued > 0 ? 'success' : 'no_qr');
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
