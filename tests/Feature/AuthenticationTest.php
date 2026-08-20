<?php

namespace Tests\Feature;

use App\Models\ChartOfAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_accountant_can_view_chart_of_accounts_with_edit(): void
    {
        $accountant = User::factory()->create(['role' => 'accountant']);
        ChartOfAccount::create(['code' => '1100', 'name' => 'Cash', 'type' => 'asset']);

        $this->actingAs($accountant)
            ->get('/accounting/chart-of-accounts')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Accounting/ChartOfAccounts/Index')
                ->where('canEdit', true)
            );
    }

    public function test_manager_can_view_chart_of_accounts_read_only(): void
    {
        $manager = User::factory()->create(['role' => 'manager']);
        ChartOfAccount::create(['code' => '1100', 'name' => 'Cash', 'type' => 'asset']);

        $this->actingAs($manager)
            ->get('/accounting/chart-of-accounts')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Accounting/ChartOfAccounts/Index')
                ->where('canEdit', false)
            );
    }

    public function test_user_can_log_out(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/logout')->assertRedirect('/login');
        $this->assertGuest();
    }
}