<?php

namespace App\Services;

use App\Models\Ticket;
use Cloudinary\Api\Upload\UploadApi;
use Cloudinary\Configuration\Configuration;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\PngWriter;

/**
 * Builds the per-ticket QR PNG, composites it on top of the show's ticket
 * template, uploads the result to Cloudinary, and stamps the URL onto the
 * ticket row. Extracted from Admin\BookingController::approve() so the
 * same image pipeline runs from a queue job instead of blocking the
 * admin's HTTP request.
 *
 * Idempotent on `qr_image_path`: if the ticket already has a URL on the
 * row (e.g. the job is retried after a partial failure), the existing
 * image is reused — we never re-upload a duplicate to Cloudinary.
 */
class TicketRenderer
{
    public function __construct()
    {
        Configuration::instance([
            'cloud' => [
                'cloud_name' => env('CLOUDINARY_CLOUD_NAME'),
                'api_key'    => env('CLOUDINARY_API_KEY'),
                'api_secret' => env('CLOUDINARY_API_SECRET'),
            ],
            'url' => ['secure' => true],
        ]);
    }

    /**
     * Build → composite → upload → persist. Returns the secure_url of the
     * Cloudinary asset that's now stored on the ticket row, or null if
     * the show doesn't have a ticket template configured yet (the
     * approve() controller already short-circuits in that case, so this
     * is just defence-in-depth for callers that don't pre-check).
     */
    public function renderAndUpload(Ticket $ticket): ?string
    {
        if ($ticket->qr_image_path) {
            return $ticket->qr_image_path;
        }

        $show = $ticket->booking?->showTime?->show;
        if (! $show || ! $show->ticket_template_path) {
            return null;
        }

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

        @unlink($tempPath);

        $secureUrl = $upload['secure_url'] ?? null;

        if ($secureUrl) {
            // whatsapp_sent stays false: the image is ready but hasn't been
            // delivered yet. The companion SendWhatsAppTicketImageJob is
            // what flips that flag once Meta accepts the send.
            $ticket->update([
                'qr_image_path' => $secureUrl,
                'whatsapp_sent' => false,
            ]);
        }

        return $secureUrl;
    }
}
