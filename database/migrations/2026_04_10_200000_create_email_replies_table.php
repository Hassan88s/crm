<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('email_replies', function (Blueprint $table) {
            $table->id();
            $table->string('message_id')->unique(); // Email header Message-ID for dedup
            $table->foreignId('speaker_id')->nullable()->constrained()->nullOnDelete();
            $table->string('from_email');
            $table->string('from_name')->nullable();
            $table->string('subject')->nullable();
            $table->longText('body_plain');
            $table->dateTime('received_at');
            $table->enum('category', ['Interested','Not Interested','Info Request','Out of Office','Spam','Negative','Manual Review'])->default('Manual Review');
            $table->unsignedTinyInteger('ai_score')->nullable();
            $table->json('ai_raw')->nullable();
            $table->dateTime('classified_at')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void {
        Schema::dropIfExists('email_replies');
    }
};
