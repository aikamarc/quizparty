<?php

namespace App\Services\Songless;

use App\Models\Track;
use App\Services\BlindTest\GuessChecker;

class GuessJudge
{
    public static function selectedTrackResult(Track $selected, Track $target): string
    {
        $sameTitle = GuessChecker::matches($selected->title, $target->title);
        $sameArtist = GuessChecker::matches($selected->artist, $target->artist);

        if ($sameTitle && $sameArtist) {
            return 'correct';
        }

        return $sameArtist ? 'artist' : 'wrong';
    }

    public static function result(string $guess, Track $track): string
    {
        if (GuessChecker::matches($guess, $track->title)) {
            return 'correct';
        }

        if (GuessChecker::matches($guess, $track->artist)) {
            return 'artist';
        }

        return 'wrong';
    }
}
