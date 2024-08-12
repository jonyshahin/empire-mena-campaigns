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
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->string('company_name')->nullable();
            $table->string('contact_person')->nullable();
            $table->string('website')->nullable();
            $table->string('phone')->nullable();
            $table->string('address')->nullable();
            $table->string('hq_map_name')->nullable();
            $table->string('hq_map_url')->nullable();
            $table->string('industry')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};
