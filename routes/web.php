<?php

use App\Http\Controllers\AdminProceedController;
use App\Http\Controllers\AppearanceController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\ContractDocumentController;
use App\Http\Controllers\ContractFollowUpController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\KontrakSewaanController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RentalApplicationController;
use App\Http\Controllers\SidebarNotificationController;
use App\Http\Controllers\StatusPermohonanController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.store')->middleware('throttle:login');
    Route::get('/lupa-kata-laluan', [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('/lupa-kata-laluan', [PasswordResetLinkController::class, 'store'])->name('password.email')->middleware('throttle:password-reset');
    Route::get('/set-semula-kata-laluan/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
    Route::post('/set-semula-kata-laluan', [NewPasswordController::class, 'store'])->name('password.update')->middleware('throttle:password-reset');
});

Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('/pengguna', [UserController::class, 'index'])->name('users.index');
    Route::get('/pengguna/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
    Route::put('/pengguna/{user}', [UserController::class, 'update'])->name('users.update');
    Route::get('/pengguna/{user}/audit-trail', [UserController::class, 'auditTrail'])->name('users.audit-trail');
    Route::get('/pengguna/daftar', [AuthController::class, 'showRegisterForm'])->name('users.create');
    Route::post('/pengguna/daftar', [AuthController::class, 'storeUser'])->name('users.store')->middleware('throttle:form-actions');
    Route::get('/status-permohonan', [StatusPermohonanController::class, 'index'])->name('status-permohonan.index');
    Route::get('/status-permohonan/sync', [StatusPermohonanController::class, 'sync'])
        ->name('status-permohonan.sync')
        ->middleware('throttle:status-permohonan-sync');
    Route::get('/status-permohonan/{contract}/semak', [StatusPermohonanController::class, 'review'])->name('status-permohonan.review');
    Route::post('/status-permohonan/{contract}/sahkan', [StatusPermohonanController::class, 'approve'])->name('status-permohonan.approve')->middleware('throttle:form-actions');
    Route::post('/status-permohonan/{contract}/draf-perjanjian', [StatusPermohonanController::class, 'uploadDraftAgreement'])->name('status-permohonan.upload-draft')->middleware('throttle:form-actions');
    Route::post('/status-permohonan/{contract}/lulus-puu', [StatusPermohonanController::class, 'approvePuuReview'])->name('status-permohonan.approve-puu')->middleware('throttle:form-actions');
    Route::post('/status-permohonan/{contract}/batal-puu', [StatusPermohonanController::class, 'rejectPuuReview'])->name('status-permohonan.reject-puu')->middleware('throttle:form-actions');
    Route::post('/status-permohonan/{contract}/selesai', [StatusPermohonanController::class, 'complete'])->name('status-permohonan.complete')->middleware('throttle:form-actions');
    Route::post('/status-permohonan/{contract}/kembali-admin', [StatusPermohonanController::class, 'returnDraftToHq'])->name('status-permohonan.return-hq')->middleware('throttle:form-actions');
    Route::patch('/status-permohonan/{contract}/pengesahan-negeri-autosave', [StatusPermohonanController::class, 'autosaveNegeriDraftAcknowledgements'])->name('status-permohonan.negeri-acknowledgements-autosave')->middleware('throttle:autosave');
    Route::post('/status-permohonan/{contract}/selesai-perjanjian', [StatusPermohonanController::class, 'finalize'])->name('status-permohonan.finalize')->middleware('throttle:form-actions');
    Route::patch('/status-permohonan/{contract}/pengesahan-ibu-pejabat-autosave', [StatusPermohonanController::class, 'autosaveHqDraftAcknowledgements'])->name('status-permohonan.hq-acknowledgements-autosave')->middleware('throttle:autosave');
    Route::post('/status-permohonan/{contract}/mati-setem', [StatusPermohonanController::class, 'completeMatiSetem'])->name('status-permohonan.complete-mati-setem')->middleware('throttle:form-actions');
    Route::patch('/status-permohonan/{contract}/mati-setem-autosave', [StatusPermohonanController::class, 'autosaveNegeriMatiSetemAcknowledgements'])->name('status-permohonan.mati-setem-autosave')->middleware('throttle:autosave');
    Route::patch('/status-permohonan/{contract}/checklist-autosave', [StatusPermohonanController::class, 'autosaveChecklist'])->name('status-permohonan.checklist-autosave')->middleware('throttle:autosave');
    Route::post('/status-permohonan/{contract}/tarik-semula', [StatusPermohonanController::class, 'requestWithdrawal'])->name('status-permohonan.request-withdrawal')->middleware('throttle:form-actions');
    Route::post('/status-permohonan/{contract}/tarik-semula/keputusan', [StatusPermohonanController::class, 'resolveWithdrawal'])->name('status-permohonan.resolve-withdrawal')->middleware('throttle:form-actions');
    Route::delete('/status-permohonan/{contract}', [StatusPermohonanController::class, 'destroy'])->name('status-permohonan.destroy');
    Route::post('/status-permohonan/{contract}/hantar-hq', [StatusPermohonanController::class, 'submitToHq'])->name('status-permohonan.submit-hq')->middleware('throttle:form-actions');
    Route::post('/kontrak-sewaan/{contract}/permohonan-susulan', [ContractFollowUpController::class, 'store'])->name('kontrak-sewaan.follow-up')->middleware('throttle:form-actions');
    Route::delete('/permohonan-susulan/{contract}', [ContractFollowUpController::class, 'destroy'])->name('kontrak-sewaan.follow-up.cancel')->middleware('throttle:form-actions');
    Route::get('/permohonan', [RentalApplicationController::class, 'create'])->name('application.form');
    Route::post('/permohonan', [RentalApplicationController::class, 'store'])->name('application.store')->middleware('throttle:form-actions');
    Route::get('/permohonan/{contract}/edit', [RentalApplicationController::class, 'edit'])->name('application.edit');
    Route::put('/permohonan/{contract}', [RentalApplicationController::class, 'update'])->name('application.update')->middleware('throttle:form-actions');
    Route::patch('/permohonan/{contract}/autosave', [RentalApplicationController::class, 'autosave'])->name('application.autosave')->middleware('throttle:autosave');
    Route::get('/kontrak/{contract}/proceed', [AdminProceedController::class, 'show'])->name('admin-proceed.show');
    Route::put('/kontrak/{contract}/proceed', [AdminProceedController::class, 'update'])->name('admin-proceed.update')->middleware('throttle:form-actions');
    Route::get('/kontrak/{contract}/dokumen', [ContractDocumentController::class, 'create'])->name('documents.create');
    Route::post('/kontrak/{contract}/dokumen', [ContractDocumentController::class, 'store'])->name('documents.store');
    Route::get('/kontrak/{contract}/dokumen/{document}', [ContractDocumentController::class, 'show'])
        ->name('documents.show')
        ->scopeBindings();
});

Route::middleware(['auth', 'admin.hq'])->group(function () {
    Route::patch('/pengguna/{user}/status', [UserController::class, 'toggleStatus'])->name('users.toggle-status');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/sidebar-notifications', SidebarNotificationController::class)
        ->name('sidebar-notifications')
        ->middleware('throttle:sidebar-notifications');

    Route::get('/peti-masuk', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/peti-masuk/baca-semua', [NotificationController::class, 'markAllAsRead'])
        ->name('notifications.mark-all-read')
        ->middleware('throttle:form-actions');

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/kontrak-sewaan', [KontrakSewaanController::class, 'index'])->name('kontrak-sewaan.index');
    Route::get('/kontrak-sewaan/{contract}', [KontrakSewaanController::class, 'show'])->name('kontrak-sewaan.show');

    Route::get('/profil', [ProfileController::class, 'show'])->name('profile.show');
    Route::get('/profil/kemaskini', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profil', [ProfileController::class, 'update'])->name('profile.update');

    Route::get('/penampilan', [AppearanceController::class, 'show'])->name('penampilan.show');
});
