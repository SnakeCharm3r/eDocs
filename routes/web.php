<?php

use App\Models\HealthDetails;
use App\Models\PrivilegeLevel;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HecController;
use App\Http\Controllers\SopController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\FormController;
use App\Http\Controllers\HslbController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\IDCardController;
use App\Http\Controllers\PolicyController;
use App\Http\Controllers\DepartmentPolicyController;
use App\Http\Controllers\RemarkController;
use App\Http\Controllers\VendorController;
use App\Http\Controllers\Procurements\VendorsController;
use App\Http\Controllers\Procurements\ContractsController;
use App\Http\Controllers\HecContractsController;
use App\Http\Controllers\BioUserController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RequestController;
use App\Http\Controllers\ContractController;
use App\Http\Controllers\DivisionController;
use App\Http\Controllers\JobTitleController;
use App\Http\Controllers\PlatformController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\IctAccessController;
use App\Http\Controllers\SapAccessController;
use App\Http\Controllers\SignatureController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\HmisAccessController;
use App\Http\Controllers\HrRequestsController;
use App\Http\Controllers\ItRequestsController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\BankDetailsController;
use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\ContractualHoursController;
use App\Http\Controllers\DataSecurityController;
use App\Http\Controllers\DeptPlatformController;
use App\Http\Controllers\LocumRequestController;
use App\Http\Controllers\NightShiftController;
use App\Http\Controllers\PlatformUnitController;
use App\Http\Controllers\ShiftSettingController;
use App\Illuminate\Controllers\hrDocumentUpload;
use App\Http\Controllers\CcbrtRelationController;
use App\Http\Controllers\ChangeRequestController;
use App\Http\Controllers\ClearanceFormController;
use App\Http\Controllers\TariffCategoryController;
use App\Http\Controllers\ServiceCategoryController;
use App\Http\Controllers\PaymentTypeController;
use App\Http\Controllers\HealthDetailsController;
use App\Http\Controllers\HumanResourceController;
use App\Http\Controllers\IdCardRequestController;
use App\Http\Controllers\OnCallRequestController;
use App\Http\Controllers\WorkflowManagementController;
use App\Http\Controllers\EmploymentTypeController;
use App\Http\Controllers\JobDescriptionController;
use App\Http\Controllers\LocumAgreementController;
use App\Http\Controllers\LocumRateController;
use App\Http\Controllers\OnCallRateController;
use App\Http\Controllers\PrivilegeLevelController;
use App\Http\Controllers\RequestApproveController;
use App\Http\Controllers\VendorContractController;
use App\Http\Controllers\LoanDeclarationController;
use App\Http\Controllers\ChangeManagementController;
use App\Http\Controllers\ContractTemplateController;
use App\Http\Controllers\NhifRegistrationController;
use App\Http\Controllers\FixedFlexContractController;
use App\Http\Controllers\LanguageKnowledgeController;
use App\Http\Controllers\NhifQualificationController;
use App\Http\Controllers\UserFamilyDetailsController;
use App\Http\Controllers\UserAdditionalInfoController;
use App\Http\Controllers\DepartmentPlatformController;
use App\Http\Controllers\ArutiLevelController;
use App\Http\Controllers\EdocsLevelController;
use App\Http\Controllers\NetworkFolderController;
use App\Http\Controllers\AccessKeyCardController;
use App\Http\Controllers\RequisitionController;


Route::get('/', function () {
    return view('auth.login');
});

Route::get('/login', [AuthController::class, 'login'])->name('login');
Route::post('/login', [AuthController::class, 'handleLogin'])->name('login.handleLogin');
Route::get('/register', [AuthController::class, 'register'])->name('register');
Route::post('/register', [AuthController::class, 'handleRegistration'])->name('register.handleRegistration');
Route::get('/logout', [AuthController::class, 'logout'])->name('logout');
Route::post('/check-email', [AuthController::class, 'checkEmail'])->name('check.email');
Route::get('/check-session', [AuthController::class, 'checkSession'])->name('session.check');

// Show the forget password
Route::get('password/forget', [AuthController::class, 'showForgetPasswordForm'])->name('password.forget');
// Handle the form submission to send the password reset link
Route::post('password/forget', [AuthController::class, 'forgetPassChange'])->name('password.forgetPassChange');
Route::get('password/reset/{token}', [AuthController::class, 'showResetPasswordForm'])->name('password.reset');
Route::post('password/reset', [AuthController::class, 'resetPassword'])->name('password.update');
Route::post('users/{user}/reset-password', [AuthController::class, 'adminResetPassword'])->name('users.reset-password');
Route::post('users/{user}/change-password', [AuthController::class, 'adminChangeUserPassword'])->name('users.change-password');

//route for job title based dept id
Route::get('/job-titles/{departmentId}', [AuthController::class, 'getJobTitles']);
Route::get('/departments', [DepartmentController::class, 'index']);

// API route for user details (for clearance form)
Route::get('/api/user-details/{id}', [ClearanceFormController::class, 'getUserDetails'])->middleware('auth');
Route::get('/departments/locum-settings', [DepartmentController::class, 'locumSettings'])->name('departments.locum-settings')->middleware('permission:view locum settings');
Route::patch('/departments/locum-settings', [DepartmentController::class, 'updateLocumSettings'])->name('departments.update-locum-settings')->middleware('permission:manage locum settings');
Route::get('/departments/oncall-settings', [DepartmentController::class, 'oncallSettings'])->name('departments.oncall-settings')->middleware('permission:view oncall settings');
Route::patch('/departments/oncall-settings', [DepartmentController::class, 'updateOncallSettings'])->name('departments.update-oncall-settings')->middleware('permission:manage oncall settings');

