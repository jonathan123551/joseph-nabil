@extends('layouts.seats-fullscreen')

@section('title', 'اختار مقعدك - ' . $showTime->show->title)

@section('content')

{{-- =====================================================================
     STEP 2 — Seat selection.
     The seat picker partial is rendered in a viewport-filling layout
     (no nav, no footer, no surrounding cards), with the canvas scaled
     to fit the screen so the user never has to scroll.
===================================================================== --}}
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
    'fullscreen'       => true,
])

@endsection
