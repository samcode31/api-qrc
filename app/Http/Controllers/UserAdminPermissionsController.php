<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\UserAdminsPermissionAssignment;

class UserAdminPermissionsController extends Controller
{
    public function show (Request $request)
    {
        return UserAdminsPermissionAssignment::join(
            'user_admins_permissions',
            'user_admins_permission_assignments.permission_id',
            'user_admins_permissions.id'
        )
        ->select(
            'permission_id',
            'menu',
            'action'
        )
        ->where('user_admin_id', $request->user_admin_id)
        ->get();
    }
}
