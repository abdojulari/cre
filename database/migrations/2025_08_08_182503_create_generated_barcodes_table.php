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
        Schema::create('generated_barcodes', function (Blueprint $table) {
            $table->id();
            $table->string('barcode', 20)->unique();
            $table->string('type', 20)->default('digital'); // 'digital' or 'physical'
            $table->string('prefix', 10);
            $table->string('numeric_part', 15);
            $table->timestamp('generated_at');
            $table->timestamps();
            
            $table->index(['prefix', 'type']);
            $table->index('generated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('generated_barcodes');
    }
};
