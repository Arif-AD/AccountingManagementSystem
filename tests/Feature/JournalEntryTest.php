<?php

namespace Tests\Feature;

use App\Models\ChartOfAccount;
use App\Models\JournalEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JournalEntryTest extends TestCase
{
    use RefreshDatabase;

    public function test_accountant_can_view_journal_list(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'accountant']))
            ->get('/accounting/journal-entries')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Accounting/JournalEntries/Index'));
    }

    public function test_manager_can_view_journal_list_but_not_create_page(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'manager']))
            ->get('/accounting/journal-entries')
            ->assertOk();

        $this->get('/accounting/journal-entries/create')->assertForbidden();
    }

    public function test_accountant_can_open_create_page(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'accountant']))
            ->get('/accounting/journal-entries/create')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Accounting/JournalEntries/Create')
                ->has('accounts'));
    }

    public function test_accountant_can_create_balanced_journal_with_generated_number(): void
    {
        $user = User::factory()->create(['role' => 'accountant']);
        $cash = ChartOfAccount::create(['code' => '1100', 'name' => 'Cash', 'type' => 'asset']);
        $revenue = ChartOfAccount::create(['code' => '4100', 'name' => 'Revenue', 'type' => 'revenue']);

        $response = $this->actingAs($user)->post('/accounting/journal-entries', [
            'transaction_date' => '2026-08-20',
            'description' => 'Initial sale',
            'status' => 'posted',
            'lines' => [
                ['chart_of_account_id' => $cash->id, 'debit' => '100.00', 'credit' => '0.00'],
                ['chart_of_account_id' => $revenue->id, 'debit' => '0.00', 'credit' => '100.00'],
            ],
        ]);

        $journal = JournalEntry::first();
        $response->assertRedirect(route('accounting.journal-entries.show', $journal));
        $this->assertSame('JV-000001', $journal->journal_number);
        $this->assertCount(2, $journal->lines);
    }

    public function test_unbalanced_journal_is_rejected(): void
    {
        $user = User::factory()->create(['role' => 'accountant']);
        $cash = ChartOfAccount::create(['code' => '1100', 'name' => 'Cash', 'type' => 'asset']);
        $revenue = ChartOfAccount::create(['code' => '4100', 'name' => 'Revenue', 'type' => 'revenue']);

        $this->actingAs($user)->post('/accounting/journal-entries', [
            'transaction_date' => '2026-08-20',
            'status' => 'draft',
            'lines' => [
                ['chart_of_account_id' => $cash->id, 'debit' => '100.00', 'credit' => '0.00'],
                ['chart_of_account_id' => $revenue->id, 'debit' => '0.00', 'credit' => '90.00'],
            ],
        ])->assertSessionHasErrors('lines');

        $this->assertDatabaseCount('journal_entries', 0);
    }

    public function test_journal_requires_two_lines_and_rejects_negative_and_dual_side_amounts(): void
    {
        $user = User::factory()->create(['role' => 'accountant']);
        $account = ChartOfAccount::create(['code' => '1100', 'name' => 'Cash', 'type' => 'asset']);

        $base = ['transaction_date' => '2026-08-20', 'status' => 'posted'];

        $this->actingAs($user)->post('/accounting/journal-entries', $base + [
            'lines' => [['chart_of_account_id' => $account->id, 'debit' => '10.00', 'credit' => '0.00']],
        ])->assertSessionHasErrors('lines');

        $this->post('/accounting/journal-entries', $base + [
            'lines' => [
                ['chart_of_account_id' => $account->id, 'debit' => '-10.00', 'credit' => '0.00'],
                ['chart_of_account_id' => $account->id, 'debit' => '0.00', 'credit' => '10.00'],
            ],
        ])->assertSessionHasErrors('lines.0.debit');

        $this->post('/accounting/journal-entries', $base + [
            'lines' => [
                ['chart_of_account_id' => $account->id, 'debit' => '10.00', 'credit' => '10.00'],
                ['chart_of_account_id' => $account->id, 'debit' => '0.00', 'credit' => '0.00'],
            ],
        ])->assertSessionHasErrors('lines.0.debit');
    }

    public function test_inactive_account_cannot_be_used_and_journal_detail_is_viewable(): void
    {
        $accountant = User::factory()->create(['role' => 'accountant']);
        $manager = User::factory()->create(['role' => 'manager']);
        $inactive = ChartOfAccount::create(['code' => '1100', 'name' => 'Inactive Cash', 'type' => 'asset', 'is_active' => false]);
        $revenue = ChartOfAccount::create(['code' => '4100', 'name' => 'Revenue', 'type' => 'revenue']);

        $this->actingAs($accountant)->post('/accounting/journal-entries', [
            'transaction_date' => '2026-08-20',
            'status' => 'posted',
            'lines' => [
                ['chart_of_account_id' => $inactive->id, 'debit' => '100.00', 'credit' => '0.00'],
                ['chart_of_account_id' => $revenue->id, 'debit' => '0.00', 'credit' => '100.00'],
            ],
        ])->assertSessionHasErrors('lines');

        $active = ChartOfAccount::create(['code' => '1200', 'name' => 'Active Cash', 'type' => 'asset']);
        $this->post('/accounting/journal-entries', [
            'transaction_date' => '2026-08-20',
            'status' => 'posted',
            'lines' => [
                ['chart_of_account_id' => $active->id, 'debit' => '100.00', 'credit' => '0.00'],
                ['chart_of_account_id' => $revenue->id, 'debit' => '0.00', 'credit' => '100.00'],
            ],
        ]);

        $journal = JournalEntry::firstOrFail();
        $this->actingAs($manager)
            ->get('/accounting/journal-entries/'.$journal->id)
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Accounting/JournalEntries/Show')
                ->where('journal.journal_number', 'JV-000001'));
    }
}