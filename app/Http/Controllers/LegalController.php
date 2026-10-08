<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;

class LegalController extends Controller
{
    /**
     * Display the official Terms of Service page.
     */
    public function terms(): View
    {
        return view('legal.terms');
    }

    /**
     * Display the official Privacy Policy page.
     */
    public function privacy(): View
    {
        return view('legal.privacy');
    }
}
