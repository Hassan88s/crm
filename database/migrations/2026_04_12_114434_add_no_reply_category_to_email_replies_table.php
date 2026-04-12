<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::statement("ALTER TABLE email_replies MODIFY COLUMN category ENUM('Interested','Not Interested','Info Request','Out of Office','Spam','Negative','Manual Review','No Reply') DEFAULT 'Manual Review'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE email_replies MODIFY COLUMN category ENUM('Interested','Not Interested','Info Request','Out of Office','Spam','Negative','Manual Review') DEFAULT 'Manual Review'");
    }
};
