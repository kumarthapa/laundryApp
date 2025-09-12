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
        Schema::create('user_activity', function (Blueprint $table) {
            $table->increments('id');
            $table->string('usercode', 25);
            $table->timestamp('datetime')->useCurrentOnUpdate()->useCurrent();
            $table->string('module', 25)->nullable();
            $table->string('activity_type', 25)->nullable();
            $table->string('message')->nullable();
            $table->string('application', 50)->nullable();
            $table->string('ip_address', 50)->nullable();
            $table->text('location')->nullable();
            $table->text('header')->nullable();
            $table->text('user_agent')->nullable();
            $table->text('device')->nullable();
            $table->text('data')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_activity');
    }
};
