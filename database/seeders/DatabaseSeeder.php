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
            'name' => 'Akuntan',
            'email' => 'accountant@example.com',
            'password' => 'password',
            'role' => 'accountant',
        ]);

        User::factory()->create([
            'name' => 'Manager',
            'email' => 'manager@example.com',
            'password' => 'password',
            'role' => 'manager',
        ]);

        $accounts = [
            ['code' => '1100', 'name' => 'Kas', 'type' => 'asset'],
            ['code' => '2100', 'name' => 'Utang Usaha', 'type' => 'liability'],
            ['code' => '3100', 'name' => 'Modal Pemilik', 'type' => 'equity'],
            ['code' => '4100', 'name' => 'Pendapatan Jasa', 'type' => 'revenue'],
            ['code' => '5100', 'name' => 'Beban Gaji', 'type' => 'expense'],
            ['code' => '5200', 'name' => 'Beban Sewa', 'type' => 'expense'],
        ];

        foreach ($accounts as $account) {
            ChartOfAccount::create($account);
        }
    }
}
