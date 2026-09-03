<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\ChartOfAccount;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class ChartOfAccountController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:20', 'unique:chart_of_accounts,code'],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:asset,liability,equity,revenue,expense'],
            'parent_id' => ['nullable', 'integer', 'exists:chart_of_accounts,id'],
            'description' => ['nullable', 'string'],
        ]);

        ChartOfAccount::create($validated + ['is_active' => true]);

        return redirect()->route('accounting.chart-of-accounts')
            ->with('success', 'Akun berhasil ditambahkan.');
    }

    public function index(): Response
    {
        $user = Auth::user();

        return Inertia::render('Accounting/ChartOfAccounts/Index', [
            'accounts' => ChartOfAccount::query()
                ->with('parent:id,code,name')
                ->orderBy('code')
                ->get(),
            'canEdit' => $user->isAccountant(),
        ]);
    }
}