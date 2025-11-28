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
        Schema::create('orders', function (Blueprint $table) {
          $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('hold_id')->nullable()->constrained('holds')->nullOnDelete();
            $table->string('client_id')->nullable();
            $table->unsignedInteger('quantity');
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->enum('status', ['pending_payment','paid','cancelled'])->default('pending_payment');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
