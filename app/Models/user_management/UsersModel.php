<?php

namespace App\Models\user_management;

use App\Helpers\LocaleHelper;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class UsersModel extends Model
{
    /* get rows from grants table as per role_id */
    protected $table = 'users';

    protected $Module;

    protected $primaryKey = 'id';

    public $timestamps = true;

    protected $Employee;

    protected $fillable = [
        'fullname',
        'username',
        'password',
        'email',
        'contact',
        'role_id',
        'user_code',
        'status',
        'remember_token',
        'api_key',
        'fcm_token',
        'created_by',
        'updated_by',
        'is_super_admin',
        'location_id',
        'device_id',

    ];

    public function activity()
    {
        return $this->hasMany(UserActivity::class, 'usercode', 'user_code');
    }

    /*
      Performs a search on Users
      */
    public function search($search = '', $filters = [], $limit_from = 0, $rows = 0, $sort = 'id', $order = 'desc')
    {
        $authUser = Auth::user();
        $query = DB::table($this->table)
            ->select('*')
            ->where(function ($q) use ($search) {
                $q->where('fullname', 'like', "%$search%")
                    ->orWhere('user_code', 'like', "%$search%")
                    ->orWhere('email', 'like', "%$search%")
                    ->orWhere('username', 'like', "%$search%");
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
        // print_r($query->toSql()); exit;
        if (! $authUser->is_super_admin) {
            // USER LOCATION CHECK
            // $query = LocaleHelper::commonWhereLocationCheck($query, 'users');
            $query->where('is_super_admin', '!=', 1);
        }

        return $query->get();
    }

    /*
          Gets row count
          */
    public function get_found_rows($search)
    {
        return $this->search($search)->count();
    }

    /*
 Get Users Overview
 */
    public function getUserOverview()
    {
        // Aggregate counts for each status
        $statusCounts = DB::table($this->table)
            ->select(DB::raw('COALESCE(NULLIF(status, \'\'), \'No Status\') as status, COUNT(*) as count'))
            ->groupBy('status')
            ->get()
            ->pluck('count', 'status');

        // Adjust keys based on actual status values
        $totalActive = $statusCounts->get('Active', 0);
        $total_pending = $statusCounts->get('Pending', 0);

        // Calculate the total number of users
        $totalUsers = $statusCounts->sum();

        // Return the results
        return [
            'total_active' => $totalActive,
            'total_pending' => $total_pending,
            'total_users' => $totalUsers,
        ];
    }
}
