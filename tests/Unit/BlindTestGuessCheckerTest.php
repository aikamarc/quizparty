<?php

namespace Tests\Unit;

use App\Services\BlindTest\GuessChecker;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BlindTestGuessCheckerTest extends TestCase
{
    #[Test]
    public function remaster_information_in_parentheses_is_ignored(): void
    {
        $this->assertTrue(GuessChecker::matches('Juicy', 'Juicy (2005 Remaster)'));
        $this->assertTrue(GuessChecker::matches('Juicy (album version)', 'Juicy (2005 Remaster)'));
    }
}
