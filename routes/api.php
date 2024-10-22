<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthenticationController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AcademicTermController;
use App\Http\Controllers\AcademicYearController;
use App\Http\Controllers\FormClassController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\EmployeePostController;
use App\Http\Controllers\TownController;
use App\Http\Controllers\OccupationController;
use App\Http\Controllers\AdminStudentController;
use App\Http\Controllers\StudentUploadTemplateController;
use App\Http\Controllers\FileUploadController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\SpreadsheetStudentDataController;
use App\Http\Controllers\RegistrationFormController;
use App\Http\Controllers\SubjectController;
use App\Http\Controllers\FormTeacherController;
use App\Http\Controllers\AdminTeacherLessonController;
use App\Http\Controllers\StudentWeeklyTestController;
use App\Http\Controllers\TeacherLessonController;
use App\Http\Controllers\Table2Controller;
use App\Http\Controllers\Table1Controller;
use App\Http\Controllers\ReportCardController;
use App\Http\Controllers\RankSheetController;
use App\Http\Controllers\MarkSheetController;
use App\Http\Controllers\TermReportController;
use App\Http\Controllers\ReportCardAccessLogController;
use App\Http\Controllers\StudentRegistrationController;
use App\Http\Controllers\TeacherSubjectController;
use App\Http\Controllers\HouseController;
use App\Http\Controllers\ReligionController;
use App\Http\Controllers\EthnicGroupController;
use App\Http\Controllers\RegionalCorporationController;
use App\Http\Controllers\PromotionController;
use App\Http\Controllers\FormDeanController;
use App\Http\Controllers\StudentSubjectController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });

/*
|--------------------------------------------------------------------------
| Setup Routes
|--------------------------------------------------------------------------
|
*/

Route::post('/admin', [UserController::class, 'createAdmin']);

/*
|--------------------------------------------------------------------------
| Authentication Routes
|--------------------------------------------------------------------------
|
*/
Route::post('/admin-login', [AuthenticationController::class, 'authenticate']);

Route::post('/employee-login', [AuthenticationController::class, 'authenticateEmployee']);

Route::post('/login-student', [AuthenticationController::class, 'authenticateStudent']);


/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
|
*/

Route::get('/current-period', [AcademicTermController::class, 'show']);

Route::post('/current-period', [AcademicTermController::class, 'store']);

Route::get('/academic-years', [AcademicYearController::class, 'show']);

Route::get('/form-classes-list', [FormClassController::class, 'show']);

Route::get('/employees', [EmployeeController::class, 'show']);

Route::get('/employee-posts', [EmployeePostController::class, 'show']);

Route::post('/employee', [EmployeeController::class, "store"]);

Route::post('/update-employee', [EmployeeController::class, 'update']);

Route::post('/reset-employee-password', [UserController::class, 'resetEmployeePassword']);

Route::get('/student-address-town', [TownController::class, 'showDistinct']);

Route::get('/student-parent-occupation', [OccupationController::class, 'show']);

Route::get('/admin-students', [AdminStudentController::class, 'show']);

Route::get('/student-upload-template', [StudentUploadTemplateController::class, 'download']);

Route::post('/store-registration-file', [FileUploadController::class, 'storeRegistration']);

Route::post('/upload-new-students', [StudentController::class, 'uploadNewStudents']);

Route::get('/spreadsheet-student-data', [SpreadsheetStudentDataController::class, 'download']);

Route::get('/registration-form', [RegistrationFormController::class, 'createPDF']);

Route::post('/student', [StudentController::class, 'store']);

Route::post('/reset-password-student', [UserController::class, 'resetPasswordStudent']);

Route::get('/subjects', [SubjectController::class, 'show']);

Route::post('/subject', [SubjectController::class, 'store']);

Route::delete('/subject', [SubjectController::class, 'delete']);

Route::get('/form-teacher-class', [FormTeacherController::class, 'show']);

Route::get('/admin-teacher-lessons', [AdminTeacherLessonController::class, 'show']);

Route::post('/form-teacher-class', [FormTeacherController::class, 'store']);

Route::delete('/admin-teacher-lessons', [AdminTeacherLessonController::class, 'delete']);

Route::post('/admin-teacher-lesson', [AdminTeacherLessonController::class, 'store']);

Route::get('/user-employee', [UserController::class, 'userEmployee']);

Route::post('/employee-change-password', [UserController::class, 'employeeChangePassword']);

Route::get('/employee', [EmployeeController::class, 'index']);

Route::post('/clear-flags', [Table1Controller::class, 'clearFlags']);

Route::post('/term-reports-register', [Table1Controller::class, 'register']);

