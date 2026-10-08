<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LocaleController extends Controller
{
    /**
     * Switch application locale between Indonesian and English.
     */
    public function switch(Request $request, string $locale): RedirectResponse
    {
        // Strictly allow only 'id' and 'en'
        if (! in_array($locale, ['id', 'en'], true)) {
            abort(400, 'Invalid locale');
        }

        session(['locale' => $locale]);

        $fallback = Auth::check() ? route('dashboard') : (route('login') ?? url('/'));

        return redirect()->back(fallback: $fallback);
    }
}
