<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\ChartOfAccount;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Ari Accountant',
            'email' => 'accountant@example.com',
            'password' => 'password',
            'role' => 'accountant',
        ]);

        User::factory()->create([
            'name' => 'Maya Manager',
            'email' => 'manager@example.com',
            'password' => 'password',
            'role' => 'manager',
        ]);

        $accounts = [
            ['code' => '1000', 'name' => 'Assets', 'type' => 'asset'],
            ['code' => '1100', 'name' => 'Cash', 'type' => 'asset', 'parent_id' => '1000'],
            ['code' => '1200', 'name' => 'Accounts Receivable', 'type' => 'asset', 'parent_id' => '1000'],
            ['code' => '2000', 'name' => 'Liabilities', 'type' => 'liability'],
            ['code' => '2100', 'name' => 'Accounts Payable', 'type' => 'liability', 'parent_id' => '2000'],
            ['code' => '3000', 'name' => 'Equity', 'type' => 'equity'],
            ['code' => '3100', 'name' => "Owner's Equity", 'type' => 'equity', 'parent_id' => '3000'],
            ['code' => '4000', 'name' => 'Revenue', 'type' => 'revenue'],
            ['code' => '4100', 'name' => 'Sales Revenue', 'type' => 'revenue', 'parent_id' => '4000'],
            ['code' => '5000', 'name' => 'Expenses', 'type' => 'expense'],
            ['code' => '5100', 'name' => 'Salary Expense', 'type' => 'expense', 'parent_id' => '5000'],
            ['code' => '5200', 'name' => 'Rent Expense', 'type' => 'expense', 'parent_id' => '5000'],
        ];

        foreach ($accounts as $account) {
            $parentCode = $account['parent_id'] ?? null;
            unset($account['parent_id']);

            ChartOfAccount::create([
                ...$account,
                'parent_id' => $parentCode ? ChartOfAccount::where('code', $parentCode)->value('id') : null,
            ]);
        }
    }
}
