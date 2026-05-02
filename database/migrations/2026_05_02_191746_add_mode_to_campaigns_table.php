<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            // 'ai' = current behavior (web search + agenda PDF + AI rewrite)
            // 'manual' = literal subject/body, no AI, audience can be picked by reply category
            $table->enum('mode', ['ai', 'manual'])->default('ai')->after('name');
        });
    }

    public function down(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropColumn('mode');
        });
    }
};
