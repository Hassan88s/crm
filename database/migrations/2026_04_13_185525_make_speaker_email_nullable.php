<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Drop unique index first, then make nullable with a new unique index that allows nulls
        DB::statement('ALTER TABLE speakers DROP INDEX speakers_email_unique');
        DB::statement('ALTER TABLE speakers MODIFY email VARCHAR(255) NULL');
        DB::statement('CREATE UNIQUE INDEX speakers_email_unique ON speakers(email)');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE speakers MODIFY email VARCHAR(255) NOT NULL');
    }
};
