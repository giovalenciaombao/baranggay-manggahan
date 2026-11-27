<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('incident_photos', function (Blueprint $table) {
            $table->foreignId('incident_id')->primary()->constrained()->cascadeOnDelete();
            $table->string('mime_type');
            $table->longText('contents');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('incident_photos');
    }
};
