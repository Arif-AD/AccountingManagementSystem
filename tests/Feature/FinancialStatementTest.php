<?php

namespace Tests\Feature;

use App\Models\ChartOfAccount;
use App\Models\JournalEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinancialStatementTest extends TestCase
{
    use RefreshDatabase;

    public function test_income_statement_uses_only_posted_revenue_and_expense_and_calculates_net_income(): void
    {
        $accountant = User::factory()->create(['role' => 'accountant']);
        $cash = $this->account('1100', 'Cash', 'asset');
        $revenue = $this->account('4100', 'Sales', 'revenue');
        $expense = $this->account('5100', 'Rent', 'expense');
        $this->entry('JV-100001', '2026-08-01', 'posted', $accountant, $cash, $revenue, 1000);
        $this->entry('JV-100002', '2026-08-02', 'posted', $accountant, $expense, $cash, 250);
        foreach (['draft', 'pending', 'approved'] as $index => $status) {
            $this->entry('JV-10000'.($index + 3), '2026-08-03', $status, $accountant, $cash, $revenue, 999);
        }

        $this->actingAs($accountant)->get('/accounting/income-statement?start_date=2026-08-01&end_date=2026-08-31')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Accounting/Reports/IncomeStatement')
                ->where('totalRevenue', 1000)
                ->where('totalExpenses', 250)
                ->where('netIncome', 750)
                ->has('revenue', 1)
                ->has('expenses', 1));
    }

    public function test_income_statement_date_filter_and_manager_access_work(): void
    {
        $manager = User::factory()->create(['role' => 'manager']);
        $cash = $this->account('1200', 'Bank', 'asset');
        $revenue = $this->account('4200', 'Consulting', 'revenue');
        $expense = $this->account('5200', 'Supplies', 'expense');
        $this->entry('JV-100010', '2026-07-31', 'posted', $manager, $cash, $revenue, 900);
        $this->entry('JV-100011', '2026-08-15', 'posted', $manager, $cash, $revenue, 300);

        $this->actingAs($manager)->get('/accounting/income-statement?start_date=2026-08-01&end_date=2026-08-31')
            ->assertInertia(fn ($page) => $page->where('totalRevenue', 300)->where('totalExpenses', 0));
    }

    public function test_balance_sheet_uses_posted_balances_through_end_date_and_includes_net_income(): void
    {
        $accountant = User::factory()->create(['role' => 'accountant']);
        $cash = $this->account('1100', 'Cash', 'asset');
        $payable = $this->account('2100', 'Payable', 'liability');
        $equity = $this->account('3100', 'Owner Equity', 'equity');
        $revenue = $this->account('4100', 'Sales', 'revenue');
        $expense = $this->account('5100', 'Rent', 'expense');
        $this->entry('JV-100020', '2026-07-31', 'posted', $accountant, $cash, $revenue, 1000);
        $this->entry('JV-100021', '2026-08-10', 'posted', $accountant, $cash, $payable, 200);
        $this->entry('JV-100022', '2026-08-15', 'posted', $accountant, $expense, $cash, 300);
        $this->entry('JV-100023', '2026-08-16', 'posted', $accountant, $equity, $cash, 100);

        $this->actingAs($accountant)->get('/accounting/balance-sheet?start_date=2026-07-01&end_date=2026-08-31')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Accounting/Reports/BalanceSheet')
                ->where('totalAssets', 800)
                ->where('totalLiabilities', 200)
                ->where('netIncome', 700)
                ->where('totalEquity', 600)
                ->where('balanced', true));
    }

    public function test_balance_sheet_excludes_non_posted_and_respects_end_date_for_manager(): void
    {
        $manager = User::factory()->create(['role' => 'manager']);
        $cash = $this->account('1200', 'Bank', 'asset');
        $equity = $this->account('3200', 'Capital', 'equity');
        $this->entry('JV-100030', '2026-08-20', 'posted', $manager, $cash, $equity, 500);
        $this->entry('JV-100031', '2026-09-01', 'posted', $manager, $cash, $equity, 700);
        $this->entry('JV-100032', '2026-08-21', 'pending', $manager, $cash, $equity, 900);

        $this->actingAs($manager)->get('/accounting/balance-sheet?start_date=2026-08-01&end_date=2026-08-31')
            ->assertInertia(fn ($page) => $page->where('totalAssets', 500)->where('totalLiabilities', 0)->where('totalEquity', 500));
    }

    public function test_guests_cannot_access_financial_statements(): void
    {
        $this->get('/accounting/income-statement')->assertRedirect('/login');
        $this->get('/accounting/balance-sheet')->assertRedirect('/login');
    }

    private function account(string $code, string $name, string $type): ChartOfAccount
    {
        return ChartOfAccount::create(['code' => $code, 'name' => $name, 'type' => $type]);
    }

    private function entry(string $number, string $date, string $status, User $user, ChartOfAccount $debitAccount, ChartOfAccount $creditAccount, int $amount): void
    {
        $journal = JournalEntry::create(['journal_number' => $number, 'transaction_date' => $date, 'status' => $status, 'created_by' => $user->id]);
        $journal->lines()->createMany([
            ['chart_of_account_id' => $debitAccount->id, 'debit' => $amount, 'credit' => 0],
            ['chart_of_account_id' => $creditAccount->id, 'debit' => 0, 'credit' => $amount],
        ]);
    }
}