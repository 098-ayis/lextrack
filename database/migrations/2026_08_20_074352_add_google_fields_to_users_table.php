<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'google_id')) {
                $table->string('google_id')->nullable()->unique()->after('email');
            }

            if (!Schema::hasColumn('users', 'provider')) {
                $table->string('provider')->nullable()->after('google_id');
            }

            if (!Schema::hasColumn('users', 'profile_photo_url')) {
                $table->text('profile_photo_url')->nullable()->after('provider');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'google_id')) {
                $table->dropColumn('google_id');
            }

            if (Schema::hasColumn('users', 'provider')) {
                $table->dropColumn('provider');
            }

            if (Schema::hasColumn('users', 'profile_photo_url')) {
                $table->dropColumn('profile_photo_url');
            }
        });
    }
};
