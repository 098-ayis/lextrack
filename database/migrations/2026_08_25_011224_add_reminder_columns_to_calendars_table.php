<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('calendars', function (Blueprint $table) {
            $table->timestamp('reminder_3_days_sent_at')->nullable();
            $table->timestamp('reminder_1_day_sent_at')->nullable();
            $table->timestamp('reminder_10_minutes_sent_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('calendars', function (Blueprint $table) {
            $table->dropColumn([
                'reminder_3_days_sent_at',
                'reminder_1_day_sent_at',
                'reminder_10_minutes_sent_at',
            ]);
        });
    }
};