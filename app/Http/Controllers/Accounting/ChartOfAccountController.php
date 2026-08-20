<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\ChartOfAccount;
use Inertia\Inertia;
use Inertia\Response;

class ChartOfAccountController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Accounting/ChartOfAccounts/Index', [
            'accounts' => ChartOfAccount::query()
                ->with('parent:id,code,name')
                ->orderBy('code')
                ->get(),
        ]);
    }
}