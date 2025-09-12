<?php

use App\Enums\IssueStatus;
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
        Schema::create('issues', function (Blueprint $table) {
            $table->id();
            $table->string('number')->unique(); // e.g. GIN-2025-0001
            $table->foreignId('warehouse_id')->constrained('warehouses')->cascadeOnDelete();
            $table->date('issue_date')->default(now());
            $table->text('remarks')->nullable();

            $table->unsignedTinyInteger('status')->default(IssueStatus::Draft->value);

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('posted_at')->nullable();
            $table->timestamps();

            $table->index(['warehouse_id', 'status', 'issue_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('issues');
    }
};
