<?php

namespace App\Http\Controllers;

use DataTables;
use App\Models\Permission;
use Illuminate\Http\Request;
use App\Http\Requests\PermissionRequest;
use Illuminate\Validation\ValidationException;

class PermissionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        if(request()->ajax()){
            return DataTables::eloquent(Permission::query())->addIndexColumn()->make(true);
        }
        return view('admin.roles&permissions.permissions.index');
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
    public function store(PermissionRequest $request)
    {
        Permission::create($request->validated());
        return ['code' => 200, 'status' => 'Success', 'message' => 'Permission Added Successfully!'];
    }

    /**
     * Display the specified resource.
     */
    public function show(Permission $permission)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Permission $permission)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(PermissionRequest $request, Permission $permission)
    {
        $permission->update($request->validated());
        return ['code' => 200, 'status' => 'Success', 'message' => 'Permission Updated Successfully!'];
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Permission $permission)
    {
        $permission->delete();
        return ['code' => 200, 'status' => 'Success', 'message' => 'Permission Deleted Successfully!'];
    }
}
