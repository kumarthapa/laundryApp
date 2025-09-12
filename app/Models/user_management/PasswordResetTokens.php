<?php

namespace App\Models\user_management;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PasswordResetTokens extends Model
{
  use HasFactory;
  protected  $table = "password_reset_tokens";
  protected $fillable = [
    'email',
    'token',
    'created_at',
    'expiry_at',
    'otp',
    'username',
    'mobile'
  ];
}
