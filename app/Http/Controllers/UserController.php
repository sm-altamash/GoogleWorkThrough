<?php

namespace App\Http\Controllers;

use DataTables;
use App\Models\Role;
use App\Models\User;
use App\Http\Requests\UserRequest;
use Illuminate\Http\Request;

class UserController extends Controller
{

    public function index()
    {
        if(request()->ajax()){
            return DataTables::eloquent(User::with('roles:id,name'))->addIndexColumn()->make(true);
        }
        $roles = Role::all();
        return view('admin.users.index',compact('roles'));
    }
 
    public function store(UserRequest $request)
    {
        $user = User::create($request->validated());
        $user->assignRole($request->role);
        return ['code' => 200, 'status' => 'Success', 'message' => 'User Added Successfully!'];
    }

    public function edit(User $user)
    {
        $role = $user->roles->value('name');
        return response()->json(['user' => $user, 'role' => $role]);
    }

    public function update(UserRequest $request, User $user)
    {
        $user->update($request->validated());
        $user->assignRole($request->role);
        return ['code' => 200, 'status' => 'Success', 'message' => 'User Updated Successfully!'];
    }

}
