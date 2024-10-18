<?php

namespace App\Http\Controllers;
ini_set('max_execution_time', '900');
use App\Models\Employee;
use App\Models\Student;
use App\Models\User;
use App\Models\UserAdmin;
use App\Models\UserStudent;
use App\Models\UserEmployee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;


class UserController extends Controller
{
    public function register ()
    {
        $students = Student::all();        
        //$students = Student::whereBetween('class_id',['4%','7%'])->get();
        //$students = Student::whereBetween('class_id',['2%','7%'])->paginate(1);
        //return $students;
        //$students = Student::where('class_id','like', '2%')->get();
        $accountsCreated = 0;        
        foreach($students as $student){
            $id = $student->id;
            $name = $student->first_name.' '.$student->last_name;
            //$password = $student->birth_certificate_no;
            $password = date_format(date_create($student->date_of_birth), 'Ymd');
            $user = UserStudent::create([
                'name' => $name,
                'student_id' => $id,
                'password' => Hash::make($password)
            ]);
            if($user->exists) $accountsCreated++;
        }
        return $accountsCreated;
    }
    
    public function createAdmin () 
    {
        $user = UserAdmin::create([
            'name' => 'Admin',
            'password' => Hash::make('PREAdm1n1$trat0r')
        ]);

        return $user;
    }

    public function registerPTA () 
    {
        $user = UserAdmin::create([
            'name' => 'PTA',
            'password' => Hash::make('Sc@rboroughSecPTA')
        ]);

        return $user;
    }

    public function user(Request $request)
    {
        return UserStudent::whereStudentId($request->input('student_id'))->first();
    }

    public function userEmployee(Request $request)
    {
        return UserEmployee::whereName($request->userName)->firstOrFail();
    }

    public function resetPasswordStudent(Request $request){        
        $id = $request->input('student_id');
        $student = Student::whereId($id)->first();
        //return $student;
        $password = date_format(date_create($student->date_of_birth), 'Ymd');
        $user = UserStudent::whereStudentId($id)->first();
        $user->password = Hash::make($password);
        $user->password_reset = 1;
        $user->save();
        if($user->wasChanged('password')) return ["change" => true, "message" => "Password Changed Successfully."];
        return response("Password Not Changed", 500);
    }

    public function resetEmployeePassword(Request $request){
        $id = $request->input('id');        
        $employee = Employee::whereId($id)->first();
        $password = date_format(date_create($employee->date_of_birth), 'Ymd');
        //return $password;        
        $user = UserEmployee::whereEmployeeId($id)->first();
        $user->password = Hash::make($password);
        $user->password_reset = 1;
        $user->save();
        if($user->wasChanged('password')) return $user;
        return ["change" => false, "message"=> "Password Not Changed"];
    }

    public function employeeChangePassword(Request $request)
    {
        $name = $request->input('name');
        $password = $request->input('password');        
        $userEmployee= UserEmployee::whereName($name)->first();
        $userEmployee->password = Hash::make($password);
        $userEmployee->password_reset = 0;
        $userEmployee->save();
        if($userEmployee->wasChanged('password')) return ["change" => true, "message" => "Password Changed Successfully."];
        return ["change" => false, "message"=> "Password Not Changed"];
        
    }

    public function changePassword(Request $request){
        $password = $request->input('password');
        $studentID = $request->input('student_id');
        $user = UserStudent::whereStudentId($studentID)->first();
        $user->password = Hash::make($password);
        $user->password_reset = 0;
        $user->save();
        if($user->wasChanged('password')) return $user;
        return response("Password Not Changed", 500);
    }

    public function defaultPassword($id){
        $student = Student::whereId($id)->get();
        $password = date_format(date_create($student->date_of_birth), 'Ymd');
        $user = UserStudent::whereStudentId($id)->first();
        $user->password = Hash::make($password);
        $user->save();
        if($user->wasChanged('password')) return ["change" => true, "message" => "Password Changed Successfully."];
        return ["change" => false, "message"=> "Password Not Changed"];
    }

    public function changeResetPassword(Request $request){        
        $resetPassword = $request->input('reset_password');
        $studentID = $request->input('student_id');
        $user = UserStudent::whereStudentId($studentID)->first();
        $user->password_reset = $resetPassword;
        $user->save();
        if($user->wasChanged('reset_password')) return ["reset_password" => $resetPassword];
        return ["error" => "Reset not changed"];
    }
    
}
