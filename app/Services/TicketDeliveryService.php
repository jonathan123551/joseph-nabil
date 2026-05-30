<?php

namespace App\Services;

use App\Models\Theater;
use App\Models\Ticket;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Outbound-WhatsApp helper.
 *
 * Extracted verbatim from Admin\BookingController so the same code paths
 * (template send for new bookings, image send for ticket delivery) can be
 * invoked from queue jobs without going through `app(...)` controller
 * lookup. No behavior change vs. pre-Tier-2: the network call shape, the
 * Cloudinary URL rewrite, the caption text, and the structured log lines
 * are all identical to what shipped before this refactor — the methods
 * just live here now instead of on the controller, so a `queue:work`
 * worker can call them with no HTTP request context.
 */
class TicketDeliveryService
{
    /**
     * Send the approved-booking confirmation TEMPLATE message (no media,
     * the customer taps "استلام التذكرة" to trigger the image send via
     * the webhook). Matches the pre-Tier-2 contract verbatim.
     */
    public function sendTemplate(string $phone): Response
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
                    'language' => ['code' => 'ar_EG'],
                    'components' => [],
                ],
            ]
        );

        // body_raw is the response body as a raw string — needed because
        // Monolog truncates deeply nested error objects to the placeholder
        // "Over 9 levels deep, aborting normalization" when normalizing
        // them into the structured `body` field.
        Log::info('WA OUTBOUND TEMPLATE', [
            'phone' => $phone,
            'status' => $response->status(),
            'ok' => $response->successful(),
            'body' => $response->json(),
            'body_raw' => $response->body(),
        ]);

        return $response;
    }

    /**
     * Send the ticket QR image with the cinematic caption. The image URL
     * is rewritten through whatsAppMediaUrl() so Meta fetches a downsized
     * JPEG instead of the raw high-res PNG (Meta rejects > 5 MB media).
     */
    public function sendImage(Ticket $ticket): Response
    {
        $phone = preg_replace('/[^0-9]/', '', (string) $ticket->phone);

        $mediaUrl = $this->whatsAppMediaUrl((string) $ticket->qr_image_path);

        // Eager-load the relationships the caption builder needs so a
        // queue worker (which has no request-scoped cache) doesn't fall
        // back to lazy queries inside the formatter.
        $ticket->loadMissing(['booking.showTime', 'booking.seats', 'bookingSeat']);

        $caption = $this->buildTicketCaption($ticket);

        $response = Http::withToken(env('WHATSAPP_TOKEN'))->post(
            'https://graph.facebook.com/v23.0/'.env('WHATSAPP_PHONE_ID').'/messages',
            [
                'messaging_product' => 'whatsapp',
                'to' => $phone,
                'type' => 'image',
                'image' => [
                    'link' => $mediaUrl,
                    'caption' => $caption,
                ],
            ]
        );

        Log::info('WA OUTBOUND IMAGE', [
            'phone' => $phone,
            'status' => $response->status(),
            'ok' => $response->successful(),
            'body' => $response->json(),
            'body_raw' => $response->body(),
            'link' => $mediaUrl,
        ]);

        return $response;
    }

    /**
     * Inject a Cloudinary transformation between "image/upload/" and the
     * version segment so Meta fetches a downsized JPEG instead of the raw
     * high-res PNG. Non-Cloudinary URLs (or already-transformed URLs)
     * pass through untouched.
     *
     *   /image/upload/v123/...png
     *     → /image/upload/q_auto,f_jpg,w_2000,c_limit/v123/...png
     */
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

    /**
     * Builds the cinematic ticket caption sent next to the QR image.
     * Only customer name, show date, show time and seat label are
     * dynamic — the movie title and the surrounding flavour text are
     * intentionally hardcoded so the brand voice stays consistent.
     */
    private function buildTicketCaption(Ticket $ticket): string
    {
        $customerName = trim((string) $ticket->name);
        $showTime = $ticket->booking?->showTime;

        $showDate = $showTime?->date
            ? $showTime->date->format('d/m/Y')
            : '';
        $showTimeStr = $showTime?->time
            ? \Carbon\Carbon::parse($showTime->time)->format('h:i A')
            : '';

        $seatLabel = $this->seatLabelForTicket($ticket);
        $seatLine = $seatLabel !== '' ? $seatLabel : '—';

        // Hardcoded film title — per product spec, this stays static.
        $filmTitle = 'قصة حياة الراهب بولس المقاري';

        return "مرحبًا {$customerName} 👋\n\n"
            ."🎬  تم تأكيد حجزك لفيلم العابد \n"
            ."\"{$filmTitle}\" 🎬\n\n"
            ."ننتظركم لنعيش معًا رحلة ممتعة من سيرة الراهب السائح ومبدد الأوجاع بصلواته ✨\n\n"
            ."📅 موعد العرض:\n"
            ."{$showDate}\n\n"
            ."🕔 الساعة:\n"
            ."{$showTimeStr}\n\n"
            ."🎟️ الكرسي:\n"
            ."{$seatLine}\n\n"
            ."⚠️ ملاحظة:\n"
            ."يرجى إحضار التذكرة عند الدخول.\n\n"
            .'✨ بركة أبونا بولس العابد تكون معكم.';
    }

    /**
     * Resolves a ticket's seat to the "صالةA11" / "بلكونB7" format.
     *   • Anba flow: 1 ticket → 1 booking_seat — single label.
     *   • Manual / "other" venue flow: ticket has no booking_seat,
     *     so we fall back to all seats on the parent booking joined
     *     with " • " ("صالةA11 • صالةA12"). Returns '' when no
     *     seats exist (purely-virtual / manual bookings).
     */
    private function seatLabelForTicket(Ticket $ticket): string
    {
        if ($ticket->booking_seat_id && $ticket->bookingSeat) {
            return $this->formatSeatLabel(
                $ticket->bookingSeat->section,
                $ticket->bookingSeat->row_letter,
                $ticket->bookingSeat->seat_number,
            );
        }

        $booking = $ticket->booking;
        if ($booking && $booking->seats && $booking->seats->isNotEmpty()) {
            return $booking->seats
                ->map(fn ($s) => $this->formatSeatLabel($s->section, $s->row_letter, $s->seat_number))
                ->implode(' • ');
        }

        return '';
    }

    /**
     * Render one seat as "صالةA11" / "بلكونB7". Defensive against
     * missing pieces so we never emit a partial label like "صالةnull".
     */
    private function formatSeatLabel(?string $section, ?string $rowLetter, $seatNumber): string
    {
        $sectionLabel = Theater::SECTION_LABELS[$section] ?? '';
        $row = strtoupper(trim((string) ($rowLetter ?? '')));
        $seat = trim((string) ($seatNumber ?? ''));

        if ($sectionLabel === '' && $row === '' && $seat === '') {
            return '';
        }

        return $sectionLabel.$row.$seat;
    }
}
