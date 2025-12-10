<?php
use App\Models\HealthDetails;
use App\Models\PrivilegeLevel;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SopController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\FormController;
use App\Http\Controllers\HslbController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\PolicyController;
use App\Http\Controllers\RemarkController;
use App\Http\Controllers\VendorController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RequestController;
use App\Http\Controllers\JobTitleController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\IctAccessController;
use App\Http\Controllers\SignatureController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\HmisAccessController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\DataSecurityController;
use App\Http\Controllers\CcbrtRelationController;
use App\Http\Controllers\ClearanceFormController;
use App\Http\Controllers\HealthDetailsController;
use App\Http\Controllers\HumanResourceController;
use App\Http\Controllers\IdCardRequestController;
use App\Http\Controllers\EmploymentTypeController;
use App\Http\Controllers\PrivilegeLevelController;
use App\Http\Controllers\RequestApproveController;
use App\Http\Controllers\ChangeManagementController;
use App\Http\Controllers\LanguageKnowledgeController;
use App\Http\Controllers\NhifQualificationController;
use App\Http\Controllers\UserFamilyDetailsController;

// use App\Http\Controllers\UserFamilyDetailsController;
// use App\Http\Controllers\UserAdditionalInfoController;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/
Route::get('/', function () {
    return view('auth.login');
});

Route::get('/login', [AuthController::class, 'login'])->name('login');
Route::post('/login', [AuthController::class, 'handleLogin'])->name('login.handleLogin');
Route::get('/register', [AuthController::class, 'register'])->name('register');
Route::post('/register', [AuthController::class, 'handleRegistration'])->name('register.handleRegistration');
Route::get('/logout', [AuthController::class, 'logout'])->name('logout');
Route::post('/check-email', [AuthController::class, 'checkEmail'])->name('check.email');


// Show the forget password form
Route::get('password/forget', [AuthController::class, 'showForgetPasswordForm'])->name('password.forget');
// Handle the form submission to send the password reset link
Route::post('password/forget', [AuthController::class, 'forgetPassChange'])->name('password.forgetPassChange');
Route::get('password/reset/{token}', [AuthController::class, 'showResetPasswordForm'])->name('password.reset');
Route::post('password/reset', [AuthController::class, 'resetPassword'])->name('password.update');


//route for job title based dept id
Route::get('/job-titles/{departmentId}', [AuthController::class, 'getJobTitles']);
Route::get('/departments', [DepartmentController::class, 'index']);

