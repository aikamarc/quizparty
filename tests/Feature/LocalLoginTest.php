<?php

namespace Tests\Feature;

use App\Http\Controllers\Auth\LocalLoginController;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class LocalLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_local_login_authenticates_the_user_with_the_smallest_id(): void
    {
        app()->detectEnvironment(fn () => 'local');
        Route::post('/test-local-login', LocalLoginController::class)->middleware('web');

        $firstUser = User::factory()->create();
        User::factory()->create();

        $response = $this->withSession(['_token' => 'test-token'])
            ->post('/test-local-login', ['_token' => 'test-token']);

        $response->assertRedirect(route('dashboard', absolute: false));
        $this->assertAuthenticatedAs($firstUser);
    }

    public function test_local_login_is_unavailable_outside_the_local_environment(): void
    {
        Route::post('/test-local-login', LocalLoginController::class)->middleware('web');
        User::factory()->create();

        $this->post('/test-local-login')->assertNotFound();
        $this->assertGuest();
    }
}
