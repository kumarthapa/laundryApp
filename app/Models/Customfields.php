<?php

namespace App\Models\Customfields;

use Illuminate\Database\Eloquent\Model;
use App\Models\user_management\UsersModel;
use App\Models\Module;
use Illuminate\Support\Facades\Schema;

use Config\Services;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use App\Helpers\UserHelper;

class Customfields extends Model
{
	/* get rows from grants table as per role_id */
	protected  $table = "custom_fields";
	protected $primaryKey = 'label_id';
	public $timestamps = false;
	protected $fillable = ['label_code', 'label_name', 'label_type',
    'is_mandatory', 'is_active', 'module_name','user_id', 'option_values', 'default_value'];
}