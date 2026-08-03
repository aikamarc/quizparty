<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class GuestController extends Controller
{
    public function updateName(Request $request): RedirectResponse
    {
        abort_unless($request->user()->is_guest, 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $request->user()->update(['name' => $validated['name']]);

        return back();
    }
}
