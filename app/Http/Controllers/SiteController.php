<?php

namespace App\Http\Controllers;

use App\Models\Show;

class SiteController extends Controller
{
    public function home()
    {
        $shows = Show::where('is_active', true)->latest()->get();
        return view('shows.index', compact('shows'));
    }
}
