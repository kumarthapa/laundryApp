<?php

namespace App\Models\user_management;

use App\Helpers\LocaleHelper;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class Role extends Model
{
    /* get rows from grants table as per role_id */
    protected $table = 'roles';

    protected $primaryKey = 'role_id';

    public $timestamps = false;

    protected $fillable = ['role_name', 'role_type', 'role_code', 'status', 'created_at', 'updated_at', 'deleted'];

    /*
    Performs a search on Roles
    */
    public function search($search = '', $filters = [], $limit_from = 0, $rows = 0, $sort = 'id', $order = 'desc')
    {
        $authUser = Auth::user();
        $query = DB::table($this->table)
            ->select('*')
            ->where(function ($q) use ($search) {
                $q->where('role_name', 'like', "%$search%")
                    ->orWhere('role_code', 'like', "%$search%");
            });
        if (! $authUser->is_super_admin) {
            $query->whereNotIn('role_type', ['super_role']);
        }

        //  $query = LocaleHelper::commonWhereLocationCheck($query, 'roles');

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
        // print_r($query->toSql()); exit;
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
