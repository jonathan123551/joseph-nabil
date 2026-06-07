<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Outbound image-send pacing
    |--------------------------------------------------------------------------
    |
    | Minimum spacing applied before each outbound ticket-image send, in
    | milliseconds. The queue worker runs at single concurrency (one process,
    | one replica), so a short sleep here spaces successive Meta calls enough
    | to avoid burst throttling on large bookings without paying the old
    | full 1-second-per-ticket cost. Set to 0 to disable.
    |
    | NOTE: this assumes the worker stays single-concurrency. If the `web`
    | service is ever scaled past 1 replica (or numprocs is raised), pacing
    | must move to a shared limiter (e.g. Redis::throttle) since an in-process
    | sleep only paces a single worker.
    |
    */
    'send_pacing_ms' => (int) env('WHATSAPP_SEND_PACING_MS', 350),

    /*
    |--------------------------------------------------------------------------
    | Stranded "sending" recovery window
    |--------------------------------------------------------------------------
    |
    | A ticket left in delivery_status='sending' for longer than this many
    | seconds is treated as a dead attempt (worker killed mid-send before the
    | failed() callback could run) and becomes re-claimable again. This MUST
    | exceed the job timeout + the queue connection's retry_after so it can
    | never steal a still-running attempt.
    |
    */
    'sending_stale_after' => (int) env('WHATSAPP_SENDING_STALE_AFTER', 300),

    /*
    |--------------------------------------------------------------------------
    | Throttle release delays
    |--------------------------------------------------------------------------
    |
    | When Meta returns a rate-limit error code, the job is released back to
    | the queue with these escalating delays (seconds) keyed by attempt number
    | instead of the short default backoff, so we stop hammering Meta inside
    | the same throttle window. The last value is reused for further attempts.
    |
    */
    'throttle_backoff' => [60, 180, 300, 300],

];
