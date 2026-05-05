@extends('layouts.app')

@section('title', 'اختار مقعدك - ' . $showTime->show->title)

@section('content')

{{-- =====================================================================
     STEP 2 — Seat selection (canvas seat picker only).
     The seat picker partial reads the current section + show time from
     data-* attributes; on Continue it persists to localStorage and
     redirects to the form page (step 3). No backend changes here.
===================================================================== --}}
<section class="max-w-7xl mx-auto px-3 sm:px-5">
    @include('bookings._anba_seat_picker', [
        'showTime'         => $showTime,
        'section'          => $section ?? 'hall',
        'seatsByRow'       => $seatsByRow ?? [],
        'unavailableSeats' => $unavailableSeats ?? [],
        'blockedSeats'     => $blockedSeats ?? [],
        'balconyPrice'     => $balconyPrice ?? 0,
        'hallPrice'        => $hallPrice ?? 0,
        'transferWallet'   => $transferWallet ?? '',
        'transferInsta'    => $transferInsta ?? '',
    ])
</section>

@endsection
