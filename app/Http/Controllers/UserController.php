<?php

namespace App\Http\Controllers;

use DataTables;
use App\Models\Role;
use App\Models\User;
use App\Http\Requests\UserRequest;
use Illuminate\Http\Request;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        if(request()->ajax()){
            return DataTables::eloquent(User::with('roles:id,name'))->addIndexColumn()->make(true);
        }
        $roles = Role::all();
        return view('admin.users.index',compact('roles'));
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
    public function store(UserRequest $request)
    {
        $user = User::create($request->validated());
        $user->assignRole($request->role);
        return ['code' => 200, 'status' => 'Success', 'message' => 'User Added Successfully!'];
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(User $user)
    {
        // $roles = $user->roles->pluck('name');
        $role = $user->roles->value('name');
        return response()->json(['user' => $user, 'role' => $role]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UserRequest $request, User $user)
    {
        $user->update($request->validated());
        $user->assignRole($request->role);
        return ['code' => 200, 'status' => 'Success', 'message' => 'User Updated Successfully!'];
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
