<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (
            Schema::hasColumn('users', 'user_id')
            && DB::table('users')
                ->whereNotNull('user_id')
                ->whereRaw("TRIM(user_id) <> ''")
                ->exists()
        ) {
            throw new \RuntimeException(
                'users.user_id contains data. Preserve it before removing the legacy column.'
            );
        }

        if (
            Schema::hasColumn('users', 'avatar')
            && Schema::hasColumn('users', 'profile_photo_url')
        ) {
            $hasConflictingAvatar = DB::table('users')
                ->whereNotNull('avatar')
                ->whereRaw("TRIM(avatar) <> ''")
                ->whereNotNull('profile_photo_url')
                ->whereRaw("TRIM(profile_photo_url) <> ''")
                ->whereColumn('avatar', '<>', 'profile_photo_url')
                ->exists();

            if ($hasConflictingAvatar) {
                throw new \RuntimeException(
                    'users.avatar conflicts with profile_photo_url. Resolve those values before removing the legacy column.'
                );
            }

            DB::table('users')
                ->whereNotNull('avatar')
                ->whereRaw("TRIM(avatar) <> ''")
                ->where(function ($query): void {
                    $query->whereNull('profile_photo_url')
                        ->orWhere('profile_photo_url', '');
                })
                ->update(['profile_photo_url' => DB::raw('avatar')]);
        }

        $columns = array_values(array_filter(
            ['user_id', 'avatar'],
            static fn (string $column): bool => Schema::hasColumn('users', $column),
        ));

        if ($columns !== []) {
            Schema::table('users', function (Blueprint $table) use ($columns): void {
                $table->dropColumn($columns);
            });
        }
    }

    public function down(): void
    {
        $restoreUserId = ! Schema::hasColumn('users', 'user_id');
        $restoreAvatar = ! Schema::hasColumn('users', 'avatar');

        if (! $restoreUserId && ! $restoreAvatar) {
            return;
        }

        Schema::table('users', function (Blueprint $table) use ($restoreUserId, $restoreAvatar): void {
            if ($restoreUserId) {
                $table->string('user_id')->nullable();
            }

            if ($restoreAvatar) {
                $table->string('avatar')->nullable();
            }
        });
    }
};
