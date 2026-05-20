<?php

namespace App\Jobs;

use App\Models\Ticket;
use App\Services\TicketDeliveryService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Per-ticket WhatsApp image-send job. Replaces the synchronous
 * sendWhatsAppTicket(...) calls inside:
 *   • Admin\BookingController::approve() (post image-generation loop)
 *   • Admin\BookingController::resendTicket()
 *   • Admin\BookingController::resendByReference()
 *   • WhatsAppWebhookController::handle() (customer-initiated "استلام")
 *
 * Failure semantics:
 *   • 3 tries with 5/15/60 s backoff for retryable Meta errors
 *     (network blips, transient 5xx, rate-limit 429).
 *   • Permanent Meta errors (131026 recipient not on WA, 131047
 *     outside the 24 h window, 131053 media download error) are
 *     marked as failed immediately so they don't waste retry budget.
 *   • whatsapp_sent flips to TRUE only when Meta acks the send. A
 *     terminal failure leaves the row at whatsapp_sent=false so the
 *     admin's resend UI can re-dispatch.
 */
class SendWhatsAppTicketImageJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public function backoff(): array
    {
        return [5, 15, 60];
    }

    public function __construct(public int $ticketId)
    {
    }

    public function handle(TicketDeliveryService $delivery): void
    {
        $ticket = Ticket::with(['booking.showTime', 'booking.seats', 'bookingSeat'])
            ->find($this->ticketId);

        if (! $ticket) {
            Log::warning('SendWhatsAppTicketImageJob: ticket not found', [
                'ticket_id' => $this->ticketId,
            ]);

            return;
        }

        if (! $ticket->qr_image_path) {
            // No image to send yet — usually means the upstream
            // GenerateTicketImageJob hasn't succeeded for this ticket.
            // Re-dispatch the generation step instead of retrying us;
            // GenerateTicketImageJob will chain back to us on success.
            Log::info('SendWhatsAppTicketImageJob: deferring, no qr_image_path', [
                'ticket_id' => $this->ticketId,
            ]);
            GenerateTicketImageJob::dispatch($this->ticketId)
                ->onQueue($this->queue ?? 'high');

            return;
        }

        $response = $delivery->sendImage($ticket);

        if ($response->successful()) {
            $ticket->update(['whatsapp_sent' => true]);

            return;
        }

        // Try to surface a Meta error code from the response envelope.
        // Permanent error codes get $this->fail()'d so they hit
        // failed_jobs immediately and don't burn retries.
        $errorCode = $response->json('error.code');
        $permanent = in_array($errorCode, [131026, 131047, 131053], true);

        if ($permanent) {
            $this->fail(new \RuntimeException(
                'Permanent Meta error '.$errorCode.' — '.$response->body()
            ));

            return;
        }

        // Retryable — throw so the queue worker schedules the next attempt.
        throw new \RuntimeException(
            'WhatsApp image send failed (status '.$response->status().'): '.$response->body()
        );
    }

    public function failed(\Throwable $e): void
    {
        Log::error('SendWhatsAppTicketImageJob: failed', [
            'ticket_id' => $this->ticketId,
            'error'     => $e->getMessage(),
        ]);
    }
}
