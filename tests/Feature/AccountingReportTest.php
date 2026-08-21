<?php

namespace Tests\Feature;

use App\Models\ChartOfAccount;
use App\Models\JournalEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountingReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_general_ledger_includes_only_posted_entries_and_calculates_running_balance(): void
    {
        $user = User::factory()->create(['role' => 'accountant']);
        [$cash, $revenue] = $this->accounts();
        $this->journal('JV-000001', '2026-01-01', 'draft', $user, $cash, $revenue, 100);
        $this->journal('JV-000002', '2026-01-02', 'pending', $user, $cash, $revenue, 200);
        $this->journal('JV-000003', '2026-01-03', 'approved', $user, $cash, $revenue, 300);
        $this->journal('JV-000004', '2026-01-04', 'posted', $user, $cash, $revenue, 400);
        $this->journal('JV-000005', '2025-12-31', 'posted', $user, $cash, $revenue, 50);

        $this->actingAs($user)->get('/accounting/general-ledger?account_id='.$cash->id.'&from=2026-01-01&to=2026-01-31')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Accounting/Reports/GeneralLedger')
                ->where('openingBalance', 50)
                ->where('totalDebit', 400)
                ->where('totalCredit', 0)
                ->where('endingBalance', 450)
                ->has('transactions', 1)
                ->where('transactions.0.journal_number', 'JV-000004'));
    }

    public function test_general_ledger_date_and_account_filters_work(): void
    {
        $manager = User::factory()->create(['role' => 'manager']);
        [$cash, $revenue] = $this->accounts();
        $this->journal('JV-000010', '2026-02-01', 'posted', $manager, $cash, $revenue, 100);
        $this->journal('JV-000011', '2026-03-01', 'posted', $manager, $cash, $revenue, 200);

        $this->actingAs($manager)->get('/accounting/general-ledger?account_id='.$revenue->id.'&from=2026-02-15&to=2026-03-15')
            ->assertInertia(fn ($page) => $page
                ->where('account.id', $revenue->id)
                ->has('transactions', 1)
                ->where('transactions.0.journal_number', 'JV-000011'));
    }

    public function test_trial_balance_uses_posted_entries_and_is_balanced(): void
    {
        $accountant = User::factory()->create(['role' => 'accountant']);
        [$cash, $revenue] = $this->accounts();
        $this->journal('JV-000020', '2026-04-01', 'draft', $accountant, $cash, $revenue, 100);
        $this->journal('JV-000021', '2026-04-02', 'pending', $accountant, $cash, $revenue, 200);
        $this->journal('JV-000022', '2026-04-03', 'approved', $accountant, $cash, $revenue, 300);
        $this->journal('JV-000023', '2026-04-04', 'posted', $accountant, $cash, $revenue, 400);

        $this->actingAs($accountant)->get('/accounting/trial-balance?from=2026-01-01&to=2026-12-31')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Accounting/Reports/TrialBalance')
                ->where('totalDebit', 400)
                ->where('totalCredit', 400)
                ->has('accounts', 2));
    }

    public function test_trial_balance_date_filter_works_for_manager(): void
    {
        $manager = User::factory()->create(['role' => 'manager']);
        [$cash, $revenue] = $this->accounts();
        $this->journal('JV-000030', '2026-05-01', 'posted', $manager, $cash, $revenue, 100);
        $this->journal('JV-000031', '2026-06-01', 'posted', $manager, $cash, $revenue, 250);

        $this->actingAs($manager)->get('/accounting/trial-balance?from=2026-06-01&to=2026-06-30')
            ->assertInertia(fn ($page) => $page->where('totalDebit', 250)->where('totalCredit', 250));
    }

    public function test_unauthenticated_user_cannot_access_reports(): void
    {
        $this->get('/accounting/general-ledger')->assertRedirect('/login');
        $this->get('/accounting/trial-balance')->assertRedirect('/login');
    }

    private function accounts(): array
    {
        return [
            ChartOfAccount::create(['code' => '1100'.fake()->unique()->numerify('##'), 'name' => 'Cash '.fake()->unique()->word(), 'type' => 'asset']),
            ChartOfAccount::create(['code' => '4100'.fake()->unique()->numerify('##'), 'name' => 'Revenue '.fake()->unique()->word(), 'type' => 'revenue']),
        ];
    }

    private function journal(string $number, string $date, string $status, User $user, ChartOfAccount $debitAccount, ChartOfAccount $creditAccount, int $amount): void
    {
        $journal = JournalEntry::create(['journal_number' => $number, 'transaction_date' => $date, 'status' => $status, 'created_by' => $user->id]);
        $journal->lines()->createMany([
            ['chart_of_account_id' => $debitAccount->id, 'debit' => $amount, 'credit' => 0],
            ['chart_of_account_id' => $creditAccount->id, 'debit' => 0, 'credit' => $amount],
        ]);
    }
}