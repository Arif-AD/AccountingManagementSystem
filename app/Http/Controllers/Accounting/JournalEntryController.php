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
        $filters = request()->validate([
            'status' => ['nullable', 'in:draft,pending,approved,posted'],
            'date' => ['nullable', 'date'],
            'journal_number' => ['nullable', 'string', 'max:20'],
            'description' => ['nullable', 'string', 'max:2000'],
        ]);

        $query = JournalEntry::query()
            ->with('creator:id,name')
            ->withSum('lines', 'debit')
            ->withSum('lines', 'credit')
            ->orderByDesc('transaction_date')
            ->orderByDesc('id');

        $query->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status));
        $query->when($filters['date'] ?? null, fn ($query, $date) => $query->whereDate('transaction_date', $date));
        $query->when($filters['journal_number'] ?? null, fn ($query, $number) => $query->where('journal_number', 'like', "%{$number}%"));
        $query->when($filters['description'] ?? null, fn ($query, $description) => $query->where('description', 'like', "%{$description}%"));

        return Inertia::render('Accounting/JournalEntries/Index', [
            'journals' => $query
                ->paginate(10)
                ->withQueryString(),
            'filters' => $filters,
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
            'lines' => ['required', 'array'],
        ]);

        $validated['lines'] = $this->validatedLines($request);

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
                'status' => JournalEntry::STATUS_DRAFT,
                'created_by' => auth()->id(),
            ]);

            $journalEntry->lines()->createMany($validated['lines']);

            return $journalEntry;
        });

        return redirect()->route('accounting.journal-entries.show', $journalEntry);
    }

    public function edit(JournalEntry $journalEntry): Response
    {
        $this->ensureDraft($journalEntry);

        return Inertia::render('Accounting/JournalEntries/Edit', [
            'journal' => $journalEntry->load('lines.account:id,code,name'),
            'accounts' => ChartOfAccount::query()->where('is_active', true)->orderBy('code')->get(['id', 'code', 'name']),
        ]);
    }

    public function update(Request $request, JournalEntry $journalEntry): RedirectResponse
    {
        $this->ensureDraft($journalEntry);
        $validated = $request->validate([
            'transaction_date' => ['required', 'date'],
            'description' => ['nullable', 'string', 'max:2000'],
            'lines' => ['required', 'array'],
        ]);
        $validated['lines'] = $this->validatedLines($request);

        DB::transaction(function () use ($journalEntry, $validated) {
            $journalEntry->update([
                'transaction_date' => $validated['transaction_date'],
                'description' => $validated['description'] ?? null,
            ]);
            $journalEntry->lines()->delete();
            $journalEntry->lines()->createMany($validated['lines']);
        });

        return redirect()->route('accounting.journal-entries.show', $journalEntry);
    }

    public function destroy(JournalEntry $journalEntry): RedirectResponse
    {
        $this->ensureDraft($journalEntry);
        DB::transaction(fn () => $journalEntry->delete());

        return redirect()->route('accounting.journal-entries.index');
    }

    public function post(JournalEntry $journalEntry): RedirectResponse
    {
        abort_unless($journalEntry->isApproved(), 403, 'Only approved journals can be posted.');
        $this->validatedLinesForEntry($journalEntry);

        DB::transaction(fn () => $journalEntry->update(['status' => JournalEntry::STATUS_POSTED]));

        return redirect()->route('accounting.journal-entries.show', $journalEntry);
    }

    public function submit(JournalEntry $journalEntry): RedirectResponse
    {
        $this->ensureDraft($journalEntry);
        $this->validatedLinesForEntry($journalEntry);

        DB::transaction(fn () => $journalEntry->update(['status' => JournalEntry::STATUS_PENDING]));

        return redirect()->route('accounting.journal-entries.show', $journalEntry);
    }

    public function approve(JournalEntry $journalEntry): RedirectResponse
    {
        abort_unless($journalEntry->isPending(), 403, 'Only pending journals can be approved.');
        $this->validatedLinesForEntry($journalEntry);

        DB::transaction(fn () => $journalEntry->update(['status' => JournalEntry::STATUS_APPROVED]));

        return redirect()->route('accounting.journal-entries.show', $journalEntry);
    }

    public function reject(JournalEntry $journalEntry): RedirectResponse
    {
        abort_unless($journalEntry->isPending(), 403, 'Only pending journals can be rejected.');

        DB::transaction(fn () => $journalEntry->update(['status' => JournalEntry::STATUS_DRAFT]));

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

    private function ensureDraft(JournalEntry $journalEntry): void
    {
        abort_unless($journalEntry->isDraft(), 403, 'Posted journals are immutable.');
    }

    private function validatedLines(Request $request): array
    {
        $validated = $request->validate([
            'lines' => ['required', 'array', 'min:2'],
            'lines.*.chart_of_account_id' => ['required', 'integer', 'exists:chart_of_accounts,id'],
            'lines.*.description' => ['nullable', 'string', 'max:1000'],
            'lines.*.debit' => ['required', 'numeric', 'gte:0'],
            'lines.*.credit' => ['required', 'numeric', 'gte:0'],
        ]);

        $totalDebit = '0.00';
        $totalCredit = '0.00';
        $hasDebit = false;
        $hasCredit = false;
        foreach ($validated['lines'] as $index => $line) {
            $debit = number_format((float) $line['debit'], 2, '.', '');
            $credit = number_format((float) $line['credit'], 2, '.', '');
            if (bccomp($debit, '0.00', 2) > 0 && bccomp($credit, '0.00', 2) > 0) {
                throw ValidationException::withMessages(["lines.$index.debit" => 'A line cannot contain both debit and credit.']);
            }
            if (bccomp($debit, '0.00', 2) === 0 && bccomp($credit, '0.00', 2) === 0) {
                throw ValidationException::withMessages(["lines.$index.debit" => 'Each line must contain a positive debit or credit amount.']);
            }
            $hasDebit = $hasDebit || bccomp($debit, '0.00', 2) > 0;
            $hasCredit = $hasCredit || bccomp($credit, '0.00', 2) > 0;
            $totalDebit = bcadd($totalDebit, $debit, 2);
            $totalCredit = bcadd($totalCredit, $credit, 2);
        }
        if (! $hasDebit || ! $hasCredit || bccomp($totalDebit, $totalCredit, 2) !== 0) {
            throw ValidationException::withMessages(['lines' => 'The journal must have debit and credit totals that are equal.']);
        }
        $accountIds = collect($validated['lines'])->pluck('chart_of_account_id')->unique();
        if (ChartOfAccount::whereIn('id', $accountIds)->where('is_active', true)->count() !== $accountIds->count()) {
            throw ValidationException::withMessages(['lines' => 'Every journal line must reference an active Chart of Account.']);
        }

        return $validated['lines'];
    }

    private function validatedLinesForEntry(JournalEntry $journalEntry): void
    {
        $this->validatedLines(new Request(['lines' => $journalEntry->lines->toArray()]));
    }
}