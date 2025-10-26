<?php

namespace App\Http\Controllers\device_registration;

use App\Helpers\TableHelper;
use App\Helpers\UtilityHelper;
use App\Http\Controllers\Controller;
use App\Models\device_registration\DeviceRegistration;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class DeviceRegistrationController extends Controller
{
    protected $model;

    public function __construct()
    {
        $this->model = new DeviceRegistration;
    }

    /**
     * Show listing page (blade)
     */
    public function index(Request $request)
    {
        // Ensure expired statuses are marked before rendering
        $this->markExpiredDevices();

        $headers = [
            ['device_id' => 'Device ID'],
            ['serial_number' => 'Serial No'],
            ['license_key' => 'License Key'],
            ['start_date' => 'Start Date'],
            ['end_date' => 'End Date'],
            ['status' => 'Status'],
            ['actions' => 'Actions'],
        ];

        $pageConfigs = ['pageHeader' => true, 'isFabButton' => true];
        $currentUrl = $request->url();
        $createPermissions = 1;
        $table_headers = TableHelper::get_manage_table_headers($headers, true, true, true, true, true);

        return view('content.device_registration.list')
            ->with('pageConfigs', $pageConfigs)
            ->with('table_headers', $table_headers)
            ->with('currentUrl', $currentUrl)
            ->with('createPermissions', $createPermissions);
    }

    /**
     * Build one row for datatable
     */
    protected function tableHeaderRowData($row)
    {
        $data = [];
        $isEdit = 1; // or use UtilityHelper check if needed

        // Compute display status based on DB status and end_date (safety)
        $now = Carbon::now();
        if ($row->end_date && Carbon::parse($row->end_date)->endOfDay()->lessThan($now)) {
            $statusKey = 'EXPIRE';
        } else {
            $statusKey = $row->status ?? 'UNKNOWN';
        }

        $statusHTML = match ($statusKey) {
            'ACTIVE' => '<span class="badge rounded bg-success">Active</span>',
            'INACTIVE' => '<span class="badge rounded bg-secondary">Inactive</span>',
            'EXPIRE' => '<span class="badge rounded bg-danger">Expired</span>',
            default => '<span class="badge rounded bg-warning">Unknown</span>',
        };

        $edit = route('device_registration.edit', ['id' => $row->device_registration_id]);

        $view = route('device_registration.view', ['id' => $row->device_registration_id]);
        $delete = $row->device_registration_id;

        // --- ACTION BUTTONS (Sneat-style with avatars) ---
        $actions = '<div class="d-flex justify-content-end">';
        if ($isEdit) {
            $actions .= '
            <a href="javascript:;" onclick="onDelete('.$delete.');" title="Delete Device">
                <div class="avatar flex-shrink-0 me-2">
                    <span class="avatar-initial rounded bg-label-danger">
                        <i class="icon-base bx bx-trash icon-lg"></i>
                    </span>
                </div>
            </a>
            <a href="'.$edit.'" title="Edit Device">
                <div class="avatar flex-shrink-0 me-2">
                    <span class="avatar-initial rounded bg-label-primary">
                        <i class="icon-base bx bxs-edit icon-lg"></i>
                    </span>
                </div>
            </a>';
        } else {
            $actions .= '<a href="javascript:;" disabled title="No Permission">
                <div class="avatar flex-shrink-0 me-2">
                    <span class="avatar-initial rounded bg-label-secondary">
                        <i class="icon-base bx bx-lock-alt icon-lg"></i>
                    </span>
                </div>
            </a>';
        }

        $actions .= '<a href="javascript:;" onclick="viewRowDetails(\''.$view.'\');" title="View Device">
            <div class="avatar flex-shrink-0 me-2">
                <span class="avatar-initial rounded bg-label-dark">
                    <i class="icon-base bx bx-show-alt icon-lg"></i>
                </span>
            </div>
        </a></div>';

        // --- BUILD TABLE DATA ---
        $data['device_id'] = e($row->device_id ?? '-');
        $data['serial_number'] = e($row->serial_number ?? '-');
        $data['license_key'] = e($row->license_key ?? '-');
        $data['start_date'] = $row->start_date ? date('Y-m-d', strtotime($row->start_date)) : '-';
        $data['end_date'] = $row->end_date ? date('Y-m-d', strtotime($row->end_date)) : '-';
        $data['status'] = $statusHTML;
        $data['actions'] = $actions;

        return $data;
    }

    /**
     * AJAX list rows
     * Expects POST (CSRF token from front-end)
     */
    public function list(Request $request)
    {
        // Update expired statuses before fetching data
        $this->markExpiredDevices();

        $search = $request->get('search') ?? '';
        $filters = [];

        if ($request->filled('status')) {
            $filters['status'] = $request->get('status');
        }

        $rows = $this->model->search($search, $filters);
        $total_rows = $this->model->get_found_rows($search);

        $data_rows = [];
        foreach ($rows as $row) {
            $data_rows[] = $this->tableHeaderRowData($row);
        }

        return response()->json([
            'data' => $data_rows,
            'recordsTotal' => $total_rows,
            'recordsFiltered' => $total_rows,
        ]);
    }

    /**
     * Save (create / update) device registration
     */
    public function save(Request $request, $id = '')
    {
        // Validation rules
        if ($id) {
            // update: ensure unique device_id ignoring current row
            $deviceIdRule = 'required|string|max:150|unique:device_registrations,device_id,'.$id.',device_registration_id';
        } else {
            // create
            $deviceIdRule = 'required|string|max:150|unique:device_registrations,device_id';
        }

        $rules = [
            'device_id' => $deviceIdRule,
            'serial_number' => 'nullable|string|max:150',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'status' => 'nullable|in:ACTIVE,INACTIVE,EXPIRE',
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()->all()], 422);
        }

        $input = $request->only(['device_id', 'serial_number', 'start_date', 'end_date', 'status']);

        DB::beginTransaction();
        try {
            $now = Carbon::now();

            // Compute status from dates (priority: EXPIRE if end_date < now)
            $computedStatus = $this->computeStatusFromDates($input['start_date'] ?? null, $input['end_date'] ?? null, $input['status'] ?? null);
            // print_r($computedStatus);
            // exit;
            if (! $id) {
                // CREATE: generate license_key and save
                $licenseKey = $this->generateLicenseKey($input['device_id']);

                $createData = [
                    'device_id' => $input['device_id'],
                    'serial_number' => $input['serial_number'] ?? null,
                    'license_key' => $licenseKey,
                    'status' => $computedStatus,
                    'start_date' => $input['start_date'] ?? null,
                    'end_date' => $input['end_date'] ?? null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];

                $model = DeviceRegistration::create($createData);
                if (! $model) {
                    DB::rollBack();

                    return response()->json(['success' => false, 'message' => 'Create failed']);
                }

                $action = 'Create';
                $saved = $model;
            } else {
                // UPDATE: do not overwrite license_key
                $model = DeviceRegistration::findOrFail($id);

                $updateData = [
                    'device_id' => $input['device_id'],
                    'serial_number' => $input['serial_number'] ?? null,
                    'status' => $computedStatus,
                    'start_date' => $input['start_date'] ?? null,
                    'end_date' => $input['end_date'] ?? null,
                    'updated_at' => $now,
                ];

                $model->update($updateData);
                $action = 'Edit';
                $saved = $model;
            }

            // optional activity logging
            if (method_exists($this, 'UserActivityLog')) {
                $this->UserActivityLog($request, [
                    'module' => 'device_registration',
                    'activity_type' => strtolower($action),
                    'message' => "{$action} device : ".$saved->device_id,
                    'application' => 'web',
                    'data' => [
                        'device_id' => $saved->device_id,
                        'license_key' => $saved->license_key,
                    ],
                ]);
            }

            DB::commit();

            return response()->json(['success' => true, 'message' => 'Form submitted successfully']);
        } catch (Exception $e) {
            DB::rollBack();

            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Compute appropriate status based on start_date, end_date and optional requested status.
     * Priority:
     *  - If end_date < now => EXPIRE
     *  - Else if start_date > now => INACTIVE
     *  - Else => ACTIVE (or provided status if no dates given)
     */
    protected function computeStatusFromDates(?string $startDate, ?string $endDate, ?string $requestedStatus): string
    {
        $now = Carbon::now();

        if ($endDate) {
            try {
                $end = Carbon::parse($endDate)->endOfDay();
                if ($end->lessThan($now)) {
                    return 'EXPIRE';
                } else {
                    return 'ACTIVE';
                }
            } catch (Exception $e) {
                // ignore parse error, fallthrough
            }
        }

        // if ($startDate) {
        //     try {
        //         $start = Carbon::parse($startDate)->startOfDay();
        //         if ($start->greaterThan($now)) {
        //             return 'INACTIVE';
        //         }
        //     } catch (Exception $e) {
        //         // ignore parse error, fallthrough
        //     }
        // }

        // If no dates or dates indicate active period, respect requested status if provided and valid, else ACTIVE
        $valid = in_array($requestedStatus, ['ACTIVE', 'INACTIVE', 'EXPIRE']);

        return $valid ? $requestedStatus : 'ACTIVE';
    }

    /**
     * Mark devices EXPIRE where end_date is before today and status is not already EXPIRE.
     * Runs an efficient single update query.
     *
     * @return int Number of rows updated
     */
    protected function markExpiredDevices(): int
    {
        $today = Carbon::now()->startOfDay()->toDateString();

        $updated = DB::table('device_registrations')
            ->whereNotNull('end_date')
            ->whereDate('end_date', '<', $today)
            ->where('status', '!=', 'EXPIRE')
            ->update([
                'status' => 'EXPIRE',
                'updated_at' => Carbon::now(),
            ]);

        return $updated;
    }

    /**
     * Generate a license key.
     * Format: PREFIX-TIMESTAMP-RAND1-RAND2-RAND3 (e.g. MR43-KF12J-9FA2-3B6D-7E1C)
     *
     * - PREFIX: first 4 alphanumeric chars from deviceId (uppercased) or "DEV"
     * - TIMESTAMP: current time encoded in base36 (compact)
     * - RAND blocks: 3 x 4-hex-char blocks from secure random bytes
     *
     * @throws \Exception
     */
    protected function generateLicenseKey(string $deviceId): string
    {
        // 1. Sanitize and extract a short prefix from deviceId (first 4 alphanumeric chars)
        $prefix = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $deviceId), 0, 4)) ?: 'DEV';

        // 2. Encode current timestamp in base36 for compactness
        $timestampBlock = strtoupper(base_convert(time(), 10, 36));

        // 3. Generate 3 random blocks (4 hex chars each) for uniqueness
        $rand1 = strtoupper(bin2hex(random_bytes(2))); // 4 hex chars
        $rand2 = strtoupper(bin2hex(random_bytes(2))); // 4 hex chars
        $rand3 = strtoupper(bin2hex(random_bytes(2))); // 4 hex chars

        // 4. Combine all parts with dashes
        $licenseKey = "{$prefix}-{$timestampBlock}-{$rand1}-{$rand2}-{$rand3}";

        // Ensure uniqueness (quick check) — try a few times if needed
        $attempt = 0;
        while (DeviceRegistration::where('license_key', $licenseKey)->exists() && $attempt < 5) {
            $attempt++;
            $rand1 = strtoupper(bin2hex(random_bytes(2)));
            $rand2 = strtoupper(bin2hex(random_bytes(2)));
            $rand3 = strtoupper(bin2hex(random_bytes(2)));
            $timestampBlock = strtoupper(base_convert(time(), 10, 36));
            $licenseKey = "{$prefix}-{$timestampBlock}-{$rand1}-{$rand2}-{$rand3}";
        }

        if (DeviceRegistration::where('license_key', $licenseKey)->exists()) {
            // very unlikely — fallback to a longer random string
            $licenseKey = $prefix.'-'.strtoupper(bin2hex(random_bytes(8)));
        }

        return $licenseKey;
    }

    /**
     * Render create/edit form
     */
    public function create(Request $request, $id = '')
    {
        $data = [];
        if ($id) {
            $info = DeviceRegistration::find($id);
            if (! $info) {
                return view('content.common.no-data-found', ['message' => 'Device Not Found!']);
            }
            $data['info'] = $info;
            $data['device_registration_id'] = $info->device_registration_id;
        }

        return view('content.device_registration.create', $data);
    }

    /**
     * Edit simply reuses create form
     */
    public function edit(Request $request, $id = '')
    {
        return $this->create($request, $id);
    }

    /**
     * View single device (AJAX)
     */
    public function view(Request $request, $id = '')
    {
        $result = [];
        if ($id) {
            $info = DeviceRegistration::find($id);
            $result['data'] = $info;
        }

        return response()->json($result);
    }

    /**
     * Delete single or multiple devices
     */
    public function delete(Request $request, $id = null)
    {

        if (! $id) {
            return response()->json(['success' => false, 'message' => 'Delete Failed!!'], 422);
        }

        DB::beginTransaction();
        try {
            $models = DeviceRegistration::find($id);
            if (! $models) {
                return response()->json(['success' => false, 'message' => 'Delete Failed']);
            }

            $models->delete();

            DB::commit();

            return response()->json(['success' => true, 'message' => $models->license_key.' Deleted Successfully.']);
        } catch (Exception $e) {
            DB::rollBack();

            return response()->json(['success' => false, 'message' => 'Delete failed: '.$e->getMessage()], 500);
        }
    }
}
