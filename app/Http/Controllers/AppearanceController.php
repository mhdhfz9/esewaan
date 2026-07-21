<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class AppearanceController extends Controller
{
    public function show(): View
    {
        return view('penampilan.show');
    }
}
