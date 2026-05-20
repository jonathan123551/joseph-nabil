<?php

namespace App\Jobs;

use App\Models\Ticket;
use App\Services\TicketRenderer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Per-ticket image-build job. Replaces the in-request `for each ticket`
 * loop in Admin\BookingController::approve() that used to build the QR,
 * composite onto the template, and upload to Cloudinary while the admin
 * sat on a spinning cursor.
 *
 * On success: dispatches SendWhatsAppTicketImageJob for the same ticket
 * so the freshly-uploaded image goes out via WhatsApp without a second
 * round trip to a controller.
 *
 * Failure semantics:
 *   • 3 tries with 5/15/60 s backoff. After the 3rd failure the row
 *     lands in `failed_jobs` for manual replay via `php artisan
 *     queue:retry <id>` or the admin's existing "إعادة إرسال" UI
 *     button (which re-dispatches SendWhatsAppTicketImageJob, not this
 *     job — by the time a human is looking the QR may already exist).
 *   • The ticket row stays with qr_image_path=null on terminal failure
 *     so the next manual click can re-run the pipeline.
 *   • Idempotent on qr_image_path: a retry that succeeds after a
 *     partial-failure re-uses the existing Cloudinary URL instead of
 *     uploading a duplicate.
 */
class GenerateTicketImageJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    /**
     * Exponential-ish backoff: 5 s, 15 s, 60 s.
     */
    public function backoff(): array
    {
        return [5, 15, 60];
    }

    public function __construct(public int $ticketId)
    {
    }

    public function handle(TicketRenderer $renderer): void
    {
        $ticket = Ticket::with(['booking.showTime.show'])->find($this->ticketId);

        if (! $ticket) {
            Log::warning('GenerateTicketImageJob: ticket not found', [
                'ticket_id' => $this->ticketId,
            ]);

            return;
        }

        $url = $renderer->renderAndUpload($ticket);

        if (! $url) {
            // The show has no ticket_template_path yet. Same fail-soft
            // behavior as the original `if (! $show->ticket_template_path)`
            // guard in approve(): we don't error, the admin just doesn't
            // get a generated ticket image until they configure one.
            Log::info('GenerateTicketImageJob: skipped (no template)', [
                'ticket_id' => $this->ticketId,
            ]);

            return;
        }

        // Chain the WhatsApp image send. Same queue as us so the worker
        // drains tickets in order without any racy half-states.
        SendWhatsAppTicketImageJob::dispatch($this->ticketId)
            ->onQueue($this->queue ?? 'high');
    }

    public function failed(\Throwable $e): void
    {
        Log::error('GenerateTicketImageJob: failed', [
            'ticket_id' => $this->ticketId,
            'error'     => $e->getMessage(),
        ]);
    }
}
