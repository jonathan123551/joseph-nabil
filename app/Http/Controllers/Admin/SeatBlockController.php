<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Seat;
use App\Models\SeatBlock;
use App\Models\Show;
use App\Models\ShowTime;
use App\Models\Theater;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Admin UI for blocking / un-blocking seats per show time on Anba Ruweis shows.
 * Each row in the seat_blocks table marks a seat as unavailable for a given
 * show time; deleting the row makes it bookable again.
 */
class SeatBlockController extends Controller
{
    public function index(ShowTime $showTime)
    {
        $showTime->loadMissing('show');

        abort_unless(
            $showTime->show && $showTime->show->theater_type === Show::THEATER_ANBA_RUWEIS,
            404
        );

        $theater = Theater::anbaRuweis();
        abort_unless($theater, 404);

        $seats = $theater->seats()
            ->orderBy('row_letter')
            ->orderBy('group_side')
            ->orderBy('display_order')
            ->get();

        $bookedSeatIds = $showTime->bookedSeats()
            ->whereHas('booking', function ($q) {
                $q->whereIn('status', ['approved', 'pending']);
            })
            ->pluck('seat_id')
            ->all();

        $blockedSeatIds = $showTime->seatBlocks()->pluck('seat_id')->all();

        $seatsByRow = [];
        foreach ($seats as $seat) {
            $seatsByRow[$seat->section][$seat->row_letter][$seat->group_side][] = $seat;
        }
        foreach ($seatsByRow as $section => $rows) {
            foreach ($rows as $row => $sides) {
                foreach (['left', 'center', 'right'] as $side) {
                    if (!isset($seatsByRow[$section][$row][$side])) {
                        $seatsByRow[$section][$row][$side] = [];
                    }
                }
            }
        }

        return view('admin.show_times.seats', [
            'showTime'       => $showTime,
            'theater'        => $theater,
            'seatsByRow'     => $seatsByRow,
            'bookedSeatIds'  => $bookedSeatIds,
            'blockedSeatIds' => $blockedSeatIds,
        ]);
    }

    public function toggle(Request $request, ShowTime $showTime, Seat $seat)
    {
        $showTime->loadMissing('show');

        abort_unless(
            $showTime->show && $showTime->show->theater_type === Show::THEATER_ANBA_RUWEIS,
            404
        );

        // Refuse to toggle a seat that's already booked by a real customer —
        // admin must reject the booking first if they want to free it.
        $isBooked = $showTime->bookedSeats()
            ->whereHas('booking', function ($q) {
                $q->whereIn('status', ['approved', 'pending']);
            })
            ->where('seat_id', $seat->id)
            ->exists();

        if ($isBooked) {
            return back()->with('status', '❌ هذا المقعد محجوز بالفعل من قِبَل عميل');
        }

        $existing = SeatBlock::where('show_time_id', $showTime->id)
            ->where('seat_id', $seat->id)
            ->first();

        if ($existing) {
            $existing->delete();
            return back()->with('status', '✅ تم تفعيل المقعد ' . $seat->row_letter . $seat->seat_number);
        }

        SeatBlock::create([
            'show_time_id' => $showTime->id,
            'seat_id'      => $seat->id,
        ]);

        return back()->with('status', '🚫 تم حجب المقعد ' . $seat->row_letter . $seat->seat_number);
    }

    /**
     * Atomically apply a batch of admin seat-toggle decisions for one
     * show time. The same toggle semantics as `toggle()` are preserved
     * (currently-blocked → unblock; otherwise → block; customer-booked
     * seats are silently rejected).
     *
     * Returns a JSON envelope so the seat picker can update its local
     * state without a full page reload — that's what makes the admin
     * "save" button feel snappy.
     */
    public function bulkToggle(Request $request, ShowTime $showTime): JsonResponse
    {
        $showTime->loadMissing('show');

        abort_unless(
            $showTime->show && $showTime->show->theater_type === Show::THEATER_ANBA_RUWEIS,
            404
        );

        $data = $request->validate([
            'seat_ids'   => ['required', 'array', 'min:1', 'max:1000'],
            'seat_ids.*' => ['integer', 'distinct'],
        ]);

        $requested = array_values(array_unique(array_map('intval', $data['seat_ids'])));

        // Filter to seats that actually belong to this theater.
        $validSeatIds = Seat::query()
            ->where('theater_id', $showTime->show->theater_id)
            ->whereIn('id', $requested)
            ->pluck('id')
            ->all();

        // Refuse to touch seats already booked by a real customer.
        $bookedIds = $showTime->bookedSeats()
            ->whereHas('booking', function ($q) {
                $q->whereIn('status', ['approved', 'pending']);
            })
            ->whereIn('seat_id', $validSeatIds)
            ->pluck('seat_id')
            ->all();

        $eligibleIds = array_values(array_diff($validSeatIds, $bookedIds));

        $blocked   = [];
        $unblocked = [];

        if (!empty($eligibleIds)) {
            DB::transaction(function () use ($showTime, $eligibleIds, &$blocked, &$unblocked) {
                $existingIds = SeatBlock::where('show_time_id', $showTime->id)
                    ->whereIn('seat_id', $eligibleIds)
                    ->pluck('seat_id')
                    ->all();

                if (!empty($existingIds)) {
                    SeatBlock::where('show_time_id', $showTime->id)
                        ->whereIn('seat_id', $existingIds)
                        ->delete();
                    $unblocked = $existingIds;
                }

                $toBlock = array_values(array_diff($eligibleIds, $existingIds));
                if (!empty($toBlock)) {
                    $now  = now();
                    $rows = array_map(fn ($id) => [
                        'show_time_id' => $showTime->id,
                        'seat_id'      => (int) $id,
                        'created_at'   => $now,
                        'updated_at'   => $now,
                    ], $toBlock);
                    SeatBlock::insert($rows);
                    $blocked = $toBlock;
                }
            });
        }

        $rejected = array_values(array_diff($requested, $validSeatIds));
        $rejected = array_values(array_unique(array_merge($rejected, $bookedIds)));

        return response()->json([
            'ok'           => true,
            'blocked'      => array_map('intval', $blocked),
            'unblocked'    => array_map('intval', $unblocked),
            'rejected'     => array_map('intval', $rejected),
            'blocked_set'  => $showTime->seatBlocks()->pluck('seat_id')->map(fn ($id) => (int) $id)->all(),
        ]);
    }
}
