<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('Dashboard/Index', [
            'summary' => [
                'transactions' => 0,
                'revenue' => 0,
                'expenses' => 0,
                'netProfit' => 0,
            ],
        ]);
    }
}