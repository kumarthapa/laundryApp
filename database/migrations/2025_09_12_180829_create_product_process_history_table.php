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
        Schema::create('product_process_history', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('product_id')->index('idx_product_id');
            $table->enum('stage', ['Bonding', 'Tapedge', 'Zip Cover', 'QC', 'Packing', 'Ready for Shipment', 'Shipped', 'Returned', 'Cancelled']);
            $table->enum('status', ['PASS', 'FAILED', 'PENDING'])->nullable()->default('PENDING');
            $table->string('machine_no', 50)->nullable();
            $table->timestamp('changed_at')->useCurrent();
            $table->unsignedBigInteger('changed_by')->nullable();
            $table->text('comments')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_process_history');
    }
};
