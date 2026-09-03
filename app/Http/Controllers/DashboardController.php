<?php

namespace App\Http\Controllers;

use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(): Response
    {
        $startOfYear = Carbon::now()->startOfYear()->toDateString();
        $today = Carbon::now()->toDateString();
        $postedEntries = JournalEntry::query()
            ->where('status', JournalEntry::STATUS_POSTED)
            ->whereBetween('transaction_date', [$startOfYear, $today]);
        $balances = JournalEntryLine::query()
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_entry_lines.journal_entry_id')
            ->join('chart_of_accounts', 'chart_of_accounts.id', '=', 'journal_entry_lines.chart_of_account_id')
            ->where('journal_entries.status', JournalEntry::STATUS_POSTED)
            ->whereBetween('journal_entries.transaction_date', [$startOfYear, $today])
            ->selectRaw("SUM(CASE WHEN chart_of_accounts.type = 'revenue' THEN journal_entry_lines.credit - journal_entry_lines.debit ELSE 0 END) as revenue")
            ->selectRaw("SUM(CASE WHEN chart_of_accounts.type = 'expense' THEN journal_entry_lines.debit - journal_entry_lines.credit ELSE 0 END) as expenses")
            ->first();

        $revenue = (float) ($balances->revenue ?? 0);
        $expenses = (float) ($balances->expenses ?? 0);

        return Inertia::render('Dashboard/Index', [
            'summary' => [
                'transactions' => $postedEntries->count(),
                'revenue' => $revenue,
                'expenses' => $expenses,
                'netProfit' => $revenue - $expenses,
            ],
        ]);
    }
}