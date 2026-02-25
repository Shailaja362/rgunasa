<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\ClubsController;
use App\Http\Controllers\FacultyController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\ProgrammeController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\AdminPasswordController;
use App\Http\Controllers\Auth\AdminAuthController;
use App\Http\Controllers\admin\AdminHomeController;
use App\Http\Controllers\Auth\StudentAuthController;
use App\Http\Controllers\admin\AssignGradeController;
use App\Http\Controllers\admin\AdminReportsController;
use App\Http\Controllers\admin\CreditPointAssignController;
use App\Http\Controllers\super_admin\EventsController;
use App\Http\Controllers\Auth\SuperAdminAuthController;
use App\Http\Controllers\admin\StudentAttendanceController;
use App\Http\Controllers\AdminImportExportController;
use App\Http\Controllers\EventRegisterdReportController;
use App\Http\Controllers\super_admin\AssignTasksController;
use App\Http\Controllers\super_admin\ReviewReportsController;
use App\Http\Controllers\super_admin\SuperAdminHomeController;
use App\Http\Controllers\student\StudentImportExportController;
use App\Http\Controllers\super_admin\StudentApprovalController;

Route::prefix('admin')->group(function () {

    Route::middleware(['admin.guest'])->as('admin.')->group(function () {
        Route::view('/login', 'auth.admin_login')->name('login');
        Route::post('/login', [AdminAuthController::class, 'index'])->name('role_check.login');
        Route::get('forgot-password', [AdminPasswordController::class, 'showEmailForm'])->name('password.forgot');
        Route::post('verify-email', [AdminPasswordController::class, 'verifyEmail'])->name('password.verify');
        Route::post('update-password', [AdminPasswordController::class, 'updatePassword'])->name('password.update');
        Route::any('/security-check', [SuperAdminAuthController::class, 'securityCheck'])->name('security_check');
    });
    Route::middleware('auth:admin')->group(function () {
        Route::any('/superadmin-security', [SuperAdminAuthController::class, 'index'])->name('superadmin_security');
        Route::get('/home', [AdminHomeController::class, 'index'])->name('home');
        Route::get('/reports', [AdminReportsController::class, 'index'])->name('reports');
        Route::get('/create_report', [AdminReportsController::class, 'crearteReport'])->name('create_report');
        Route::post('/save_report', [AdminReportsController::class, 'saveReport'])->name('save_report');

        Route::post('/security_login', [SuperAdminAuthController::class, 'index'])->name('security_check.login');
        Route::get('/super_admin_home', [SuperAdminHomeController::class, 'index'])->name('super_admin_home');
        Route::get('/events', [EventsController::class, 'index'])->name('events');
        Route::get('/create-event', [EventsController::class, 'createEvent'])->name('create_event');
        Route::post('/save-event', [EventsController::class, 'saveEvent'])->name('save_event');
        Route::get('/event-list', [EventsController::class, 'eventlist'])->name('event_list');

        //Assign Tasks

        Route::any('/assign-tasks', [AssignTasksController::class, 'index'])->name('assign_tasks');
        Route::get('/create-assign-task', [AssignTasksController::class, 'createAssignTasks'])->name('create_assign_task');
        Route::post('/save-task', [AssignTasksController::class, 'saveTasks'])->name('save_task');

        Route::get('/student-approval', [StudentApprovalController::class, 'index'])->name('student_approval');
        Route::get('/student-attendance', [StudentAttendanceController::class, 'index'])->name('student_attendance');
        Route::get('/student-attendance-entry', [StudentAttendanceController::class, 'attendanceEntry'])->name('student_attendance_entry');
        Route::get('/attendance/download', [StudentAttendanceController::class, 'download'])->name('attendance.download');
        Route::any('/attendance/mark', [StudentAttendanceController::class, 'markAttendance'])->name('attendance.mark');
        Route::get('/assign-grade', [AssignGradeController::class, 'index'])->name('assign_grades');
        Route::get('/assign-grade-entry', [AssignGradeController::class, 'gradeEntry'])->name('assign_grade_entry');
        Route::post('/grade-save', [AssignGradeController::class, 'saveGrades'])->name('grade_save');

        Route::get('/review-reports', [ReviewReportsController::class, 'index'])->name('review_reports');
        Route::get('/home', [AdminHomeController::class, 'index'])->name('home');
        Route::get('/reports', [AdminReportsController::class, 'index'])->name('reports');
        Route::get('/create_report', [AdminReportsController::class, 'crearteReport'])->name('create_report');

        //club
        Route::get('/club-list', [ClubsController::class, 'index'])->name('club_list');
        Route::get('/create-club', [ClubsController::class, 'createClub'])->name('create_club');
        Route::post('/save-club', [ClubsController::class, 'saveClub'])->name('save_club');

        //department
        Route::get('/department-list', [DepartmentController::class, 'index'])->name('department_list');
        Route::get('/create-department', [DepartmentController::class, 'createDepartment'])->name('create_department');
        Route::post('/save-department', [DepartmentController::class, 'saveDepartment'])->name('save_department');

        //programme
        Route::get('/programme-list', [ProgrammeController::class, 'index'])->name('programme_list');
        Route::get('/create-programme', [ProgrammeController::class, 'createProgramme'])->name('create_programme');
        Route::post('/save-programme', [ProgrammeController::class, 'saveProgramme'])->name('save_programme');

        //faculty
        Route::get('/faculty-list', [FacultyController::class, 'index'])->name('faculty_list');
        Route::get('/create-faculty', [FacultyController::class, 'createFaculty'])->name('create_faculty');
        Route::post('/save-faculty', [FacultyController::class, 'saveFaculty'])->name('save_faculty');

        //student
        Route::get('/student-list', [StudentController::class, 'index'])->name('student_list');
        Route::get('/create-student', [StudentController::class, 'createStudent'])->name('create_student');
        Route::post('/save-student', [StudentController::class, 'saveStudent'])->name('save_student');

        Route::get('{id}/view-pdf', [AdminReportsController::class, 'viewPdf'])->name('reports_view_pdf');
        Route::get('{id}/download-pdf', [AdminReportsController::class, 'downloadPdf'])->name('reports_download_pdf');

        Route::post('/student-event-approval', [StudentApprovalController::class, 'studentEventApproval'])->name('student_event_approval');

        //Student Upload
        Route::get('/students/download-template', [StudentImportExportController::class, 'downloadTemplate'])
            ->name('students.download.template');
        Route::post('/students/upload', [StudentImportExportController::class, 'uploadStudents'])
            ->name('students.upload');

        //create admins
        Route::get('/admin-list', [AdminController::class, 'index'])->name('admin_list');
        Route::get('/create-admin', [AdminController::class, 'createAdmin'])->name('create_admin');
        Route::post('/save-admin', [AdminController::class, 'saveAdmin'])->name('save_admin');
        Route::any('/logout', [AdminAuthController::class, 'logout'])->name('admin.logout');

        //Admin Upload
        Route::get('/admin/download-template', [AdminImportExportController::class, 'downloadTemplate'])
            ->name('admin.download.template');
        Route::post('/admin/upload', [AdminImportExportController::class, 'uploadAdmin'])
            ->name('admin.upload');
        Route::get('/registered-report-index', [EventRegisterdReportController::class, 'index'])
            ->name('registered_report_index');
        Route::get('/event-registrations/export', [EventRegisterdReportController::class, 'export'])
            ->name('admin.event-registrations.export');
        Route::get('/task-view', [AssignTasksController::class, 'viewTask'])
            ->name('task_view');

        Route::get('/student-event-report', [AssignGradeController::class, 'downloadEventReport'])->name('student_event_report');
        Route::get('/download-otherfiles/{event}/{student}/{schedule_id}', [AssignGradeController::class, 'downloadAll'])->name('download_all');
        Route::post('/events/{id}', [EventsController::class, 'destroy'])
            ->name('events.destroy');

        //promote student
        Route::post('/promote-student', [StudentController::class, 'promoteStudent'])->name('promote_student');

        //credit point assign for programme and semester wise
        Route::get('/credit-point-assign', [CreditPointAssignController::class, 'index'])->name('credit_point_assign');
        Route::post('/save-credit-point', [CreditPointAssignController::class, 'saveCreditPoint'])->name('save_credit_point');
    });
});
