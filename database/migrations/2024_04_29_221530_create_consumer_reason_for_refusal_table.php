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
        Schema::create('consumer_reason_for_refusal', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('consumer_id')->unsigned();
            $table->foreign('consumer_id')->references('id')->on('consumers')->onUpdate('cascade')->onDelete('cascade');
            $table->bigInteger('refused_reason_id')->unsigned();
            $table->foreign('refused_reason_id')->references('id')->on('refused_reasons')->onUpdate('cascade')->onDelete('cascade');
            $table->string('other_refused_reason')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('consumer_reason_for_refusal');
    }
};
