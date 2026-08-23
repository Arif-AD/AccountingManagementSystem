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

    public function incomeStatement(Request $request): Response
    {
        $filters = $this->statementDateFilters($request);
        $accounts = $this->postedAccountBalances($filters['start_date'], $filters['end_date'], ['revenue', 'expense']);
        $revenue = $accounts->where('type', 'revenue')->values();
        $expenses = $accounts->where('type', 'expense')->values();
        $totalRevenue = $revenue->sum('balance');
        $totalExpenses = $expenses->sum('balance');

        return Inertia::render('Accounting/Reports/IncomeStatement', [
            'revenue' => $revenue,
            'expenses' => $expenses,
            'totalRevenue' => $totalRevenue,
            'totalExpenses' => $totalExpenses,
            'netIncome' => $totalRevenue - $totalExpenses,
            'filters' => $filters,
        ]);
    }

    public function balanceSheet(Request $request): Response
    {
        $filters = $this->statementDateFilters($request);
        $accounts = $this->postedAccountBalances(null, $filters['end_date'], ['asset', 'liability', 'equity']);
        $assets = $accounts->where('type', 'asset')->values();
        $liabilities = $accounts->where('type', 'liability')->values();
        $equity = $accounts->where('type', 'equity')->values();
        $incomeAccounts = $this->postedAccountBalances($filters['start_date'], $filters['end_date'], ['revenue', 'expense']);
        $netIncome = $incomeAccounts->where('type', 'revenue')->sum('balance') - $incomeAccounts->where('type', 'expense')->sum('balance');
        $totalAssets = $assets->sum('balance');
        $totalLiabilities = $liabilities->sum('balance');
        $totalEquity = $equity->sum('balance') + $netIncome;

        return Inertia::render('Accounting/Reports/BalanceSheet', [
            'assets' => $assets,
            'liabilities' => $liabilities,
            'equity' => $equity,
            'netIncome' => $netIncome,
            'totalAssets' => $totalAssets,
            'totalLiabilities' => $totalLiabilities,
            'totalEquity' => $totalEquity,
            'balanced' => abs($totalAssets - ($totalLiabilities + $totalEquity)) < 0.005,
            'filters' => $filters,
        ]);
    }

    public function financialPosition(Request $request): Response
    {
        $filters = $this->statementDateFilters($request);
        $accounts = $this->postedAccountBalances(null, $filters['end_date'], ['asset', 'liability', 'equity']);
        $assets = $accounts->where('type', 'asset')->values();
        $liabilities = $accounts->where('type', 'liability')->values();
        $equity = $accounts->where('type', 'equity')->values();
        $incomeAccounts = $this->postedAccountBalances($filters['start_date'], $filters['end_date'], ['revenue', 'expense']);
        $netIncome = $incomeAccounts->where('type', 'revenue')->sum('balance') - $incomeAccounts->where('type', 'expense')->sum('balance');
        $totalAssets = $assets->sum('balance');
        $totalLiabilities = $liabilities->sum('balance');
        $totalEquity = $equity->sum('balance') + $netIncome;

        return Inertia::render('Accounting/Reports/FinancialPosition', [
            'assets' => $assets,
            'liabilities' => $liabilities,
            'equity' => $equity,
            'netIncome' => $netIncome,
            'totalAssets' => $totalAssets,
            'totalLiabilities' => $totalLiabilities,
            'totalEquity' => $totalEquity,
            'balanced' => abs($totalAssets - ($totalLiabilities + $totalEquity)) < 0.005,
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

    private function statementDateFilters(Request $request): array
    {
        $validated = $request->validate([
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ]);

        return [
            'start_date' => $validated['start_date'] ?? now()->startOfYear()->toDateString(),
            'end_date' => $validated['end_date'] ?? now()->toDateString(),
        ];
    }

    private function postedAccountBalances(?string $startDate, string $endDate, array $types)
    {
        $query = JournalEntryLine::query()
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_entry_lines.journal_entry_id')
            ->join('chart_of_accounts', 'chart_of_accounts.id', '=', 'journal_entry_lines.chart_of_account_id')
            ->where('journal_entries.status', JournalEntry::STATUS_POSTED)
            ->where('chart_of_accounts.is_active', true)
            ->whereIn('chart_of_accounts.type', $types)
            ->whereDate('journal_entries.transaction_date', '<=', $endDate)
            ->when($startDate, fn ($query) => $query->whereDate('journal_entries.transaction_date', '>=', $startDate))
            ->groupBy('chart_of_accounts.id', 'chart_of_accounts.code', 'chart_of_accounts.name', 'chart_of_accounts.type')
            ->select('chart_of_accounts.id', 'chart_of_accounts.code', 'chart_of_accounts.name', 'chart_of_accounts.type')
            ->selectRaw('SUM(CASE WHEN chart_of_accounts.type IN (\'asset\', \'expense\') THEN journal_entry_lines.debit - journal_entry_lines.credit ELSE journal_entry_lines.credit - journal_entry_lines.debit END) as balance')
            ->orderBy('chart_of_accounts.code')
            ->get();

        return $query->map(fn ($account) => [
            'id' => $account->id,
            'code' => $account->code,
            'name' => $account->name,
            'type' => $account->type,
            'balance' => (float) $account->balance,
        ])->filter(fn ($account) => abs($account['balance']) >= 0.005)->values();
    }

    private function signedBalance(?string $type, float $debit, float $credit): float
    {
        return in_array($type, ['asset', 'expense'], true) ? $debit - $credit : $credit - $debit;
    }
}