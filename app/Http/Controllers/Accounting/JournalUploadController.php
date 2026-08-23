<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\ChartOfAccount;
use App\Models\JournalEntry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class JournalUploadController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Accounting/JournalEntries/Upload');
    }

    public function store(Request $request): RedirectResponse
    {
        $file = $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt'],
        ])['file'];

        $rows = array_map('str_getcsv', file($file->getRealPath()));
        $headers = array_shift($rows);
        if (!in_array('date', $headers) || !in_array('description', $headers) || !in_array('account_code', $headers) || !in_array('debit', $headers) || !in_array('credit', $headers)) {
            throw ValidationException::withMessages(['file' => 'CSV must contain columns: date, description, account_code, debit, credit']);
        }

        $lines = [];
        $accountCodesInFile = [];
        $totalDebit = '0.00';
        $totalCredit = '0.00';
        $hasDebit = false;
        $hasCredit = false;

        foreach ($rows as $rowIndex => $row) {
            if (count($row) < 5 || (empty($row[0]) && empty($row[1]) && empty($row[2]) && empty($row[3]) && empty($row[4]))) {
                continue;
            }

            $data = array_combine($headers, $row);
            $date = trim($data['date'] ?? '');
            $description = trim($data['description'] ?? '');
            $accountCode = trim($data['account_code'] ?? '');
            $debit = trim($data['debit'] ?? '0');
            $credit = trim($data['credit'] ?? '0');

            if (!$date || !$accountCode) {
                throw ValidationException::withMessages(['file' => "Row " . ($rowIndex + 2) . ": date and account_code are required"]);
            }

            if (!strtotime($date)) {
                throw ValidationException::withMessages(['file' => "Row " . ($rowIndex + 2) . ": invalid date format"]);
            }

            if (!is_numeric($debit) || !is_numeric($credit) || (float) $debit < 0 || (float) $credit < 0) {
                throw ValidationException::withMessages(['file' => "Row " . ($rowIndex + 2) . ": debit and credit must be non-negative numbers"]);
            }

            if ((float) $debit > 0 && (float) $credit > 0) {
                throw ValidationException::withMessages(['file' => "Row " . ($rowIndex + 2) . ": line cannot have both debit and credit"]);
            }

            if ((float) $debit === 0.0 && (float) $credit === 0.0) {
                throw ValidationException::withMessages(['file' => "Row " . ($rowIndex + 2) . ": line must have debit or credit"]);
            }

            $debitFormatted = number_format((float) $debit, 2, '.', '');
            $creditFormatted = number_format((float) $credit, 2, '.', '');
            $totalDebit = bcadd($totalDebit, $debitFormatted, 2);
            $totalCredit = bcadd($totalCredit, $creditFormatted, 2);
            $hasDebit = $hasDebit || (float) $debitFormatted > 0;
            $hasCredit = $hasCredit || (float) $creditFormatted > 0;

            $lines[] = [
                'date' => $date,
                'description' => $description ?: null,
                'account_code' => $accountCode,
                'debit' => $debitFormatted,
                'credit' => $creditFormatted,
            ];

            $accountCodesInFile[] = $accountCode;
        }

        if (count($lines) < 2) {
            throw ValidationException::withMessages(['file' => 'CSV must contain at least 2 journal lines']);
        }

        if (!$hasDebit || !$hasCredit || bccomp($totalDebit, $totalCredit, 2) !== 0) {
            throw ValidationException::withMessages(['file' => 'Journal must have debit and credit totals that are equal']);
        }

        $accounts = ChartOfAccount::query()
            ->whereIn('code', array_unique($accountCodesInFile))
            ->where('is_active', true)
            ->get(['id', 'code']);

        $accountMap = $accounts->keyBy('code');

        foreach ($accountCodesInFile as $code) {
            if (!isset($accountMap[$code])) {
                throw ValidationException::withMessages(['file' => "Account code '{$code}' does not exist or is inactive"]);
            }
        }

        $journalEntry = DB::transaction(function () use ($lines, $accountMap) {
            $lastNumber = JournalEntry::query()
                ->lockForUpdate()
                ->latest('id')
                ->value('journal_number');
            $nextNumber = $lastNumber ? ((int) substr($lastNumber, 3)) + 1 : 1;

            $journal = JournalEntry::create([
                'journal_number' => 'JV-' . str_pad((string) $nextNumber, 6, '0', STR_PAD_LEFT),
                'transaction_date' => $lines[0]['date'],
                'description' => 'Imported from CSV',
                'status' => JournalEntry::STATUS_DRAFT,
                'created_by' => auth()->id(),
            ]);

            foreach ($lines as $line) {
                $journal->lines()->create([
                    'chart_of_account_id' => $accountMap[$line['account_code']]->id,
                    'description' => $line['description'],
                    'debit' => $line['debit'],
                    'credit' => $line['credit'],
                ]);
            }

            return $journal;
        });

        return redirect()->route('accounting.journal-entries.show', $journalEntry)
            ->with('success', 'Journal imported successfully as draft. Please review and submit for approval.');
    }
}
