<?php

namespace Tests\Feature;

use App\Models\BlindTest\GameRoom;
use App\Models\CultureQuiz\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class CleanupStaleGameRoomsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::forget('games:stale-room-cleanup');
    }

    public function test_it_deletes_only_inactive_lobby_and_finished_rooms(): void
    {
        $host=User::factory()->create();
        $stale=now()->subSeconds(61);

        $staleBlindLobby=GameRoom::create(['code'=>'BL0001','host_id'=>$host->id,'status'=>'lobby','last_activity_at'=>$stale]);
        $staleBlindFinished=GameRoom::create(['code'=>'BL0002','host_id'=>$host->id,'status'=>'finished','last_activity_at'=>$stale]);
        $activeBlindLobby=GameRoom::create(['code'=>'BL0003','host_id'=>$host->id,'status'=>'lobby','last_activity_at'=>now()]);
        $staleBlindPlaying=GameRoom::create(['code'=>'BL0004','host_id'=>$host->id,'status'=>'playing','last_activity_at'=>$stale]);
        $staleCultureLobby=Room::create(['host_id'=>$host->id,'status'=>'lobby','last_activity_at'=>$stale]);
        $staleCultureFinished=Room::create(['host_id'=>$host->id,'status'=>'finished','last_activity_at'=>$stale]);
        $activeCultureLobby=Room::create(['host_id'=>$host->id,'status'=>'lobby','last_activity_at'=>now()]);
        $staleCulturePlaying=Room::create(['host_id'=>$host->id,'status'=>'answering','last_activity_at'=>$stale]);

        Artisan::call('games:cleanup-stale-rooms');

        $this->assertModelMissing($staleBlindLobby);
        $this->assertModelMissing($staleBlindFinished);
        $this->assertModelExists($activeBlindLobby);
        $this->assertModelExists($staleBlindPlaying);
        $this->assertModelMissing($staleCultureLobby);
        $this->assertModelMissing($staleCultureFinished);
        $this->assertModelExists($activeCultureLobby);
        $this->assertModelExists($staleCulturePlaying);
    }

    public function test_visiting_blind_test_list_cleans_stale_rooms_without_scheduler(): void
    {
        $host=User::factory()->create();
        $room=GameRoom::create(['code'=>'BL0001','host_id'=>$host->id,'status'=>'lobby','last_activity_at'=>now()->subSeconds(61)]);

        $this->actingAs($host)->get(route('blindtest.index'))->assertOk();

        $this->assertModelMissing($room);
    }

    public function test_visiting_culture_quiz_list_cleans_stale_rooms_without_scheduler(): void
    {
        $host=User::factory()->create();
        $room=Room::create(['host_id'=>$host->id,'status'=>'finished','last_activity_at'=>now()->subSeconds(61)]);

        $this->actingAs($host)->get(route('culture-quiz.index'))->assertOk();

        $this->assertModelMissing($room);
    }
}
