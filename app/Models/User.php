<?php

namespace App\Models;

use App\Models\user_management\GrantsPermission;
use App\Models\user_management\Role;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'fullname',
        'username',
        'password',
        'email',
        'contact',
        'role_id',
        'user_type',
        'user_code',
        'status',
        'remember_token',
        'api_key',
        'login_token',
        'created_at',
        'updated_at',
        'location_id',
        'device_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function role()
    {
        return $this->belongsTo(Role::class, 'role_id', 'role_id');
    }

    public function getUserPermissions()
    {
        $permissions = GrantsPermission::where('role_id', $this->role_id)->get();
        if (! $permissions) {
            return [];
        }
        $user_permissions = [];
        foreach ($permissions as $permission) {
            $user_permissions[$permission->module_id] = json_decode($permission->permission_id);
        }

        return $user_permissions;
    }
}
