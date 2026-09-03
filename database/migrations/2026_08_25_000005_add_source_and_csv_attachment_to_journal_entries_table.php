<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('journal_entries', function (Blueprint $table) {
            $table->string('source', 20)->default('manual')->after('description');
            $table->string('original_file_name')->nullable()->after('source');
            $table->string('file_path')->nullable()->after('original_file_name');
        });
    }

    public function down(): void
    {
        Schema::table('journal_entries', function (Blueprint $table) {
            $table->dropColumn(['source', 'original_file_name', 'file_path']);
        });
    }
};