<?php

namespace Tests\Feature;

use App\Models\BlindTest\GameRoom;
use App\Models\CultureQuiz\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActiveGameJoinTest extends TestCase
{
    use RefreshDatabase;

    public function test_blind_test_cannot_be_joined_by_code_after_it_starts(): void
    {
        $host=User::factory()->create();
        $room=GameRoom::create(['code'=>'ABC123','host_id'=>$host->id,'status'=>'playing']);

        $this->post(route('blindtest.rooms.resolve-code'),['code'=>$room->code])
            ->assertSessionHasErrors('code');
    }

    public function test_culture_quiz_cannot_be_joined_by_code_after_it_starts(): void
    {
        $host=User::factory()->create();
        $player=User::factory()->create();
        $room=Room::create(['host_id'=>$host->id,'status'=>'answering']);

        $this->actingAs($player)->post(route('culture-quiz.rooms.resolve-code'),['code'=>$room->code])
            ->assertSessionHasErrors('code');
        $this->actingAs($player)->post(route('culture-quiz.rooms.join',$room))
            ->assertStatus(422);
    }
}
