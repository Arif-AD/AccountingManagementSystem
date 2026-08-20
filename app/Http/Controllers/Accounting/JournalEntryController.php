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

class JournalEntryController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Accounting/JournalEntries/Index', [
            'journals' => JournalEntry::query()
                ->with('creator:id,name')
                ->withSum('lines', 'debit')
                ->orderByDesc('transaction_date')
                ->orderByDesc('id')
                ->paginate(10)
                ->withQueryString(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Accounting/JournalEntries/Create', [
            'accounts' => ChartOfAccount::query()
                ->where('is_active', true)
                ->orderBy('code')
                ->get(['id', 'code', 'name']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'transaction_date' => ['required', 'date'],
            'description' => ['nullable', 'string', 'max:2000'],
            'status' => ['required', 'in:draft,posted'],
            'lines' => ['required', 'array', 'min:2'],
            'lines.*.chart_of_account_id' => ['required', 'integer', 'exists:chart_of_accounts,id'],
            'lines.*.description' => ['nullable', 'string', 'max:1000'],
            'lines.*.debit' => ['required', 'numeric', 'min:0'],
            'lines.*.credit' => ['required', 'numeric', 'min:0'],
        ]);

        $totalDebit = '0.00';
        $totalCredit = '0.00';
        $hasDebit = false;
        $hasCredit = false;

        foreach ($validated['lines'] as $index => $line) {
            $debit = number_format((float) $line['debit'], 2, '.', '');
            $credit = number_format((float) $line['credit'], 2, '.', '');

            if (bccomp($debit, '0.00', 2) > 0 && bccomp($credit, '0.00', 2) > 0) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    "lines.$index.debit" => 'A line cannot contain both debit and credit.',
                ]);
            }

            if (bccomp($debit, '0.00', 2) > 0) {
                $hasDebit = true;
            }

            if (bccomp($credit, '0.00', 2) > 0) {
                $hasCredit = true;
            }

            $totalDebit = bcadd($totalDebit, $debit, 2);
            $totalCredit = bcadd($totalCredit, $credit, 2);
        }

        if (! $hasDebit || ! $hasCredit) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'lines' => 'The journal must contain at least one debit and one credit.',
            ]);
        }

        if (bccomp($totalDebit, $totalCredit, 2) !== 0) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'lines' => 'Total debit must equal total credit.',
            ]);
        }

        $accountIds = collect($validated['lines'])->pluck('chart_of_account_id')->unique();
        $activeAccountCount = ChartOfAccount::query()
            ->whereIn('id', $accountIds)
            ->where('is_active', true)
            ->count();

        if ($activeAccountCount !== $accountIds->count()) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'lines' => 'Every journal line must reference an active Chart of Account.',
            ]);
        }

        $journalEntry = DB::transaction(function () use ($validated) {
            $lastNumber = JournalEntry::query()
                ->lockForUpdate()
                ->latest('id')
                ->value('journal_number');
            $nextNumber = $lastNumber ? ((int) substr($lastNumber, 3)) + 1 : 1;

            $journalEntry = JournalEntry::create([
                'journal_number' => 'JV-'.str_pad((string) $nextNumber, 6, '0', STR_PAD_LEFT),
                'transaction_date' => $validated['transaction_date'],
                'description' => $validated['description'] ?? null,
                'status' => $validated['status'],
                'created_by' => auth()->id(),
            ]);

            $journalEntry->lines()->createMany($validated['lines']);

            return $journalEntry;
        });

        return redirect()->route('accounting.journal-entries.show', $journalEntry);
    }

    public function show(JournalEntry $journalEntry): Response
    {
        return Inertia::render('Accounting/JournalEntries/Show', [
            'journal' => $journalEntry->load([
                'creator:id,name',
                'lines.account:id,code,name',
            ]),
        ]);
    }
}