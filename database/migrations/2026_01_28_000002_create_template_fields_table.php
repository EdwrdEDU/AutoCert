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
        Schema::create('template_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('template_id')->constrained()->onDelete('cascade');
            $table->string('field_name');
            $table->string('field_type', 20)->default('text'); // text, date, auto_id, number
            $table->string('placeholder')->nullable();
            $table->json('position_data')->nullable(); // Store position/formatting info
            $table->boolean('is_required')->default(true);
            $table->string('default_value')->nullable();
            $table->timestamps();
            
            $table->index(['template_id', 'field_name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('template_fields');
    }
};
