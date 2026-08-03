<?php

namespace Tests\Unit;

use App\Models\Track;
use App\Services\Songless\GuessJudge;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class SonglessGuessJudgeTest extends TestCase
{
    #[DataProvider('guesses')]
    public function test_it_classifies_guesses(string $guess, string $expected): void
    {
        $track = new Track(['title' => 'One More Time', 'artist' => 'Daft Punk']);

        $this->assertSame($expected, GuessJudge::result($guess, $track));
    }

    public static function guesses(): array
    {
        return [
            'title' => ['One More Time', 'correct'],
            'combined answer' => ['Daft Punk - One More Time', 'correct'],
            'small typo' => ['One More Tme', 'correct'],
            'artist only' => ['Daft Punk', 'artist'],
            'wrong answer' => ['Around the World', 'wrong'],
        ];
    }

    #[DataProvider('selectedTracks')]
    public function test_it_classifies_a_selected_library_track(string $title, string $artist, string $expected): void
    {
        $target = new Track(['title' => 'Armed & Dangerous', 'artist' => 'Juice WRLD']);
        $selected = new Track(['title' => $title, 'artist' => $artist]);

        $this->assertSame($expected, GuessJudge::selectedTrackResult($selected, $target));
    }

    public static function selectedTracks(): array
    {
        return [
            'correct track' => ['Armed & Dangerous', 'Juice WRLD', 'correct'],
            'same artist' => ['Lucid Dreams', 'Juice WRLD', 'artist'],
            'different track' => ['One More Time', 'Daft Punk', 'wrong'],
        ];
    }
}
