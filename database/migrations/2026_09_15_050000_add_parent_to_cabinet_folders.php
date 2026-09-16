<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void { Schema::table('cabinet_folders', function (Blueprint $table) { $table->string('parent_type')->nullable(); $table->string('parent_office')->nullable(); }); }
    public function down(): void { Schema::table('cabinet_folders', fn (Blueprint $table) => $table->dropColumn(['parent_type', 'parent_office'])); }
};
