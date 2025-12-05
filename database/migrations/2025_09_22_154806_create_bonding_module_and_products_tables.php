<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // ----------------------------
        // Create rfid_tags
        // ----------------------------
        Schema::create('rfid_tags', function (Blueprint $table) {
            $table->id();
            $table->string('sku', 150)->nullable();
            $table->string('product_name', 200);
            $table->string('model', 100);
            $table->string('rfid_code', 200);
            $table->string('size', 100);
            $table->integer('date')->default(0);
            $table->integer('month')->default(0);
            $table->integer('year')->default(0);
            $table->string('serial_no', 100)->nullable();
            $table->string('contractor', 100)->nullable();
            $table->string('bonding_name', 150)->nullable();
            $table->integer('quantity')->default(0);
            $table->boolean('is_write')->default(0);
            $table->unsignedBigInteger('write_by')->default(0);
            $table->timestamp('write_date')->nullable();
            $table->string('reference_code', 150)->nullable();
            $table->timestamps();
        });

        // ----------------------------
        // Create products table
        // ----------------------------
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bonding_plan_product_id')
                ->nullable()
                ->constrained('rfid_tags')
                ->nullOnDelete()
                ->cascadeOnUpdate();
            $table->string('product_name', 200);
            $table->string('rfid_tag', 200)->unique();
            $table->string('rfid_code', 200)->unique();
            $table->string('sku', 100)->nullable();
            $table->string('size', 150);
            $table->integer('quantity')->default(0);
            $table->string('reference_code', 150)->nullable();
            $table->timestamp('qc_confirmed_at')->nullable();
            $table->unsignedBigInteger('qc_status_updated_by')->nullable();
            $table->timestamps();
        });

        // ----------------------------
        // Create product_process_history table
        // ----------------------------
        Schema::create('product_process_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')
                ->constrained('products')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();
            $table->string('stages', 150)->nullable();
            $table->string('status', 150)->nullable();
            $table->text('defects_points')->nullable();
            $table->timestamp('changed_at')->useCurrent();
            $table->unsignedBigInteger('changed_by')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();
        });

        // ----------------------------
        // Module and Permissions
        // ----------------------------

        // Get max sort value
        $maxSort = DB::table('modules')->max('sort') ?? 0;

        // Insert bonding module at the end
        DB::table('modules')->insert([
            'module_id' => 'inventory',
            'slug' => 'inventory',
            'icon' => 'bx bx-spreadsheet',
            'is_active' => 1,
            'is_menu' => 1,
            'parent_module_id' => null,
            'sort' => $maxSort + 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Insert permissions
        $permissions = [
            ['permission_name' => 'View Bonding Products', 'permission_id' => 'view.inventory', 'module_id' => 'inventory'],
            ['permission_name' => 'Create Bonding Products', 'permission_id' => 'create.inventory', 'module_id' => 'inventory'],
            ['permission_name' => 'Edit Bonding Products', 'permission_id' => 'edit.inventory', 'module_id' => 'inventory'],
            ['permission_name' => 'Delete Bonding Products', 'permission_id' => 'delete.inventory', 'module_id' => 'inventory'],
            ['permission_name' => 'Write Tags', 'permission_id' => 'write.inventory', 'module_id' => 'inventory'],
            ['permission_name' => 'Lock Tags', 'permission_id' => 'lock.inventory', 'module_id' => 'inventory'],
        ];

        DB::table('permissions')->insert($permissions);
    }

    public function down(): void
    {
        // Delete bonding module and permissions
        DB::table('permissions')->where('module_id', 'inventory')->delete();
        DB::table('modules')->where('module_id', 'inventory')->delete();

        Schema::dropIfExists('product_process_history');
        Schema::dropIfExists('products');
        Schema::dropIfExists('rfid_tags');
    }
};