Route::get('/term-reports-posted', [TermReportController::class, 'show']); 

Route::post('/post-term-reports', [TermReportController::class, 'store']);

Route::post('/possible-attendance', [Table1Controller::class, 'storePossibleAttendance']);

Route::post('/new-term-beginning', [Table1Controller::class, 'storeNewTermBeginning']);

Route::post('/promotion', [PromotionController::class, 'store']);

Route::post('/promotion-undo', [PromotionController::class, 'undo']);

Route::get('/dean-form-classes', [FormDeanController::class, 'show']);

Route::post('/dean-form-classes', [FormDeanController::class, 'store']);

Route::get('/form-levels', [FormClassController::class, 'showFormLevels']);

Route::get('/subject-students', [StudentSubjectController::class, 'show']);

Route::post('/subject-students', [StudentSubjectController::class, 'store']);

Route::post('/subject-students-batch', [StudentSubjectController::class, 'storeBatch']);

Route::delete('/subject-students', [StudentSubjectController::class, 'delete']);



/*
|--------------------------------------------------------------------------
| Term Marks Routes
|--------------------------------------------------------------------------
|
*/

Route::get('/weekly-test', [StudentWeeklyTestController::class, 'show']);

Route::get('/weekly-test-subjects', [StudentWeeklyTestController::class, 'showSubjects']);

Route::post('/weekly-test', [StudentWeeklyTestController::class, 'post']);

Route::post('/weekly-test-subject', [StudentWeeklyTestController::class, 'postSubjectWeeklyTest']);

Route::delete('/weekly-test-subject', [StudentWeeklyTestController::class, 'delete']);

Route::get('/teacher-subjects', [TeacherSubjectController::class, 'show']);

/*
|--------------------------------------------------------------------------
| Term Test Reports
|--------------------------------------------------------------------------
*/

Route::get('/teacher-lessons', [TeacherLessonController::class, 'show']);

Route::get('/teacher-lesson-students', [Table2Controller::class, 'show']);

Route::post('/table2', [Table2Controller::class, 'store']);

Route::post('/import-weekly-tests', [Table2Controller::class, 'importAssesments']);

/*
|--------------------------------------------------------------------------
| Edit View Term Details
|--------------------------------------------------------------------------
*/
Route::get('/form-classes', [Table1Controller::class, 'formClasses']);

Route::get('/students-registered', [Table1Controller::class, 'show']);

Route::get('/student-table2-records', [Table2Controller::class, 'studentRecords']);

Route::post('/table1', [Table1Controller::class, 'store']);

Route::post('/table2-record-delete', [Table2Controller::class, 'delete']);

Route::post('/update-table2', [Table2Controller::class, 'update']);

/*
|--------------------------------------------------------------------------
| Reports
|--------------------------------------------------------------------------
*/
Route::get('/report-card', [ReportCardController::class, 'create']);

Route::get('/rank-sheet', [RankSheetController::class, 'show']);

Route::get('/mark-sheet', [MarkSheetController::class, 'show']);

Route::get('/report-card-access-logs', [ReportCardAccessLogController::class, 'show']);

Route::get('/report-card-terms', [Table1Controller::class, 'showReportTerms']);


/*
|--------------------------------------------------------------------------
| Student Registration
|--------------------------------------------------------------------------
*/

Route::get('/students', [StudentController::class, 'show']);

Route::get('/user', [UserController::class, 'user']);

Route::post('/change-password-student', [UserController::class, 'changePassword']);

Route::get('/student-record', [StudentController::class, 'index']);

Route::get('/student-registration-all', [StudentRegistrationController::class, 'showAll']);

Route::get('/houses', [HouseController::class, 'show']);

Route::get('/towns', [TownController::class, 'show']);

Route::get('/religions', [ReligionController::class, 'show']);

Route::get('/ethnic-groups', [EthnicGroupController::class, 'show']);

Route::get('/regional-corporation', [RegionalCorporationController::class, 'show']);

Route::get('/student-registration', [StudentRegistrationController::class, 'show']);

Route::post('/student-registration', [StudentRegistrationController::class, 'store']);

Route::get('/get-files', [FileUploadController::class, 'getFiles']);

Route::post('/store-file', [FileUploadController::class, 'fileStore']);

Route::get('/registration-form', [RegistrationFormController::class, 'createPDF']);

Route::delete('/file', [FileUploadController::class, 'delete']);

/*
|--------------------------------------------------------------------------
| Upload Routes
|--------------------------------------------------------------------------
*/

Route::post('/upload-classes',[FormClassController::class, 'upload']);

Route::post('/upload-subjects', [SubjectController::class, 'upload']);






