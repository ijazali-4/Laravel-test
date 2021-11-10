<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function Authentication($request){
        $user = User::where('email', $request->email)->first();
        if ($user) {
            if (Hash::check($request->password, $user->password)) {
                if( $user->active==1){
                  $user->remember_token=$this->generateRandomString();
                  $user->save();
                    $response = ['code'=>'200','user' =>['email'=>$user->email,'username'=>$user->name,'id'=>$user->id,'token'=>$user->remember_token]];
                }else{

                $response = ["message" => "inActive User"];
            }
                return response($response, 200);
            } else {
                $response = ["message" => "Password mismatch"];
                return response($response, 422);
            }
        } else {
            $response = ["message" =>'User does not exist'];
            return response($response, 422);
        }
    }

    public function sendMail($request,$obj){
        $message='Hello '.$request->email.'
        please  set your passsword using this link below
        '.asset('api/user/active').'/'.$obj->id;
        Mail::raw($message, function($message) use ($request){
            $message->to($request->email,$request->email)->subject
               ('Login Link');
            $message->from(env('MAIL_USERNAME'),'Laravel Developer');
         });
         return true;
    }
    public function sendPin($obj){
        $message='Hello '.$obj->name.'
        Your Activation Pin is '.$obj->pin;
        Mail::raw($message, function($message) use ($obj){
            $message->to($obj->email,$obj->name)->subject
               ('Activation key');
            $message->from(env('MAIL_USERNAME'),'Laravel Developer');
         });
         return true;
    }

    function generateRandomString($length = 30) {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }
}
