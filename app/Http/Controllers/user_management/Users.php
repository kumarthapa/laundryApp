<?php

namespace App\Http\Controllers\user_management;

use App\Helpers\EmailHelper;
use App\Helpers\TableHelper;
use App\Helpers\UtilityHelper;
use App\Http\Controllers\Controller;
use App\Models\location\Location;
use App\Models\User;
use App\Models\user_management\Role;
use App\Models\user_management\UserActivity;
use App\Models\user_management\UsersModel;
use Carbon\Carbon;
// Using necessary Helpers and libraries
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Validator;

class Users extends Controller
{
    protected $users;

    public function __construct()
    {
        $this->users = new UsersModel;
    }

    /* Load data list */
    public function index(Request $request)
    {
        $headers = [
            // array('id' => 'ID'),
            ['fullname' => 'Name'],
            ['username' => 'Username'],
            ['email' => 'Email'],
            ['contact' => 'Contact'],
            ['roles_name' => 'Role'],
            ['location_id' => 'Location'],
            ['status' => 'Status'],
            ['actions' => 'Actions'],
        ];
        $data = [];
        $usersOverview = $this->users->getUserOverview();
        $users_info = UsersModel::select('*')->get();
        $roles_info = Role::select('*')->get();
        $pageConfigs = ['pageHeader' => true, 'isFabButton' => true];
        $currentUrl = $request->url();
        $UtilityHelper = new UtilityHelper;
        // $table_headers =  json_encode($headers);
        $createPermissions = $UtilityHelper::CheckModulePermissions('users', 'create.users');
        $deletePermissions = $UtilityHelper::CheckModulePermissions('users', 'delete.users');
        $table_headers = TableHelper::get_manage_table_headers($headers, true, false, true, true, true);

        // print_r($createPermissions); exit;
        return view('content.users.list')
            ->with('pageConfigs', $pageConfigs)
            ->with('table_headers', $table_headers)
            ->with('currentUrl', $currentUrl)
            ->with('users_info', $users_info)
            ->with('createPermissions', $createPermissions)
            ->with('deletePermissions', $deletePermissions)
            ->with('usersOverview', $usersOverview)
            ->with('roles_info', $roles_info);
    }

    protected function tableHeaderRowData($row)
    {
        $data = [];
        $isEdit = UtilityHelper::CheckModulePermissions('users', 'edit.users');
        // $is_delete = UtilityHelper::CheckModulePermissions('users', 'delete.users');
        $userNameHtml = '-';
        $statusHTML = '-';
        $data['id'] = $row->id;
        $name = ($row->fullname) ? $row->fullname : $row->username;
        $initials = preg_match_all('/\b\w/', $name, $matches) ? $matches[0] : [];
        $initials = (array_shift($initials) ?: '').(array_pop($initials) ?: '');
        $initials = strtoupper($initials);

        $view = route('users.view', ['id' => $row->id]);
        $edit = route('users.edit', ['user_code' => $row->user_code]);
        // $delete = route('users.delete', ["id" => $row->id]);

        // $delete_route = route('users.delete', ['id' => $row->id]);
        $userNameHtml =
          '<div class="d-flex justify-content-start align-items-center">
          <div class="avatar-wrapper">
          <div class="avatar me-2">
          <span class="avatar-initial rounded-circle bg-label-warning">'.$initials.'</span>
          </div>
          </div>
          <div class="d-flex flex-column">
          <a href="javascript:;" onclick="viewRowDetails(\''.$view.'\');">
          <span class="emp_name text-truncate">
          '.$row->fullname.'
          </span></a>
          <small class="emp_post text-truncate text-muted">
          '.$row->email.'
          </small>
          </div>
          </div>';
        // Checkbox column (first cell) with data-id
        $data['checkbox'] = '<div class="form-check"><input type="checkbox" class="row-checkbox form-check-input" data-id="'.e($row->id).'"></div>';

        $data['fullname'] = $userNameHtml;
        $data['username'] = $row->username;
        $data['email'] = $row->email;
        $data['contact'] = $row->contact;
        //   print_r(Role::find($row->role_id)); exit;
        $role_name = '-';
        if ($row->role_id > 0) {
            $role_info = Role::find($row->role_id);
            if (isset($role_info) && $role_info) {
                $role_name = $role_info->role_name;
            }
        }
        $data['roles_name'] = $role_name;
        $data['location_id'] = ($row->location_id) ? Location::find($row->location_id)->location_name : '-';
        if ($row->status == 'Active') {
            $statusHTML = '<span class="badge rounded bg-label-success" title="Active">Active</span>';
        } else {
            $statusHTML = '<span class="badge rounded bg-label-danger" title="Pending">Pending</span>';
        }
        $data['status'] = $statusHTML;

        // $is_edit = ($isEdit) ? '
        // <a href="'.$edit.'" class="btn btn-sm text-primary btn-icon item-edit"><i class="bx bxs-edit"></i></a>' : '';

        if ($isEdit) {
            $edit_html = '<a href="'.$edit.'" class="btn btn-sm text-primary btn-icon item-edit" title="Edit Employee"><i class="bx bxs-edit"></i></a>';
        } else {
            $edit_html = '<a href="javascript:;" disabled class="btn btn-sm text-secondary btn-icon item-edit" title="No Permission"><i class="bx bxs-edit"></i></a>';
        }

        // $is_delete = ($isDelete) ? '
        // <li><a href="javascript:;" onclick="deleteRow(\'' . $delete . '\');" class="dropdown-item text-danger delete-record">Delete</a></li>' : '';

        // ===============    Common action dropdown display add/edit/view/delete  ============= //
        // $delete_button = '';
        // if ($is_delete) {
        //     $delete_button = '<li><a href="javascript:;" onclick="deleteRow(\''.$delete_route.'\');" class="dropdown-item text-danger delete-record"><i class="bx bx-trash"></i> Delete</a></li>';
        // }

        //     $data['actions'] = '<div class="d-inline-block">
        // 	<a href="javascript:;" class="btn btn-sm text-primary btn-icon dropdown-toggle hide-arrow" data-bs-toggle="dropdown"><i class="bx bx-dots-vertical-rounded"></i></a>
        // 	<ul class="dropdown-menu dropdown-menu-end">
        // 	<li><a href="javascript:;" onclick="viewRowDetails(\''.$view.'\');" class="dropdown-item">Details</a></li>
        // 	<li><a href="'.route('users.activity', $row->user_code).'"  class="dropdown-item"><i class="bx bxl-deezer me-1"></i>Activity</a></li>
        // '.$delete_button.'
        // 	<div class="dropdown-divider"></div>
        // 	</ul>
        // 	</div> '.$is_edit.'';
        $data['actions'] = $edit_html;

        return $data;
    }

