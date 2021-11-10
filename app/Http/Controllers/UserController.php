<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Image;
class UserController extends Controller
{
    /* Sending ling to the Users*/
    function sendLink(Request $request){
        $validator = Validator::make($request->all(), [
            'email' => 'required',
            'user_name' => 'min:4, max:20'
        ]);
        //incase of exception occur
        if ($validator->fails())
        {
            return response(['errors'=>$validator->errors()->all()], 422);
        }
        //register new user
       $obj= new User;
       $obj->name= $request->name;
       $obj->user_name = $request->user_name;
       $obj->email=$request->email;
       $obj->password= sha1('password123');
       $obj->user_role=$request->user_role;
       $obj->save();
        //send email
        try {
            $userModel=new User;
            $userModel->sendMail($request,$obj);
            return response(['success'=>200,'message'=> "Kindly check your Email"], 200);
        } catch (\Throwable $th) {
            //throw $th;
            return response(['failed'=>400,'message'=> $th->getMessage()], 400);
        }

    }
    /* Login method*/
    public function login (Request $request) {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email|max:255',
            'password' => 'required',
        ]);
        if ($validator->fails())
        {
            return response(['errors'=>$validator->errors()->all()], 422);
        }
        $userModel=new User;
        return   $userModel->authentication($request);

    }
    /* Send Pin to the user to Active his Account*/
    public function userActive(Request $request,$id){
        $validator = Validator::make($request->all(), [
            'password' => 'required',
            'name' => 'required',
        ]);
        if ($validator->fails())
        {
            return response(['errors'=>$validator->errors()->all()], 422);
        }
        $pin=rand(111111,999999);
        $obj=user::find($id);
        $obj->name=$request->name;
        $obj->pin=$pin;
        $obj->registered_at=date('y-m-d h:m:s');
        $obj->password=Hash::make($request->password);
        $obj->save();
        $userModel=new User;
        $userModel->sendPin($obj);
        return ['success'=>200, 'message'=>'Please check your email to get verification pin.'];
    }
    /*Activate the user account */
    public function userActiveByPin(Request $request){
        $validator = Validator::make($request->all(), [
            'email' => 'required',
            'pin' => 'required',
        ]);
        if ($validator->fails())
        {
            return response(['errors'=>$validator->errors()->all()], 422);
        }
        $obj=user::where('email',$request->email)->first();
        if($obj->pin==$request->pin){
            $obj->active=1;
            $obj->save();
          return ['message'=>'User Activted'];
        }
        return ['message'=>'Incorrect Pin'];
    }
    /*Update user profile & Image Dimention work 256px 256px */
    public function editUser(Request $request){
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'user_name' => 'required|min:4,max:20',
            'role'=>'required',
            'id'=>'required',
            'avatar'=>'required|dimensions:max_width=256,max_height=256',
        ]);
        if ($validator->fails() )
        {
            return response(['errors'=>$validator->errors()->all()], 422);
        }
         $obj=user::find($request->id );
         //if user token is mismatch then unAuthorized message will be display.
         if(!$request->hasheader('token') || $obj->remember_token != $request->header('token')){
             return ['message'=>'unAuthoriozed'];
         }

        if($request->hasFile('avatar')) {
            $image       = $request->file('avatar');
            $input = time().'.'.$image->extension();
            $image->move('images', $input);
            $obj->avatar='images/'.$input;
        }
        $obj->user_name=$request->user_name;
        $obj->name=$request->name;
        $obj->user_role=$request->role;
        $obj->save();
        return ['success'=>200, 'message'=>'successfully update profile'];
    }
}
