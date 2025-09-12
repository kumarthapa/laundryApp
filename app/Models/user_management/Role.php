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

class Role extends Model
{
	/* get rows from grants table as per role_id */
	protected  $table = "roles";
	protected $primaryKey = 'role_id';
	public $timestamps = false;
	protected $fillable = ['role_name', 'role_code', 'status', 'created_at', 'updated_at', 'deleted'];

	/*
	Performs a search on Roles
	*/
	public function search($search = '', $filters = [], $limit_from = 0, $rows = 0, $sort = "id", $order = 'desc')
	{
		$query = DB::table($this->table)
			->select('*')
			->where(function ($q) use ($search) {
				$q->where('role_name', 'like', "%$search%")
					->orWhere('role_code', 'like', "%$search%");
			});

		// print_r($order); exit;
		// if ($sort) {
		// 	$query->orderBy($sort, $order);
		// } else {
		// 	$query->orderBy('id', 'desc');
		// }
		// $query->groupBy('id');

		// if ($rows > 0) {
		// 	$query->limit($rows)->offset($limit_from);
		// }
		//print_r($query->toSql()); exit;
		return $query->get();
	}

	/*
		Gets row count
		*/
	public function get_found_rows($search)
	{
		return $this->search($search)->count();
	}
}
