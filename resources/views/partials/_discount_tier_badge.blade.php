{{--
    Discount-tier badge — single source of truth for the branded
    chip shown next to a booking's ticket count in admin / customer
    surfaces.

    Reads the booking's *stored* discount_percent and classifies it
    against `BookingPricing::resolveTierForPercent()`. Legacy rows
    that paid 20 % under the old flat rule still render correctly
    as "🎁 -20% خصومات العيلة" — they paid that price, they get
    that label.

    Vars:
      $booking      = required \App\Models\Booking
      $variant      = optional 'inline' (default, table cells) | 'pill'
                      | 'bar' (sticky admin action bar).
--}}
@php
    use App\Support\BookingPricing;
    $variantBadge   = $variant ?? 'inline';
    $percentBadge   = (int) ($booking->discount_percent ?? 0);
    $amountBadge    = (int) ($booking->discount_amount ?? 0);
    $tierBadgeMeta  = BookingPricing::resolveTierForPercent($percentBadge);
@endphp

@if($tierBadgeMeta)
    @php
        $isChurchBadge = $tierBadgeMeta['family'] === BookingPricing::FAMILY_CHURCH;
        // Two visual palettes — warm gold for family, premium violet for church.
        $palette = $isChurchBadge
            ? [
                'color'  => '#ddd6fe',
                'border' => 'rgba(167,139,250,0.55)',
                'bg'     => 'rgba(124,58,237,0.10)',
              ]
            : [
                'color'  => '#fde68a',
                'border' => 'rgba(251,191,36,0.55)',
                'bg'     => 'rgba(251,191,36,0.10)',
              ];
        $labelText = $isChurchBadge ? 'خصومات الكنائس' : 'خصومات العيلة';
        $titleAttr = $labelText . ' · خصم ' . $percentBadge . '% · وفّر ' . $amountBadge . ' جنيه';
    @endphp

    @if($variantBadge === 'bar')
        <span class="pt-bar-chip pt-bar-chip-muted"
              title="{{ $titleAttr }}"
              data-tier-percent="{{ $percentBadge }}"
              data-tier-family="{{ $tierBadgeMeta['family'] }}"
              style="color:{{ $palette['color'] }}; border-color:{{ $palette['border'] }}; background:{{ $palette['bg'] }};">
            <span aria-hidden="true">{{ $tierBadgeMeta['badge'] }}</span>
            <span dir="ltr">-{{ $percentBadge }}%</span>
        </span>
    @elseif($variantBadge === 'pill')
        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold"
              title="{{ $titleAttr }}"
              data-tier-percent="{{ $percentBadge }}"
              data-tier-family="{{ $tierBadgeMeta['family'] }}"
              style="color:{{ $palette['color'] }}; border:1px solid {{ $palette['border'] }}; background:{{ $palette['bg'] }};">
            <span aria-hidden="true">{{ $tierBadgeMeta['badge'] }}</span>
            <span dir="ltr">-{{ $percentBadge }}%</span>
            <span class="opacity-80 hidden sm:inline">·</span>
            <span class="opacity-90 hidden sm:inline">{{ $labelText }}</span>
        </span>
    @else
        <span class="inline-flex items-center gap-1 ms-1 px-1.5 py-0.5 rounded-md text-[10px] font-bold"
              title="{{ $titleAttr }}"
              data-tier-percent="{{ $percentBadge }}"
              data-tier-family="{{ $tierBadgeMeta['family'] }}"
              style="color:{{ $palette['color'] }}; border:1px solid {{ $palette['border'] }}; background:{{ $palette['bg'] }};">
            <span aria-hidden="true">{{ $tierBadgeMeta['badge'] }}</span>
            <span dir="ltr">-{{ $percentBadge }}%</span>
        </span>
    @endif
@endif
