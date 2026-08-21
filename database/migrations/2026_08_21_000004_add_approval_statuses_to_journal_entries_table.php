<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE journal_entries MODIFY status ENUM('draft', 'pending', 'approved', 'posted') NOT NULL DEFAULT 'draft'");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE journal_entries MODIFY status ENUM('draft', 'posted') NOT NULL DEFAULT 'draft'");
        }
    }
};