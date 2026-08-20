<?php

namespace Tests\Feature;

use App\Models\ChartOfAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_accountant_can_sign_in_and_view_chart_of_accounts(): void
    {
        $user = User::factory()->create([
            'email' => 'accountant@example.com',
            'password' => 'password',
            'role' => 'accountant',
        ]);
        ChartOfAccount::create(['code' => '1100', 'name' => 'Cash', 'type' => 'asset']);

        $response = $this->post('/login', [
            'email' => 'accountant@example.com',
            'password' => 'password',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);
        $this->get('/accounting/chart-of-accounts')->assertOk();
    }

    public function test_manager_cannot_view_accountant_only_chart_of_accounts(): void
    {
        $manager = User::factory()->create(['role' => 'manager']);

        $this->actingAs($manager)
            ->get('/accounting/chart-of-accounts')
            ->assertForbidden();
    }

    public function test_user_can_log_out(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/logout')->assertRedirect('/login');
        $this->assertGuest();
    }
}