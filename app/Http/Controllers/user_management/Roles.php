<?php

namespace App\Http\Controllers\user_management;

use App\Helpers\TableHelper;
use App\Helpers\UtilityHelper;
use App\Http\Controllers\Controller;
use App\Models\user_management\GrantsPermission;
use App\Models\user_management\Permission;
use App\Models\user_management\Role;
use App\Models\user_management\UsersModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class Roles extends Controller
{
    protected $roles;

    public function __construct()
    {
        $this->roles = new Role;
    }

    public function index(Request $request)
    {

        $headers = [
            // array('id' => 'ID'),
            ['role_name' => 'Role Name'],
            ['role_code' => 'Role Code'],
            ['created_at' => 'Create Date'],
            ['status' => 'Status'],
            ['actions' => 'Actions'],
        ];
        $role_info = Role::select('*')->get();
        $pageConfigs = ['pageHeader' => true, 'isFabButton' => true];
        $currentUrl = $request->url();
        $UtilityHelper = new UtilityHelper;
        $createPermissions = $UtilityHelper::CheckModulePermissions('roles', 'create.roles');
        // print_r($createPermissions);
        // exit;
        foreach ($role_info as $_role) {
            $users_info = UsersModel::select('*')->where('role_id', $_role->role_id)->get();
            if (isset($users_info) && $users_info->count() > 0) {
                $_role->user_count = $users_info->count();
            }
        }
        $deletePermissions = $UtilityHelper::CheckModulePermissions('roles', 'delete.roles');
        $table_headers = TableHelper::get_manage_table_headers($headers, true, false, true, true, true);

        // /print_r($table_headers); exit;
        return view('content.roles-and-permissions.list')
            ->with('pageConfigs', $pageConfigs)
            ->with('table_headers', $table_headers)
            ->with('currentUrl', $currentUrl)
            ->with('users_info', $users_info ?? null)
            ->with('createPermissions', $createPermissions)
            ->with('deletePermissions', $deletePermissions)
            ->with('role_info', $role_info);
    }

    protected function tableHeaderRowData($row)
    {
        $data = [];
        // $isCreate = UtilityHelper::CheckModulePermissions('roles', 'create.roles');
        $is_edit = UtilityHelper::CheckModulePermissions('roles', 'edit.roles'); /* check if permission is there to edit */
        // $is_delete = UtilityHelper::CheckModulePermissions('roles', 'delete.roles'); /* check if permission is there to delete */
        // $is_view = UtilityHelper::CheckModulePermissions('roles', 'view.roles'); /* check if permission is there to view */

        $userNameHtml = '-';
        $statusHTML = '-';
        //   $data['role_id'] =  $row->role_id;
        $name = ($row->role_name) ? $row->role_name : '';
        $initials = preg_match_all('/\b\w/', $name, $matches) ? $matches[0] : [];
        $initials = (array_shift($initials) ?: '').(array_pop($initials) ?: '');
        $initials = strtoupper($initials);
        $edit_route = route('roles.edit', ['id' => $row->role_id]);
        $view = route('roles.view', ['id' => $row->role_id]);
        $delete = route('roles.delete', ['id' => $row->role_id]);
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
	  '.$row->role_name.'
	  </span></a>
	  </div>
	  </div>';
        // Checkbox column (first cell) with data-id
        $data['checkbox'] = '<div class="form-check"><input type="checkbox" class="row-checkbox form-check-input" data-id="'.e($row->role_id).'"></div>';
        $data['role_name'] = $userNameHtml;
        $data['created_at'] = $row->created_at;
        $data['role_code'] = $row->role_code;
        if ($row->status == 1) {
            $statusHTML = '<span class="badge rounded bg-label-success " title="Active">Active</span>';
        } else {
            $statusHTML = '<span class="badge rounded bg-label-primary" title="Inactive">Inactive</span>';
        }
        $data['status'] = $statusHTML;

        // $delete_html = ($is_delete) ? '
        // <li><a href="javascript:;" onclick="deleteRow(\''.$delete.'\');" class="dropdown-item text-danger delete-record">Delete</a></li>' : '';
        if ($is_edit) {
            $edit_html = '<a href="'.$edit_route.'" class="btn btn-sm text-primary btn-icon item-edit" title="Edit Employee"><i class="bx bxs-edit"></i></a>';
        } else {
            $edit_html = '<a href="javascript:;" disabled class="btn btn-sm text-secondary btn-icon item-edit" title="No Permission"><i class="bx bxs-edit"></i></a>';
        }
        // ===============    Common action dropdown display add/edit/view/delete  ============= //
        // $data['actions'] = '<div class="d-inline-block">
        // <a href="javascript:;" class="btn btn-sm text-primary btn-icon dropdown-toggle hide-arrow" data-bs-toggle="dropdown"><i class="bx bx-dots-vertical-rounded"></i></a>
        // <ul class="dropdown-menu dropdown-menu-end">
        // <li><a href="javascript:;" onclick="viewRowDetails(\''.$view.'\');" class="dropdown-item">Details</a></li>
        // <div class="dropdown-divider"></div>
        // '.$delete_html.'
        // </ul>
        // </div>
        // '.$edit_html.'';
        $data['actions'] = $edit_html;

        return $data;
    }

    // <a href="javascript:;" class="btn btn-sm text-primary btn-icon item-edit"  data-bs-toggle="offcanvas" data-bs-target="#offcanvasAddrole"><i class="bx bxs-edit"></i></a>';
    /* Returns quotations table data rows. This will be called with AJAX. */
    public function list(Request $request)
    {
        // print_r($request->all()); exit;
        $search = '';
        $limit = 10;
        $offset = 0;
        $sort = $request->get('sort') ?? 'role_id';
        $order = $request->get('order') ?? 'desc';
        $filters = [];
        $searchData = $this->roles->search($search, $filters, $limit, $offset, $sort, $order);
        $total_rows = $this->roles->get_found_rows($search);
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

    public function edit(Request $request, $role_id = '')
    {
        $authUser = Auth::user();
        $data = [];

        // 🧩 1. Get all permissions
        $permissions = Permission::all();

        // 🧩 2. Prepare array for module -> permissions mapping
        $module_permission_array = [];

        foreach ($permissions as $per) {

            // --- Skip restricted modules and permissions for non-super-admin ---
            if (! $authUser->is_super_admin) {

                // Skip whole modules (Roles and Locations)
                // $restrictedModules = ['roles', 'locations'];

                // if (in_array($per->module_id, $restrictedModules)) {
                //     continue; // Don’t include this module
                // }

                // Skip specific permission IDs
                $restrictedPermissions = ['create.roles', 'delete.roles', 'add.permission', 'locations.create'];

                if (in_array($per->permission_id, $restrictedPermissions)) {
                    continue; // Don’t include this permission
                }
            }

            // --- Add allowed permission to grouped array ---
            $module_permission_array[$per->module_id][] = [
                $per->permission_id => $per->permission_name,
            ];
        }

        // 🧩 3. Convert grouped array into a clean key=>value array
        $module_permission = [];
        foreach ($module_permission_array as $resource => $actions) {
            $module_permission[$resource] = array_reduce($actions, 'array_merge', []);
        }

        // 🧩 4. Assign data to view
        $data['module_permission'] = $module_permission;
        $data['role_id'] = $role_id;

        // 🧩 5. Load role info if editing
        if ($role_id) {
            $role_info = Role::find($role_id);
            if (! $role_info) {
                return view('content.common.no-data-found', ['message' => 'Role Not Found!']);
            }

            $data['role_info'] = $role_info;
            $data['role_name'] = $role_info->role_name;
            $data['role_id'] = $role_info->role_id;
            $data['status'] = $role_info->status;

            // --- Fetch granted permissions ---
            $GrantsPermission = GrantsPermission::select('*')->where('role_id', $role_id)->get();

            $GrantsPermissionData = [];
            if ($GrantsPermission) {
                foreach ($GrantsPermission as $per) {
                    $per_ids = json_decode($per->permission_id, true);
                    foreach ($per_ids as $_name) {
                        $GrantsPermissionData[$per->module_id][$_name] = $_name;
                    }
                }
            }

            $data['grants_permission'] = $GrantsPermissionData;
        }

        // 🧩 6. Additional data
        $data['is_super_admin'] = $authUser->is_super_admin;
        $data['role_types'] = UtilityHelper::getRoleType();

        return view('content.roles-and-permissions.create', $data);
    }

    public function create(Request $request, $role_id = '')
    {
        $authUser = Auth::user();
        $data = [];

        // 🧩 1. Get all permissions
        $permissions = Permission::all();

        // 🧩 2. Prepare array for module -> permissions mapping
        $module_permission_array = [];

        foreach ($permissions as $per) {

            // --- Skip restricted modules and permissions for non-super-admin ---
            if (! $authUser->is_super_admin) {

                // Skip whole modules (Roles and Locations)
                // $restrictedModules = ['roles', 'locations'];

                // if (in_array($per->module_id, $restrictedModules)) {
                //     continue; // Don’t include this module
                // }

                // Skip specific permission IDs
                $restrictedPermissions = ['create.roles', 'delete.roles', 'add.permission', 'locations.create'];

                if (in_array($per->permission_id, $restrictedPermissions)) {
                    continue; // Don’t include this permission
                }
            }

            // --- Add allowed permission to grouped array ---
            $module_permission_array[$per->module_id][] = [
                $per->permission_id => $per->permission_name,
            ];
        }

        // 🧩 3. Convert grouped array into a clean key=>value array
        $module_permission = [];
        foreach ($module_permission_array as $resource => $actions) {
            $module_permission[$resource] = array_reduce($actions, 'array_merge', []);
        }

        // 🧩 4. Assign data to view
        $data['module_permission'] = $module_permission;
        $data['role_id'] = $role_id;

        // 🧩 6. Additional data
        $data['is_super_admin'] = $authUser->is_super_admin;
        $data['role_types'] = UtilityHelper::getRoleType();

        return view('content.roles-and-permissions.create', $data);
    }

    public function view(Request $request, $id = '')
    {
        $result = [];
        if ($id) {
            $info = '';
            $info = Role::find($id);
            $result['data'] = $info;
            // Get Selected grants permission_id
            $GrantsPermission = GrantsPermission::select('*')->where('role_id', $id)->get();
            $GrantsPermissionData = [];
            if (isset($GrantsPermission)) {
                foreach ($GrantsPermission as $per) {
                    $per_ids = json_decode($per->permission_id, true);
                    foreach ($per_ids as $_name) {
                        $createPermissions = UtilityHelper::getSelectedPermissionInfo($_name, $per->module_id);
                        // print_r($createPermissions); exit;
                        $module_lang_name = __('roles.'.$per->module_id);
                        $GrantsPermissionData[$module_lang_name][$createPermissions] = $_name;
                    }
                }
            }
            $result['grants_permission'] = $GrantsPermissionData;
        }

        return response()->json($result);
    }

    public function save(Request $request, $role_id = '')
    {
        // Validate form data
        $validator = Validator::make($request->all(), [
            'RoleName' => 'required|string|max:255', // Add more validation rules as needed
        ]);

        // If validation fails, return error response
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()->all()], 422);
        }

        /* Updating role info -------- Start ----------- */
        $isRoleUpdate = $request->post('isRoleUpdate');
        if ($isRoleUpdate) {
            $roleId = $request->post('role_id');
            $roleName = $request->post('RoleName');
            $status = $request->post('status');
            $post_updatedata = [
                'role_name' => $roleName,
                'status' => $status ? $status : 0,
            ];
            $roleModel = Role::find($roleId);
            if (! $roleModel) {
                return response()->json(['success' => false, 'message' => 'Form Update Failed!']);
            }
            $roleModel->update($post_updatedata);

            return response()->json(['success' => true, 'message' => 'Form submitted successfully']);
        }
        /* Updating role info -------- END ----------- */
        $role_name = $request->post('RoleName');
        $role_id = ($role_id) ? $role_id : $request->post('role_id');
        $post_data = [];
        $role_name = $request->post('RoleName');
        $post_data = [
            'role_name' => $role_name,
            'status' => $request->post('status') ? $request->post('status') : 0, // != NULL,
        ];
        if (! $role_id) {
            $post_data['created_at'] = date('Y-m-d H:i:s');
            $_code = UtilityHelper::generateCustomCode($role_name);
            $post_data['role_code'] = UtilityHelper::generateRandomString(3, 'ROL-'.$_code, true, true, true);
        } else {
            $post_data['updated_at'] = date('Y-m-d H:i:s');
        }
        $role_type = $request->post('role_type');
        if ($role_type) {
            $post_data['role_type'] = $role_type;
        }

        $permission_data = [];
        $permission_postData = [];
        $permissions = Permission::select('module_id')->get();
        if ($permissions) {
            foreach ($permissions as $name) {
                if ($request->post($name->module_id)) {
                    $permission_postData[$name->module_id] = [
                        'permission_id' => json_encode($request->post($name->module_id)),
                        'module_id' => $name->module_id,
                    ];
                }
            }
        }
        DB::beginTransaction();
        try {
            // print_r($post_data);
            // exit;
            if (! $role_id) {
                // Check this role name is exist or not
                $roleName = $request->post('RoleName');
                $is_ExistRoleName = Role::select('role_name')->where('role_name', $roleName)->first();
                if (isset($is_ExistRoleName->role_name)) {
                    return response()->json(['success' => false, 'message' => 'Failed to save role name is already existing ']);
                }
                // Creating new role -------- // --------
                $roleModel = Role::create($post_data);
                if (! $roleModel) {
                    return response()->json(['success' => false, 'message' => 'Form submitted Failed']);
                }
                $insert_id = $roleModel->role_id;
                // Save grants_permissions ----------------------------- // ------------
                if ($permission_postData && $insert_id) {
                    foreach ($permission_postData as $post_d) {
                        $post_d['role_id'] = $insert_id;
                        GrantsPermission::create($post_d);
                    }
                }
            } else {

                $roleModel = Role::find($role_id);
                $roleModel->update($post_data);

                // Save grants_permissions
                if (is_array($permission_postData) && $role_id) {
                    foreach ($permission_postData as $post_d) {
                        $post_d['role_id'] = $role_id;
                        $permission_d = isset($post_d['permission_id']) ? $post_d['permission_id'] : [];

                        $permissionToInsert = array_merge($post_d, ['permission_id' => $permission_d]);

                        // Remove items from GrantsPermission that are no longer present
                        GrantsPermission::where('role_id', $role_id)
                            ->whereNotIn('module_id', array_column($permission_postData, 'module_id'))
                            ->delete();

                        // Update or insert new permissions
                        GrantsPermission::updateOrInsert(
                            ['role_id' => $role_id, 'module_id' => $post_d['module_id']], // where clause for update
                            $permissionToInsert // attributes to insert or update
                        );
                    }
                } else {
                    return response()->json(['success' => false, 'message' => 'Save Failed! Permission Check is Required']);
                }
            }
            DB::commit();
            // Insert user activity --------------------- START ---------------------
            $roleData = [
                'role_name' => $post_data['role_name'] ?? '',
                'role_code' => $update_data['role_code'] ?? '',
                'status' => $post_data['status'] ?? '',
            ];
            $action = 'Edit';
            if (! $role_id) {
                $action = 'Create';
            }
            $this->UserActivityLog(
                $request,
                [
                    'module' => 'roles',
                    'activity_type' => $action,
                    'message' => $action.' roles : '.$roleData['role_name'],
                    'application' => 'web',
                    'data' => $roleData,
                ]
            );

            // Insert user activity --------------------- END ----------------------
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
            $models = Role::whereIn('id', $ids)->get();
            if ($models->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No matching role(s) found.',
                    'bg_color' => 'bg-danger',
                ], 404);
            }

            $deletedCount = 0;

            foreach ($models as $model) {
                $roleData = [
                    'role_name' => $model->role_name ?? '',
                    'role_code' => $model->role_code ?? '',
                ];

                // delete grants first
                GrantsPermission::where('role_id', $model->id)->delete();

                $model->delete();
                $deletedCount++;

                // log per role
                $this->UserActivityLog($request, [
                    'module' => 'roles',
                    'activity_type' => 'delete',
                    'message' => 'Deleted role: '.$roleData['role_name'],
                    'application' => 'web',
                    'data' => $roleData,
                ]);
            }

            DB::commit();

            $message = $deletedCount.' role(s) deleted successfully.';

            return response()->json([
                'success' => true,
                'message' => $message,
                'bg_color' => 'bg-success',
                'deleted' => $deletedCount,
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

    /* ------------------  Save module permission  ----------------------- */
    public function saveModulePermissions(Request $request, $id = '')
    {
        $post_data = $request->all();
        $permission_name = $request->post('permission_name');
        $module_id = $request->post('module_id');
        if ($permission_name && $module_id) {
            $permission_id = str_replace(' ', '.', strtolower($permission_name));
            $post_data = [
                'permission_name' => $permission_name,
                'permission_id' => $permission_id,
                'module_id' => $module_id,
            ];
        }
        try {
            if ($post_data) {
                // print_r($post_data);
                // exit;
                Permission::updateOrInsert(
                    ['permission_name' => $permission_name, 'permission_id' => $permission_id, 'module_id' => $module_id],
                    $post_data
                );

                // Insert user activity --------------------- START ---------------------
                $moduleData = [
                    'permission_name' => $post_data['permission_name'] ?? '',
                    'permission_id' => $update_data['permission_id'] ?? '',
                    'module_id' => $post_data['module_id'] ?? '',
                ];
                $this->UserActivityLog(
                    $request,
                    [
                        'module' => 'roles',
                        'activity_type' => 'update',
                        'message' => 'Update Module Permissions: '.$moduleData['permission_name'],
                        'application' => 'web',
                        'data' => $moduleData,
                    ]
                );
                // Insert user activity --------------------- END ----------------------

                return response()->json(['success' => true, 'message' => 'Form submitted successfully']);
            }

            // print_r($permission_id);
            // exit;
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'submitted  Failed!'.' '.
              $e->getMessage()]);
        }
    }
}
