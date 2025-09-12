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
        Schema::create('grants_permissions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('role_id');
            $table->text('permission_id')->nullable();
            $table->string('module_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('grants_permissions');
    }
};
