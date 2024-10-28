<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\UserEmployee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;

class EmployeeController extends Controller
{
    public function store(Request $request)
    {
        $firstName = $request->input('first_name');
        $lastName = $request->input('last_name');
        $teacherNum = $request->input('teacher_num');
        $dateOfBirth = $request->input('date_of_birth');
        $dayOfBirth = date_format(date_create($dateOfBirth), 'j'); 
        $employeePost = $request->input('post_id') ; 

        $employee = Employee::withTrashed()
        ->where('teacher_num', $teacherNum)
        ->first();

        if($employee) abort(500, 'Teacher number already exists');

        $employee = Employee::create([
            'last_name' => $lastName,
            'first_name' => $firstName,
            'teacher_num' => $teacherNum,
            'date_of_birth' => $dateOfBirth,
            'post_id' => $employeePost,
        ]);

        if($employee->wasRecentlyCreated){                    
            $userName = $firstName[0].$lastName;
            $appendDigit = 0;
            $employee_id = $employee->id;
            //return $employee_id;
            if(UserEmployee::where("name", $userName)->first()){
                $userName = $userName.$dayOfBirth;
            }
            while(UserEmployee::whereName($userName)->exists())
            {                
                $appendDigit++;
                $userName = $userName.$appendDigit;
            }       
            $dateOfBirth = date_format(date_create($dateOfBirth), 'Ymd');
            $user = UserEmployee::create([
                'name' => $userName,
                'employee_id' => $employee_id,
                'password' => Hash::make($dateOfBirth),
                
            ]);
            return $user;
        }
        else{
            return 'employee not created';
        } 
    }

    public function update(Request $request)
    {
        $data = [];
        $id = $request->id;
        $first_name = $request->first_name;
        $last_name = $request->last_name;
        $date_of_birth = $request->date_of_birth;
        $teacher_num = $request->teacher_num;

        $employee = Employee::withTrashed()
        ->where('id', $id)->first();

        if($employee->teacher_num != $teacher_num)
        {
            //update teacher number
            $teacherNumber = Employee::withTrashed()
            ->where('teacher_num', $teacher_num)
            ->first();
            if($teacherNumber) abort(500, 'Teacher Number already assigned!');
        }

        
        $employee->first_name = $first_name;
        $employee->last_name = $last_name;
        $employee->date_of_birth = $date_of_birth;
        $employee->teacher_num = $teacher_num;
        $employee->post_id = $request->post_id;
        $employee->save();
        $data['employee'] = $employee;

        if($teacher_num >= 1000) $employee->delete();

        if($teacher_num <= 1000 && $employee->trashed()) {
            $employee->restore();
        }

        $user = UserEmployee::where('employee_id', $id)->first();
        $user_name = $first_name[0].$last_name;
        $user_name = str_replace('-','',$user_name);
        $user_name = str_replace(' ', '', $user_name);

        if($user->name != $user_name){
            $appendDigit = 0;

            while(UserEmployee::where('name', $user_name)->first()){
                $appendDigit++;
                $user_name = $user_name.$appendDigit;
            }

            $user->name = $user_name;
            $user->save();
        }

        $data['user'] = $user;


        return $data;
    }

    public function insert (Request $request)
    {
        return $request->all();
        $data = [];        
        $first_name = $request->first_name;
        $last_name = $request->last_name;
        $date_of_birth = $request->date_of_birth;
        $teacher_num = $request->teacher_num;

        $employee = Employee::create([
            'first_name' => $first_name,
            'last_name' => $last_name,
            'date_of_birth' => $date_of_birth,
            'teacher_num' => $teacher_num
        ]);

        $data['employee'] = $employee;

        $user_name = $first_name[0].$last_name;
        $user_name = str_replace('-','',$user_name);
        $user_name = str_replace(' ', '', $user_name);
        $appendDigit = 0;

        while(UserEmployee::where('name', $user_name)->first()){
            $appendDigit++;
            $user_name = $user_name.$appendDigit; 
        }

        $password = date_format(date_create($date_of_birth), 'Ymd');
        $user = UserEmployee::create([
            'name' => $user_name,
            'employee_id' => $employee->id,
            'password' => Hash::make($password),
            
        ]);
        
        $data['user'] = $user;

        return $data;

    }

    public function upload ()
    {
        $file = './files/employees.xlsx';
        $reader = new Xlsx();
        $spreadsheet = $reader->load($file);
        $rows = $spreadsheet->getActiveSheet()->getHighestDataRow();
        // return $rows;
        //return $dateOfBirth = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(3,2)->getValue();        
        $records = 0;        
        for($i = 2; $i <= $rows; $i++){            
            $teacherNum = $spreadsheet->getActiveSheet()->getCell([1,$i])->getValue();
            $lastName = $spreadsheet->getActiveSheet()->getCell([2,$i])->getValue();
            $firstName = $spreadsheet->getActiveSheet()->getCell([3,$i])->getValue();
            $dateOfBirth = $spreadsheet->getActiveSheet()->getCell([4,$i])->getValue();
            if(!$lastName || !$firstName) continue;
            $dayOfBirth = 1;
            if($dateOfBirth){
                $dateOfBirth = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($dateOfBirth);
                $dateOfBirth = date_format($dateOfBirth, "Y-m-d");
                $dayOfBirth = date_format(date_create($dateOfBirth), 'j'); 
             }
            $employee = Employee::create([
                'last_name' => $lastName,
                'first_name' => $firstName,                
                'date_of_birth' => $dateOfBirth,
                'teacher_num' => $teacherNum   
            ]);
            if($employee->wasRecentlyCreated){
                $userName = str_replace('-','',$firstName[0].$lastName);
                $userName = str_replace(' ', '', $userName);
                $appendDigit = 0;
                $employee_id = $employee->id;
                //return $employee_id;
                if(UserEmployee::whereName($userName)->exists()){
                    $userName = $userName.$dayOfBirth;
                }
                while(UserEmployee::whereName($userName)->exists())
                {                
                    $appendDigit++;
                    $userName = $userName.$appendDigit;
                }       
                $dateOfBirth = date_format(date_create($dateOfBirth), 'Ymd');
                $user = UserEmployee::create([
                    'name' => $userName,
                    'employee_id' => $employee_id,
                    'password' => Hash::make($dateOfBirth),
                    
                ]);
                if($user->wasRecentlyCreated) $records++;
            }
            
        }
        //return $spreadsheet->getActiveSheet()->getHighestDataRow();
        return $records;
    }

    public function index(Request $request)
    {
        return Employee::whereId($request->id)->get();
    }

    public function show()
    {
        $data = [];
        $employeeRecords = Employee::withTrashed()->get();
        foreach($employeeRecords as $employeeRecord){
            $employee = [];
            $employee['id'] = $employeeRecord->id;
            $employee['last_name'] = $employeeRecord->last_name;
            $employee['first_name'] = $employeeRecord->first_name;
            $employee['teacher_num'] = $employeeRecord->teacher_num;
            $employee['date_of_birth'] = $employeeRecord->date_of_birth;
            $employee['post_id'] = $employeeRecord->post_id;
            $employee['deleted_at'] = $employeeRecord->deleted_at;
            $employeeRecord->user;
            $employee['user_name'] = null;
            if($employeeRecord->user) $employee['user_name'] = $employeeRecord->user->name;

            array_push($data, $employee);
        }
        return $data;
    }
}
