<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('campaign_recipients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained()->cascadeOnDelete();
            $table->foreignId('speaker_id')->constrained()->cascadeOnDelete();
            $table->enum('status', ['pending','processing','sent','failed','skipped'])->default('pending');
            $table->timestamp('scheduled_at')->nullable();
            $table->string('ai_topic')->nullable();
            $table->text('generated_subject')->nullable();
            $table->longText('generated_body')->nullable();
            $table->foreignId('smtp_account_id')->nullable()->constrained('smtp_accounts')->nullOnDelete();
            $table->text('error')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->unique(['campaign_id', 'speaker_id']);
            $table->index(['status', 'scheduled_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaign_recipients');
    }
};
