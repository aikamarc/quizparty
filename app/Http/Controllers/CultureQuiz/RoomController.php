<?php

namespace App\Http\Controllers\CultureQuiz;

use App\Http\Controllers\Controller;
use App\Models\CultureQuiz\Answer;
use App\Models\CultureQuiz\Category;
use App\Models\CultureQuiz\Question;
use App\Models\CultureQuiz\Room;
use App\Services\StaleGameRoomCleaner;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class RoomController extends Controller
{
    private const PETIT_BAC_FIELDS = ['animal','object','celebrity','place','capital','job'];

    public function index(Request $request, StaleGameRoomCleaner $cleaner): Response
    {
        $cleaner->cleanupIfDue();
        return Inertia::render('CultureQuiz/Index', ['rooms' => Room::whereHas('players', fn($q)=>$q->where('user_id',$request->user()->id))->with('host:id,name')->latest()->get()]);
    }
    public function store(Request $request): RedirectResponse
    {
        $room = Room::create(['host_id'=>$request->user()->id]);
        $room->players()->create(['user_id'=>$request->user()->id]);
        $room->categories()->attach(Category::query()->pluck('id'));
        return redirect()->route('culture-quiz.rooms.show',$room);
    }
    public function resolveCode(Request $request): RedirectResponse
    {
        $data=$request->validate(['code'=>['required','string','size:6']]);
        $room=Room::where('code',strtoupper($data['code']))->firstOrFail();
        if ($room->status !== 'lobby') throw ValidationException::withMessages(['code'=>'Cette partie a déjà commencé et ne peut plus être rejointe.']);
        return redirect()->route('culture-quiz.rooms.show',$room);
    }
    public function show(Request $request, Room $room): Response
    {
        return Inertia::render('CultureQuiz/Room', ['initialState'=>$this->stateData($room,$request->user()->id),'categories'=>Category::withCount('questions')->orderBy('name')->get()]);
    }
    public function join(Request $request, Room $room): RedirectResponse
    {
        abort_unless($room->status==='lobby',422);
        $room->players()->firstOrCreate(['user_id'=>$request->user()->id]);
        return back();
    }
    public function invite(Request $request, Room $room): RedirectResponse
    {
        $this->host($request,$room); abort_unless($room->status==='lobby',422);
        $data=$request->validate(['friend_id'=>['required','integer','exists:users,id']]);
        $friend=\App\Models\User::findOrFail($data['friend_id']);
        if(!$request->user()->isFriendsWith($friend)) throw ValidationException::withMessages(['friend_id'=>'Cet utilisateur ne fait pas partie de vos amis.']);
        $room->players()->firstOrCreate(['user_id'=>$friend->id]); return back();
    }
    public function update(Request $request, Room $room): JsonResponse
    {
        $this->host($request,$room);
        abort_unless($room->status==='lobby',422);
        $data=$request->validate(['questions_total'=>['required','integer','min:1','max:50'],'seconds_per_question'=>['required','integer','min:5','max:180'],'category_ids'=>['required','array','min:1'],'category_ids.*'=>['integer','exists:culture_categories,id']]);
        $room->update($data); $room->categories()->sync($data['category_ids']);
        return response()->json(['ok'=>true]);
    }
    public function start(Request $request, Room $room): RedirectResponse
    {
        $this->host($request,$room); abort_unless($room->status==='lobby',422);
        $includeSpecial = $room->questions_total >= 5;
        $standardCount = $room->questions_total - ($includeSpecial ? 1 : 0);
        $ids=Question::whereIn('culture_category_id',$room->categories()->pluck('culture_categories.id'))->inRandomOrder()->limit($standardCount)->pluck('id');
        abort_if($ids->isEmpty(),422,'No questions available');
        DB::transaction(function() use($room,$ids,$includeSpecial) {
            $entries = $ids->map(fn ($id) => ['culture_question_id'=>$id])->all();
            if ($includeSpecial) $entries[] = ['special_type'=>'petit_bac','special_letter'=>chr(random_int(65,90))];
            shuffle($entries);
            $position=1;
            foreach($entries as $entry) $room->rounds()->create([...$entry,'position'=>$position++]);
            $room->update(['status'=>'answering','current_question'=>1,'question_ends_at'=>now()->addSeconds($room->seconds_per_question),'questions_total'=>$position-1]);
        });
        return back();
    }
    public function state(Request $request, Room $room): JsonResponse
    {
        if ($room->players()->where('user_id',$request->user()->id)->exists()
            && (!$room->last_activity_at || $room->last_activity_at->lte(now()->subSeconds(15)))) {
            $room->updateQuietly(['last_activity_at'=>now()]);
        }
        $this->tick($room);
        return response()->json($this->stateData($room->fresh(),$request->user()->id));
    }
    public function answer(Request $request, Room $room): JsonResponse
    {
        abort_unless($room->players()->where('user_id',$request->user()->id)->exists(),403);
        abort_unless($room->status==='answering',422);
        $data=$request->validate(['answer'=>['required','array']]);
        $round=$room->rounds()->where('position',$room->current_question)->firstOrFail();
        $round->answers()->updateOrCreate(['user_id'=>$request->user()->id],['answer'=>$data['answer']]);
        return response()->json(['ok'=>true]);
    }
    public function judge(Request $request, Room $room, Answer $answer): JsonResponse
    {
        $this->host($request,$room);
        $this->answerBelongsToRoom($answer,$room);
        $answer->loadMissing('round');

        if ($answer->round->special_type === 'petit_bac') {
            $data=$request->validate(['categories'=>['required','array','size:'.count(self::PETIT_BAC_FIELDS)],'categories.*'=>['required','boolean']]);
            abort_unless(
                collect(array_keys($data['categories']))->sort()->values()->all() === collect(self::PETIT_BAC_FIELDS)->sort()->values()->all(),
                422,
            );
            $grading=$data['categories'];
            $points=collect($grading)->filter()->count() * 0.5;
            $correct=$points > 0;
        } else {
            $data=$request->validate(['correct'=>['required','boolean']]);
            $grading=null;
            $points=$data['correct'] ? 1.0 : 0.0;
            $correct=$data['correct'];
        }

        abort_unless($answer->is_correct === null,422,'Answer already judged');
        DB::transaction(function () use ($room,$answer,$grading,$points,$correct) {
            if ($points > 0) $room->players()->where('user_id',$answer->user_id)->increment('score',$points);
            $answer->update(['grading'=>$grading,'awarded_points'=>$points,'is_correct'=>$correct,'poll_open'=>false]);
        });
        if (!Answer::whereHas('round',fn($q)=>$q->where('culture_room_id',$room->id))->whereNull('is_correct')->exists()) $room->update(['status'=>'finished']);
        return response()->json(['ok'=>true]);
    }
    public function openPoll(Request $request, Room $room, Answer $answer): JsonResponse
    {
        $this->host($request,$room); $this->answerBelongsToRoom($answer,$room); $answer->update(['poll_open'=>true]); return response()->json(['ok'=>true]);
    }
    public function vote(Request $request, Room $room, Answer $answer): JsonResponse
    {
        $this->answerBelongsToRoom($answer,$room); abort_unless($answer->poll_open && $room->players()->where('user_id',$request->user()->id)->exists(),403);
        $data=$request->validate(['accepted'=>['required','boolean']]);
        $answer->votes()->updateOrCreate(['user_id'=>$request->user()->id],$data); return response()->json(['ok'=>true]);
    }
    private function tick(Room $room): void
    {
        if($room->status!=='answering'||!$room->question_ends_at||now()->lt($room->question_ends_at)) return;
        $round = $room->rounds()->where('position', $room->current_question)->first();
        if ($round) {
            foreach ($room->players()->pluck('user_id') as $userId) {
                $round->answers()->firstOrCreate(['user_id'=>$userId], ['answer'=>['text'=>'']]);
            }
        }
        if($room->current_question >= $room->questions_total) $room->update(['status'=>'reviewing','question_ends_at'=>null]);
        else $room->update(['current_question'=>$room->current_question+1,'question_ends_at'=>now()->addSeconds($room->seconds_per_question)]);
    }
    private function stateData(Room $room,int $userId): array
    {
        $room->load(['host:id,name','players.user:id,name,profile_photo_path','categories:id,name']);
        $round=$room->rounds()->where('position',$room->current_question)->with('question.translations')->first();
        $translation=$round?->question?->translation(app()->getLocale());
        $review=$room->status==='reviewing' ? Answer::whereHas('round',fn($q)=>$q->where('culture_room_id',$room->id))->whereNull('is_correct')->with(['user:id,name','votes','round.question.translations'])->first() : null;
        return ['room'=>$room,'isPlayer'=>$room->players->contains('user_id',$userId),'isHost'=>$room->host_id===$userId,'invitableFriends'=>$room->host_id===$userId ? request()->user()->friends()->reject(fn($friend)=>$room->players->contains('user_id',$friend->id))->values() : [],
            'question'=>$round ? ['position'=>$round->position,'type'=>$round->special_type??'standard','letter'=>$round->special_letter ? strtoupper($round->special_letter) : null,'fields'=>self::PETIT_BAC_FIELDS,'question'=>$translation?->question,'expected_answer'=>$room->host_id===$userId?$translation?->answer:null,'image_url'=>$round->question?->image_url] : null,
            'review'=>$review ? ['id'=>$review->id,'player'=>$review->user,'type'=>$review->round->special_type??'standard','letter'=>$review->round->special_letter ? strtoupper($review->round->special_letter) : null,'fields'=>self::PETIT_BAC_FIELDS,'question'=>$review->round->question?->translation(app()->getLocale())?->question,'answer'=>$review->answer,'expected_answer'=>$review->round->question?->translation(app()->getLocale())?->answer,'poll_open'=>$review->poll_open,'yes'=>$review->votes->where('accepted',true)->count(),'no'=>$review->votes->where('accepted',false)->count(),'voted'=>$review->votes->contains('user_id',$userId)] : null];
    }
    private function host(Request $request,Room $room): void { abort_unless($room->host_id===$request->user()->id,403); }
    private function answerBelongsToRoom(Answer $answer,Room $room): void { abort_unless(DB::table('culture_rounds')->where('id',$answer->culture_round_id)->where('culture_room_id',$room->id)->exists(),404); }
}
