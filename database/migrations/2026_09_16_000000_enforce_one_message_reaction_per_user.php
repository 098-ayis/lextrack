<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Keep the newest reaction for each user/message pair before adding
        // the constraint. Older versions allowed one row per reaction type.
        $duplicateGroups = DB::table('message_reactions')
            ->select('message_id', 'user_id')
            ->groupBy('message_id', 'user_id')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($duplicateGroups as $group) {
            $idsToDelete = DB::table('message_reactions')
                ->where('message_id', $group->message_id)
                ->where('user_id', $group->user_id)
                ->orderByDesc('created_at')
                ->orderByDesc('id')
                ->pluck('id')
                ->skip(1)
                ->values();

            if ($idsToDelete->isNotEmpty()) {
                DB::table('message_reactions')
                    ->whereIn('id', $idsToDelete->all())
                    ->delete();
            }
        }

        Schema::table('message_reactions', function (Blueprint $table): void {
            $table->unique(
                ['message_id', 'user_id'],
                'message_reactions_message_id_user_id_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('message_reactions', function (Blueprint $table): void {
            $table->dropUnique('message_reactions_message_id_user_id_unique');
        });
    }
};
