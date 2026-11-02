<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('waste_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('waste_type');
            $table->date('collection_date');
            $table->string('collection_time', 5)->nullable();   // HH:MM (24h)
            $table->string('area')->nullable();                 // blank = whole barangay
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('collection_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('waste_schedules');
    }
};