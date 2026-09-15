<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('calendars', function (Blueprint $table) {
            $table->renameColumn(
                'reminder_10_minutes_sent_at',
                'reminder_1_hour_sent_at',
            );
        });
    }

    public function down(): void
    {
        Schema::table('calendars', function (Blueprint $table) {
            $table->renameColumn(
                'reminder_1_hour_sent_at',
                'reminder_10_minutes_sent_at',
            );
        });
    }
};
