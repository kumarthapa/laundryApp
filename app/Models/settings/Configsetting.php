<?php

namespace App\Models\settings;

use Illuminate\Database\Eloquent\Model;
use App\Models\user_management\UsersModel;
use App\Models\Module;
use Illuminate\Support\Facades\Schema;

use Config\Services;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use App\Helpers\UserHelper;

class Configsetting extends Model
{
	/* get rows from grants table as per role_id */
	protected  $table = "config_settings";
	// protected $primaryKey = 'id';
	public $timestamps = false;
	protected $fillable = ['key', 'name', 'value', 'user_id'];
}
