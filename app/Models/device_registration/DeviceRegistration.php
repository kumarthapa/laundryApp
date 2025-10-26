<?php

namespace App\Models\device_registration;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class DeviceRegistration extends Model
{
    protected $table = 'device_registrations';

    protected $primaryKey = 'device_registration_id';

    public $timestamps = true;

    protected $fillable = [
        'device_id',
        'serial_number',
        'license_key',
        'status',
        'start_date',
        'end_date',
    ];

    protected $dates = [
        'start_date',
        'end_date',
        'created_at',
        'updated_at',
    ];

    /**
     * Basic search used by controller list() - keeps parity with other models' search signature
     */
    public function search($search = '', $filters = [], $limit_from = 0, $rows = 0, $sort = 'device_registration_id', $order = 'desc')
    {

        $query = DB::table($this->table)
            ->select('*')
            ->where(function ($q) use ($search) {
                $q->where('device_id', 'like', "%$search%")
                    ->orWhere('serial_number', 'like', "%$search%")
                    ->orWhere('license_key', 'like', "%$search%");
            });

        // Apply filters (status etc.)
        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Sorting & pagination if required
        if ($sort) {
            $query->orderBy($sort, $order);
        }

        if ($rows > 0) {
            $query->limit($rows)->offset($limit_from);
        }

        return $query->get();
    }

    public function get_found_rows($search = '')
    {
        return $this->search($search)->count();
    }
}
