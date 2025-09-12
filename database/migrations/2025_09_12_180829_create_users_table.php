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
        Schema::create('users', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('fullname')->nullable();
            $table->string('email')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
            $table->string('username', 100);
            $table->integer('role_id');
            $table->enum('status', ['Active', 'Pending'])->nullable()->default('Active');
            $table->string('contact', 100)->nullable();
            $table->string('user_code', 100)->unique('user_code');
            $table->string('api_key', 100);
            $table->string('fcm_token')->nullable();
            $table->string('login_token')->nullable();
            $table->string('created_by', 45)->nullable();
            $table->string('updated_by', 45)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
