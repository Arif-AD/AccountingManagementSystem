<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\ChartOfAccount;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ReportController extends Controller
{
    public function generalLedger(Request $request): Response
    {
        $filters = $this->dateFilters($request);
        $accounts = ChartOfAccount::query()->where('is_active', true)->orderBy('code')->get(['id', 'code', 'name', 'type']);
        $accountId = (int) ($request->input('account_id') ?: ($accounts->first()?->id ?? 0));
        $account = $accounts->firstWhere('id', $accountId);

        $opening = ['debit' => 0.0, 'credit' => 0.0];
        $lines = collect();

        if ($account) {
            $opening = JournalEntryLine::query()
                ->join('journal_entries', 'journal_entries.id', '=', 'journal_entry_lines.journal_entry_id')
                ->where('journal_entries.status', JournalEntry::STATUS_POSTED)
                ->where('journal_entry_lines.chart_of_account_id', $account->id)
                ->whereDate('journal_entries.transaction_date', '<', $filters['from'])
                ->selectRaw('COALESCE(SUM(journal_entry_lines.debit), 0) as debit, COALESCE(SUM(journal_entry_lines.credit), 0) as credit')
                ->first()
                ->only(['debit', 'credit']);

            $lines = JournalEntryLine::query()
                ->join('journal_entries', 'journal_entries.id', '=', 'journal_entry_lines.journal_entry_id')
                ->select('journal_entry_lines.*')
                ->with('journalEntry:id,journal_number,transaction_date,description')
                ->where('chart_of_account_id', $account->id)
                ->whereHas('journalEntry', function ($query) use ($filters) {
                    $query->where('status', JournalEntry::STATUS_POSTED)
                        ->whereDate('transaction_date', '>=', $filters['from'])
                        ->whereDate('transaction_date', '<=', $filters['to']);
                })
                ->orderBy('journal_entries.transaction_date')
                ->orderBy('journal_entry_lines.id')
                ->get();
        }

        $openingBalance = $this->signedBalance($account?->type, (float) $opening['debit'], (float) $opening['credit']);
        $runningBalance = $openingBalance;
        $totalDebit = 0.0;
        $totalCredit = 0.0;
        $transactions = $lines->map(function (JournalEntryLine $line) use (&$runningBalance, &$totalDebit, &$totalCredit, $account) {
            $debit = (float) $line->debit;
            $credit = (float) $line->credit;
            $totalDebit += $debit;
            $totalCredit += $credit;
            $runningBalance += $this->signedBalance($account?->type, $debit, $credit);

            return [
                'date' => $line->journalEntry->transaction_date->toDateString(),
                'journal_number' => $line->journalEntry->journal_number,
                'description' => $line->description ?: $line->journalEntry->description,
                'debit' => $debit,
                'credit' => $credit,
                'running_balance' => $runningBalance,
            ];
        });

        return Inertia::render('Accounting/Reports/GeneralLedger', [
            'accounts' => $accounts,
            'account' => $account,
            'transactions' => $transactions,
            'openingBalance' => $openingBalance,
            'totalDebit' => $totalDebit,
            'totalCredit' => $totalCredit,
            'endingBalance' => $runningBalance,
            'filters' => [...$filters, 'account_id' => $account?->id],
        ]);
    }

    public function trialBalance(Request $request): Response
    {
        $filters = $this->dateFilters($request);
        $aggregates = JournalEntryLine::query()
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_entry_lines.journal_entry_id')
            ->where('journal_entries.status', JournalEntry::STATUS_POSTED)
            ->whereDate('journal_entries.transaction_date', '>=', $filters['from'])
            ->whereDate('journal_entries.transaction_date', '<=', $filters['to'])
            ->groupBy('journal_entry_lines.chart_of_account_id')
            ->select('journal_entry_lines.chart_of_account_id')
            ->selectRaw('SUM(journal_entry_lines.debit) as debit, SUM(journal_entry_lines.credit) as credit')
            ->get()
            ->keyBy('chart_of_account_id');

        $accounts = ChartOfAccount::query()
            ->where('is_active', true)
            ->whereIn('id', $aggregates->keys())
            ->orderBy('code')
            ->get(['id', 'code', 'name', 'type'])
            ->map(function (ChartOfAccount $account) use ($aggregates) {
                $aggregate = $aggregates->get($account->id);

                return [
                    'id' => $account->id,
                    'code' => $account->code,
                    'name' => $account->name,
                    'type' => $account->type,
                    'debit' => (float) $aggregate->debit,
                    'credit' => (float) $aggregate->credit,
                ];
            })
            ->values();

        return Inertia::render('Accounting/Reports/TrialBalance', [
            'accounts' => $accounts,
            'totalDebit' => $accounts->sum('debit'),
            'totalCredit' => $accounts->sum('credit'),
            'filters' => $filters,
        ]);
    }

    private function dateFilters(Request $request): array
    {
        $validated = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);
        $from = $validated['from'] ?? now()->startOfYear()->toDateString();
        $to = $validated['to'] ?? now()->toDateString();

        return ['from' => $from, 'to' => $to];
    }

    private function signedBalance(?string $type, float $debit, float $credit): float
    {
        return in_array($type, ['asset', 'expense'], true) ? $debit - $credit : $credit - $debit;
    }
}