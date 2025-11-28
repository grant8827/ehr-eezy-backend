<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PatientController;
use App\Http\Controllers\Api\StaffController;
use App\Http\Controllers\Api\DoctorController;
use App\Http\Controllers\Api\AppointmentController;
use App\Http\Controllers\Api\MedicalRecordController;
use App\Http\Controllers\Api\VitalSignsController;
use App\Http\Controllers\Api\LabResultController;
use App\Http\Controllers\Api\PrescriptionController;
use App\Http\Controllers\Api\PharmacyController;
use App\Http\Controllers\Api\PharmacyStaffController;
use App\Http\Controllers\Api\MedicalDocumentController;
use App\Http\Controllers\Api\BillController;
use App\Http\Controllers\Api\MessageController;
use App\Http\Controllers\Api\TelehealthController;
use App\Http\Controllers\Api\EmailController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Health check endpoint for Railway deployment
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'timestamp' => now(),
        'app' => config('app.name'),
        'environment' => config('app.env'),
        'database' => 'connected'
    ]);
});

// Load debug routes in development
if (config('app.debug')) {
    require __DIR__ . '/debug.php';
}

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Public patient invitation routes (for patient registration)
Route::prefix('patient-invitations')->group(function () {
    Route::get('check', [App\Http\Controllers\PatientInvitationController::class, 'checkInvitation']);
    Route::post('complete', [App\Http\Controllers\PatientInvitationController::class, 'completeRegistration']);
});

// Public authentication routes
Route::prefix('auth')->group(function () {
    Route::post('/setup-business', [AuthController::class, 'setupBusiness']);
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);

    // Protected auth routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/refresh', [AuthController::class, 'refresh']);

        // Profile management routes
        Route::put('/profile', [AuthController::class, 'updateProfile']);
        Route::post('/change-password', [AuthController::class, 'changePassword']);
        Route::post('/profile-picture', [AuthController::class, 'uploadProfilePicture']);
        Route::put('/notification-preferences', [AuthController::class, 'updateNotificationPreferences']);
        Route::post('/deactivate-account', [AuthController::class, 'deactivateAccount']);
    });
});

