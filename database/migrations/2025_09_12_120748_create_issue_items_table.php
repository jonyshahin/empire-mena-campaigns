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
        Schema::create('issue_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('issue_id')->constrained('issues')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->decimal('quantity', 18, 6);
            $table->string('uom')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->index(['issue_id', 'product_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('issue_items');
    }
};
