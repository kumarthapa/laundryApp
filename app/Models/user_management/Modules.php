<?php

namespace App\Models\user_management;

use Illuminate\Database\Eloquent\Model;
use App\Models\user_management\UsersModel;
use Illuminate\Support\Facades\Schema;
use Config\Services;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use App\Helpers\UserHelper;

class Modules extends Model
{
    protected  $table = "modules";
    protected $primaryKey = 'id';
    protected $fillable = ['module_id', 'slug', 'icon', 'is_active', 'is_menu', 'parent_module_id'];
}