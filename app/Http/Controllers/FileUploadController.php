<?php

namespace App\Http\Controllers;

use App\Models\AssesmentCourse;
use App\Models\AssesmentEmployeeAssignment;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use Illuminate\Support\Facades\Storage;
use Throwable;


class FileUploadController extends Controller
{
     // function to store file in 'upload' folder
   public function fileStore(Request $request)
   {
        $id = $request->input('id');
        $file_type = $request->input('file_type');
        //$upload_path = public_path('upload');
        $file_name = $request->file->getClientOriginalName();
        $time = time();
        //$generated_new_name = time() . '.' . $request->file->getClientOriginalExtension();
        $generated_new_name = $id . '_' . $file_type . '_' . $time . '.' . $request->file->getClientOriginalExtension();
        $picture_name = $id .'.' . $request->file->getClientOriginalExtension();

        if($file_type == 'picture')
        $request->file->storeAs('public', $picture_name);
        else
        $request->file->storeAs('public', $generated_new_name);

        $student = Student::where('id', $id)->first();

        switch ($file_type){
           case 'birth_certificate';
               $student->file_birth_certificate = $generated_new_name;
               break;

           case 'sea_slip';
               $student->file_sea_slip = $generated_new_name;
               break;

           case 'immunization_card';
               $student->file_immunization_card = $generated_new_name;
               break;

           case 'contribution_receipt';
               $student->file_contribution_receipt = $generated_new_name;
               break;

           case 'picture';
               $student->picture = $picture_name;
               break;
        }

        $student->save();


        return response()->json(['success' => 'You have successfully uploaded "' . $file_name . '"', "file" => $generated_new_name]);
   }

   public function delete(Request $request)
   {
      $id = $request->input('id');
      $fileName = $request->input('fileName');
      $fileType = $request->input('fileType');
      
      if(!Storage::disk('public')->exists($fileName)) return abort(404, 'File Not Found');

      if(!Storage::disk('public')->delete($fileName)) return abort(500, 'File Could Not be Deleted.');

      if($fileType === 'birth_certificate'){
         return Student::where('id', $id)
         ->update(['file_birth_certificate'=> null]);
      }

      if($fileType === 'immunization_card'){
         return Student::where('id', $id)
         ->update(['file_immunization_card'=> null]);
      }

      if($fileType === 'sea_slip'){
         return Student::where('id', $id)
         ->update(['file_sea_slip' => null]);
      }

      if($fileType === 'contribution_receipt'){
         return Student::where('id', $id)
         ->update(['file_contribution_receipt' => null]);
      }

      if($fileType === 'picture'){
         return Student::where('id', $id)
         
         ->update(['picture' => null]);
      }
      // if(Storage::disk('public')->exists($fileName) && Storage::disk('public')->delete('fileName')){
      //    if($fileType == 'birth_certificate')
      //    return Student::where('id', $id)
      //    ->select(
      //       'id',
      //       'file_birth_certificate',
      //       'file_immunization_card',
      //       'file_sea_slip'
      //    )
      //    ->update(['']);
      //    // return $student
      // } else {
      //    return abort(404, 'File Not Found');
      // }
      
   }

   public function uploadCourseAssesment (Request $request)
   {
      $data = [];
      $fileName = $request->file->getClientOriginalName();
      $academicYearId = $request->year;
      $term = $request->term;
      $subjectId = $request->subjectId;
      $formClassId = $request->classId;
      $employeeId = $request->employeeId;

      $request->file->storeAs('public', $fileName);
      $file = storage_path('app/public/'.$fileName);
      $reader = new Xlsx();
      $spreadsheet = $reader->load($file);
      $sheet = $spreadsheet->getActiveSheet();
      $rows = $sheet->getHighestDataRow();
      $highestColumn = $sheet->getHighestColumn();
      $cols =  \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);
      $markColStart = 4;
      $markRowStart = 6;
      $assesmentNumber = 0;
      $courseMarks = 0;

