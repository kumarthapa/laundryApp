<?php

namespace App\Models\location;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Location extends Model
{
    protected $table = 'locations';

    protected $primaryKey = 'location_id';

    public $timestamps = true;

    protected $fillable = [
        'location_name', 'location_code', 'address', 'city', 'pincode', 'state', 'status',
    ];

    protected $dates = [
        'created_at',
        'updated_at',
    ];

    /*
      Performs a search on Users
      */
    public function search($search = '', $filters = [], $limit_from = 0, $rows = 0, $sort = 'id', $order = 'desc')
    {

        $query = DB::table($this->table)
            ->select('*')
            ->where(function ($q) use ($search) {
                $q->where('location_name', 'like', "%$search%")
                    ->orWhere('location_code', 'like', "%$search%")
                    ->orWhere('address', 'like', "%$search%")
                    ->orWhere('city', 'like', "%$search%");
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
 Get Locations Overview
 */
    public function getLocationOverview()
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

        // Calculate the total number of locations
        $totalLocations = $statusCounts->sum();

        // Return the results
        return [
            'total_active' => $totalActive,
            'total_pending' => $total_pending,
            'total_locations' => $totalLocations,
        ];
    }
}
