<?php 
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table): void {
            $table->timestamp('archived_at')
                ->nullable()
                ->after('status');
        });

        DB::table('documents')
            ->where('status', 'archived')
            ->whereNull('archived_at')
            ->update(['archived_at' => DB::raw('updated_at')]);
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table): void {
            $table->dropColumn('archived_at');
        });
    }
};
