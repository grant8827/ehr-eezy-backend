<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PatientController;
use App\Http\Controllers\Api\StaffController;
use App\Http\Controllers\Api\DoctorController;
use App\Http\Controllers\Api\AppointmentController;
use App\Http\Controllers\Api\MedicalRecordController;
use App\Http\Controllers\Api\BillController;
use App\Http\Controllers\Api\MessageController;
use App\Http\Controllers\Api\TelehealthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

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
    });
});

// Protected API routes
Route::middleware('auth:sanctum')->group(function () {
    // Patient routes
    Route::apiResource('patients', PatientController::class);

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

    // Billing routes
    Route::apiResource('bills', BillController::class);
    Route::post('bills/{bill}/pay', [BillController::class, 'markAsPaid']);
    Route::get('patients/{patient}/bills', [BillController::class, 'patientBills']);

    // Message routes
    Route::apiResource('messages', MessageController::class);
    Route::post('messages/{message}/read', [MessageController::class, 'markAsRead']);
    Route::get('messages/unread/count', [MessageController::class, 'unreadCount']);

    // Telehealth routes
    Route::apiResource('telehealth', TelehealthController::class);
    Route::post('telehealth/{session}/start', [TelehealthController::class, 'startSession']);
    Route::post('telehealth/{session}/end', [TelehealthController::class, 'endSession']);

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
