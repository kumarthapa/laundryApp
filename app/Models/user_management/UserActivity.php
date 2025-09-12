<?php

namespace App\Models\user_management;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;




class UserActivity extends Model
{
  use HasFactory;
  protected $primaryKey = 'id';
  protected $table = 'user_activity';

  protected $fillable = [
    'usercode',
    'datetime',
    'module',
    'activity_type',
    'message',
    'application',
    'user_agent',
    'device',
    'data',
    'header',
    'ip_address',
  ];
  public $timestamps = true;
}