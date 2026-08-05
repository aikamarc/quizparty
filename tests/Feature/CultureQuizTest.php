<?php

namespace Tests\Feature;

use App\Models\CultureQuiz\Category;
use App\Models\CultureQuiz\Question;
use App\Models\CultureQuiz\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CultureQuizTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_import_a_localized_question(): void
    {
        $admin=User::factory()->create(['is_admin'=>true]);
        $category=Category::create(['name'=>'Géographie']);
        $this->actingAs($admin)->post(route('admin.culture-quiz.import',$category),[
            'list'=>'Capitale de la France ? || Paris || FR',
        ])->assertRedirect();
        $this->assertDatabaseHas('culture_question_translations',['locale'=>'fr','question'=>'Capitale de la France ?','answer'=>'Paris']);
    }

    public function test_new_room_selects_all_categories_by_default(): void
    {
        $user=User::factory()->create();
        $categories=collect(['Géographie','Science'])->map(fn($name)=>Category::create(['name'=>$name]));

        $this->actingAs($user)->post(route('culture-quiz.rooms.store'))->assertRedirect();

        $room=Room::firstOrFail();
        $this->assertEqualsCanonicalizing($categories->pluck('id')->all(),$room->categories()->pluck('culture_categories.id')->all());
    }

    public function test_petit_bac_replaces_a_question_in_the_requested_total(): void
    {
        $host=User::factory()->create();
        $category=Category::create(['name'=>'Général']);
        foreach (range(1, 5) as $number) {
            $question=Question::create(['culture_category_id'=>$category->id]);
            $question->translations()->create(['locale'=>'fr','question'=>'Question '.$number,'answer'=>'Réponse '.$number]);
        }
        $room=Room::create(['host_id'=>$host->id,'questions_total'=>5]);
        $room->players()->create(['user_id'=>$host->id]);
        $room->categories()->attach($category);

        $this->actingAs($host)->post(route('culture-quiz.rooms.start',$room))->assertRedirect();

        $this->assertSame(5, $room->rounds()->count());
        $this->assertSame(1, $room->rounds()->where('special_type','petit_bac')->count());
        $this->assertSame(5, $room->fresh()->questions_total);
    }

    public function test_one_question_game_does_not_add_petit_bac(): void
    {
        $host=User::factory()->create();
        $category=Category::create(['name'=>'Général']);
        $question=Question::create(['culture_category_id'=>$category->id]);
        $question->translations()->create(['locale'=>'fr','question'=>'Question unique','answer'=>'Réponse']);
        $room=Room::create(['host_id'=>$host->id,'questions_total'=>1]);
        $room->players()->create(['user_id'=>$host->id]);
        $room->categories()->attach($category);

        $this->actingAs($host)->post(route('culture-quiz.rooms.start',$room))->assertRedirect();

        $this->assertSame(1, $room->rounds()->count());
        $this->assertSame(0, $room->rounds()->where('special_type','petit_bac')->count());
    }

    public function test_room_runs_a_timed_question_then_host_scores_the_answer(): void
    {
        $host=User::factory()->create(); $player=User::factory()->create();
        $category=Category::create(['name'=>'Science']);
        $question=Question::create(['culture_category_id'=>$category->id]);
        $question->translations()->create(['locale'=>'fr','question'=>'Planète rouge ?','answer'=>'Mars']);
        $room=Room::create(['host_id'=>$host->id,'questions_total'=>1,'seconds_per_question'=>5]);
        $room->players()->createMany([['user_id'=>$host->id],['user_id'=>$player->id]]); $room->categories()->attach($category);

        $this->actingAs($host)->post(route('culture-quiz.rooms.start',$room))->assertRedirect();
        $this->actingAs($player)->postJson(route('culture-quiz.rooms.answer',$room),['answer'=>['text'=>'Mars']])->assertOk();
        $this->travel(6)->seconds();
        $state=$this->actingAs($host)->getJson(route('culture-quiz.rooms.state',$room))->assertOk()->json();
        $this->assertSame('reviewing',$state['room']['status']);
        $answerId=collect($state['review'])->get('id');
        $this->actingAs($host)->postJson(route('culture-quiz.rooms.answers.judge',[$room,$answerId]),['correct'=>true])->assertOk();
        $this->assertDatabaseHas('culture_room_players',['culture_room_id'=>$room->id,'user_id'=>$player->id,'score'=>1]);
        $next=$this->actingAs($host)->getJson(route('culture-quiz.rooms.state',$room))->json('review.id');
        $this->actingAs($host)->postJson(route('culture-quiz.rooms.answers.judge',[$room,$next]),['correct'=>false])->assertOk();
        $this->assertSame('finished',$room->fresh()->status);
    }

    public function test_host_grades_each_petit_bac_category_for_half_a_point(): void
    {
        $host=User::factory()->create(); $player=User::factory()->create();
        $room=Room::create(['host_id'=>$host->id,'status'=>'reviewing','questions_total'=>1]);
        $room->players()->createMany([['user_id'=>$host->id],['user_id'=>$player->id]]);
        $round=$room->rounds()->create(['position'=>1,'special_type'=>'petit_bac','special_letter'=>'b']);
        $answer=$round->answers()->create(['user_id'=>$player->id,'answer'=>[
            'animal'=>'Baleine','object'=>'Bouteille','celebrity'=>'','place'=>'Berlin','capital'=>'Bruxelles','job'=>'Boulanger',
        ]]);

        $this->actingAs($host)->postJson(route('culture-quiz.rooms.answers.judge',[$room,$answer]),['categories'=>[
            'animal'=>true,'object'=>false,'celebrity'=>false,'place'=>true,'capital'=>true,'job'=>false,
        ]])->assertOk();

        $this->assertSame('1.5',$room->players()->where('user_id',$player->id)->value('score'));
        $this->assertSame('1.5',$answer->fresh()->awarded_points);
        $this->assertSame(['animal'=>true,'object'=>false,'celebrity'=>false,'place'=>true,'capital'=>true,'job'=>false],$answer->fresh()->grading);
    }
}