Route::group(['middleware'=> 'auth'], function ()
{
Route::resource('sops', SopController::class);
Route::get('/sop', [SopController::class, 'sops'])->name('sops.show');
Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');


Route::get('/change-password', [AuthController::class, 'showChangePasswordForm'])->name('user_profile.pass');
Route::put('/change-password', [AuthController::class, 'changePassword'])->name('change.password.update');

Route::resource('clearance', ClearanceFormController::class);

Route::resource('policies', PolicyController::class);
Route::get('/user-policies', [PolicyController::class, 'user'])->name('policies.user');
Route::post('/policies/accept', [PolicyController::class, 'accept'])->name('policies.accept');
Route::get('/download-policy', [PolicyController::class, 'downloadPolicy'])->name('download.policy');



Route::get('/family-details', [UserFamilyDetailsController::class, 'index'])->name('family-details.index');
Route::put('/family-details', [UserFamilyDetailsController::class, 'edit'])->name('family-details.edit');
Route::post('/family-details', [UserFamilyDetailsController::class, 'addFamilyData'])->name('family-details.addFamilyData');
Route::delete('/family-details/{id}', [UserFamilyDetailsController::class, 'deleteFamilyData'])->name('family-details.destroy');
Route::put('/family-details/{id}', [UserFamilyDetailsController::class, 'editData'])->name('family-details.editData');
Route::get('/family-details/{id}/edit', [UserFamilyDetailsController::class, 'edit'])->name('family-details.edit');

Route::get('/health-details', [HealthDetailsController::class, 'index'])->name('health-details.index');
Route::post('/health', [HealthDetailsController::class, 'addHealthData'])->name('health-details.addHealthData');
Route::put('/health-details/{id}', [HealthDetailsController::class, 'update'])->name('health-details.update');
Route::delete('/health-details/{id}', [HealthDetailsController::class, 'deleteHealthData'])->name('health-details.delete');

Route::get('/ccbrt_relation', [CcbrtRelationController::class, 'index'])->name('ccbrt_relation.index');
Route::put('/ccbrt_relation/{id}', [CcbrtRelationController::class, 'update'])->name('ccbrt_relation.update');
Route::delete('/ccbrt_relation/{id}', [CcbrtRelationController::class, 'destroy'])->name('ccbrt_relation.destroy');
Route::post('/relation', [CcbrtRelationController::class, 'addRelationData'])->name('ccbrt_relation.addRelationData');
Route::get('/health-details/{id}/edit', [HealthDetailsController::class, 'edit'])->name('health-details.edit');
Route::put('/ccbrt_relation/{id}', [CcbrtRelationController::class, 'update'])->name('ccbrt_relation.update');

Route::get('language-knowledge', [LanguageKnowledgeController::class, 'index'])->name('language_knowledge.index');
Route::post('language-knowledge', [LanguageKnowledgeController::class, 'addLanguageKnowledge'])->name('language_knowledge.add');
Route::get('language-knowledge/{id}/edit', [LanguageKnowledgeController::class, 'edit'])->name('language_knowledge.edit');
Route::put('language-knowledge/{id}', [LanguageKnowledgeController::class, 'update'])->name('language_knowledge.update');
Route::delete('language-knowledge/{id}', [LanguageKnowledgeController::class, 'destroy'])->name('language_knowledge.destroy');

Route::resource('job_titles', JobTitleController::class);

Route::post('/signature', [SignatureController::class, 'store']);
Route::post('/save-signature', [SignatureController::class, 'store'])->name('signature.store');
Route::get('/signature', [SignatureController::class, 'index'])->name('signature.index');

Route::get('/signature/{id}/edit', [SignatureController::class, 'edit'])->name('signature.edit');
Route::delete('/signature/{id}', [SignatureController::class, 'destroy'])->name('signature.destroy');
Route::get('/all-users-signatures', [SignatureController::class, 'showUsersWithSignatures'])->name('users.signatures');

Route::resource('/profile', ProfileController::class);
Route::middleware(['auth'])->post('profile/update-password', [ProfileController::class, 'updatePassword'])->name('profile.update.password');
Route::post('picture', [ProfileController::class, 'updateProfilePicture'])->name('profile.update.picture');
Route::get('/profile/edit/{id}', [ProfileController::class, 'edit'])->name('profile.edit');
Route::put('/profile/{id}', [ProfileController::class, 'update'])->name('profile.update');

//ict-access controller
Route::resource('/form', IctAccessController::class);
Route::resource('/hr', HumanResourceController::class);
Route::resource('/data', DataSecurityController::class);
Route::resource('/change', ChangeManagementController::class);
Route::resource('/department', DepartmentController::class);
Route::resource('/nhif', NhifQualificationController::class);
Route::resource('/hmis', HmisAccessController::class);
Route::resource('/remark', RemarkController::class);
Route::resource('/privilege', PrivilegeLevelController::class);
Route::resource('/employment', EmploymentTypeController::class);
Route::resource('/role',RoleController::class);
Route::resource('/permission',PermissionController::class);
Route::resource('/request',RequestController::class);
Route::resource('/requestapprove',RequestApproveController::class);
Route::resource('/card',IdCardRequestController::class);
Route::resource('/hslb',HslbController::class);
Route::post('hslb/hr-confirm/{id}', [HslbController::class, 'hrConfirm'])->name('hslb.hrConfirm');


Route::get('/users', [AuthController::class, 'getAllUser'])->name('users.index');
Route::delete('/users/{id}', [AuthController::class, 'destroy'])->name('users.destroy');
Route::get('/employees', [AuthController::class, 'userDetail'])->name('employee.index');
Route::put('/employee/{id}', [AuthController::class, 'update'])->name('employee.update');

Route::resource('vendors', VendorController::class);

//for user deatils
Route::get('/employee/{id}', [AuthController::class, 'employee'])->name('employees_details.show');
Route::get('/employee/{id}/edit', [AuthController::class, 'changedept'])->name('employees_details.edit');
Route::put('/employee/{id}', [AuthController::class, 'update'])->name('employees_details.update');

Route::get('/users/{id}/edit', [AuthController::class, 'showEditForm'])->name('users.showEditForm');
Route::post('/users/{id}/edit', [AuthController::class, 'editUserRole'])->name('users.edit');
Route::put('/users/{id}/destroy', [AuthController::class, 'destroyUserRole'])->name('users.destroy');
// Route::put('users/{id}', [AuthController::class, 'update'])->name('users.update');
Route::post('users/{id}/role', [AuthController::class, 'editUserRole'])->name('users.edit.role');
Route::delete('/permission/{id}', 'PermissionController@destroy')->name('permission.delete');
Route::post('roles/permissions', [PermissionController::class, 'updateRolePermissions'])->name('role.updatePermissions');
Route::get('role/{roleId}/permissions', [PermissionController::class, 'getRolePermissions']);


Route::post('/approve_form', [FormController::class, 'approveForm'])->name('approve_form');
Route::get('/approver_form', [FormController::class, 'getApprover'])->name('approver_form');
Route::get('/show_form/{id}', [FormController::class, 'getForm']);
Route::post('/reject_form', [FormController::class, 'rejectForm']);


Route::get('/announcements', [AnnouncementController::class, 'index'])->name('announcements.index');
Route::get('/announcements/create', [AnnouncementController::class, 'create'])->name('announcements.create');
Route::post('/announcements', [AnnouncementController::class, 'store'])->name('announcements.store');
Route::get('/announcements/{id}/edit', [AnnouncementController::class, 'edit'])->name('announcements.edit');
Route::put('/announcements/{id}', [AnnouncementController::class, 'update'])->name('announcements.update');
Route::delete('/announcements/{id}', [AnnouncementController::class, 'destroy'])->name('announcements.destroy');

Route::put('/ict-access-form/update/{id}', [RequestController::class, 'updateIctForm'])->name('form.updateIctForm');

Route::post('/approve_clearform', [FormController::class, 'approveClearanceForm'])->name('approve_clearform');
Route::get('/exit_forms/approvers', [ClearanceFormController::class, 'getApprover'])->name('exit_forms.approvers');
Route::get('/exit_forms/{id}', [FormController::class, 'getClearance']);
Route::post('/exit_forms/{id}/reject', [ClearanceFormController::class, 'rejectForm'])->name('exit_forms.reject');

// Route for Clearance Forms View
Route::get('/clearance_forms/{id}', [FormController::class, 'getClearance']);

//Get Clearance in my request view
Route::get('/clearance/edit/{id}', [RequestController::class, 'editClearance'])->name('clearance.edit');
Route::put('/clearance/update/{id}', [RequestController::class, 'update'])->name('clearance.update');

Route::get('role-permission/{roleId}/give-permission', [RoleController::class, 'addPermissionToRole']);
Route::put('role-permission/{roleId}/give-permission', [RoleController::class, 'givePermissionToRole']);


});
