<?php

namespace App\Http\Controllers;

use App\Models\BlindTest\GameRoom;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\Schema;

class HomeController extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('Home', [
            'games' => config('games'),
            'publicRooms' => Schema::hasTable('game_rooms') ? GameRoom::query()
                ->select(['id', 'public_id', 'host_id', 'created_at'])
                ->where('status', 'lobby')
                ->where('is_public', true)
                ->withCount('players')
                ->latest()
                ->limit(12)
                ->get() : [],
        ]);
    }
}