      for($i = $markColStart; $i <= $cols; $i++){
         $assesmentNumber++;
         $assesment = [];
         $error = [];
         $assesment['number'] = $i;


         $topic = $sheet->getCell([$i,3])->getValue();

         if($topic == "TOTAL" || $topic == "AVERAGE"){
            break;
         }

         if($this->courseAssesmentCheck($sheet, 6, $rows, $i)){
            $assesment['check'] = $this->courseAssesmentCheck($sheet, 6, $rows, $i);
            $assesment['topic'] = $topic;

            $date = $sheet->getCell([$i,4])->getValue();
            try{
               if($date && date_create($date)){
                  $date = date_create($date);
                  $date = date_format($date, "Y-m-d");
               }
               elseif($date){
                  $date = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($date);
                  $date = date_format($date, "Y-m-d");
               }
               $total = $sheet->getCell([$i,5])->getValue();
   
               $assesmentEmployeeAssignment = AssesmentEmployeeAssignment::updateOrCreate(
                  [
                     'employee_id' => $employeeId,
                     'academic_year_id' => $academicYearId,
                     'term' => $term,
                     'subject_id' => $subjectId,
                     'form_class_id' => $formClassId,
                     'assesment_number' => $assesmentNumber
                  ],
                  [
                     'date' => $date,
                     'topic' => $topic,
                     'total' => $total
                  ]
               );
   
               for($j = $markRowStart; $j <= $rows; $j++){
                  if($sheet->getCell([$i,$j])->getValue()){
                     $assesment[$sheet->getCell([1,$j])->getValue()] = $sheet->getCell([$i,$j])->getValue();
                  }
                  AssesmentCourse::updateOrCreate(
                     [
                        'student_id' => $sheet->getCell([1,$j])->getValue(),
                        'assesment_employee_assignment_id' => $assesmentEmployeeAssignment->id
                     ],
                     [
                        'mark' => $sheet->getCell([$i,$j])->getValue()
                     ]
                  );
                  $courseMarks++;
               }

            } catch (Throwable $e){
               $error['error'] = $e->getMessage();
               $error['date'] = $date;
               $error['assessment_number'] = $i;
            }
         }
         array_push($data, $error);
         array_push($data, $assesment);

      }
      return $data;
   }

   public function getFiles(Request $request)
   {
      $id = $request->input('student_id');

      $data = [];

      $student = Student::where('id', $id)->first();
      if($student->file_birth_certificate){
         array_push($data,
            array(
               'type' => 'birth_certificate',
               // 'url' => URL::asset('storage/'.$student->file_birth_certificate),
               'url' => Storage::url($student->file_birth_certificate),
               'name' => $student->file_birth_certificate
            )
         );
      }

      if($student->file_sea_slip){
         array_push($data,
            array(
               'type' => 'sea_slip',
               'url' => Storage::url($student->file_sea_slip),
               'name' => $student->file_sea_slip
            )
         );
      }

      if($student->file_immunization_card){
         array_push($data,
            array(
               'type' => 'immunization_card',
               'url' => Storage::url($student->file_immunization_card),
               'name' => $student->file_immunization_card
            )
         );
      }

      if($student->file_contribution_receipt){
         array_push($data,
            array(
               'type' => 'contribution_receipt',
               'url' => Storage::url($student->file_contribution_receipt),
               'name' => $student->file_contribution_receipt
            )
         );
      }

      if($student->picture){
         array_push($data,
            array(
               'type' => 'picture',
               'url' => Storage::url($student->picture),
               'name' => $student->picture
            )
         );
      }

      return $data;
   }

   public function storeRegistration(Request $request)
   {
      $fileName = $request->file->getClientOriginalName();
      $request->file->storeAs('public', $fileName);
      $file = storage_path('app/public/'.$fileName);
      // return $file;
      $reader = new Xlsx();
      $spreadsheet = $reader->load($file);
      $rows = $spreadsheet->getActiveSheet()->getHighestDataRow();
      $data = [];
      for($i = 2; $i <= $rows; $i++){
         $student = new Student;
         $student->id = $spreadsheet->getActiveSheet()->getCell([1,$i])->getValue();
         if(!$student->id) continue;
         $student->last_name = $spreadsheet->getActiveSheet()->getCell([2,$i])->getValue();
         $student->first_name = $spreadsheet->getActiveSheet()->getCell([3,$i])->getValue();
         $student->sea_no = $spreadsheet->getActiveSheet()->getCell([4,$i])->getValue();
         $student->class_id = $spreadsheet->getActiveSheet()->getCell([5,$i])->getValue();
         $dob = $spreadsheet->getActiveSheet()->getCell([6,$i])->getValue();
         if($dob){
            $dob = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($dob);
            $dob = date_format($dob, "Y-m-d");
         }
         $student->date_of_birth = $dob;
         $student->house_name = $spreadsheet->getActiveSheet()->getCell([7,$i])->getValue();
         $entry_date = $spreadsheet->getActiveSheet()->getCell([8,$i])->getValue();
         if($entry_date) {
            $entry_date = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($entry_date);
            $entry_date = date_format($entry_date, "Y-m-d");
         }
         $student->entry_date = $entry_date;
         array_push($data, $student);
      }
      return $data;

   }

   private function courseAssesmentCheck($sheet, $rowStart, $rowEnd, $col)
   {
      $blank = false;
      for($i = $rowStart; $i <= $rowEnd; $i++){
         $value = $sheet->getCell([$col, $i])->getValue();
         if($value){
            $blank = true;
         }
      }
      return $blank;
   }
}
