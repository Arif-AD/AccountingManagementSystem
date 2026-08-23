<?php

namespace Tests\Feature;

use App\Models\ChartOfAccount;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use SplFileObject;
use Tests\TestCase;

class Day6Test extends TestCase
{
    use RefreshDatabase;

    protected function createCsvFile(string $content): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'csv_');
        file_put_contents($path, $content);
        
        return new UploadedFile(
            $path,
            'upload.csv',
            'text/csv',
            null,
            true
        );
    }

    // Financial Position Tests
    public function test_accountant_can_view_financial_position(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'accountant']))
            ->get('/accounting/financial-position')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Accounting/Reports/FinancialPosition')
                ->has('assets')
                ->has('liabilities')
                ->has('equity')
                ->has('totalAssets')
                ->has('balanced'));
    }

    public function test_manager_can_view_financial_position(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'manager']))
            ->get('/accounting/financial-position')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Accounting/Reports/FinancialPosition'));
    }

    public function test_financial_position_only_counts_posted_journals(): void
    {
        $user = User::factory()->create(['role' => 'accountant']);
        $cash = ChartOfAccount::create(['code' => '1100', 'name' => 'Cash', 'type' => 'asset']);
        $revenue = ChartOfAccount::create(['code' => '4100', 'name' => 'Revenue', 'type' => 'revenue']);

        // Create posted journal
        $postedJournal = JournalEntry::create([
            'journal_number' => 'JV-000001',
            'transaction_date' => '2026-08-01',
            'description' => 'Posted sale',
            'status' => JournalEntry::STATUS_POSTED,
            'created_by' => $user->id,
        ]);
        $postedJournal->lines()->createMany([
            ['chart_of_account_id' => $cash->id, 'debit' => 100000, 'credit' => 0],
            ['chart_of_account_id' => $revenue->id, 'debit' => 0, 'credit' => 100000],
        ]);

        // Create draft journal with same amounts (should NOT be counted)
        $draftJournal = JournalEntry::create([
            'journal_number' => 'JV-000002',
            'transaction_date' => '2026-08-02',
            'description' => 'Draft sale',
            'status' => JournalEntry::STATUS_DRAFT,
            'created_by' => $user->id,
        ]);
        $draftJournal->lines()->createMany([
            ['chart_of_account_id' => $cash->id, 'debit' => 100000, 'credit' => 0],
            ['chart_of_account_id' => $revenue->id, 'debit' => 0, 'credit' => 100000],
        ]);

        $this->actingAs($user)
            ->get('/accounting/financial-position?start_date=2026-08-01&end_date=2026-08-31')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('totalAssets', 100000));
    }

    // Upload Jurnal Tests
    public function test_accountant_can_view_upload_page(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'accountant']))
            ->get('/accounting/journal-upload')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Accounting/JournalEntries/Upload'));
    }

    public function test_manager_cannot_view_upload_page(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'manager']))
            ->get('/accounting/journal-upload')
            ->assertForbidden();
    }

    public function test_accountant_can_upload_valid_csv(): void
    {
        $user = User::factory()->create(['role' => 'accountant']);
        $cash = ChartOfAccount::create(['code' => '1100', 'name' => 'Cash', 'type' => 'asset', 'is_active' => true]);
        $expense = ChartOfAccount::create(['code' => '5100', 'name' => 'Expense', 'type' => 'expense', 'is_active' => true]);

        $csvContent = "date,description,account_code,debit,credit\n";
        $csvContent .= "2026-08-22,Pembelian laptop,1100,10000000,0\n";
        $csvContent .= "2026-08-22,Pembelian laptop,5100,0,10000000\n";

        $file = $this->createCsvFile($csvContent);

        $response = $this->actingAs($user)->post('/accounting/journal-upload', ['file' => $file]);

        $response->assertRedirect();

        $this->assertDatabaseHas('journal_entries', [
            'status' => JournalEntry::STATUS_DRAFT,
            'description' => 'Imported from CSV',
        ]);

        $this->assertDatabaseCount('journal_entry_lines', 2);
    }

    public function test_upload_csv_with_invalid_account_code_rejected(): void
    {
        $user = User::factory()->create(['role' => 'accountant']);

        $csvContent = "date,description,account_code,debit,credit\n";
        $csvContent .= "2026-08-22,Pembelian laptop,9999,10000000,0\n";
        $csvContent .= "2026-08-22,Pembelian laptop,9998,0,10000000\n";

        $file = $this->createCsvFile($csvContent);

        $this->actingAs($user)->post('/accounting/journal-upload', ['file' => $file])
            ->assertSessionHasErrors('file');

        $this->assertDatabaseCount('journal_entries', 0);
    }

    public function test_upload_csv_with_unbalanced_journal_rejected(): void
    {
        $user = User::factory()->create(['role' => 'accountant']);
        $cash = ChartOfAccount::create(['code' => '1100', 'name' => 'Cash', 'type' => 'asset', 'is_active' => true]);
        $expense = ChartOfAccount::create(['code' => '5100', 'name' => 'Expense', 'type' => 'expense', 'is_active' => true]);

        $csvContent = "date,description,account_code,debit,credit\n";
        $csvContent .= "2026-08-22,Pembelian laptop,1100,10000000,0\n";
        $csvContent .= "2026-08-22,Pembelian laptop,5100,0,5000000\n"; // Unbalanced

        $file = $this->createCsvFile($csvContent);

        $this->actingAs($user)->post('/accounting/journal-upload', ['file' => $file])
            ->assertSessionHasErrors('file');

        $this->assertDatabaseCount('journal_entries', 0);
    }

    public function test_upload_csv_with_negative_amounts_rejected(): void
    {
        $user = User::factory()->create(['role' => 'accountant']);
        $cash = ChartOfAccount::create(['code' => '1100', 'name' => 'Cash', 'type' => 'asset', 'is_active' => true]);
        $expense = ChartOfAccount::create(['code' => '5100', 'name' => 'Expense', 'type' => 'expense', 'is_active' => true]);

        $csvContent = "date,description,account_code,debit,credit\n";
        $csvContent .= "2026-08-22,Pembelian laptop,1100,-10000000,0\n";
        $csvContent .= "2026-08-22,Pembelian laptop,5100,0,10000000\n";

        $file = $this->createCsvFile($csvContent);

        $this->actingAs($user)->post('/accounting/journal-upload', ['file' => $file])
            ->assertSessionHasErrors('file');

        $this->assertDatabaseCount('journal_entries', 0);
    }

    public function test_upload_csv_with_dual_amounts_rejected(): void
    {
        $user = User::factory()->create(['role' => 'accountant']);
        $cash = ChartOfAccount::create(['code' => '1100', 'name' => 'Cash', 'type' => 'asset', 'is_active' => true]);
        $expense = ChartOfAccount::create(['code' => '5100', 'name' => 'Expense', 'type' => 'expense', 'is_active' => true]);

        $csvContent = "date,description,account_code,debit,credit\n";
        $csvContent .= "2026-08-22,Pembelian laptop,1100,10000000,5000000\n";
        $csvContent .= "2026-08-22,Pembelian laptop,5100,0,5000000\n";

        $file = $this->createCsvFile($csvContent);

        $this->actingAs($user)->post('/accounting/journal-upload', ['file' => $file])
            ->assertSessionHasErrors('file');

        $this->assertDatabaseCount('journal_entries', 0);
    }

    public function test_uploaded_journal_is_draft_status(): void
    {
        $user = User::factory()->create(['role' => 'accountant']);
        $cash = ChartOfAccount::create(['code' => '1100', 'name' => 'Cash', 'type' => 'asset', 'is_active' => true]);
        $expense = ChartOfAccount::create(['code' => '5100', 'name' => 'Expense', 'type' => 'expense', 'is_active' => true]);

        $csvContent = "date,description,account_code,debit,credit\n";
        $csvContent .= "2026-08-22,Pembelian laptop,1100,10000000,0\n";
        $csvContent .= "2026-08-22,Pembelian laptop,5100,0,10000000\n";

        $file = $this->createCsvFile($csvContent);

        $this->actingAs($user)->post('/accounting/journal-upload', ['file' => $file]);

        $this->assertDatabaseHas('journal_entries', [
            'status' => JournalEntry::STATUS_DRAFT,
        ]);
    }

    public function test_uploaded_journal_number_auto_generated(): void
    {
        $user = User::factory()->create(['role' => 'accountant']);
        $cash = ChartOfAccount::create(['code' => '1100', 'name' => 'Cash', 'type' => 'asset', 'is_active' => true]);
        $expense = ChartOfAccount::create(['code' => '5100', 'name' => 'Expense', 'type' => 'expense', 'is_active' => true]);

        $csvContent = "date,description,account_code,debit,credit\n";
        $csvContent .= "2026-08-22,Pembelian laptop,1100,10000000,0\n";
        $csvContent .= "2026-08-22,Pembelian laptop,5100,0,10000000\n";

        $file = $this->createCsvFile($csvContent);

        $this->actingAs($user)->post('/accounting/journal-upload', ['file' => $file]);

        $this->assertDatabaseHas('journal_entries', [
            'journal_number' => 'JV-000001',
        ]);
    }

    public function test_manager_cannot_upload_csv(): void
    {
        $user = User::factory()->create(['role' => 'manager']);

        $csvContent = "date,description,account_code,debit,credit\n";
        $csvContent .= "2026-08-22,Pembelian laptop,1100,10000000,0\n";

        $file = $this->createCsvFile($csvContent);

        $this->actingAs($user)->post('/accounting/journal-upload', ['file' => $file])
            ->assertForbidden();
    }

    public function test_manager_can_view_uploaded_journal_after_creation(): void
    {
        $accountant = User::factory()->create(['role' => 'accountant']);
        $manager = User::factory()->create(['role' => 'manager']);
        $cash = ChartOfAccount::create(['code' => '1100', 'name' => 'Cash', 'type' => 'asset', 'is_active' => true]);
        $expense = ChartOfAccount::create(['code' => '5100', 'name' => 'Expense', 'type' => 'expense', 'is_active' => true]);

        // Create journal via upload
        $journal = JournalEntry::create([
            'journal_number' => 'JV-000001',
            'transaction_date' => '2026-08-22',
            'description' => 'Imported from CSV',
            'status' => JournalEntry::STATUS_DRAFT,
            'created_by' => $accountant->id,
        ]);
        $journal->lines()->createMany([
            ['chart_of_account_id' => $cash->id, 'debit' => 10000000, 'credit' => 0],
            ['chart_of_account_id' => $expense->id, 'debit' => 0, 'credit' => 10000000],
        ]);

        // Manager should be able to view
        $this->actingAs($manager)->get('/accounting/journal-entries/' . $journal->id)
            ->assertOk();
    }

    public function test_upload_csv_with_less_than_2_lines_rejected(): void
    {
        $user = User::factory()->create(['role' => 'accountant']);
        $cash = ChartOfAccount::create(['code' => '1100', 'name' => 'Cash', 'type' => 'asset', 'is_active' => true]);

        $csvContent = "date,description,account_code,debit,credit\n";
        $csvContent .= "2026-08-22,Pembelian laptop,1100,10000000,0\n";

        $file = $this->createCsvFile($csvContent);

        $this->actingAs($user)->post('/accounting/journal-upload', ['file' => $file])
            ->assertSessionHasErrors('file');

        $this->assertDatabaseCount('journal_entries', 0);
    }
}
