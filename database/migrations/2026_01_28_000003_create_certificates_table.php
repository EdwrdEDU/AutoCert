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
        Schema::create('certificates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('template_id')->constrained()->onDelete('cascade');
            $table->json('recipient_data'); // Store all field values
            $table->string('generated_pdf_path')->nullable();
            $table->string('status', 20)->default('pending'); // pending, generated, failed
            $table->timestamp('generated_at')->nullable();
            $table->timestamps();
            
            $table->index(['template_id', 'status']);
            $table->index('generated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('certificates');
    }
};