// Protected API routes
Route::middleware('auth:sanctum')->group(function () {
    // Patient routes
    Route::apiResource('patients', PatientController::class);

    // Patient Invitation routes
    Route::prefix('patient-invitations')->group(function () {
        Route::post('/', [App\Http\Controllers\PatientInvitationController::class, 'sendInvitation']);
        Route::get('/', [App\Http\Controllers\PatientInvitationController::class, 'getAllInvitations']);
        Route::get('/stats', [App\Http\Controllers\PatientInvitationController::class, 'getStats']);
        Route::post('{id}/resend', [App\Http\Controllers\PatientInvitationController::class, 'resendInvitation']);
        Route::post('{id}/cancel', [App\Http\Controllers\PatientInvitationController::class, 'cancelInvitation']);
        Route::get('{id}', [App\Http\Controllers\PatientInvitationController::class, 'getInvitationById']);
    });

    // Staff routes
    Route::apiResource('staff', StaffController::class);
    Route::get('staff-stats', [StaffController::class, 'stats']);

    // Doctor routes
    Route::apiResource('doctors', DoctorController::class);

    // Appointment routes
    Route::apiResource('appointments', AppointmentController::class);
    Route::get('appointments/availability/check', [AppointmentController::class, 'checkAvailability']);
    Route::patch('appointments/{appointment}/status', [AppointmentController::class, 'updateStatus']);

    // Medical record routes
    Route::apiResource('medical-records', MedicalRecordController::class);
    Route::get('patients/{patient}/medical-records', [MedicalRecordController::class, 'patientRecords']);
    Route::get('medical-records/stats', [MedicalRecordController::class, 'statistics']);

    // Vital Signs routes
    Route::apiResource('vital-signs', VitalSignsController::class);
    Route::get('patients/{patient}/vital-signs', [VitalSignsController::class, 'patientVitalSigns']);
    Route::get('patients/{patient}/vital-signs/latest', [VitalSignsController::class, 'latestVitalSigns']);
    Route::get('patients/{patient}/vital-signs/trends', [VitalSignsController::class, 'vitalSignsTrends']);

    // Lab Results routes
    Route::apiResource('lab-results', LabResultController::class);
    Route::get('patients/{patient}/lab-results', [LabResultController::class, 'patientLabResults']);
    Route::get('lab-results/category/{category}', [LabResultController::class, 'byCategory']);
    Route::get('lab-results/abnormal', [LabResultController::class, 'abnormalResults']);

    // Prescriptions routes
    Route::apiResource('prescriptions', PrescriptionController::class);
    Route::get('patients/{patient}/prescriptions', [PrescriptionController::class, 'patientPrescriptions']);
    Route::patch('prescriptions/{prescription}/status', [PrescriptionController::class, 'updateStatus']);
    Route::get('prescriptions/active', [PrescriptionController::class, 'activeList']);
    Route::get('prescriptions/expiring', [PrescriptionController::class, 'expiringList']);
    Route::post('prescriptions/{prescription}/refill', [PrescriptionController::class, 'refill']);
    Route::post('prescriptions/{prescription}/send-to-pharmacy', [PrescriptionController::class, 'sendToPharmacy']);

    // Pharmacy routes
    Route::apiResource('pharmacies', PharmacyController::class);
    Route::get('pharmacies/nearby', [PharmacyController::class, 'nearby']);
    Route::get('pharmacies/{pharmacy}/prescriptions', [PharmacyController::class, 'prescriptions']);
    Route::patch('pharmacies/{pharmacy}/prescriptions/{prescription}/status', [PharmacyController::class, 'updatePrescriptionStatus']);

    // Pharmacy Staff routes
    Route::apiResource('pharmacy-staff', PharmacyStaffController::class);
    Route::get('pharmacies/{pharmacy}/staff', [PharmacyStaffController::class, 'byPharmacy']);
    Route::get('pharmacy-staff/expiring-licenses', [PharmacyStaffController::class, 'expiringLicenses']);
    Route::patch('pharmacy-staff/{id}/status', [PharmacyStaffController::class, 'updateStatus']);

    // Medical Documents routes
    Route::apiResource('medical-documents', MedicalDocumentController::class);
    Route::get('patients/{patient}/documents', [MedicalDocumentController::class, 'patientDocuments']);
    Route::get('medical-documents/{medicalDocument}/download', [MedicalDocumentController::class, 'download']);
    Route::get('medical-documents/metadata', [MedicalDocumentController::class, 'metadata']);

    // Billing routes
    Route::apiResource('bills', BillController::class);
    Route::post('bills/{bill}/pay', [BillController::class, 'markAsPaid']);
    Route::get('patients/{patient}/bills', [BillController::class, 'patientBills']);

    // Message routes
    Route::apiResource('messages', MessageController::class);
    Route::post('messages/{message}/read', [MessageController::class, 'markAsRead']);
    Route::get('messages/unread/count', [MessageController::class, 'unreadCount']);
    Route::get('messages/conversations', [MessageController::class, 'conversations']);
    Route::get('messages/available-users', [MessageController::class, 'availableUsers']);

    // Telehealth routes
    Route::apiResource('telehealth', TelehealthController::class);
    Route::post('telehealth/{session}/start', [TelehealthController::class, 'startSession']);
    Route::post('telehealth/{session}/end', [TelehealthController::class, 'endSession']);

    // Email routes
    Route::prefix('emails')->group(function () {
        Route::post('patient-invitation', [EmailController::class, 'sendPatientInvitation']);
        Route::post('appointment-reminder', [EmailController::class, 'sendAppointmentReminder']);
        Route::post('billing-notification', [EmailController::class, 'sendBillingNotification']);
        Route::post('custom', [EmailController::class, 'sendCustomEmail']);
        Route::post('test-smtp', [EmailController::class, 'testSmtpConfiguration']);
        Route::get('templates', [EmailController::class, 'getEmailTemplates']);
    });

    // Dashboard routes
    Route::get('dashboard/stats', function (Request $request) {
        $user = $request->user();

        // Return different stats based on user role
        if ($user->isDoctor()) {
            return response()->json([
                'todayAppointments' => 8,
                'totalPatients' => 150,
                'pendingRecords' => 12,
                'unreadMessages' => 5,
            ]);
        } elseif ($user->isPatient()) {
            return response()->json([
                'upcomingAppointments' => 2,
                'medicalRecords' => 15,
                'unreadMessages' => 3,
                'prescriptions' => 8,
            ]);
        } else {
            return response()->json([
                'totalPatients' => 1247,
                'todayAppointments' => 8,
                'monthlyRevenue' => 45670,
                'pendingRecords' => 12,
            ]);
        }
    });
});
