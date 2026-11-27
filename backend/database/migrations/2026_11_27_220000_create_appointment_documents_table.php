<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointment_documents', function (Blueprint $table) {
            $table->foreignId('appointment_id')->primary()->constrained()->cascadeOnDelete();
            $table->string('extension', 4);
            $table->longText('contents');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointment_documents');
    }
};
