<?php

namespace Tests\Feature;

use App\Models\ChartOfAccount;
use App\Models\JournalEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
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

    public function test_accountant_can_upload_csv_with_excel_mime_type(): void
    {
        $user = User::factory()->create(['role' => 'accountant']);
        $file = UploadedFile::fake()->create('journal.csv', 10, 'application/vnd.ms-excel');

        $this->actingAs($user)
            ->post('/accounting/journal-entries/upload', ['file' => $file])
            ->assertRedirect();

        $this->assertDatabaseHas('journal_entries', [
            'status' => 'pending',
            'original_file_name' => 'journal.csv',
        ]);
    }

    public function test_accountant_can_upload_supported_spreadsheet_extensions(): void
    {
        foreach (['journal.csv', 'journal.xlsx', 'journal.xls'] as $fileName) {
            $this->actingAs(User::factory()->create(['role' => 'accountant']))
                ->post('/accounting/journal-entries/upload', [
                    'file' => UploadedFile::fake()->create($fileName, 10),
                ])
                ->assertRedirect();
        }

        $this->assertDatabaseCount('journal_entries', 3);
    }

    public function test_accountant_cannot_upload_when_they_have_a_pending_upload(): void
    {
        $user = User::factory()->create(['role' => 'accountant']);
        JournalEntry::create([
            'journal_number' => 'JV-000001',
            'transaction_date' => '2026-09-04',
            'description' => 'Pending upload',
            'source' => 'csv',
            'original_file_name' => 'existing.csv',
            'file_path' => 'journal-uploads/existing.csv',
            'status' => JournalEntry::STATUS_PENDING,
            'created_by' => $user->id,
        ]);

        $this->actingAs($user)
            ->post('/accounting/journal-entries/upload', [
                'file' => UploadedFile::fake()->create('another.csv', 10),
            ])
            ->assertSessionHasErrors('file');

        $this->assertDatabaseCount('journal_entries', 1);
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
        $this->assertSame('draft', $journal->status);
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

    public function test_accountant_can_edit_and_delete_draft_journal(): void
    {
        $accountant = User::factory()->create(['role' => 'accountant']);
        $journal = $this->createDraft($accountant);

        $this->actingAs($accountant)->get(route('accounting.journal-entries.edit', $journal))->assertOk();
        $this->put(route('accounting.journal-entries.update', $journal), $this->journalPayload($journal, 'Updated draft'))
            ->assertRedirect(route('accounting.journal-entries.show', $journal));
        $this->assertDatabaseHas('journal_entries', ['id' => $journal->id, 'description' => 'Updated draft']);

        $this->delete(route('accounting.journal-entries.destroy', $journal))->assertRedirect(route('accounting.journal-entries.index'));
        $this->assertDatabaseMissing('journal_entries', ['id' => $journal->id]);
        $this->assertDatabaseCount('journal_entry_lines', 0);
    }

    public function test_accountant_can_post_approved_journal_and_posted_journal_is_immutable(): void
    {
        $accountant = User::factory()->create(['role' => 'accountant']);
        $manager = User::factory()->create(['role' => 'manager']);
        $journal = $this->createDraft($accountant);

        $this->actingAs($accountant)->post(route('accounting.journal-entries.submit', $journal));
        $this->actingAs($manager)->post(route('accounting.journal-entries.approve', $journal));
        $this->actingAs($accountant)->post(route('accounting.journal-entries.post', $journal))
            ->assertRedirect(route('accounting.journal-entries.show', $journal));
        $this->assertDatabaseHas('journal_entries', ['id' => $journal->id, 'status' => 'posted']);

        $this->put(route('accounting.journal-entries.update', $journal), $this->journalPayload($journal, 'Should fail'))->assertForbidden();
        $this->delete(route('accounting.journal-entries.destroy', $journal))->assertForbidden();
        $this->post(route('accounting.journal-entries.post', $journal))->assertForbidden();
    }

    public function test_unbalanced_or_one_sided_draft_cannot_be_posted(): void
    {
        $accountant = User::factory()->create(['role' => 'accountant']);
        $cash = ChartOfAccount::create(['code' => '1100', 'name' => 'Cash', 'type' => 'asset']);
        $revenue = ChartOfAccount::create(['code' => '4100', 'name' => 'Revenue', 'type' => 'revenue']);

        foreach ([
            [['chart_of_account_id' => $cash->id, 'debit' => '100.00', 'credit' => '0.00'], ['chart_of_account_id' => $revenue->id, 'debit' => '0.00', 'credit' => '90.00']],
            [['chart_of_account_id' => $cash->id, 'debit' => '100.00', 'credit' => '0.00'], ['chart_of_account_id' => $revenue->id, 'debit' => '100.00', 'credit' => '0.00']],
            [['chart_of_account_id' => $cash->id, 'debit' => '0.00', 'credit' => '100.00'], ['chart_of_account_id' => $revenue->id, 'debit' => '0.00', 'credit' => '100.00']],
        ] as $lines) {
            $journal = JournalEntry::create(['journal_number' => 'JV-'.fake()->unique()->numerify('######'), 'transaction_date' => '2026-08-21', 'status' => 'draft', 'created_by' => $accountant->id]);
            $journal->lines()->createMany($lines);
            $this->actingAs($accountant)->post(route('accounting.journal-entries.submit', $journal))->assertSessionHasErrors('lines');
            $this->assertSame('draft', $journal->fresh()->status);
        }
    }

    public function test_manager_can_view_but_cannot_edit_delete_or_post(): void
    {
        $manager = User::factory()->create(['role' => 'manager']);
        $journal = $this->createDraft(User::factory()->create(['role' => 'accountant']));

        $this->actingAs($manager)->get(route('accounting.journal-entries.show', $journal))->assertOk();
        $this->get(route('accounting.journal-entries.edit', $journal))->assertForbidden();
        $this->put(route('accounting.journal-entries.update', $journal), $this->journalPayload($journal, 'Nope'))->assertForbidden();
        $this->delete(route('accounting.journal-entries.destroy', $journal))->assertForbidden();
        $this->post(route('accounting.journal-entries.post', $journal))->assertForbidden();
    }

    public function test_accountant_can_submit_draft_and_manager_can_approve_or_reject(): void
    {
        $accountant = User::factory()->create(['role' => 'accountant']);
        $manager = User::factory()->create(['role' => 'manager']);
        $journal = $this->createDraft($accountant);

        $this->actingAs($accountant)->post(route('accounting.journal-entries.submit', $journal))->assertRedirect();
        $this->assertDatabaseHas('journal_entries', ['id' => $journal->id, 'status' => 'pending']);
        $this->actingAs($accountant)->get(route('accounting.journal-entries.edit', $journal))->assertForbidden();
        $this->actingAs($accountant)->delete(route('accounting.journal-entries.destroy', $journal))->assertForbidden();
        $this->actingAs($accountant)->post(route('accounting.journal-entries.approve', $journal))->assertForbidden();

        $this->actingAs($manager)->post(route('accounting.journal-entries.reject', $journal))->assertRedirect();
        $this->assertDatabaseHas('journal_entries', ['id' => $journal->id, 'status' => 'draft', 'journal_number' => $journal->journal_number]);
        $this->actingAs($accountant)->post(route('accounting.journal-entries.submit', $journal));
        $this->actingAs($manager)->post(route('accounting.journal-entries.approve', $journal))->assertRedirect();
        $this->assertDatabaseHas('journal_entries', ['id' => $journal->id, 'status' => 'approved']);
        $this->actingAs($manager)->post(route('accounting.journal-entries.post', $journal))->assertForbidden();
    }

    public function test_invalid_transition_returns_forbidden(): void
    {
        $accountant = User::factory()->create(['role' => 'accountant']);
        $manager = User::factory()->create(['role' => 'manager']);
        $journal = $this->createDraft($accountant);

        $this->actingAs($manager)->post(route('accounting.journal-entries.approve', $journal))->assertForbidden();
        $this->actingAs($accountant)->post(route('accounting.journal-entries.post', $journal))->assertForbidden();
        $this->actingAs($manager)->post(route('accounting.journal-entries.reject', $journal))->assertForbidden();
    }

    private function createDraft(User $user): JournalEntry
    {
        $cash = ChartOfAccount::create(['code' => '1100'.fake()->unique()->numerify('##'), 'name' => 'Cash '.fake()->unique()->word(), 'type' => 'asset']);
        $revenue = ChartOfAccount::create(['code' => '4100'.fake()->unique()->numerify('##'), 'name' => 'Revenue '.fake()->unique()->word(), 'type' => 'revenue']);
        $journal = JournalEntry::create(['journal_number' => 'JV-'.fake()->unique()->numerify('######'), 'transaction_date' => '2026-08-21', 'status' => 'draft', 'created_by' => $user->id]);
        $journal->lines()->createMany([
            ['chart_of_account_id' => $cash->id, 'debit' => '100.00', 'credit' => '0.00'],
            ['chart_of_account_id' => $revenue->id, 'debit' => '0.00', 'credit' => '100.00'],
        ]);
        return $journal;
    }

    private function journalPayload(JournalEntry $journal, string $description): array
    {
        return ['transaction_date' => '2026-08-22', 'description' => $description, 'lines' => $journal->lines->map(fn ($line) => ['chart_of_account_id' => $line->chart_of_account_id, 'description' => $line->description, 'debit' => $line->debit, 'credit' => $line->credit])->all()];
    }
}