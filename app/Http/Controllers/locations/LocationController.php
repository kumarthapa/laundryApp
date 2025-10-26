<?php

namespace App\Http\Controllers\locations;

use App\Helpers\TableHelper;
use App\Helpers\UtilityHelper;
use App\Http\Controllers\Controller;
use App\Models\location\Location;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class LocationController extends Controller
{
    protected $locationModel;

    public function __construct()
    {
        $this->locationModel = new Location;
    }

    /* Index - list page view */
    public function index(Request $request)
    {
        $headers = [
            ['location_name' => 'Name'],
            ['location_code' => 'Code'],
            ['address' => 'Address'],
            ['city' => 'City'],
            ['pincode' => 'Pincode'],
            ['state' => 'State'],
            ['status' => 'Status'],
            ['actions' => 'Actions'],
        ];

        $pageConfigs = ['pageHeader' => true, 'isFabButton' => true];
        $currentUrl = $request->url();
        $UtilityHelper = new UtilityHelper;
        $createPermissions = $UtilityHelper::CheckModulePermissions('locations', 'locations.create');
        // print_r($createPermissions);
        // exit;
        // Get table headers
        $table_headers = TableHelper::get_manage_table_headers($headers, true, false, true, true, true);
        $overview = $this->locationModel->getLocationOverview();

        return view('content.locations.list')
            ->with('pageConfigs', $pageConfigs)
            ->with('table_headers', $table_headers)
            ->with('currentUrl', $currentUrl)
            ->with('createPermissions', $createPermissions)
            ->with('locationsOverview', $overview);
    }

    /* Build one row for datatable */
    protected function tableHeaderRowData($row)
    {

        $data = [];
        $isEdit = UtilityHelper::CheckModulePermissions('locations', 'locations.edit');

        $nameHtml = '<div class="d-flex flex-column"><span class="fw-semibold">'.$row->location_name.'</span></div>';

        $statusHTML = ($row->status == 1) ? '<span class="badge rounded bg-label-success">Active</span>' : '<span class="badge rounded bg-label-secondary">Inactive</span>';

        $edit = route('locations.edit', ['id' => $row->location_id]);
        $view = route('locations.view', ['id' => $row->location_id]);

        $actions = '';
        if ($isEdit) {
            $actions .= '<div class="d-flex justify-content-end"><a href="'.$edit.'"  title="Edit Location"><div class="avatar flex-shrink-0 me-2">
              <span class="avatar-initial rounded bg-label-primary">
              <i class="icon-base bx bxs-edit icon-lg"></i></span>
            </div></a>';

            // $actions .= '<a href="'.$edit.'" class="btn btn-sm text-primary btn-icon item-edit" title="Edit Location"><i class="bx bxs-edit"></i></a> ';
        } else {
            $actions .= '<a href="javascript:;" disabled title="No Permission"><div class="avatar flex-shrink-0 me-2">
              <span class="avatar-initial rounded bg-label-primary">
              <i class="icon-base bx bx-cube icon-lg"></i></span>
            </div></a>';
            // $actions .= '<a href="javascript:;" disabled class="btn btn-sm text-secondary btn-icon item-edit" title="No Permission"><i class="bx bxs-edit"></i></a> ';
        }

        $actions .= '<a href="javascript:;" onclick="viewRowDetails(\''.$view.'\');" title="View Location"><div class="avatar flex-shrink-0 me-2">
              <span class="avatar-initial rounded bg-label-primary">
              <i class="icon-base bx bx-show-alt icon-lg"></i></span>
            </div></a></div>';

        // $actions .= '<a href="javascript:;" onclick="viewRowDetails(\''.$view.'\');" class="btn btn-sm btn-outline-primary" title="View"><i class="bx bx-show-alt"></i></a>';

        $data['id'] = $row->location_id;
        $data['checkbox'] = '<div class="form-check"><input type="checkbox" class="row-checkbox form-check-input" data-id="'.e($row->location_id).'"></div>';
        $data['location_name'] = $nameHtml;
        $data['location_code'] = $row->location_code ?? '-';
        $data['address'] = $row->address ?? '-';
        $data['city'] = $row->city ?? '-';
        $data['pincode'] = $row->pincode ?? '-';
        $data['state'] = $row->state ?? '-';
        $data['status'] = $statusHTML;
        $data['actions'] = $actions;

        return $data;
    }

    /* AJAX list rows */
    public function list(Request $request)
    {

        $search = $request->get('search') ?? '';
        // For now we use the model search which returns collection
        $searchData = $this->locationModel->search($search);
        $total_rows = $this->locationModel->get_found_rows($search);

        $data_rows = [];
        foreach ($searchData as $row) {
            $data_rows[] = $this->tableHeaderRowData($row);
        }
        // print_r($data_rows);
        // exit;
        $response = [
            'data' => $data_rows,
            'recordsTotal' => $total_rows,
            'recordsFiltered' => $total_rows,
        ];

        return response()->json($response);
    }

    /* Save (create / update) */
    public function save(Request $request, $id = '')
    {
        $rules = [
            'location_name' => 'required|string|max:255',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'pincode' => 'nullable|string|max:20',
            'state' => 'nullable|string|max:100',
            'status' => 'nullable|in:0,1',
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()->all()], 422);
        }

        $input = $request->only(['location_name', 'address', 'city', 'pincode', 'state', 'status']);
        $input['status'] = isset($input['status']) ? (int) $input['status'] : 1;

        DB::beginTransaction();
        try {
            $authUser = Auth::user();
            $now = now();

            if (! $id) {
                // create
                $createData = [
                    'location_name' => $input['location_name'],
                    'address' => $input['address'] ?? null,
                    'city' => $input['city'] ?? null,
                    'pincode' => $input['pincode'] ?? null,
                    'state' => $input['state'] ?? null,
                    'status' => $input['status'],
                    'created_at' => $now,
                ];

                // Generate location_code: first 3 letters of name (no spaces), uppercase, + id + random str
                $createData['location_code'] = $this->getLocationPrefix($input['location_name']);

                // print_r($createData);
                // exit;
                $location = Location::create($createData);

                if (! $location) {
                    DB::rollBack();

                    return response()->json(['success' => false, 'message' => 'Form submission failed']);
                }

                $random = substr(Str::random(8), 0, 6); // alphanumeric random
                $location_code = $location->location_code.$location->location_id.$random;
                $location->location_code = $location_code;
                $location->save();

                $savedModel = $location;
                $action = 'Create';
            } else {
                // update
                $location = Location::findOrFail($id);
                $updateData = [
                    'location_name' => $input['location_name'],
                    'address' => $input['address'] ?? null,
                    'city' => $input['city'] ?? null,
                    'pincode' => $input['pincode'] ?? null,
                    'state' => $input['state'] ?? null,
                    'status' => $input['status'],
                    'updated_at' => $now,
                ];

                $location->update($updateData);
                $savedModel = $location;
                $action = 'Edit';
            }

            // Log activity if you have UserActivityLog helper (mirrors Users controller)
            if (method_exists($this, 'UserActivityLog')) {
                $this->UserActivityLog($request, [
                    'module' => 'locations',
                    'activity_type' => strtolower($action),
                    'message' => "{$action} location : {$savedModel->location_name}",
                    'application' => 'web',
                    'data' => [
                        'location_name' => $savedModel->location_name,
                        'location_code' => $savedModel->location_code ?? '',
                    ],
                ]);
            }

            DB::commit();

            return response()->json(['success' => true, 'message' => 'Form submitted successfully']);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'bg_color' => 'bg-danger',
            ], 500);
        }
    }

    /* Create form */
    public function create(Request $request, $id = '')
    {
        $data = [];
        if ($id) {
            $info = Location::find($id);
            if (! $info) {
                return view('content.common.no-data-found', ['message' => 'Location Not Found!']);
            }
            $data['info'] = $info;
            $data['location_id'] = $info->location_id;
        }

        return view('content.locations.create', $data);
    }

    /* Edit alias (you used edit earlier) */
    public function edit(Request $request, $id = '')
    {
        return $this->create($request, $id);
    }

    /* View row details (JSON) */
    public function view(Request $request, $id = '')
    {
        $result = [];
        if ($id) {
            $info = Location::find($id);
            $result['data'] = $info;
        }

        return response()->json($result);
    }

    /* Delete (soft or hard as needed) */
    public function delete(Request $request, $id = null)
    {
        $inputIds = $request->input('ids', null);
        $singleId = $id ?: $request->input('id', null);

        if (is_array($inputIds) && ! empty($inputIds)) {
            $ids = array_values(array_filter($inputIds, fn ($v) => ! is_null($v) && $v !== ''));
        } elseif ($singleId) {
            $ids = [$singleId];
        } else {
            return response()->json(['success' => false, 'message' => 'No id(s) provided.'], 422);
        }

        DB::beginTransaction();
        try {
            $models = Location::whereIn('location_id', $ids)->get();
            if ($models->isEmpty()) {
                return response()->json(['success' => false, 'message' => 'No matching location(s) found.'], 404);
            }

            $deletedCount = 0;
            foreach ($models as $model) {
                $model->delete();
                $deletedCount++;
            }
            DB::commit();

            return response()->json(['success' => true, 'message' => "{$deletedCount} location(s) deleted."]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json(['success' => false, 'message' => 'Delete failed: '.$e->getMessage()], 500);
        }
    }

    public function getLocationPrefix($productName)
    {

        // echo getLocationPrefix("Lux pro");        // LUP
        // echo getLocationPrefix("Lux pro Demon"); // LPD
        // echo getLocationPrefix("Lux");           // LUX
        // echo getLocationPrefix("Luxuman");       // LUX

        // split and keep only alphabetic characters in each word
        $rawWords = preg_split('/\s+/', trim($productName));
        $words = [];
        foreach ($rawWords as $w) {
            $clean = preg_replace('/[^A-Za-z]/', '', $w);
            if ($clean !== '') {
                $words[] = $clean;
            }
        }

        if (empty($words)) {
            return 'N/A';
        }

        $count = count($words);
        $model = '';

        if ($count === 1) {
            // single word -> first 3 letters
            $model = mb_strtoupper(mb_substr($words[0], 0, 3));
        } elseif ($count === 2) {
            $first = $words[0];
            $second = $words[1];
            if (mb_strlen($first) >= 2) {
                $model = mb_strtoupper(mb_substr($first, 0, 2).mb_substr($second, 0, 1));
            } else {
                // first word only 1 char -> take 1 from first + 2 from second
                $model = mb_strtoupper(mb_substr($first, 0, 1).mb_substr($second, 0, 2));
            }
        } else {
            // 3 or more words -> first letter of each of first 3 words
            for ($i = 0; $i < 3; $i++) {
                $model .= mb_strtoupper(mb_substr($words[$i], 0, 1));
            }
        }

        // ensure exactly 3 chars (pad with 'X' if very short)
        if (mb_strlen($model) < 3) {
            $model = str_pad($model, 3, 'X');
        } elseif (mb_strlen($model) > 3) {
            $model = mb_substr($model, 0, 3);
        }

        return $model;
    }
}
