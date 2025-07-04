<?php

namespace App\Http\Controllers;

use DataTables;
use App\Models\Role;
use App\Models\Permission;
use App\Http\Requests\RoleRequest;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        if(request()->ajax()){
            return DataTables::eloquent(Role::query())->addIndexColumn()->make(true);
        }
        $permissions = Permission::all();
        return view('admin.roles&permissions.roles.index',compact('permissions'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(RoleRequest $request)
    {
        $role = Role::create($request->validated());
        $role->syncPermissions($request->permissions);
        return ['code' => 200, 'status' => 'Success', 'message' => 'Role Added Successfully!'];
    }

    /**
     * Display the specified resource.
     */
    public function show(Role $role)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Role $role)
    {
        $permissions = $role->permissions->pluck('name');
        return response()->json(['role' => $role, 'permissions' => $permissions]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(RoleRequest $request, Role $role)
    {
        $role->update($request->validated());
        $role->syncPermissions($request->permissions);
        return ['code' => 200, 'status' => 'Success', 'message' => 'Role Updated Successfully!'];
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Role $role)
    {
        $role->delete();
        return ['code' => 200, 'status' => 'Success', 'message' => 'Role Deleted Successfully!'];
    }
}
