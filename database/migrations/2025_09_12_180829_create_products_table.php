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
        Schema::create('products', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('product_name', 150);
            $table->string('sku', 100)->unique('sku');
            $table->string('reference_code', 150)->nullable();
            $table->string('size', 150);
            $table->string('rfid_tag', 150)->unique('rfid_tag');
            $table->integer('quantity')->nullable()->default(0);
            $table->enum('current_stage', ['Bonding', 'Tapedge', 'Zip Cover', 'QC', 'Packing', 'Ready for Shipment', 'Shipped', 'Returned', 'Cancelled'])->nullable()->default('Bonding');
            $table->enum('qc_status', ['PASS', 'FAILED', 'PENDING'])->nullable()->default('PENDING');
            $table->timestamp('qc_confirmed_at')->nullable();
            $table->unsignedBigInteger('qc_status_update_by')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrentOnUpdate()->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