    /* Returns quotations table data rows. This will be called with AJAX. */
    public function list(Request $request)
    {
        $search = $request->get('search') ?? '';
        $limit = 10;
        $offset = 0;
        $sort = $request->get('sort') ?? 'id';
        $order = $request->get('order') ?? 'desc';
        $filters = [];
        $searchData = $this->users->search($search, $filters, $limit, $offset, $sort, $order);
        $total_rows = $this->users->get_found_rows($search);
        $is_edit = 1; /* check if permission is there to edit */
        // print_r($searchData); exit;
        $data_rows = [];
        foreach ($searchData as $row) {
            $data_rows[] = $this->tableHeaderRowData($row);
        }
        $response = [
            'data' => $data_rows,
            'recordsTotal' => $total_rows,
            'recordsFiltered' => $total_rows,
        ];
        echo json_encode($response);
    }

    public function save(Request $request, $id = '')
    {
        // Validation rules: password required only on create, optional on update
        $rules = [
            'fullName' => 'required|string|max:255',
            'userName' => ['required', 'string', 'max:255', 'regex:/^\S*$/u'], // no spaces allowed
            'userEmail' => ['nullable', 'email', 'max:255'],
            'userPassWord' => $id ? 'nullable|string|min:5' : 'required|string|min:5',
            'user_role_id' => 'nullable|exists:roles,role_id',
            'userContact' => 'nullable|string|max:50',
            'user_location_id' => 'required',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()->all()], 422);
        }

        // Gather inputs
        $input = $request->only(['fullName', 'userName', 'userEmail', 'userContact', 'status', 'user_role_id', 'user_location_id']);
        $input['status'] = $input['status'] ?? 'Active';

        // Prepare data for saving
        $saveData = [
            'fullname' => $input['fullName'],
            'username' => $input['userName'],
            'email' => $input['userEmail'],
            'contact' => $input['userContact'],
            'status' => $input['status'],
        ];

        if (! empty($input['user_role_id'])) {
            $saveData['role_id'] = $input['user_role_id'];
        }
        if (! empty($input['user_location_id'])) {
            $saveData['location_id'] = $input['user_location_id'];
        }

        // Hash password if provided (required on create, optional on update)
        if ($request->filled('userPassWord')) {
            $saveData['password'] = Hash::make($request->input('userPassWord'));
        } elseif (! $id) {
            return response()->json(['success' => false, 'message' => 'Password is required.']);
        }
        // print_r($saveData);
        // exit;
        $now = now();
        if (! $id) {
            $saveData['created_at'] = $now;
            $saveData['remember_token'] = $request->_token;
            $saveData['api_key'] = UtilityHelper::generateUniqueApiKey(60);
            $_code = UtilityHelper::generateCustomCode($input['fullName']);
            $saveData['user_code'] = UtilityHelper::generateRandomString(3, 'USR-'.$_code, true, true, true);
        } else {
            $saveData['updated_at'] = $now;
        }

        DB::beginTransaction();

        try {
            $authUser = Auth::user();

            if (! $id) {
                if (UsersModel::where('username', $saveData['username'])->exists()) {
                    return response()->json(['success' => false, 'message' => 'Username already exists!']);
                }
                if (! empty($saveData['email']) && UsersModel::where('email', $saveData['email'])->exists()) {
                    return response()->json(['success' => false, 'message' => 'Email already exists!']);
                }

                $saveData['created_by'] = $authUser->user_code ?? null;
                $userModel = UsersModel::create($saveData);

                if (! $userModel) {
                    return response()->json(['success' => false, 'message' => 'Form submission failed']);
                }

                // $email_data = [
                //   'type' => 'user_registration',
                //   'name' => $saveData['fullname'] ?? '',
                //   'code' => $saveData['user_code'] ?? '',
                //   'user_email' => $saveData['email'] ?? '',
                //   'username' => $saveData['username'] ?? '',
                //   'password' => $request->input('userPassWord') ?? '',
                //   'role_name' => Role::find($saveData['role_id'])->role_name ?? '',
                //   'date' => $now->toDateTimeString(),
                //   'status' => $saveData['status'] ?? '',
                // ];
                // EmailHelper::sendRegistrationEmail($email_data);
            } else {
                $userModel = UsersModel::findOrFail($id);
                $saveData['updated_by'] = $authUser->user_code ?? null;
                $userModel->update($saveData);
            }

            $userData = [
                'fullname' => $saveData['fullname'] ?? '',
                'username' => $saveData['username'] ?? '',
                'email' => $saveData['email'] ?? '',
                'user_code' => $saveData['user_code'] ?? ($userModel->user_code ?? ''),
            ];
            $action = $id ? 'Edit' : 'Create';

            $this->UserActivityLog(
                $request,
                [
                    'module' => 'users',
                    'activity_type' => $action,
                    'message' => "{$action} user : {$userData['fullname']}",
                    'application' => 'web',
                    'data' => $userData,
                ]
            );

            DB::commit();

            return response()->json(['success' => true, 'message' => 'Form submitted successfully']);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'bg_color' => 'bg-danger',
            ]);
        }
    }

    public function create(Request $request, $id = '')
    {
        $data = [];
        $roles_info = Role::all();
        // print_r($roles_info);
        // exit;
        $locations_info = Location::select('*')->get();
        $data['roles_info'] = $roles_info ?? null;
        $data['locations_info'] = $locations_info ?? null;

        return view('content.users.create', $data);
    }

    public function edit_user(Request $request, $user_code = '')
    {
        $data = [];
        if ($user_code) {
            $info = UsersModel::where('user_code', $user_code)->first();
            if (! $info) {
                return view('content.common.no-data-found', ['message' => 'User Not Found!']);
            }

            $roles_info = Role::all();
            $locations_info = Location::select('*')->get();
            $data['roles_info'] = $roles_info ?? null;
            $data['info'] = $info;
            $data['user_id'] = $info->id;
            $data['locations_info'] = $locations_info ?? null;
            // $data['role_id'] = $info->role_id;
        } else {
            return view('content.common.no-data-found', ['message' => 'User Not Found!']);
        }

        return view('content.users.create', $data);
    }

    public function view(Request $request, $id = '')
    {
        $result = [];
        if ($id) {
            $info = '';
            $info = UsersModel::find($id);
            $info->role_id = Role::find($info->role_id)->role_name;
            $result['data'] = $info;
        }

        return response()->json($result);
    }

    /* ------------------  Delete selected Items ----------------------- */
    public function delete(Request $request, $id = null)
    {
        $inputIds = $request->input('ids', null);
        $singleId = $id ?: $request->input('id', null);

        if (is_array($inputIds) && ! empty($inputIds)) {
            $ids = array_values(array_filter($inputIds, fn ($v) => ! is_null($v) && $v !== ''));
        } elseif ($singleId) {
            $ids = [$singleId];
        } else {
            return response()->json([
                'success' => false,
                'message' => 'No id(s) provided.',
                'bg_color' => 'bg-danger',
            ], 422);
        }

        DB::beginTransaction();
        try {
            $models = UsersModel::whereIn('id', $ids)->get();
            if ($models->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No matching user(s) found.',
                    'bg_color' => 'bg-danger',
                ], 404);
            }

            $deletedCount = 0;
            $skipped = [];

            foreach ($models as $model) {
                // Example: skip admin user
                if ($model->is_super_admin ?? false) {
                    $skipped[] = $model->id;

                    continue;
                }

                $userData = [
                    'fullname' => $model->fullname ?? '',
                    'username' => $model->username ?? '',
                    'user_code' => $model->user_code ?? '',
                ];

                $model->delete();
                $deletedCount++;

                // Log activity per user
                $this->UserActivityLog($request, [
                    'module' => 'users',
                    'activity_type' => 'delete',
                    'message' => 'Deleted user: '.$userData['fullname'],
                    'application' => 'web',
                    'data' => $userData,
                ]);
            }

            DB::commit();

            $message = $deletedCount.' user(s) deleted successfully.';
            if (! empty($skipped)) {
                $message .= ' Skipped '.count($skipped).' user(s): ['.implode(',', $skipped).'].';
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'bg_color' => 'bg-success',
                'deleted' => $deletedCount,
                'skipped' => $skipped,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Delete failed: '.$e->getMessage(),
                'bg_color' => 'bg-danger',
            ], 500);
        }
    }

    /* ------------------  User Profile info ----------------------- */
    public function profile($user_code = '')
    {
        $data = [];
        $user_info = UtilityHelper::getLoginUserInfo();
        $data['user_info'] = $user_info;

        $role_info = UtilityHelper::getUserRoleInfo();
        $data['role_info'] = $role_info;

        return view('content.user-profile.profile', $data);
    }

    /* ------------------  Get changePassword ----------------------- */
    public function changePassword(Request $request)
    {
        Log::info($request->all());
        $user = User::where('email', $request->post('email'))->first();
        if (! Hash::check($request->post('old_password'), $user->password)) {
            return response()->json(['success' => false, 'message' => 'Old password is Incorrect', 'bg_color' => 'bg-danger']);
        }
        if ($request->post('new_password') != $request->post('confirm_password')) {
            return response()->json(['success' => false, 'message' => "Password doesn't match", 'bg_color' => 'bg-danger']);
        }
        if ($user) {
            $user->update(['password' => Hash::make($request->post('new_password'))]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'User not found',
                'bg_color' => 'bg-danger',
            ]);
        }

        return response()->json(['success' => true, 'message' => 'Password updated successfully', 'bg_color' => 'bg-success']);
    }

    public function userActivity(Request $request, $id = null)
    {

        if (! $id) {
            return view('content.miscellaneous.no-data');
        }
        $user = User::where('user_code', $id)->first();

        $data = [];
        $data['user'] = $user;
        $data['id'] = $id;

        return view('content.users.user-activity', $data);
    }

    public function userActivityLogs(Request $request)
    {
        $post_data = $request->post();
        try {
            // Extract date range format ----------- START ---------------
            $daterange = $request->input('date');
            [$startDate, $endDate] = explode(' - ', $daterange);

            // Convert the dates to Y-m-d format
            $startDate = \Carbon\Carbon::createFromFormat('d/m/Y', $startDate)->startOfDay()->format('Y-m-d H:i:s');
            $endDate = \Carbon\Carbon::createFromFormat('d/m/Y', $endDate)->endOfDay()->format('Y-m-d H:i:s');

            // Corrected query
            $activity = UserActivity::whereBetween('datetime', [$startDate, $endDate])
                ->where('usercode', $post_data['id'])
                ->get()
                ->map(function ($item) {
                    $item->header = json_decode($item->header);
                    $item->date = Carbon::today();

                    return $item;
                });
            // $activity = UserActivity::where('usercode', $post_data['id'])
            //   ->whereDate('datetime', isset($post_data['date']) ? Carbon::parse($post_data['date']) : Carbon::today())
            //   ->get()
            //   ->map(function ($item) {
            //     $item->header = json_decode($item->header);
            //     $item->date = Carbon::today();
            //     return $item;
            //   });
            // `datetime` TIMESTAMP,

            return response()->json([
                'data' => $activity,
                'post_data' => $post_data,
                'success' => true,
                'message' => 'Fetched user activity !',
                'bg_color' => 'bg-success',
            ]);
        } catch (Exception  $e) {
            return response()->json(['success' => false, 'message' => 'Server Error !', 'bg_color' => 'bg-danger', 'error' => 'Error : '.$e->getMessage().', in File : '.$e->getFile().', in Line : '.$e->getLine()]);
        }
    }
}
