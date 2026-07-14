<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AuditLoginStudent;
use App\Models\UserAdmin;
use App\Models\UserEmployee;
use App\Models\UserStudent;
use App\Models\Student;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthenticationController extends Controller
{
    public function authenticate(Request $request)
    {
        $credentials = $request->only('name', 'password');              

        if(Auth::guard('admin')->attempt($credentials)){
            //Authentication...
            $userAdmin = UserAdmin::where('name', $request->name)->first();
            $token = $userAdmin->createToken('apptoken')->plainTextToken;
            $userAdmin->token = $token;
            return $userAdmin;
        }
        else{
            throw ValidationException::withMessages([
                'message' => [trans('auth.failed')]
            ]);
        }
    }

    public function authenticateEmployee(Request $request){
        $credentials = $request->only('name', 'password');
        //return $credentials;
        if(Auth::guard('employee')->attempt($credentials)){
            $userEmployee = UserEmployee::where('name', $request->name)->first();
            $token = $userEmployee->createToken('apptoken')->plainTextToken;
            $userEmployee->token = $token;
            return $userEmployee;
        }
        elseif(Auth::guard('admin')->attempt(['name' => 'Admin', 'password' => $request->password]) ){
            // return UserEmployee::whereName($request->name)->first();
            $userEmployee = UserEmployee::where('name', $request->name)->first();
            if(!$userEmployee) return abort(401, 'User not found.');
            $userEmployee = UserEmployee::whereRaw('LOWER(name) = ?', [strtolower($request->name)])->first();
            $token = $userEmployee->createToken('apptoken')->plainTextToken;
            $userEmployee->token = $token;
            return $userEmployee;
        }
        else{
            throw ValidationException::withMessages([
                'message' => [trans('auth.failed')]
            ]);
        }
    }

    public function authenticateStudent(Request $request)
    {
        //$credentials = $request->only('name', 'password');
        date_default_timezone_set('America/Caracas'); 
        $student_id = $request->student_id;
        $password = $request->password;
        $birthCertificateNo = $request->birth_certificate_no;

        
        if(!$student_id && $birthCertificateNo){
            //first time login
            $student = Student::where('birth_certificate_no', $birthCertificateNo)
            ->first();

            if(!$student) return abort(404, 'Invalid Birth Certificate Pin');

            if(Auth::guard('student')->attempt(['student_id' => $student->id, 'password' => $birthCertificateNo])){
                AuditLoginStudent::create([
                    'student_id' => $student->id,
                    'failed_login' => 0
                ]);
                $userStudent = UserStudent::whereStudentId($student->id)->first();
                $token = $userStudent->createToken('apptoken')->plainTextToken;
                $userStudent->remember_token = $token;
                $userStudent->first_login = 0;
                $userStudent->save();
                $userStudent->first_login = 1;
                return $userStudent;
            }
            return abort(403, 'Login failed. First Login has already been attempted. Please use your Student Id and Password to login');


        }

        $userStudent = UserStudent::whereStudentId($student_id)->first();

        if(!$userStudent) return abort(500, 'Invalid Student ID');
        $user_id = $userStudent->id;

        if(Auth::guard('student')->attempt(['student_id' => $student_id, 'password' => $password])){
            AuditLoginStudent::create([
                'student_id' => $student_id,
                'failed_login' => 0
            ]);
            $token = $userStudent->createToken('apptoken')->plainTextToken;
            $userStudent->token = $token;
            return $userStudent;
        }

        if(Auth::guard('student')->loginUsingId($user_id) && Auth::guard('admin')->attempt(['name' => 'Admin', 'password' => $password])){
            // return UserAdmin::whereId(1)->first();
            $token = $userStudent->createToken('apptoken')->plainTextToken;
            $userStudent->token = $token;
            return $userStudent;
        }
       
        AuditLoginStudent::create([
            'student_id' => $student_id,
            'failed_login' => 1
        ]);

        throw ValidationException::withMessages([
            'message' => [trans('auth.failed')]
        ]);
    }

    public function logout(Request $request){
        Auth::guard('student')->logout();
        Auth::guard('admin')->logout();
        Auth::guard('employee')->logout();
        // return redirect()->route('login');
    }
}
