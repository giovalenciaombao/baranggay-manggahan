<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE appointments DROP CONSTRAINT IF EXISTS appointments_status_check');
        }

        Schema::table('appointments', function (Blueprint $table) {
            $table->string('status')->default('pending')->change();

            $table->text('admin_note')->nullable()->after('notes');
            $table->date('original_date')->nullable()->after('admin_note');
            $table->string('original_time')->nullable()->after('original_date');
            $table->timestamp('rescheduled_at')->nullable()->after('original_time');
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropColumn(['admin_note', 'original_date', 'original_time', 'rescheduled_at']);
        });
    }
};