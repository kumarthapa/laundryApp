<?php

namespace App\Models\user_management;

use Illuminate\Database\Eloquent\Model;
use App\Models\user_management\UsersModel;
use App\Models\Module;
use Illuminate\Support\Facades\Schema;

use Config\Services;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use App\Helpers\UserHelper;

class Permission extends Model
{
	/* get rows from grants table as per role_id */
	protected  $table = "permissions";
	protected $primaryKey = 'id';
	public $timestamps = false;
	protected $fillable = ['permission_name', 'permission_id', 'module_id'];
}