Route::group(['middleware' => 'auth'], function () {
    Route::post('/switch-role', [AuthController::class, 'switchRole'])->name('switch-role');
    Route::post('/clear-active-role', [AuthController::class, 'clearActiveRole'])->name('clear-active-role');

    Route::middleware(['auth'])->group(function () {
        Route::get('/profile/personal-details', [AuthController::class, 'personalDetails'])->name('profile.personalDetails');
        Route::post('/profile/personal-details', [AuthController::class, 'savePersonalDetails']);
        Route::post('/profile/check-ccbrt-code', [AuthController::class, 'checkCcbrtCode'])->name('profile.checkCcbrtCode');

        Route::get('/profile/family-details', [AuthController::class, 'familyDetails'])->name('profile.familyDetails');
        Route::post('/profile/family-details', [AuthController::class, 'saveFamilyDetails'])->name('profile.saveFamilyDetails');
        Route::get('/profile/family-details/edit/{id}', [AuthController::class, 'editFamilyDetails'])->name('profile.editFamilyDetails');
        Route::put('/profile/family-details/{id}', [AuthController::class, 'updateFamilyDetails'])->name('profile.updateFamilyDetails');
        Route::delete('/profile/family-details/{id}', [AuthController::class, 'deleteFamilyDetail'])->name('profile.deleteFamilyDetail');


        Route::get('/profile/health-details', [AuthController::class, 'healthDetails'])->name('profile.healthDetails');
        // Route::post('/profile/health-details', [AuthController::class, 'saveHealthDetails']);
        Route::post('profile/health-details', [AuthController::class, 'saveHealthDetails'])->name('profile.saveHealthDetails');
        Route::get('/profile/confirm', [AuthController::class, 'confirmView'])->name('profile.confirm');

        // Routes for the language knowledge actions
        Route::get('/profile/languageKnowledge', [AuthController::class, 'languageKnowledge'])->name('profile.languageKnowledge');
        Route::get('/languageKnowledge', [AuthController::class, 'languageKnowledge'])->name('languageKnowledge');
        Route::post('languageKnowledgeStore', [AuthController::class, 'saveLanguageKnowledge'])->name('languageKnowledgeStore');
        Route::get('/profile/languageKnowledge/edit/{id}', [AuthController::class, 'edit'])->name('profile.languageKnowledge.edit');
        Route::put('/profile/languageKnowledge/{id}', [AuthController::class, 'updateLanguage'])->name('profile.languageKnowledge.update');


        // Routes for CCBRT relation
        Route::get('profile/ccbrt_relation', [AuthController::class, 'ccbrtrelation'])->name('profile.ccbrt_relation');
        Route::post('profile/ccbrt_relation', [AuthController::class, 'addRelationData'])->name('profile.addRelationData');
        Route::get('profile/ccbrt_relation/edit/{id}', [AuthController::class, 'editRelation'])->name('profile.editRelation');
        Route::put('profile/ccbrt_relation/{id}', [AuthController::class, 'updateRelationData'])->name('profile.updateRelation');
        Route::delete('profile/ccbrt_relation/{id}', [AuthController::class, 'deleteRelation'])->name('profile.deleteRelation');

        //interest
        Route::get('/conflict-interest', [AuthController::class, 'showConflictOfInterestForm'])->name('conflict-interest.viewit');
        Route::post('/conflict-interest', [AuthController::class, 'storeConflictOfInterest'])->name('conflict.store');

        Route::post('/signature', [SignatureController::class, 'store'])->middleware('auth');
        Route::post('/save-signature', [SignatureController::class, 'store'])->name('signature.store')->middleware('auth');
        Route::get('/signature', [SignatureController::class, 'index'])->name('signature.index')->middleware('auth');

        Route::get('/signature/{id}/edit', [SignatureController::class, 'edit'])->name('signature.edit')->middleware('permission:manage signatures');
        Route::delete('/signature/{id}', [SignatureController::class, 'destroy'])->name('signature.destroy')->middleware('permission:manage signatures');
        Route::get('/all-users-signatures', [SignatureController::class, 'showUsersWithSignatures'])->name('users.signatures')->middleware('permission:view signatures');
        Route::get('/hr_form/{id}', [SignatureController::class, 'showHrForm'])->name('hr_form')->middleware('permission:view signatures|ict_acces_report|view my requests');
        Route::get('/bank_form/{id}', [SignatureController::class, 'showBankForm'])->name('bank_form')->middleware('permission:view signatures|ict_acces_report|view my requests');
        Route::post('/bank_form_confirm', [SignatureController::class, 'approveBankForm'])->name('bank_form_confirm')->middleware('permission:approve signatures');
        Route::post('/bank_form_reject', [SignatureController::class, 'rejectBankForm'])->name('bank_form_reject')->middleware('permission:reject signatures');
        Route::get('/heslb_form/{id}', [SignatureController::class, 'showHeslbkForm'])->name('heslb_form')->middleware('permission:view signatures|ict_acces_report|view my requests');
        Route::post('/heslb_form_confirm', [SignatureController::class, 'approveHeslbForm'])->name('heslb_form_confirm')->middleware('permission:approve signatures');
        Route::post('/heslb_form_reject', [SignatureController::class, 'rejectHeslbForm'])->name('heslb_form_reject')->middleware('permission:reject signatures');
        Route::get('/nhif_form/{id}', [SignatureController::class, 'showNhifForm'])->name('nhif_form')->middleware('permission:view signatures|ict_acces_report|view my requests');
        Route::post('/nhif_form_confirm', [SignatureController::class, 'approveNhifForm'])->name('nhif_form_confirm')->middleware('permission:approve signatures');
        Route::post('/nhif_form_reject', [SignatureController::class, 'rejectNhifForm'])->name('nhif_form_reject')->middleware('permission:reject signatures');
        Route::get('/id_form/{id}', [SignatureController::class, 'showIdForm'])->name('id_form')->middleware('permission:view signatures|ict_acces_report|view my requests');
        Route::post('/id_form_confirm', [SignatureController::class, 'approveIdForm'])->name('id_form_confirm')->middleware('permission:approve signatures');
        Route::post('/id_form_reject', [SignatureController::class, 'rejectIdForm'])->name('id_form_reject')->middleware('permission:reject signatures');
        Route::post('/hr_form_approve/{id}', [SignatureController::class, 'approveHrForm'])->name('hr_form_approve')->middleware('permission:approve signatures');
        Route::post('/hr_form_verify_professional_reg', [SignatureController::class, 'verifyProfessionalRegistration'])->name('hr_form.verify_professional_reg')->middleware('permission:approve signatures');
        Route::get('/submit_to_hr', [SignatureController::class, 'submitRequest'])->name('hr.send')->middleware('auth');
        Route::post('/hr_form_reject', [SignatureController::class, 'hrformReject'])->name('hr_form_reject')->middleware('permission:reject signatures');
        // Change Request routes - specific routes must come before catch-all routes
        Route::get('/change-request', function () {
            return redirect()->route('change_request.create');
        })->name('change_request')->middleware('permission:view change management');
        Route::get('/change-request/report', [ChangeRequestController::class, 'index'])->name('change_request.index')->middleware('permission:view change management');
        Route::get('/change-request/create', [ChangeRequestController::class, 'create'])->name('change_request.create')->middleware('permission:view change management');
        Route::post('/change-request', [ChangeRequestController::class, 'store'])->name('change_request.store')->middleware('permission:view change management');
        Route::post('/change-request/{id}/approve', [ChangeRequestController::class, 'approve'])->name('change_request.approve')->middleware('auth');
        Route::post('/change-reject/{id}', [ChangeRequestController::class, 'rejectChangeRequest'])->name('change_request.reject')->middleware('auth');
        Route::get('/change-request/{id}', [SignatureController::class, 'show'])->name('change_request.show')->middleware('auth');
        Route::get('/change-request/{id}/pdf', [ChangeRequestController::class, 'showPdf'])->name('change_request.pdf')->middleware('auth');
        // Legacy routes - kept for backward compatibility (using different names to avoid conflicts)
        Route::post('/change-request/{id}/approve-legacy', [SignatureController::class, 'approveAndForwardToPriceCommittee'])
            ->name('change_request.approve.legacy')->middleware('permission:approve forms');
        Route::post('/send_to_hec', [SignatureController::class, 'sendToHec'])
            ->name('change_request.send_to_hec')->middleware('permission:approve forms');

        Route::resource('sops', SopController::class)->middleware('role_or_permission:view sops|view all sops');
        Route::post('/sops/{id}/archive', [SopController::class, 'archive'])->name('sops.archive')->middleware('role_or_permission:view sops|view all sops');
        Route::post('/sops/{id}/restore', [SopController::class, 'restore'])->name('sops.restore')->middleware('role_or_permission:view sops|view all sops');
        Route::get('/sops-expiring', [SopController::class, 'expiring'])->name('sops.expiring')->middleware('role_or_permission:view sops|view all sops');
        Route::get('/my-sops', [SopController::class, 'mySops'])->name('sops.my-sops')->middleware('role_or_permission:view sops|view all sops');
        Route::get('/sops/departments-by-division/{divisionId}', [SopController::class, 'getDepartmentsByDivision'])->name('sops.departments-by-division')->middleware('role_or_permission:view sops|view all sops');
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('/review-dashboard', [DashboardController::class, 'reviewDashboard'])->name('review-dashboard');

        Route::get('/change-password', [AuthController::class, 'showChangePasswordForm'])->name('user_profile.pass');
        Route::put('/change-password', [AuthController::class, 'changePassword'])->name('change.password.update');

        // Clearance form routes - only for users submitting their own forms
        Route::get('/clearance', [ClearanceFormController::class, 'index'])->name('clearance.index')->middleware('role_or_permission:coo|super-admin|access clearance form|ict_acces_report');
        Route::get('/clearance/export', [ClearanceFormController::class, 'exportExcel'])->name('clearance.export')->middleware('role_or_permission:coo|super-admin|ict_acces_report');
        Route::get('/clearance/create', [ClearanceFormController::class, 'create'])->name('clearance.create')->middleware('permission:access clearance form');
        Route::match(['get', 'post'], '/clearance/review', [ClearanceFormController::class, 'review'])->name('clearance.review')->middleware('permission:access clearance form');
        Route::post('/clearance', [ClearanceFormController::class, 'store'])->name('clearance.store')->middleware('permission:access clearance form');
        Route::get('/clearance/{id}', [ClearanceFormController::class, 'show'])->name('clearance.show')->middleware('role_or_permission:coo|super-admin|access clearance form|view clearance forms|ict_acces_report');
        Route::delete('/clearance/{id}', [ClearanceFormController::class, 'destroy'])->name('clearance.destroy')->middleware('permission:access clearance form');

        // Staff signed policies: all authenticated users can view (no permission required)
        Route::get('/staff-signed-policies', [PolicyController::class, 'staffSignedIndex'])->name('policies.staff-signed')->middleware('auth');

        // Override resource routes to use {id} instead of {policy} for edit, update, show, destroy
        Route::get('/policies', [PolicyController::class, 'index'])->name('policies.index')->middleware('permission:view policies');
        Route::get('/policies/create', [PolicyController::class, 'create'])->name('policies.create')->middleware('permission:view policies');
        Route::post('/policies', [PolicyController::class, 'store'])->name('policies.store')->middleware('permission:view policies');
        Route::post('/policies/other-organization-email-notifications', [PolicyController::class, 'updateOtherOrgEmailNotifications'])->name('policies.other-org-email-notifications.update')->middleware('permission:view policies');
        Route::get('/policies/{id}', [PolicyController::class, 'show'])->name('policies.show')->middleware('permission:view policies');
        Route::post('/policies/{id}/record-view', [PolicyController::class, 'recordView'])->name('policies.record-view')->middleware('permission:view policies');
        Route::get('/policies/{id}/edit', [PolicyController::class, 'edit'])->name('policies.edit')->middleware('permission:view policies');
        Route::put('/policies/{id}', [PolicyController::class, 'update'])->name('policies.update')->middleware('permission:view policies');
        Route::delete('/policies/{id}', [PolicyController::class, 'destroy'])->name('policies.destroy')->middleware('permission:view policies');
        Route::post('/policies/{id}/archive', [PolicyController::class, 'archive'])->name('policies.archive')->middleware('permission:view policies');
        Route::post('/policies/{id}/restore', [PolicyController::class, 'restore'])->name('policies.restore')->middleware('permission:view policies');
        Route::get('/policies/departments-by-division/{divisionId}', [PolicyController::class, 'getDepartmentsByDivision'])->name('policies.departments-by-division')->middleware('permission:view policies');
        Route::get('/policy-categories/next-section', [\App\Http\Controllers\PolicyCategoryController::class, 'nextSectionNumber'])->name('policy-categories.next-section')->middleware('permission:view policies');
        Route::post('/policy-categories', [\App\Http\Controllers\PolicyCategoryController::class, 'store'])->name('policy-categories.store')->middleware('permission:view policies');
        Route::put('/policy-categories/{category}', [\App\Http\Controllers\PolicyCategoryController::class, 'update'])->name('policy-categories.update')->middleware('permission:view policies');
        Route::delete('/policy-categories/{category}', [\App\Http\Controllers\PolicyCategoryController::class, 'destroy'])->name('policy-categories.destroy')->middleware('permission:view policies');

        // Department policies (line manager managed, PDF only, max 5MB) - visible to anyone with view policies (e.g. admin)
        Route::get('/department-policies', [DepartmentPolicyController::class, 'index'])->name('department-policies.index')->middleware('permission:view policies');
        Route::get('/department-policies/create', [DepartmentPolicyController::class, 'create'])->name('department-policies.create')->middleware('permission:view policies');
        Route::post('/department-policies', [DepartmentPolicyController::class, 'store'])->name('department-policies.store')->middleware('permission:view policies');
        Route::get('/department-policies/{id}', [DepartmentPolicyController::class, 'show'])->name('department-policies.show')->middleware('permission:view policies');
        Route::get('/department-policies/{id}/edit', [DepartmentPolicyController::class, 'edit'])->name('department-policies.edit')->middleware('permission:view policies');
        Route::put('/department-policies/{id}', [DepartmentPolicyController::class, 'update'])->name('department-policies.update')->middleware('permission:view policies');
        Route::delete('/department-policies/{id}', [DepartmentPolicyController::class, 'destroy'])->name('department-policies.destroy')->middleware('permission:view policies');

        // Legacy routes for backward compatibility
        Route::get('/create-department', [PolicyController::class, 'createDepartmentPolicy'])->name('policies.create-department');
        Route::post('/policies/department', [PolicyController::class, 'storeDepartmentPolicy'])->name('policies.store-department');
        Route::get('policies/{id}/edit-department', [PolicyController::class, 'editDepartmentPolicy'])->name('policies.edit-department');
        Route::put('policies/{id}/update-department', [PolicyController::class, 'updateDepartmentPolicy'])->name('policies.update-department');
        Route::delete('department-policies/{departmentPolicy}/destroy-department', [PolicyController::class, 'destroydept'])->name('department-policies.destroy-department');

        Route::get('/user-policies', [PolicyController::class, 'user'])->name('policies.user');
        Route::post('/policies/accept', [PolicyController::class, 'accept'])->name('policies.accept');
        Route::get('/user/{id}/download-policy', [PolicyController::class, 'downloadPolicy'])->name('user.policy.download');
        Route::get('/user/{id}/download-policies', [PolicyController::class, 'downloadMultiplePolicies'])->name('user.policies.download');
        Route::get('/user/{id}/preview-policies', [PolicyController::class, 'previewMultiplePolicies'])->name('user.policies.preview');
        Route::get('/privacy-policy', [PolicyController::class, 'index1']);

        Route::post('/profile/next-of-kin', [UserAdditionalInfoController::class, 'addNextOfKins'])->name('profile.next-of-kin.add');
        Route::get('/family-details', [UserFamilyDetailsController::class, 'index'])->name('family-details.index');
        Route::post('/family-details', [UserFamilyDetailsController::class, 'addFamilyData'])->name('family-details.addFamilyData');
        Route::delete('/family-details/{id}', [UserFamilyDetailsController::class, 'deleteFamilyData'])->name('family-details.destroy');
        Route::put('/family-details/{id}', [UserFamilyDetailsController::class, 'editData'])->name('family-details.editData');

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

        Route::resource('job_titles', JobTitleController::class)->middleware('permission:job title');
        Route::resource('/profile', ProfileController::class);
        Route::middleware(['auth'])->post('profile/update-password', [ProfileController::class, 'updatePassword'])->name('profile.update.password');
        Route::post('picture', [ProfileController::class, 'updateProfilePicture'])->name('profile.update.picture');
        Route::get('/profile/edit/{id}', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::delete('/profile/{user}/picture', [ProfileController::class, 'deletePicture'])->name('profile.picture.delete');
        Route::put('/profile/{id}', [ProfileController::class, 'update'])->name('profile.update');

        Route::get('/announcements/fetch', [DashboardController::class, 'fetch'])->name('announcements.fetch');

        //education
        Route::get('/education-details', [AuthController::class, 'showEducationDetails'])->name('education.details');
        Route::post('/education-details', [AuthController::class, 'updateEducationDetails'])->name('education.update');
        // Route to delete profile picture
        Route::delete('/profile/picture', [ProfileController::class, 'deleteProfilePicture'])->name('profile.delete.picture');

        //ict-access controller
        Route::resource('/form', IctAccessController::class)->middleware('role_or_permission:line-manager|view ict access form|view it requests');
        Route::resource('/hr', HumanResourceController::class)->middleware('permission:view hr clearance form');
        Route::resource('/data', DataSecurityController::class)->middleware('permission:view data security agreement');
        Route::resource('/change', ChangeManagementController::class)->middleware('permission:view change management');
        Route::resource('/department', DepartmentController::class)->middleware('permission:view departments');
        Route::get('/departments/deleted', [DepartmentController::class, 'deleted'])->name('departments.deleted')->middleware('permission:view departments');
        Route::delete('/departments/{id}/force-delete', [DepartmentController::class, 'forceDelete'])->name('departments.force-delete')->middleware('permission:view departments');
        Route::get('/nhif', [NhifQualificationController::class, 'index'])->name('nhif.index')->middleware('permission:view nhif');
        Route::get('/nhif/create', [NhifQualificationController::class, 'create'])->name('nhif.create')->middleware('role_or_permission:Super-Admin|super-admin|it|view nhif');
        Route::post('/nhif', [NhifQualificationController::class, 'store'])->name('nhif.store')->middleware('role_or_permission:Super-Admin|super-admin|it|view nhif');
        Route::get('/nhif/{id}/edit', [NhifQualificationController::class, 'edit'])->name('nhif.edit')->middleware('role_or_permission:Super-Admin|super-admin|it|view nhif');
        Route::put('/nhif/{id}', [NhifQualificationController::class, 'update'])->name('nhif.update')->middleware('role_or_permission:Super-Admin|super-admin|it|view nhif');
        Route::delete('/nhif/{id}', [NhifQualificationController::class, 'destroy'])->name('nhif.destroy')->middleware('role_or_permission:Super-Admin|super-admin|it|view nhif');
        Route::get('/hmis', [HmisAccessController::class, 'index'])->name('hmis.index')->middleware('permission:view hmis');
        Route::get('/hmis/create', [HmisAccessController::class, 'create'])->name('hmis.create')->middleware('role_or_permission:Super-Admin|super-admin|it|view hmis');
        Route::post('/hmis', [HmisAccessController::class, 'store'])->name('hmis.store')->middleware('role_or_permission:Super-Admin|super-admin|it|view hmis');
        Route::get('/hmis/{id}/edit', [HmisAccessController::class, 'edit'])->name('hmis.edit')->middleware('role_or_permission:Super-Admin|super-admin|it|view hmis');
        Route::put('/hmis/{id}', [HmisAccessController::class, 'update'])->name('hmis.update')->middleware('role_or_permission:Super-Admin|super-admin|it|view hmis');
        Route::delete('/hmis/{id}', [HmisAccessController::class, 'destroy'])->name('hmis.destroy')->middleware('role_or_permission:Super-Admin|super-admin|it|view hmis');
        Route::resource('/remark', RemarkController::class)->middleware('permission:view remarks');
        Route::get('/privilege', [PrivilegeLevelController::class, 'index'])->name('privilege.index')->middleware('permission:view user category');
        Route::get('/privilege/create', [PrivilegeLevelController::class, 'create'])->name('privilege.create')->middleware('role_or_permission:Super-Admin|super-admin|it|view user category');
        Route::post('/privilege', [PrivilegeLevelController::class, 'store'])->name('privilege.store')->middleware('role_or_permission:Super-Admin|super-admin|it|view user category');
        Route::get('/privilege/{id}/edit', [PrivilegeLevelController::class, 'edit'])->name('privilege.edit')->middleware('role_or_permission:Super-Admin|super-admin|it|view user category');
        Route::put('/privilege/{id}', [PrivilegeLevelController::class, 'update'])->name('privilege.update')->middleware('role_or_permission:Super-Admin|super-admin|it|view user category');
        Route::delete('/privilege/{id}', [PrivilegeLevelController::class, 'destroy'])->name('privilege.destroy')->middleware('role_or_permission:Super-Admin|super-admin|it|view user category');

        // ARUT Levels Management
        Route::get('/aruti', [ArutiLevelController::class, 'index'])->name('aruti.index')->middleware('permission:view user category');
        Route::get('/aruti/create', [ArutiLevelController::class, 'create'])->name('aruti.create')->middleware('role_or_permission:Super-Admin|super-admin|it|view user category');
        Route::post('/aruti', [ArutiLevelController::class, 'store'])->name('aruti.store')->middleware('role_or_permission:Super-Admin|super-admin|it|view user category');
        Route::get('/aruti/{id}/edit', [ArutiLevelController::class, 'edit'])->name('aruti.edit')->middleware('role_or_permission:Super-Admin|super-admin|it|view user category');
        Route::put('/aruti/{id}', [ArutiLevelController::class, 'update'])->name('aruti.update')->middleware('role_or_permission:Super-Admin|super-admin|it|view user category');
        Route::delete('/aruti/{id}', [ArutiLevelController::class, 'destroy'])->name('aruti.destroy')->middleware('role_or_permission:Super-Admin|super-admin|it|view user category');

        // eDocs Levels Management
        Route::get('/edocs', [EdocsLevelController::class, 'index'])->name('edocs.index')->middleware('permission:view user category');
        Route::get('/edocs/create', [EdocsLevelController::class, 'create'])->name('edocs.create')->middleware('role_or_permission:Super-Admin|super-admin|it|view user category');
        Route::post('/edocs', [EdocsLevelController::class, 'store'])->name('edocs.store')->middleware('role_or_permission:Super-Admin|super-admin|it|view user category');
        Route::get('/edocs/{id}/edit', [EdocsLevelController::class, 'edit'])->name('edocs.edit')->middleware('role_or_permission:Super-Admin|super-admin|it|view user category');
        Route::put('/edocs/{id}', [EdocsLevelController::class, 'update'])->name('edocs.update')->middleware('role_or_permission:Super-Admin|super-admin|it|view user category');
        Route::delete('/edocs/{id}', [EdocsLevelController::class, 'destroy'])->name('edocs.destroy')->middleware('role_or_permission:Super-Admin|super-admin|it|view user category');

        // Network Folders Management
        Route::get('/network-folder', [NetworkFolderController::class, 'index'])->name('network-folder.index')->middleware('permission:view user category');
        Route::get('/network-folder/create', [NetworkFolderController::class, 'create'])->name('network-folder.create')->middleware('role_or_permission:Super-Admin|super-admin|it|view user category');
        Route::post('/network-folder', [NetworkFolderController::class, 'store'])->name('network-folder.store')->middleware('role_or_permission:Super-Admin|super-admin|it|view user category');
        Route::get('/network-folder/{id}/edit', [NetworkFolderController::class, 'edit'])->name('network-folder.edit')->middleware('role_or_permission:Super-Admin|super-admin|it|view user category');
        Route::put('/network-folder/{id}', [NetworkFolderController::class, 'update'])->name('network-folder.update')->middleware('role_or_permission:Super-Admin|super-admin|it|view user category');
        Route::delete('/network-folder/{id}', [NetworkFolderController::class, 'destroy'])->name('network-folder.destroy')->middleware('role_or_permission:Super-Admin|super-admin|it|view user category');

        // Access Key Cards Management
        Route::get('/access-key-card', [AccessKeyCardController::class, 'index'])->name('access-key-card.index')->middleware('permission:view user category');
        Route::get('/access-key-card/create', [AccessKeyCardController::class, 'create'])->name('access-key-card.create')->middleware('role_or_permission:Super-Admin|super-admin|it|view user category');
        Route::post('/access-key-card', [AccessKeyCardController::class, 'store'])->name('access-key-card.store')->middleware('role_or_permission:Super-Admin|super-admin|it|view user category');
        Route::get('/access-key-card/{id}/edit', [AccessKeyCardController::class, 'edit'])->name('access-key-card.edit')->middleware('role_or_permission:Super-Admin|super-admin|it|view user category');
        Route::put('/access-key-card/{id}', [AccessKeyCardController::class, 'update'])->name('access-key-card.update')->middleware('role_or_permission:Super-Admin|super-admin|it|view user category');
        Route::delete('/access-key-card/{id}', [AccessKeyCardController::class, 'destroy'])->name('access-key-card.destroy')->middleware('role_or_permission:Super-Admin|super-admin|it|view user category');
        Route::resource('/employment', EmploymentTypeController::class)->middleware('permission:view employment type');
        Route::resource('/role', RoleController::class)->middleware('permission:manage roles');
        Route::resource('/permission', PermissionController::class)->middleware('permission:manage permissions');
        Route::resource('/external-system-links', \App\Http\Controllers\ExternalSystemLinkController::class)->middleware('permission:manage users');
        Route::resource('/locum-rates', LocumRateController::class)->middleware('role_or_permission:view departments|Manage Category')->except(['show']);
        Route::post('/locum-rates/expire-all-agreements', [LocumRateController::class, 'expireAllAgreements'])->name('locum-rates.expire-all')->middleware('role_or_permission:view departments|Manage Category');
        Route::post('/locum-rates/reactivate-agreements', [LocumRateController::class, 'reactivateAgreements'])->name('locum-rates.reactivate-agreements')->middleware('role_or_permission:view departments|Manage Category');
        Route::resource('/oncall-rates', OnCallRateController::class)->middleware('role_or_permission:view departments|Manage Category')->except(['show']);
        Route::resource('/request', RequestController::class)->middleware('permission:view my requests');
        Route::get('/requestapprove/export', [RequestApproveController::class, 'exportExcel'])->name('requestapprove.export')->middleware('permission:approve requests|ict_acces_report');
        Route::resource('/requestapprove', RequestApproveController::class)->middleware('permission:approve requests|ict_acces_report');
        // Route::resource('/hslb',HslbController::class);
        // Route::post('hslb/hr-confirm/{id}', [HslbController::class, 'hrConfirm'])->name('hslb.hrConfirm');
        Route::resource('ict-access-form', IctAccessController::class)->middleware('role_or_permission:line-manager|view ict access form|view it requests');
        // Route::post('/generate-pdf', [IctAccessController::class, 'generatePDF'])->name('generate.pdf');

        Route::get('/ict-access-form/{id}/edit', [IctAccessController::class, 'edit'])->name('ict-access-form.edit')->middleware('role_or_permission:line-manager|view ict access form|view it requests');
        Route::get('/users', [AuthController::class, 'getAllUser'])->name('users.index')->middleware('permission:manage users');
        Route::get('/users/export', [AuthController::class, 'exportUsers'])->name('users.export')->middleware('permission:manage users');
        Route::get('/users/{id}/details', [AuthController::class, 'getUserDetails'])->name('users.details')->middleware('permission:manage users');
        Route::get('/users/{id}/permissions', [AuthController::class, 'getUserPermissions'])->name('users.permissions')->middleware('permission:manage users');
        Route::post('/users/{id}/permissions', [AuthController::class, 'updateUserPermissions'])->name('users.permissions.update')->middleware('permission:manage users');
        Route::post('/users/bulk-action', [AuthController::class, 'bulkAction'])->name('users.bulk-action')->middleware('permission:manage users');
        Route::delete('/users/{id}', [AuthController::class, 'destroy'])->name('users.destroy')->middleware('permission:delete users');
        Route::post('/users/{id}/unlock', [AuthController::class, 'unlockUser'])->name('users.unlock')->middleware('permission:manage users');
        Route::get('/users/login-logs', [AuthController::class, 'getFailedLoginLogs'])->name('users.login-logs')->middleware('permission:manage users');
        Route::get('/staff-details', [AuthController::class, 'userDetail'])->name('employee.index')->middleware('permission:view staff details');

        // Certificate of Service
        Route::get('/certificate-of-service', [\App\Http\Controllers\CertificateOfServiceController::class, 'index'])->name('certificate-of-service.index')->middleware('permission:view staff details');
        Route::get('/certificate-of-service/create', [\App\Http\Controllers\CertificateOfServiceController::class, 'create'])->name('certificate-of-service.create')->middleware('permission:view staff details');
        Route::post('/certificate-of-service', [\App\Http\Controllers\CertificateOfServiceController::class, 'store'])->name('certificate-of-service.store')->middleware('permission:view staff details');
        Route::get('/certificate-of-service/template/settings', [\App\Http\Controllers\CertificateOfServiceController::class, 'templateSettings'])->name('certificate-of-service.template')->middleware('permission:view staff details');
        Route::post('/certificate-of-service/template/settings', [\App\Http\Controllers\CertificateOfServiceController::class, 'updateTemplateSettings'])->name('certificate-of-service.template.update')->middleware('permission:view staff details');
        Route::get('/certificate-of-service/{certificateOfService}', [\App\Http\Controllers\CertificateOfServiceController::class, 'show'])->name('certificate-of-service.show')->middleware('permission:view staff details');
        Route::post('/certificate-of-service/logo', [\App\Http\Controllers\CertificateOfServiceController::class, 'updateLogo'])->name('certificate-of-service.logo')->middleware('permission:view staff details');
        Route::get('/certificate-of-service/{certificateOfService}/download', [\App\Http\Controllers\CertificateOfServiceController::class, 'download'])->name('certificate-of-service.download')->middleware('permission:view staff details');
        Route::post('/certificate-of-service/{certificateOfService}/initialize', [\App\Http\Controllers\CertificateOfServiceController::class, 'initialize'])->name('certificate-of-service.initialize')->middleware('permission:view staff details');
        Route::delete('/certificate-of-service/{certificateOfService}', [\App\Http\Controllers\CertificateOfServiceController::class, 'destroy'])->name('certificate-of-service.destroy')->middleware('permission:view staff details');
        Route::get('/staff/add', [AuthController::class, 'showAddStaffForm'])->name('staff.add')->middleware('permission:view staff details');
        Route::post('/staff/add', [AuthController::class, 'handleAddStaff'])->name('staff.add.handle')->middleware('permission:view staff details');
        Route::post('/staff/bulk-action', [AuthController::class, 'staffBulkAction'])->name('staff.bulk-action')->middleware('permission:view staff details');
        Route::post('/staff/manage-license', [AuthController::class, 'manageLicense'])->name('staff.manage-license')->middleware('permission:view staff details');
        Route::get('/staff/export', [AuthController::class, 'exportStaff'])->name('staff.export')->middleware('permission:view staff details');
        Route::post('/users/{user}/reset-password', [AuthController::class, 'adminResetUserPassword'])->name('users.reset-password')->middleware('permission:reset user password');
        Route::put('/employee/{id}', [AuthController::class, 'update'])->name('employee.update')->middleware('permission:edit employee details');
        Route::delete('/delete/{id}', [AuthController::class, 'deleteUser'])->name('auth.destroy')->middleware('permission:delete users');

        Route::get('/get-job-titles/{departmentId}', [AuthController::class, 'getJobTitless']);

        //deactivate and activate user
        Route::put('/auth/deactivate/{id}', [AuthController::class, 'deactivate'])->name('auth.deactivate')->middleware('permission:deactivate users');
        Route::put('/user/{user}/activate', [AuthController::class, 'activate'])->name('auth.activate')->middleware('permission:activate users');


        //timesheet
        Route::get('/biotime', [BioUserController::class, 'index'])->name('biotime.index')->middleware('permission:view biometric attendance');
        Route::get('biotime/{userid}', [BioUserController::class, 'show'])->name('biotime.show')->middleware('permission:view biometric attendance');
        Route::get('biodetails', [BioUserController::class, 'details'])->name('biotime.details')->middleware('permission:view biometric attendance');
        Route::post('/biotime-export', [BioUserController::class, 'export'])->name('biotime.export')->middleware('permission:view biometric attendance');

        // Workflow Management
        Route::prefix('workflow-management')->name('workflow-management.')->group(function () {
            Route::get('/', [WorkflowManagementController::class, 'index'])->name('index')->middleware('permission:manage workflows');
            Route::get('/clearance', [WorkflowManagementController::class, 'clearanceWorkflows'])->name('clearance')->middleware('role_or_permission:coo|super-admin|manage workflows');
            Route::get('/errors', [WorkflowManagementController::class, 'errors'])->name('errors')->middleware('permission:manage workflows');
            Route::delete('/bulk-delete', [WorkflowManagementController::class, 'bulkDestroy'])->name('bulk-destroy')->middleware('permission:manage workflows');
            Route::put('/{id}/update-status', [WorkflowManagementController::class, 'updateStatus'])->name('update-status')->middleware('permission:manage workflows');
            Route::put('/{id}/update-form-status', [WorkflowManagementController::class, 'updateFormStatus'])->name('update-form-status')->middleware('permission:manage workflows');
            Route::get('/clearance/{id}', [WorkflowManagementController::class, 'showClearance'])->name('show-clearance')->middleware('role_or_permission:coo|super-admin|manage workflows');
            Route::delete('/clearance/{id}', [WorkflowManagementController::class, 'destroyClearance'])->name('destroy-clearance')->middleware('role_or_permission:coo|super-admin|manage workflows');
            Route::get('/history/{id}/edit', [WorkflowManagementController::class, 'editHistory'])->name('edit-history')->middleware('permission:manage workflows');
            Route::put('/history/{id}', [WorkflowManagementController::class, 'updateHistory'])->name('update-history')->middleware('permission:manage workflows');
            Route::put('/history/{id}/update-status', [WorkflowManagementController::class, 'updateHistoryStatus'])->name('update-history-status')->middleware('permission:manage workflows');
            Route::get('/clearance-history/{id}/edit', [WorkflowManagementController::class, 'editClearanceHistory'])->name('edit-clearance-history')->middleware('role_or_permission:coo|super-admin|manage workflows');
            Route::put('/clearance-history/{id}', [WorkflowManagementController::class, 'updateClearanceHistory'])->name('update-clearance-history')->middleware('role_or_permission:coo|super-admin|manage workflows');
            Route::put('/clearance-history/{id}/update-status', [WorkflowManagementController::class, 'updateClearanceHistoryStatus'])->name('update-clearance-history-status')->middleware('role_or_permission:coo|super-admin|manage workflows');
            Route::delete('/{id}', [WorkflowManagementController::class, 'destroy'])->name('destroy')->middleware('permission:manage workflows');
            Route::get('/{id}', [WorkflowManagementController::class, 'show'])->name('show')->middleware('permission:manage workflows');
        });

        Route::resource('platforms', PlatformController::class)->except(['show'])->middleware('permission:manage platforms');
        Route::post('users/{id}/platform-and-units', [AuthController::class, 'assignPlatformAndUnits'])->name('users.assign.platform-and-units')->middleware('permission:manage platforms');

        Route::prefix('departments/{department}')->group(function () {
            Route::get('platforms', [DepartmentPlatformController::class, 'index'])
                ->name('departments.platforms.index')->middleware('permission:manage platforms'); // full settings page

        });

        Route::prefix('departments/{department}')->group(function () {
            Route::get('platforms/modal', [DepartmentPlatformController::class, 'modal'])
                ->name('departments.platforms.modal')->middleware('permission:manage platforms');     // returns HTML for the modal
            Route::post('platforms/toggle', [DepartmentPlatformController::class, 'toggle'])
                ->name('departments.platforms.toggle')->middleware('permission:manage platforms');    // attach/detach via AJAX
        });

        Route::post('/platforms/{platform}/assign-manager', [PlatformController::class, 'assignManager'])
            ->name('platforms.assignManager')->middleware('permission:assign platform managers');
        Route::get('users/search', [PlatformController::class, 'searchUsers'])
            ->name('users.search')->middleware('permission:manage platforms');
        Route::post('platforms/{platform}/assign-manager', [PlatformController::class, 'assignManager'])
            ->name('platforms.assignManager')->middleware('permission:assign platform managers');
        Route::prefix('platforms/{platform}')->group(function () {
            Route::get('units/modal',   [PlatformUnitController::class, 'modal'])->name('platforms.units.modal')->middleware('permission:manage units');   // load modal body
            Route::post('units',        [PlatformUnitController::class, 'store'])->name('platforms.units.store')->middleware('permission:manage units');   // create
            Route::put('units/{unit}',  [PlatformUnitController::class, 'update'])->name('platforms.units.update')->middleware('permission:manage units'); // update
            Route::delete('units/{unit}', [PlatformUnitController::class, 'destroy'])->name('platforms.units.destroy')->middleware('permission:manage units'); // delete
        });
        Route::get('/platforms/{platform}/meta/json', [PlatformController::class, 'platformMeta'])
            ->whereNumber('platform')
            ->name('platforms.meta.json')->middleware('permission:manage platforms');

        // Public routes for locum requests (no special permissions required)
        Route::get('/platforms/{platform}/meta/json/public', [PlatformController::class, 'platformMeta'])
            ->whereNumber('platform')
            ->name('platforms.meta.json.public')->middleware('auth');

        Route::get('/platforms/{platform}/units/json', [PlatformUnitController::class, 'unitsJson'])->middleware('permission:manage units');

        // Public route for locum requests (no special permissions required)
        Route::get('/platforms/{platform}/units/json/public', [PlatformUnitController::class, 'unitsJson'])
            ->name('platforms.units.json.public')->middleware('auth');


        // Route::get('/platforms/{platform}/units/json', [DepartmentPlatformController::class, 'unitsJson'])
        //     ->name('platforms.units.json');

        Route::get('/units', [PlatformUnitController::class, 'index'])->name('units.index')->middleware('permission:manage units');

        Route::post('units/{unit}/assign-incharge', [PlatformUnitController::class, 'assignIncharge'])
            ->name('platforms.units.assignIncharge')->middleware('permission:assign unit incharge');

        Route::get('departments/{department}/users', [PlatformUnitController::class, 'usersByDepartment'])
            ->name('departments.users')->middleware('permission:manage platforms');

        Route::post(
            'platforms/{platform}/units/{unit}/assign-incharge',
            [PlatformUnitController::class, 'assignIncharge']
        )->name('platforms.units.assignIncharge')->middleware('permission:assign unit incharge');

        //to view hr forms
        // Route::get('/hr_form/{id}', [AuthController::class, 'showHrForm'])->name('hr_form');
        Route::get('/view-hr-form/{id}', [AuthController::class, 'downloadHrForm'])->name('download-hr-form')->middleware('permission:view hr forms');
        Route::get('/view-bank-form/{id}', [AuthController::class, 'downloadBankForm'])->name('download-bank-form')->middleware('permission:access bank details form');
        Route::get('/view-nhif-form/{id}', [AuthController::class, 'downloadNhifForm'])->name('download-nhif-form')->middleware('permission:access nhif registration');
        Route::get('/view-hslb-form/{id}', [AuthController::class, 'downloadHslbForm'])->name('download-hslb-form')->middleware('permission:access heslb form');
        Route::get('/view-it-access/{id}', [AuthController::class, 'downloadItForm'])->name('download-it-form')->middleware('role_or_permission:line-manager|view ict access form|view it requests');
        Route::get('/view-id-form/{id}', [AuthController::class, 'downloadIdForm'])->name('download-id-form')->middleware('permission:view signatures');
        Route::get('/view-exit-form/{id}', [AuthController::class, 'downloadExitForm'])->name('download-exit-form')->middleware('permission:access clearance form');


        //for user deatils
        Route::get('/employee/{id}', [AuthController::class, 'employee'])->name('employees_details.show')->middleware('permission:view employee details');
        Route::get('/employee/{id}/edit', [AuthController::class, 'changedept'])->name('employees_details.edit')->middleware('permission:edit employee details');
        Route::put('/employee/{id}', [AuthController::class, 'update'])->name('employees_details.update')->middleware('permission:edit employee details');

        // HR routes to manage staff profile details
        Route::get('/employee/{id}/personal-details', [AuthController::class, 'hrPersonalDetails'])->name('hr.employee.personal-details')->middleware('permission:view staff details');
        Route::post('/employee/{id}/personal-details', [AuthController::class, 'hrSavePersonalDetails'])->name('hr.employee.save-personal-details')->middleware('permission:view staff details');
        Route::get('/employee/{id}/family-details', [AuthController::class, 'hrFamilyDetails'])->name('hr.employee.family-details')->middleware('permission:view staff details');
        Route::post('/employee/{id}/family-details', [AuthController::class, 'hrSaveFamilyDetails'])->name('hr.employee.save-family-details')->middleware('permission:view staff details');
        Route::get('/employee/{id}/health-details', [AuthController::class, 'hrHealthDetails'])->name('hr.employee.health-details')->middleware('permission:view staff details');
        Route::post('/employee/{id}/health-details', [AuthController::class, 'hrSaveHealthDetails'])->name('hr.employee.save-health-details')->middleware('permission:view staff details');
        Route::get('/employee/{id}/language-knowledge', [AuthController::class, 'hrLanguageKnowledge'])->name('hr.employee.language-knowledge')->middleware('permission:view staff details');
        Route::post('/employee/{id}/language-knowledge', [AuthController::class, 'hrSaveLanguageKnowledge'])->name('hr.employee.save-language-knowledge')->middleware('permission:view staff details');
        Route::get('/employee/{id}/ccbrt-relation', [AuthController::class, 'hrCcbrtRelation'])->name('hr.employee.ccbrt-relation')->middleware('permission:view staff details');
        Route::post('/employee/{id}/ccbrt-relation', [AuthController::class, 'hrSaveCcbrtRelation'])->name('hr.employee.save-ccbrt-relation')->middleware('permission:view staff details');
        Route::get('/employee/{id}/conflict-interest', [AuthController::class, 'hrConflictInterest'])->name('hr.employee.conflict-interest')->middleware('permission:view staff details');
        Route::post('/employee/{id}/conflict-interest', [AuthController::class, 'hrSaveConflictInterest'])->name('hr.employee.save-conflict-interest')->middleware('permission:view staff details');
        Route::post('/employee/{id}/submit-registration', [AuthController::class, 'hrSubmitRegistration'])->name('hr.employee.submit-registration')->middleware('permission:view staff details');
        Route::put('/employee/{id}/oncall-rates', [AuthController::class, 'updateOnCallRates'])->name('employee.update-oncall-rates')->middleware('permission:view staff details');
        // web.php (or api.php)
        Route::get('/user/{id}/edit', [AuthController::class, 'editUserDetails'])->name('user.edit')->middleware('role_or_permission:hr|super-admin|edit users');
        Route::put('/user/{id}', [AuthController::class, 'updateUserDetails'])->name('user.update')->middleware('role_or_permission:hr|super-admin|edit users');


        Route::get('/users/{id}/edit', [AuthController::class, 'showEditForm'])->name('users.showEditForm')->middleware('permission:edit users');
        Route::post('/users/{id}/edit', [AuthController::class, 'editUserRole'])->name('users.edit')->middleware('permission:assign user roles');
        Route::put('/users/{id}/destroy', [AuthController::class, 'destroyUserRole'])->name('users.destroy')->middleware('permission:delete users');
        // Route::put('users/{id}', [AuthController::class, 'update'])->name('users.update');
        Route::post('users/{id}/role', [AuthController::class, 'editUserRole'])->name('users.edit.role')->middleware('permission:assign user roles');
        Route::delete('/permission/{id}', [PermissionController::class, 'destroy'])->name('permission.delete')->middleware('permission:manage permissions');
        Route::post('roles/permissions', [PermissionController::class, 'updateRolePermissions'])->name('role.updatePermissions');
        Route::get('role/{roleId}/permissions', [PermissionController::class, 'getRolePermissions']);
        Route::get('/check-ccbrt', [ProfileController::class, 'checkCcbrt'])->name('ccbrt.check');


        Route::post('/approve_form', [FormController::class, 'approveForm'])->name('approve_form')->middleware('permission:approve forms');
        Route::get('/approver_form', [FormController::class, 'getApprover'])->name('approver_form')->middleware('permission:approve forms');
        Route::get('/show_form/{id}', [FormController::class, 'getForm'])->name('show_form')->middleware('permission:view form details|ict_acces_report|view my requests');
        Route::get('/show_form/{id}/pdf', [FormController::class, 'getFormPdf'])->name('show_form.pdf')->middleware('permission:view form details|ict_acces_report|view my requests');
        Route::post('/reject_form', [FormController::class, 'rejectForm'])->middleware('permission:reject forms');
        Route::post('/remove_hardware_item', [FormController::class, 'removeHardwareItem'])->name('remove_hardware_item')->middleware('permission:approve forms');


        // Route for the ID Card Request landing page
        Route::get('/id-card-request', [IDCardController::class, 'index'])->name('IDCard')->middleware('permission:view id card requests');
        Route::get('/id-card-request', [IDCardController::class, 'create'])->name('IDCard.create')->middleware('permission:create id card requests');
        Route::post('/idcard/store', [IDCardController::class, 'store'])->name('IDCard.store')->middleware('permission:create id card requests');

        //HR forms to request
        Route::get('/hr-requests', [HrRequestsController::class, 'index'])->name('hr-requests.index')->middleware('permission:view hr forms');
        //IT Request Controller
        Route::get('/it-requests', [ItRequestsController::class, 'index'])->name('it-requests.index')->middleware('permission:view it requests');

        Route::get('/announcements', [AnnouncementController::class, 'index'])->name('announcements.index');
        Route::get('/announcements/create', [AnnouncementController::class, 'create'])->name('announcements.create')->middleware('role_or_permission:hr|line-manager|cfo|cms|coo|super-admin|Super-Admin');
        Route::post('/announcements', [AnnouncementController::class, 'store'])->name('announcements.store')->middleware('role_or_permission:hr|line-manager|cfo|cms|coo|super-admin|Super-Admin');
        Route::get('/announcements/{id}/edit', [AnnouncementController::class, 'edit'])->name('announcements.edit')->middleware('role_or_permission:hr|line-manager|cfo|cms|coo|super-admin|Super-Admin');
        Route::put('/announcements/{id}', [AnnouncementController::class, 'update'])->name('announcements.update')->middleware('role_or_permission:hr|line-manager|cfo|cms|coo|super-admin|Super-Admin');
        Route::delete('/announcements/{id}', [AnnouncementController::class, 'destroy'])->name('announcements.destroy')->middleware('role_or_permission:hr|line-manager|cfo|cms|coo|super-admin|Super-Admin');
        Route::post('/announcements/{id}/record-view', [AnnouncementController::class, 'recordView'])->name('announcements.record-view');

        // Contractual Hours Management
        Route::resource('contractual-hours', ContractualHoursController::class)->middleware('permission:Manage Category');

        //Hrdocuments route
        Route::get('view', [AnnouncementController::class, 'ViewHRDocuments'])->name('HrDocuments.index')->middleware('permission:view hr documents');
        Route::get('addview', [AnnouncementController::class, 'addview'])->name('HrDocuments.addview')->middleware('permission:upload hr documents');
        Route::post('add', [AnnouncementController::class, 'add'])->name('HrDocuments.add')->middleware('permission:upload hr documents');
        Route::get('documents/{id}/edit', [AnnouncementController::class, 'editHrDocument'])->name('HrDocuments.edit')->middleware('permission:upload hr documents');
        Route::put('documents/{id}', [AnnouncementController::class, 'updateHrDocument'])->name('HrDocuments.update')->middleware('permission:upload hr documents');
        Route::delete('documents/{id}', [AnnouncementController::class, 'destroyhrdoc'])->name('HrDocuments.destroyhrdoc')->middleware('permission:delete hr documents');
        Route::get('view/{DocId}', [AnnouncementController::class, 'view'])->name('documents.view')->middleware('permission:view hr documents');
        Route::get('download/{DocId}', [AnnouncementController::class, 'download'])->name('documents.download')->middleware('permission:download hr documents');
        // Route::put('/ict-access-form/update/{id}', [RequestController::class, 'updateIctForm'])->name('form.updateIctForm');

        Route::post('/approve_clearform', [FormController::class, 'approveClearanceForm'])->name('approve_clearform')->middleware('permission:approve clearance forms');
        Route::get('/exit_forms/approvers', [ClearanceFormController::class, 'getApprover'])->name('exit_forms.approvers')->middleware('permission:approve clearance forms');
        Route::get('/exit_forms/{id}', [FormController::class, 'getClearance'])->name('exit_forms.show')->middleware('role_or_permission:coo|super-admin|view clearance forms|access clearance form|ict_acces_report');
        Route::post('/exit_forms/{id}/reject', [ClearanceFormController::class, 'rejectForm'])->name('exit_forms.reject')->middleware('permission:reject clearance forms');
        Route::post('/clearance/{id}/notify-cos', [ClearanceFormController::class, 'notifyCosRequest'])->name('clearance.notify-cos')->middleware('role_or_permission:coo|super-admin|view clearance forms');
        Route::get('/clearance/{id}/download', [FormController::class, 'downloadClearancePDF'])->name('clearance.download')->middleware('permission:view clearance forms');

        // Route for Clearance Forms View
        Route::get('/clearance_forms/{id}', [FormController::class, 'getClearance'])->middleware('permission:view clearance forms|ict_acces_report|view my requests');

        //Get Clearance in my request view
        Route::get('/clearance/edit/{id}', [RequestController::class, 'editClearance'])->name('clearance.edit')->middleware('permission:view clearance forms');
        Route::put('/clearance/update/{id}', [RequestController::class, 'update'])->name('clearance.update')->middleware('permission:view clearance forms');

        Route::get('role-permission/{roleId}/give-permission', [RoleController::class, 'addPermissionToRole'])->middleware('permission:assign permissions');
        Route::put('role-permission/{roleId}/give-permission', [RoleController::class, 'givePermissionToRole'])->middleware('permission:assign permissions');

        // Recruitment Requisitions Routes
        Route::prefix('requisitions')->name('requisitions.')->group(function () {
            Route::get('/', [RequisitionController::class, 'index'])->name('index');
            Route::get('/pending', [RequisitionController::class, 'pending'])->name('pending');
            Route::get('/create', [RequisitionController::class, 'create'])->name('create');
            Route::post('/', [RequisitionController::class, 'store'])->name('store');
            Route::get('/employees', [RequisitionController::class, 'getEmployeesByDepartment'])->name('employees');
            Route::get('/line-managers', [RequisitionController::class, 'getLineManagersByDepartment'])->name('line-managers');
            Route::get('/job-titles', [RequisitionController::class, 'getJobTitlesByDepartment'])->name('job-titles');
            Route::get('/export', [RequisitionController::class, 'exportExcel'])->name('export');
            Route::get('/{accessId}', [RequisitionController::class, 'show'])->name('show');
            Route::get('/{accessId}/download-jd', [RequisitionController::class, 'downloadJobDescription'])->name('download-jd');
            Route::post('/{accessId}/payroll-review', [RequisitionController::class, 'payrollReview'])->name('payroll-review');
            Route::post('/{accessId}/hec-review', [RequisitionController::class, 'hecReview'])->name('hec-review');
            Route::post('/{accessId}/cfo-review', [RequisitionController::class, 'cfoReview'])->name('cfo-review');
            Route::post('/{accessId}/ceo-review', [RequisitionController::class, 'ceoReview'])->name('ceo-review');
            Route::post('/{accessId}/hr-review', [RequisitionController::class, 'hrReview'])->name('hr-review');
            Route::get('/{accessId}/edit', [RequisitionController::class, 'edit'])->name('edit');
            Route::put('/{accessId}', [RequisitionController::class, 'update'])->name('update');
            Route::post('/{accessId}/resubmit', [RequisitionController::class, 'resubmit'])->name('resubmit');
        });

        // ========== Performance Management System ==========
        Route::prefix('pms')->name('pms.')->group(function () {
            // Dashboard
            Route::get('/dashboard', [\App\Http\Controllers\Pms\PmsDashboardController::class, 'index'])->name('dashboard');

            // Cycles (HR/Admin/CEO only)
            Route::resource('cycles', \App\Http\Controllers\Pms\PmsCycleController::class)->except(['show', 'destroy']);
            Route::post('/cycles/{cycle}/toggle', [\App\Http\Controllers\Pms\PmsCycleController::class, 'toggleActive'])->name('cycles.toggle');

            // Strategic Goals (CEO/HR only)
            Route::resource('goals', \App\Http\Controllers\Pms\PmsStrategicGoalController::class);

            // KPIs
            Route::get('/kpis', [\App\Http\Controllers\Pms\PmsKpiController::class, 'index'])->name('kpis.index');
            Route::get('/kpis/create', [\App\Http\Controllers\Pms\PmsKpiController::class, 'create'])->name('kpis.create');
            Route::post('/kpis', [\App\Http\Controllers\Pms\PmsKpiController::class, 'store'])->name('kpis.store');
            Route::get('/kpis/{kpi}', [\App\Http\Controllers\Pms\PmsKpiController::class, 'show'])->name('kpis.show');
            Route::post('/kpis/{kpi}/accept', [\App\Http\Controllers\Pms\PmsKpiController::class, 'accept'])->name('kpis.accept');
            Route::post('/kpis/{kpi}/reject', [\App\Http\Controllers\Pms\PmsKpiController::class, 'reject'])->name('kpis.reject');
            Route::post('/kpis/{kpi}/negotiate', [\App\Http\Controllers\Pms\PmsKpiController::class, 'negotiate'])->name('kpis.negotiate');
            Route::post('/kpis/{kpi}/revise', [\App\Http\Controllers\Pms\PmsKpiController::class, 'revise'])->name('kpis.revise');
            Route::post('/kpis/{kpi}/agree', [\App\Http\Controllers\Pms\PmsKpiController::class, 'agree'])->name('kpis.agree');
            Route::post('/kpis/{kpi}/progress', [\App\Http\Controllers\Pms\PmsKpiController::class, 'updateProgress'])->name('kpis.progress');

            // HR Monitoring
            Route::get('/hr-monitor', [\App\Http\Controllers\Pms\PmsKpiController::class, 'hrMonitor'])->name('kpis.hr-monitor');

            // KPI Tree Visualization
            Route::get('/kpi-tree', [\App\Http\Controllers\Pms\PmsKpiController::class, 'tree'])->name('kpis.tree');

            // Performance Reviews
            Route::get('/reviews', [\App\Http\Controllers\Pms\PmsReviewController::class, 'index'])->name('reviews.index');
            Route::get('/reviews/create', [\App\Http\Controllers\Pms\PmsReviewController::class, 'create'])->name('reviews.create');
            Route::post('/reviews', [\App\Http\Controllers\Pms\PmsReviewController::class, 'store'])->name('reviews.store');
            Route::get('/reviews/{review}', [\App\Http\Controllers\Pms\PmsReviewController::class, 'show'])->name('reviews.show');
            Route::post('/reviews/{review}/acknowledge', [\App\Http\Controllers\Pms\PmsReviewController::class, 'acknowledge'])->name('reviews.acknowledge');

            // Notifications
            Route::get('/notifications', [\App\Http\Controllers\Pms\PmsNotificationController::class, 'index'])->name('notifications.index');
            Route::post('/notifications/{notification}/read', [\App\Http\Controllers\Pms\PmsNotificationController::class, 'markAsRead'])->name('notifications.read');
            Route::post('/notifications/mark-all-read', [\App\Http\Controllers\Pms\PmsNotificationController::class, 'markAllRead'])->name('notifications.mark-all-read');

            // Settings (HR/Admin only)
            Route::get('/settings', [\App\Http\Controllers\Pms\PmsSettingsController::class, 'index'])->name('settings.index');
            Route::put('/settings', [\App\Http\Controllers\Pms\PmsSettingsController::class, 'update'])->name('settings.update');
            Route::post('/settings/toggle', [\App\Http\Controllers\Pms\PmsSettingsController::class, 'toggle'])->name('settings.toggle');

        });

//Contract Description
        Route::get('/contracts', [ContractController::class, 'index'])->name('contracts.index')->middleware('permission:view contracts');
        Route::get('/contracts/create', [ContractController::class, 'create'])->name('contracts.create')->middleware('permission:view contracts');
        Route::post('/contracts', [ContractController::class, 'store'])->name('contracts.store')->middleware('permission:view contracts');
        Route::get('/contracts/{id}', [ContractController::class, 'show'])->name('contracts.show')->middleware('permission:view contracts');
        Route::get('/contracts/{id}/edit', [ContractController::class, 'edit'])->name('contracts.edit')->middleware('permission:view contracts');
        Route::put('/contracts/{id}', [ContractController::class, 'update'])->name('contracts.update')->middleware('permission:view contracts');
        Route::delete('/contracts/{id}', [ContractController::class, 'destroy'])->name('contracts.destroy')->middleware('permission:view contracts');


        // Fixed Flex
        Route::get('/fixed-flex', [ContractController::class, 'indexFixedFlex'])->name('contracts.fixed_flex.index');
        Route::get('/fixed-flex/create', [ContractController::class, 'createFixedFlex'])->name('contracts.fixed_flex.create');
        Route::post('/fixed-flex', [ContractController::class, 'storeFixedFlex'])->name('contracts.fixed_flex.store');
        Route::get('/fixed-flex/{id}/edit', [ContractController::class, 'editFixedFlex'])->name('contracts.fixed_flex.edit');
        Route::put('/fixed-flex/{id}', [ContractController::class, 'updateFixedFlex'])->name('contracts.fixed_flex.update');
        Route::delete('/fixed-flex/{id}', [ContractController::class, 'destroyFixedFlex'])->name('contracts.fixed_flex.destroy');
        Route::get('/fixed-flex/{id}', [ContractController::class, 'showFixedFlex'])->name('contracts.fixed_flex.show');
        // Consultant
        Route::get('/consultant', [ContractController::class, 'indexConsultant'])->name('contracts.consultant.index');
        Route::get('/consultant/create', [ContractController::class, 'createConsultant'])->name('contracts.consultant.create');
        Route::post('/consultant', [ContractController::class, 'storeConsultant'])->name('contracts.consultant.store');
        Route::get('/consultant/{id}/edit', [ContractController::class, 'editConsultant'])->name('contracts.consultant.edit');
        Route::put('/consultant/{id}', [ContractController::class, 'updateConsultant'])->name('contracts.consultant.update');
        Route::delete('/consultant/{id}', [ContractController::class, 'destroyConsultant'])->name('contracts.consultant.destroy');

        // Volunteer
        Route::get('/volunteer', [ContractController::class, 'indexVolunteer'])->name('contracts.volunteer.index');
        Route::get('/volunteer/create', [ContractController::class, 'createVolunteer'])->name('contracts.volunteer.create');
        Route::post('/volunteer', [ContractController::class, 'storeVolunteer'])->name('contracts.volunteer.store');
        Route::get('/volunteer/{id}/edit', [ContractController::class, 'editVolunteer'])->name('contracts.volunteer.edit');
        Route::put('/volunteer/{id}', [ContractController::class, 'updateVolunteer'])->name('contracts.volunteer.update');
        Route::delete('/volunteer/{id}', [ContractController::class, 'destroyVolunteer'])->name('contracts.volunteer.destroy');

        // Output Based
        Route::get('/output-based', [ContractController::class, 'indexOutputBased'])->name('contracts.output_based.index');
        Route::get('/output-based/create', [ContractController::class, 'createOutputBased'])->name('contracts.output_based.create');
        Route::post('/output-based', [ContractController::class, 'storeOutputBased'])->name('contracts.output_based.store');
        Route::get('/output-based/{id}/edit', [ContractController::class, 'editOutputBased'])->name('contracts.output_based.edit');
        Route::put('/output-based/{id}', [ContractController::class, 'updateOutputBased'])->name('contracts.output_based.update');
        Route::delete('/output-based/{id}', [ContractController::class, 'destroyOutputBased'])->name('contracts.output_based.destroy');

        // Exposure Replacement
        Route::get('/exposure-replacement', [ContractController::class, 'indexExposureReplacement'])->name('contracts.exposure_replacement.index');
        Route::get('/exposure-replacement/create', [ContractController::class, 'createExposureReplacement'])->name('contracts.exposure_replacement.create');
        Route::post('/exposure-replacement', [ContractController::class, 'storeExposureReplacement'])->name('contracts.exposure_replacement.store');
        Route::get('/exposure-replacement/{id}/edit', [ContractController::class, 'editExposureReplacement'])->name('contracts.exposure_replacement.edit');
        Route::put('/exposure-replacement/{id}', [ContractController::class, 'updateExposureReplacement'])->name('contracts.exposure_replacement.update');
        Route::delete('/exposure-replacement/{id}', [ContractController::class, 'destroyExposureReplacement'])->name('contracts.exposure_replacement.destroy');

        // Locum
        Route::get('/locum', [ContractController::class, 'indexLocum'])->name('contracts.locum.index');
        Route::get('/locum/create', [ContractController::class, 'createLocum'])->name('contracts.locum.create');
        Route::post('/locum', [ContractController::class, 'storeLocum'])->name('contracts.locum.store');
        Route::get('/locum/{id}/edit', [ContractController::class, 'editLocum'])->name('contracts.locum.edit');
        Route::put('/locum/{id}', [ContractController::class, 'updateLocum'])->name('contracts.locum.update');
        Route::delete('/locum/{id}', [ContractController::class, 'destroyLocum'])->name('contracts.locum.destroy');

        Route::get('/me/platforms', [DepartmentPlatformController::class, 'myPlatforms']);

        // Units on a specific platform (JSON)
        // Route::get('/platforms/{platform}/units/json', [DepartmentPlatformController::class, 'unitsJson']);

        Route::get('/locum-requests/{id}/status', [LocumRequestController::class, 'showLocumRequest'])
            ->name('locum-requests.showLocumRequest');

        Route::get('/shift-settings', [ShiftSettingController::class, 'index'])->name('shift-settings.index')->middleware('permission:view departments');
        Route::post('/shift-settings', [ShiftSettingController::class, 'store'])->name('shift-settings.store')->middleware('role_or_permission:Super-Admin|super-admin|it|view departments');
        Route::get('/shift-settings/{shift_setting}/edit', [ShiftSettingController::class, 'edit'])->name('shift-settings.edit')->middleware('role_or_permission:Super-Admin|super-admin|it|view departments');
        Route::put('/shift-settings/{shift_setting}', [ShiftSettingController::class, 'update'])->name('shift-settings.update')->middleware('role_or_permission:Super-Admin|super-admin|it|view departments');
        Route::delete('/shift-settings/{shift_setting}', [ShiftSettingController::class, 'destroy'])->name('shift-settings.destroy')->middleware('role_or_permission:Super-Admin|super-admin|it|view departments');

        //On-Call Request
        Route::get('/oncall-requests', [OnCallRequestController::class, 'index'])->name('oncall_requests.index')->middleware('permission:view oncall requests');
        Route::get('/oncall-requests/view', [OnCallRequestController::class, 'view'])->name('oncall_requests.view')->middleware('permission:approve oncall requests');
        Route::get('/oncall-requests/actioned', [OnCallRequestController::class, 'actioned'])->name('oncall_requests.actioned')->middleware('permission:view oncall requests');
        Route::get('/oncall-requests/report', [OnCallRequestController::class, 'report'])->name('oncall_requests.report')->middleware('permission:view oncall reports|view oncall requests');
        Route::get('/oncall-requests/create', [OnCallRequestController::class, 'create'])->name('oncall_requests.create')->middleware('permission:create oncall requests');
        Route::get('/oncall-requests/create-for-staff', [OnCallRequestController::class, 'createForStaff'])->name('oncall_requests.create-for-staff')->middleware('permission:create oncall requests');
        Route::post('/oncall-requests', [OnCallRequestController::class, 'store'])->name('oncall_requests.store')->middleware('permission:create oncall requests');
        Route::post('/oncall-requests/claim-for-staff', [OnCallRequestController::class, 'claimForStaff'])->name('oncall_requests.claim-for-staff')->middleware('permission:create oncall requests');
        Route::get('/oncall-requests/{id}', [OnCallRequestController::class, 'show'])->name('oncall_requests.show')->middleware('permission:view oncall requests');
        Route::get('/oncall-requests/{id}/status', [OnCallRequestController::class, 'showOnCallRequest'])->name('oncall_requests.status')->middleware('permission:view oncall requests');
        Route::delete('/oncall-requests/{id}', [OnCallRequestController::class, 'destroy'])->name('oncall_requests.destroy')->middleware('permission:delete oncall requests');
        Route::post('/oncall-requests/reject', [OnCallRequestController::class, 'reject'])->name('oncall_requests.reject')->middleware('permission:reject oncall requests');
        Route::post('/oncall-requests/bulk-approve', [OnCallRequestController::class, 'bulkApprove'])->name('oncall_requests.bulk-approve')->middleware('permission:approve oncall requests');
        Route::post('/oncall-requests/bulk-reject', [OnCallRequestController::class, 'bulkReject'])->name('oncall_requests.bulk-reject')->middleware('permission:reject oncall requests');
        Route::get('/oncall-requests/show/{id}', [OnCallRequestController::class, 'showOnCallRequest'])->name('oncall_requests.show-details');
        Route::get('/oncall-requests/worked-days/{id}', [OnCallRequestController::class, 'showWorkedDays'])->name('oncall_requests.worked-days');
        Route::post('/oncall-requests/fetch-biotime', [OnCallRequestController::class, 'fetchBioTimeData'])->name('oncall_requests.fetch-biotime');
        Route::post('/oncall-requests/incharge-fetch-biotime', [OnCallRequestController::class, 'inchargeFetchBioTimeData'])->name('oncall_requests.incharge-fetch-biotime');

        Route::get('/oncall-requests/{onCallRequest}/edit', [OnCallRequestController::class, 'edit'])
            ->name('oncall_requests.edit')->middleware('permission:edit oncall requests');
        Route::put('/oncall-requests/{onCallRequest}', [OnCallRequestController::class, 'update'])
            ->name('oncall_requests.update')->middleware('permission:edit oncall requests');

        Route::post('oncall_requests_bulk-approve', [OnCallRequestController::class, 'bulkApprove'])->name('oncall_requests.bulk-approve')->middleware('permission:approve oncall requests');
        Route::post('oncall_requests_bulk-reject', [OnCallRequestController::class, 'bulkReject'])->name('oncall_requests.bulk-reject')->middleware('permission:reject oncall requests');

        Route::get('/oncall_requests/approved', [OnCallRequestController::class, 'approvedRequests'])->name('oncall_requests.approved')->middleware('permission:view oncall reports');
        Route::get('/done-requests', [OnCallRequestController::class, 'approvedRequests'])->name('done_requests.index')->middleware('permission:view oncall reports');
        Route::get('/oncall_requests/consumption_report', [OnCallRequestController::class, 'consumptionReport'])->name('oncall_requests.consumption_report')->middleware('permission:view oncall reports');

        Route::get('/oncall-requests/reports', [OnCallRequestController::class, 'reports'])
            ->name('oncall_requests.reports')->middleware('permission:view oncall reports');

        Route::get('/oncall-requests/reports/data', [OnCallRequestController::class, 'reportsData'])
            ->name('oncall_requests.reports-data')->middleware('permission:view oncall reports');


        //claim for staff oncall

        Route::get('/oncall-requests-create-for-staff', [OnCallRequestController::class, 'createForStaff'])->name('oncall_requests.create_for_staff')->middleware('permission:create oncall requests');
        Route::post('/oncall-requests/claim-for-staff',  [OnCallRequestController::class, 'claimForStaff'])->name('oncall_requests.claim_for_staff')->middleware('permission:create oncall requests');

        // BioTime (in-charge) for On-Call
        Route::post('/oncall-requests-fetch-biotime-incharge', [OnCallRequestController::class, 'inchargeFetchBioTimeDataForOnCall'])
            ->name('oncall_requests.fetch_biotime_incharge')->middleware('permission:create oncall requests');

        // Route::get('/oncall-requests/reports/export', [OnCallRequestController::class, 'exportReport'])
        //     ->name('oncall_requests.export_report')
        //     ->middleware(['auth', 'role:hr|coo|cfo|cms|line-manager']);
        Route::get('/oncall/approved/export', [\App\Http\Controllers\OnCallRequestController::class, 'exportApprovedExcel'])
            ->name('oncall_requests.export')->middleware('permission:view oncall reports');

        Route::get('oncall_requests/{id}', [OnCallRequestController::class, 'show'])->name('oncall_requests.show')->middleware('permission:view oncall requests');
        // Government Intern
        Route::get('/govt-intern', [ContractController::class, 'indexGovtIntern'])->name('contracts.govt_intern.index')->middleware('permission:view contracts');
        Route::get('/govt-intern/create', [ContractController::class, 'createGovtIntern'])->name('contracts.govt_intern.create')->middleware('permission:view contracts');
        Route::post('/govt-intern', [ContractController::class, 'storeGovtIntern'])->name('contracts.govt_intern.store')->middleware('permission:view contracts');
        Route::get('/govt-intern/{id}/edit', [ContractController::class, 'editGovtIntern'])->name('contracts.govt_intern.edit')->middleware('permission:view contracts');
        Route::put('/govt-intern/{id}', [ContractController::class, 'updateGovtIntern'])->name('contracts.govt_intern.update')->middleware('permission:view contracts');
        Route::delete('/govt-intern/{id}', [ContractController::class, 'destroyGovtIntern'])->name('contracts.govt_intern.destroy')->middleware('permission:view contracts');



        //Job Description
        Route::get('/job-descriptions', [JobDescriptionController::class, 'index'])->name('job.description')->middleware('permission:job title');
        Route::get('/job-descriptions/create', [JobDescriptionController::class, 'create'])->name('job.description.create')->middleware('permission:job title');
        Route::post('/job-descriptions', [JobDescriptionController::class, 'store'])->name('job.description.store')->middleware('permission:job title');
        Route::get('/job-descriptions/{id}/edit', [JobDescriptionController::class, 'edit'])->name('job.description.edit')->middleware('permission:job title');
        Route::put('/job-descriptions/{id}', [JobDescriptionController::class, 'update'])->name('job.description.update')->middleware('permission:job title');

        // Route::get('/job-descriptions/{jobDescription}/edit', [JobDescriptionController::class, 'edit'])->name('job.description.edit');
        // Route::put('/job-descriptions/{jobDescription}', [JobDescriptionController::class, 'update'])->name('job.description.update');
        Route::patch('/job-descriptions/{id}', [JobDescriptionController::class, 'update'])->name('job-descriptions.update')->middleware('permission:job title');
        Route::get('job-description/{id}', [JobDescriptionController::class, 'show'])->name('job.description.show')->middleware('permission:job title');
        Route::delete('job-description/{id}', [JobDescriptionController::class, 'destroy'])->name('job.description.destroy')->middleware('permission:job title');
        Route::get('job-descriptions/{id}/download', [JobDescriptionController::class, 'downloadPDF'])->name('job-descriptions.download')->middleware('permission:job title');



        // Resource route for bank details
        Route::get('bank-details', [BankDetailsController::class, 'index'])->name('bank-details.index')->middleware('permission:access bank details form');
        Route::post('bank-details', [BankDetailsController::class, 'store'])->name('bank-details.store')->middleware('permission:access bank details form');


        Route::resource('loan-declarations', LoanDeclarationController::class)->middleware('permission:view forms');
        Route::get('loan-declarations', [LoanDeclarationController::class, 'index'])->name('loan-declarations.index')->middleware('permission:view forms');
        Route::get('loan-declarations/create', [LoanDeclarationController::class, 'create'])->name('loan-declarations.create')->middleware('permission:view forms');
        Route::post('loan-declarations', [LoanDeclarationController::class, 'store'])->name('loan-declarations.store')->middleware('permission:view forms');
        Route::get('loan-declarations/{loanDeclaration}', [LoanDeclarationController::class, 'show'])->name('loan-declarations.show')->middleware('permission:view forms');
        Route::get('loan-declarations/{loanDeclaration}/edit', [LoanDeclarationController::class, 'edit'])->name('loan-declarations.edit')->middleware('permission:view forms');
        Route::put('loan-declarations/{loanDeclaration}', [LoanDeclarationController::class, 'update'])->name('loan-declarations.update')->middleware('permission:view forms');
        Route::delete('loan-declarations/{loanDeclaration}', [LoanDeclarationController::class, 'destroy'])->name('loan-declarations.destroy')->middleware('permission:view forms');

        // Locum Agreements
        Route::get('/locum-agreements', [LocumAgreementController::class, 'index'])->name('locum-agreements.index')->middleware('permission:view locum requests');
        Route::get('/locum-agreements/create', [LocumAgreementController::class, 'create'])->name('locum-agreements.create')->middleware('permission:view locum requests');
        Route::post('/locum-agreements', [LocumAgreementController::class, 'store'])->name('locum-agreements.store')->middleware('permission:view locum requests');
        Route::get('/view-agreements', [LocumAgreementController::class, 'view'])->name('locum-agreements.view')->middleware('permission:view locum requests');
        Route::get('/locum-agreements/{id}', [LocumAgreementController::class, 'show'])->name('locum-agreements.show')->middleware('permission:view locum requests');
        Route::get('/locum-agreements/{id}/edit', [LocumAgreementController::class, 'edit'])->name('locum-agreements.edit')->middleware('permission:view locum requests');
        Route::put('/locum-agreements/{id}', [LocumAgreementController::class, 'update'])->name('locum-agreements.update')->middleware('permission:view locum requests');
        Route::post('/locum-agreements/{id}/update-rate', [LocumAgreementController::class, 'updateRate'])->name('locum-agreements.update-rate')->middleware('permission:view locum requests');
        Route::get('/locum-agreements-approve/{id}', [LocumAgreementController::class, 'approve'])->name('locum-agreements.approve')->middleware('permission:approve locum requests');
        Route::get('/locum-agreements-reject/{id}', [LocumAgreementController::class, 'reject'])->name('locum-agreements.reject')->middleware('permission:reject locum requests');

        Route::get('/locum-requests/worked-days/{id}', [LocumRequestController::class, 'showWorkedDays'])->name('locum-requests.worked-days')->middleware('permission:view locum requests');

        // Locum Requests
        Route::get('/locum-requests', [LocumRequestController::class, 'index'])->name('locum-requests.index')->middleware('permission:view locum requests');
        Route::get('/locum-approve', [LocumRequestController::class, 'view'])->name('locum-requests.view')->middleware('permission:approve locum requests');
        Route::get('/locum-requests/create', [LocumRequestController::class, 'create'])->name('locum-requests.create')->middleware('permission:create locum requests');
        Route::post('/locum-requests', [LocumRequestController::class, 'store'])->name('locum-requests.store')->middleware('permission:create locum requests');
        Route::get('/locum-requests/{id}', [LocumRequestController::class, 'show'])->name('locum-requests.show')->middleware('permission:view locum requests');
        Route::get('/locum-requests/{id}/edit', [LocumRequestController::class, 'edit'])->name('locum-requests.edit')->middleware('permission:edit locum requests');
        Route::put('/locum-requests/{id}', [LocumRequestController::class, 'update'])->name('locum-requests.update')->middleware('permission:edit locum requests');
        Route::get('/locum-requests-export', [LocumRequestController::class, 'export'])->name('locum-requests.export')->middleware('permission:view locum reports');
        Route::get('/locum-requests-actioned-export', [LocumRequestController::class, 'exportActioned'])
            ->name('locum-requests.actioned.export')->middleware('permission:view locum reports|ict_acces_report');


        // Procurements Module - Contracts
        Route::prefix('procurements/contracts')->name('procurements.contracts.')->group(function () {
            Route::get('/', [ContractsController::class, 'index'])->name('index')->middleware('role_or_permission:manage_contracts|view contracts');
            Route::get('/create', [ContractsController::class, 'create'])->name('create')->middleware('role_or_permission:manage_contracts|view contracts');
            Route::post('/', [ContractsController::class, 'store'])->name('store')->middleware('role_or_permission:manage_contracts|view contracts');
            Route::get('/departments/{divisionId}', [ContractsController::class, 'getDepartmentsByEntity'])->name('departments.by-entity');
            Route::get('/departments/{departmentId}/line-manager', [ContractsController::class, 'getLineManager'])->name('departments.line-manager');
            Route::get('/export', [ContractsController::class, 'export'])->name('export')->middleware('role_or_permission:manage_contracts|view contracts');
            Route::get('/notifications/manage', [ContractsController::class, 'notificationManagement'])->name('notification-management')->middleware('role_or_permission:manage_contracts|view contracts');
            Route::get('/upload/create', [ContractsController::class, 'uploadContract'])->name('upload.create')->middleware('role_or_permission:manage_contracts|view contracts');
            Route::get('/add-new/create', [ContractsController::class, 'addNewContract'])->name('add-new.create')->middleware('role_or_permission:manage_contracts|view contracts');
            Route::get('/renewal/create', [ContractsController::class, 'makeContract'])->name('renewal.create')->middleware('role_or_permission:manage_contracts|view contracts');
            Route::get('/approvals', [ContractsController::class, 'contractApprovalIndex'])->name('approvals.index')->middleware('role_or_permission:manage_contracts|view contracts');
            Route::get('/{id}/details', [ContractsController::class, 'getDetails'])->name('details')->middleware('role_or_permission:manage_contracts|view contracts');
            Route::get('/{id}/document', [ContractsController::class, 'document'])->name('document')->middleware('role_or_permission:manage_contracts|view contracts');
            Route::get('/{id}/edit', [ContractsController::class, 'edit'])->name('edit')->middleware('role_or_permission:manage_contracts|view contracts');
            Route::get('/{id}', [ContractsController::class, 'show'])->name('show')->middleware('role_or_permission:manage_contracts|view contracts');
            Route::post('/{id}/approve', [ContractsController::class, 'approveContract'])->name('approve')->middleware('role_or_permission:manage_contracts|view contracts');
            Route::post('/{id}/reject', [ContractsController::class, 'rejectContract'])->name('reject')->middleware('role_or_permission:manage_contracts|view contracts');
            Route::post('/{id}/renewal', [ContractsController::class, 'initiateRenewal'])->name('renewal.initiate')->middleware('role_or_permission:manage_contracts|view contracts');
            Route::post('/{id}/file', [ContractsController::class, 'fileContract'])->name('file')->middleware('role_or_permission:manage_contracts|view contracts');
            Route::post('/{id}/send-reminder', [ContractsController::class, 'sendReminder'])->name('send-reminder')->middleware('role_or_permission:manage_contracts|view contracts');
            Route::post('/{id}/vendor-found', [ContractsController::class, 'markVendorFound'])->name('vendor.found')->middleware('role_or_permission:manage_contracts|view contracts');
            Route::put('/{id}', [ContractsController::class, 'update'])->name('update')->middleware('role_or_permission:manage_contracts|view contracts');
            Route::put('/{id}/notifications', [ContractsController::class, 'updateNotificationSettings'])->name('update-notification')->middleware('role_or_permission:manage_contracts|view contracts');
            Route::delete('/{id}', [ContractsController::class, 'destroy'])->name('destroy')->middleware('role_or_permission:manage_contracts|view contracts');
            Route::post('/send-bulk-reminders', [ContractsController::class, 'sendBulkReminders'])->name('send-bulk-reminders')->middleware('role_or_permission:manage_contracts|view contracts');
            Route::post('/test-reminder-email', [ContractsController::class, 'testReminderEmail'])->name('test-reminder-email')->middleware('role_or_permission:manage_contracts|view contracts');
            Route::post('/test-near-expiry-notification', [ContractsController::class, 'testNearExpiryNotification'])->name('test-near-expiry-notification')->middleware('role_or_permission:manage_contracts|view contracts');
            Route::get('/get-contract-recipients', [ContractsController::class, 'getContractRecipients'])->name('get-contract-recipients')->middleware('role_or_permission:manage_contracts|view contracts');
            Route::post('/test-initiate-renewal', [ContractsController::class, 'testInitiateRenewal'])->name('test-initiate-renewal')->middleware('role_or_permission:manage_contracts|view contracts');
            Route::post('/run-near-expiry-command', [ContractsController::class, 'runNearExpiryCommand'])->name('run-near-expiry-command')->middleware('role_or_permission:manage_contracts|view contracts');
            Route::post('/run-expired-command', [ContractsController::class, 'runExpiredCommand'])->name('run-expired-command')->middleware('role_or_permission:manage_contracts|view contracts');
            Route::post('/run-hec-contracts-command', [ContractsController::class, 'runHecContractsCommand'])->name('run-hec-contracts-command')->middleware('role_or_permission:manage_contracts|view contracts');
            Route::post('/upload', [ContractsController::class, 'upload'])->name('upload')->middleware('role_or_permission:manage_contracts|view contracts');
            Route::post('/renewal', [ContractsController::class, 'initiateContractRenewal'])->name('renewal.store')->middleware('role_or_permission:manage_contracts|view contracts');
        });

        // HEC Contracts Module — authorization handled inside controller
        Route::prefix('hec-contracts')->name('hec-contracts.')->group(function () {
            Route::get('/',          [HecContractsController::class, 'index'])->name('index');
            Route::get('/export',    [HecContractsController::class, 'export'])->name('export');
            Route::get('/create',    [HecContractsController::class, 'create'])->name('create');
            Route::post('/',         [HecContractsController::class, 'store'])->name('store');
            Route::get('/{id}/edit', [HecContractsController::class, 'edit'])->name('edit');
            Route::put('/{id}',      [HecContractsController::class, 'update'])->name('update');
            Route::delete('/{id}',   [HecContractsController::class, 'destroy'])->name('destroy');
            Route::post('/{id}/renew',[HecContractsController::class, 'renew'])->name('renew');
            Route::post('/{id}/archive',[HecContractsController::class, 'archive'])->name('archive');
            Route::get('/{id}',      [HecContractsController::class, 'show'])->name('show');
        });

        // Legacy routes for backward compatibility (deprecated)
        Route::get('/Vendor-index', [VendorContractController::class, 'index'])->name('vendorContract.index')->middleware('permission:view contracts');
        Route::get('/vendor-create', [VendorContractController::class, 'create'])->name('vendorContract.create')->middleware('permission:view contracts');
        Route::get('/vendor-upload', [VendorContractController::class, 'uploadContract'])->name('vendorContract.upload')->middleware('permission:view contracts');
        Route::post('/vendor-uploadContract', [VendorContractController::class, 'upload'])->name('vendorContract.ExistingContract')->middleware('permission:view contracts');
        Route::get('/vendor-addNewContract', [VendorContractController::class, 'addNewContract'])->name('vendorContract.addNew')->middleware('permission:view contracts');
        Route::post('/vendor-contracts', [VendorContractController::class, 'store'])->name('vendorContract.store')->middleware('permission:view contracts');
        Route::get('vendor-contractsMake', [VendorContractController::class, 'makeContract'])->name('vendorContract.makeContract')->middleware('permission:view contracts');
        Route::post('vendor-contractNew', [VendorContractController::class, 'newContract'])->name('vendorContract.newContract')->middleware('permission:view contracts');
        Route::get('vendor-ContractApproval', [VendorContractController::class, 'contractApprovalIndex'])->name('vendorContract.contractApprovalIndex')->middleware('permission:view contracts');
        Route::post('vendor-approveContract', [VendorContractController::class, 'InitiateContractRenewal'])->name('vendorContract.approveContract')->middleware('permission:view contracts');
        Route::post('vendor-rejectContract', [VendorContractController::class, 'rejectContract'])->name('vendorContract.rejectContract')->middleware('permission:view contracts');

        Route::delete('/locum-requests/{id}', [LocumRequestController::class, 'destroy'])->name('locum-requests.destroy')->middleware('permission:delete locum requests');
        Route::post('/locum_reject_requests', [LocumRequestController::class, 'reject'])->name('locum-requests.reject')->middleware('permission:reject locum requests');


        // Procurements Module - Vendors
        Route::prefix('procurements/vendors')->name('procurements.vendors.')->group(function () {
            Route::get('/', [VendorsController::class, 'index'])->name('index')->middleware('role_or_permission:manage_vendor|view vendors');
            Route::get('/department-vendors', [VendorsController::class, 'departmentVendors'])->name('department-vendors')->middleware('role_or_permission:line-manager|view vendors');
            Route::get('/hec-department-vendors', [VendorsController::class, 'hecDepartmentVendors'])->name('hec-department-vendors')->middleware('role_or_permission:coo|cfo|cms|ccdro|view vendors');
            Route::get('/create', [VendorsController::class, 'create'])->name('create')->middleware('role_or_permission:manage_vendor|create vendor');
            Route::post('/', [VendorsController::class, 'store'])->name('store')->middleware('role_or_permission:manage_vendor|create vendor');
            Route::get('/{id}', [VendorsController::class, 'show'])->name('show')->middleware('role_or_permission:manage_vendor|line-manager|coo|cfo|cms|ccdro|view vendors');
            Route::get('/{id}/edit', [VendorsController::class, 'edit'])->name('edit')->middleware('permission:manage_vendor');
            Route::put('/{id}', [VendorsController::class, 'update'])->name('update')->middleware('permission:manage_vendor');
            Route::delete('/{id}', [VendorsController::class, 'destroy'])->name('destroy')->middleware('permission:manage_vendor');
            Route::post('/{id}/rate', [VendorsController::class, 'rate'])->name('rate')->middleware('permission:view vendors');
            Route::get('/{id}/ratings', [VendorsController::class, 'ratings'])->name('ratings')->middleware('role_or_permission:line-manager|coo|cfo|cms|ccdro|view vendors');
        });

        // Legacy routes for backward compatibility (deprecated)
        Route::get('/vendors-index', [VendorController::class, 'index'])->name('vendors.index')->middleware('permission:view vendors');
        Route::get('/vendors-create', [VendorController::class, 'create'])->name('vendors.create')->middleware('permission:create vendor');
        Route::post('/vendors-store', [VendorController::class, 'store'])->name('vendors.store')->middleware('permission:create vendor');


        //CCBRT Company Divisions
        Route::get('/company-divisions', [DivisionController::class, 'index'])->name('division.index')->middleware('role_or_permission:Super-Admin|super-admin|view entities');
        Route::post('/Division-store', [DivisionController::class, 'store'])->name('division.store')->middleware('role_or_permission:Super-Admin|super-admin|view entities');
        Route::get('/Division-create', [DivisionController::class, 'create'])->name('division.create')->middleware('role_or_permission:Super-Admin|super-admin|view entities');
        Route::get('/Division/{id}/edit', [DivisionController::class, 'edit'])->name('division.edit')->middleware('role_or_permission:Super-Admin|super-admin|view entities');
        Route::put('/Division/{id}', [DivisionController::class, 'update'])->name('division.update')->middleware('role_or_permission:Super-Admin|super-admin|view entities');
        Route::delete('/Division/{id}', [DivisionController::class, 'destroy'])->name('division.destroy')->middleware('role_or_permission:Super-Admin|super-admin|it|view entities');

        Route::post('locum-requests/{workflow}/approve', [LocumRequestController::class, 'approve'])->name('locum-requests.approve')->middleware('permission:approve locum requests');
        Route::post('locum-requests/bulk-reject', [LocumRequestController::class, 'bulkReject'])->name('locum-requests.bulk-reject')->middleware('permission:reject locum requests');
        Route::post('locum-requests/bulk-approve', [LocumRequestController::class, 'bulkApprove'])->name('locum-requests.bulk-approve')->middleware('permission:approve locum requests');

        Route::get('locum-requests/{locumRequest}/show', [LocumRequestController::class, 'showLocumRequest'])->name('locum-requests.showLocumRequest');
        Route::get('/locum-agreement-show', [LocumAgreementController::class, 'viewAgreementRequest'])->name('locum-requests.viewAgreementRequest');

        // ── Night Shift ──────────────────────────────────────────────────────
        Route::get('/night-shift',                  [NightShiftController::class, 'index'])->name('night-shift.index');
        Route::get('/night-shift/create',           [NightShiftController::class, 'create'])->name('night-shift.create');
        Route::post('/night-shift',                 [NightShiftController::class, 'store'])->name('night-shift.store');
        Route::get('/night-shift/{nightShift}',           [NightShiftController::class, 'show'])->name('night-shift.show');
        Route::get('/night-shift/{nightShift}/details',  [NightShiftController::class, 'details'])->name('night-shift.details');
        Route::get('/night-shift/{nightShift}/edit',     [NightShiftController::class, 'edit'])->name('night-shift.edit');
        Route::put('/night-shift/{nightShift}/resubmit', [NightShiftController::class, 'resubmit'])->name('night-shift.resubmit');
        Route::get('/night-shift-approve',                [NightShiftController::class, 'approveIndex'])->name('night-shift.approve.index');
        Route::post('/night-shift/{nightShift}/approve',  [NightShiftController::class, 'approve'])->name('night-shift.approve');
        Route::post('/night-shift/{nightShift}/reject',   [NightShiftController::class, 'reject'])->name('night-shift.reject');
        Route::post('/night-shift/bulk-approve',          [NightShiftController::class, 'bulkApprove'])->name('night-shift.bulk-approve');
        Route::post('/night-shift/bulk-reject',           [NightShiftController::class, 'bulkReject'])->name('night-shift.bulk-reject');
        Route::get('/night-shift/{nightShift}/biotime',   [NightShiftController::class, 'biotimeData'])->name('night-shift.biotime');
        Route::get('/night-shift-report',                [NightShiftController::class, 'report'])->name('night-shift.report');
        Route::get('/night-shift-report/export',          [NightShiftController::class, 'reportExport'])->name('night-shift.report.export');

        //biotime
        Route::post('/fetch-biotime', [LocumRequestController::class, 'fetchBioTimeData'])->name('locum-requests.fetch-biotime')->middleware('permission:create locum requests');
        Route::post('/fetch-biotime-incharge', [LocumRequestController::class, 'inchargeFetchBioTimeData'])->name('locum-requests.fetch-biotime-incharge')->middleware('permission:create locum requests');

        Route::get('done-requests/', [LocumRequestController::class, 'report'])->name('locum-requests.report');
        Route::get('/locum-requests/approved', [LocumRequestController::class, 'approvedLocumRequests'])->name('locum_requests.approved')->middleware('permission:view locum reports');
        // Backward compatibility: actioned method redirects to report
        //         //locum request


        // Locum Requests for Staff
        Route::get('/create-for-staff', [LocumRequestController::class, 'createForStaff'])->name('locum-requests.create-for-staff')->middleware('permission:create locum requests');
        Route::post('/claim-for-staff', [LocumRequestController::class, 'claimForStaff'])->name('locum-requests.claim-for-staff')->middleware('permission:create locum requests');

        Route::get('/nhif-registration', [NhifRegistrationController::class, 'index'])->name('nhif_registration.index')->middleware('permission:access nhif registration');
        Route::post('/nhif-store', [NhifRegistrationController::class, 'store'])->name('nhif_registration.store')->middleware('permission:access nhif registration');

        Route::get('/hec', [HecController::class, 'index'])->name('hec.index')->middleware('permission:view departments');
        Route::get('/hec/create', [HecController::class, 'create'])->name('hec.create')->middleware('permission:view departments');
        Route::post('/hec/addHec', [HecController::class, 'addHec'])->name('hec.addHec')->middleware('permission:view departments');
        Route::get('/hec/{id}/edit', [HecController::class, 'edit'])->name('hec.edit')->middleware('permission:view departments');
        Route::put('/hec/{id}/update', [HecController::class, 'update'])->name('hec.update')->middleware('permission:view departments');


        // Change Request Categories Management
        Route::resource('tariff-categories', TariffCategoryController::class)->middleware('permission:manage change request categories');
        Route::resource('service-categories', ServiceCategoryController::class)->middleware('permission:manage change request categories');
        Route::resource('payment-types', PaymentTypeController::class)->middleware('permission:manage change request categories');

        Route::get('/settings/email', [SettingsController::class, 'emailSettings'])->name('settings.email')->middleware('permission:view settings');
        Route::post('/settings/email/update', [SettingsController::class, 'updateMailSettings'])->name('settings.email.update')->middleware('permission:view settings');
        Route::post('/settings/email/test', [SettingsController::class, 'sendTestEmail'])->name('settings.email.test')->middleware('permission:view settings');
        Route::post('/settings/email/test-queue', [SettingsController::class, 'sendTestQueueEmail'])->name('settings.email.test-queue')->middleware('permission:view settings');

        Route::get('/settings/maintenance', [SettingsController::class, 'maintenanceMode'])->name('settings.maintenance')->middleware('permission:manage maintenance mode');
        Route::post('/settings/maintenance/update', [SettingsController::class, 'updateMaintenanceMode'])->name('settings.maintenance.update')->middleware('permission:manage maintenance mode');

        Route::get('/settings/deadlines', [SettingsController::class, 'deadlineSettings'])->name('settings.deadlines')->middleware('permission:view settings');
        Route::post('/settings/deadlines/update', [SettingsController::class, 'updateDeadlineSettings'])->name('settings.deadlines.update')->middleware('permission:view settings');

        Route::get('/settings/jobs', [SettingsController::class, 'jobMonitor'])->name('settings.jobs')->middleware('permission:view settings');
        Route::post('/settings/jobs/retry/{uuid}', [SettingsController::class, 'retryFailedJob'])->name('settings.jobs.retry')->middleware('permission:view settings');
        Route::post('/settings/jobs/retry-all', [SettingsController::class, 'retryAllFailedJobs'])->name('settings.jobs.retry-all')->middleware('permission:view settings');

        Route::get('/settings/requisition-flow', function () {
            return view('settings.requisition-flow');
        })->name('settings.requisition-flow')->middleware('permission:view settings');

        Route::get('/sap', [SapAccessController::class, 'index'])->name('sap.index')->middleware('permission:view sap access');
        Route::get('/sap/create', [SapAccessController::class, 'create'])->name('sap.create')->middleware('role_or_permission:Super-Admin|super-admin|it|view sap access');
        Route::post('/sap/store', [SapAccessController::class, 'store'])->name('sap.store')->middleware('role_or_permission:Super-Admin|super-admin|it|view sap access');
        Route::get('/sap/{id}/edit', [SapAccessController::class, 'edit'])->name('sap.edit')->middleware('role_or_permission:Super-Admin|super-admin|it|view sap access');
        Route::put('/sap/{id}/update', [SapAccessController::class, 'update'])->name('sap.update')->middleware('role_or_permission:Super-Admin|super-admin|it|view sap access');
        Route::delete('/sap/{id}', [SapAccessController::class, 'destroy'])->name('sap.destroy')->middleware('role_or_permission:Super-Admin|super-admin|it|view sap access');


        // Fix-Flex Contracts
        Route::prefix('contracts/fixed-flex')->name('contracts.fixed-flex.')->middleware('permission:view contracts')->group(function () {
            Route::get('/', [FixedFlexContractController::class, 'index'])->name('index');
            Route::get('/create', [FixedFlexContractController::class, 'create'])->name('create');
            Route::post('/', [FixedFlexContractController::class, 'store'])->name('store');
            Route::get('/{id}', [FixedFlexContractController::class, 'show'])->name('show');
            // Add other routes as needed
        });



        // routes/web.php
        Route::resource('contract-templates', ContractTemplateController::class)->middleware('permission:view contracts');

        Route::prefix('contracts/fixed-flex')->middleware('permission:view contracts')->group(function () {
            Route::get('/create', [FixedFlexContractController::class, 'create'])->name('contracts.fixed-flex.create');
            Route::post('/store', [FixedFlexContractController::class, 'store'])->name('contracts.fixed-flex.store');
            Route::get('/{id}', [FixedFlexContractController::class, 'show'])->name('contracts.fixed-flex.show');
            Route::get('/{id}/download', [FixedFlexContractController::class, 'download'])->name('contracts.fixed-flex.download');
        });
    });

    // Asset Management Routes
    Route::prefix('asset-management')->name('asset-management.')->group(function () {
        // Asset Categories
        Route::resource('asset-categories', \App\Http\Controllers\AssetCategoryController::class)->middleware('permission:manage asset categories');

        // Assets
        Route::get('assets/tag-management', [\App\Http\Controllers\AssetController::class, 'tagManagement'])->name('assets.tag-management')->middleware('permission:manage assets');
        Route::get('assets/get-next-tag/{categoryId}', [\App\Http\Controllers\AssetController::class, 'getNextTag'])->name('assets.get-next-tag')->middleware('permission:manage assets');
        Route::get('assets/import', [\App\Http\Controllers\AssetController::class, 'showImport'])->name('assets.import')->middleware('permission:manage assets');
        Route::post('assets/import', [\App\Http\Controllers\AssetController::class, 'import'])->name('assets.import.store')->middleware('permission:manage assets');
        Route::get('assets/export', [\App\Http\Controllers\AssetController::class, 'export'])->name('assets.export')->middleware('permission:manage assets');
        Route::resource('assets', \App\Http\Controllers\AssetController::class)->middleware('permission:manage assets');

        // Asset Assignments
        Route::get('assignments', [\App\Http\Controllers\AssetAssignmentController::class, 'index'])->name('assignments.index')->middleware('permission:manage assets');
        Route::get('assignments/{assetId}/create', [\App\Http\Controllers\AssetAssignmentController::class, 'create'])->name('assignments.create')->middleware('permission:manage assets');
        Route::post('assignments/{assetId}', [\App\Http\Controllers\AssetAssignmentController::class, 'store'])->name('assignments.store')->middleware('permission:manage assets');
        Route::post('assignments/{assetId}/unassign', [\App\Http\Controllers\AssetAssignmentController::class, 'unassign'])->name('assignments.unassign')->middleware('permission:manage assets');

        // Asset Movements
        Route::get('assets/{assetId}/movements/create', [\App\Http\Controllers\AssetMovementController::class, 'create'])->name('movements.create');
        Route::post('assets/{assetId}/movements', [\App\Http\Controllers\AssetMovementController::class, 'store'])->name('movements.store');
        Route::get('movements', [\App\Http\Controllers\AssetMovementController::class, 'index'])->name('movements.index');

        // Asset Maintenance
        Route::get('assets/{assetId}/maintenance/create', [\App\Http\Controllers\AssetMaintenanceController::class, 'create'])->name('maintenance.create');
        Route::post('assets/{assetId}/maintenance', [\App\Http\Controllers\AssetMaintenanceController::class, 'store'])->name('maintenance.store');
        Route::get('maintenance', [\App\Http\Controllers\AssetMaintenanceController::class, 'index'])->name('maintenance.index');

        // Asset Retirement
        Route::get('assets/{assetId}/retirement/create', [\App\Http\Controllers\AssetRetirementController::class, 'create'])->name('retirement.create');
        Route::post('assets/{assetId}/retirement', [\App\Http\Controllers\AssetRetirementController::class, 'store'])->name('retirement.store');
        Route::get('retirement', [\App\Http\Controllers\AssetRetirementController::class, 'index'])->name('retirement.index');

        // Reports & Dashboard
        Route::get('dashboard', [\App\Http\Controllers\AssetReportController::class, 'dashboard'])->name('dashboard');
        Route::get('reports/export', [\App\Http\Controllers\AssetReportController::class, 'exportReport'])->name('reports.export');
    });
});
