<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('email_logs', function (Blueprint $table) {
            $table->foreignId('smtp_account_id')->nullable()->after('speaker_id')
                ->constrained('smtp_accounts')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('email_logs', function (Blueprint $table) {
            $table->dropForeign(['smtp_account_id']);
            $table->dropColumn('smtp_account_id');
        });
    }
};
