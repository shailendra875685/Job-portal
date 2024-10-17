<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use App\Models\User;

class UserController extends Controller
{
    public function index(){
        $users = User::orderBy('created_at','DESC')->paginate(10);
        return view ('admin.user.list',[
            'users'=> $users ,
        ]);
      
    }

    public function edit($id){
        $user = User::findOrFail($id);
        // dd($user);
        return view ('admin.user.edit',[
            'user'=>$user,
        ]);

    }

    public function update($id, Request $request){
       

       
        $validator = validator::make($request->all(),[
            'name'=>'required|min:5|max:20',
            'email'=>'required|email|unique:users,email,'.$id.',id',
        ]);

        if($validator->passes()){

            $user= User::find($id);
            $user->name = $request->name;
            $user->email = $request->email;
            $user->designation = $request->designation;
            $user->mobile = $request->mobile;

            $user->save();
        
            session()->flash('success','User information updated successfully');
            return response()->json([
                'status'=>true,
                'errors'=>[]
            ]);
            
        }else{
            return response()->json([
                'status'=>false,
                'errors'=>$validator->errors()
            ]);
        }

    }
    public function destroy(Request $request){
        $id = $request->id;

        $user = User::find($id);
        if($user == null){
            session()->flash('error','User not found');
            return response()->json([
                'status'=>false,
            ]);

        }
            $user->delete();
            session()->flash('success','User delete successfully');
            return response()->json([
                'status'=>true,
            ]);
    }
}
