<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Models\PatientInvitation;
use App\Models\User;
use App\Models\Patient;
use Illuminate\Support\Facades\Schema;

// Debug route to test patient registration
Route::get('/debug/patient-registration', function () {
    try {
        // Check database connection
        $dbConnection = \DB::connection()->getPdo();
        
        // Check if tables exist
        $tablesExist = [
            'users' => Schema::hasTable('users'),
            'patients' => Schema::hasTable('patients'),
            'patient_invitations' => Schema::hasTable('patient_invitations'),
        ];
        
        // Get patient table columns
        $patientColumns = Schema::hasTable('patients') 
            ? Schema::getColumnListing('patients') 
            : [];
            
        // Get user table columns
        $userColumns = Schema::hasTable('users') 
            ? Schema::getColumnListing('users') 
            : [];
        
        // Check for recent patient invitations
        $recentInvitations = PatientInvitation::latest()->take(5)->get();
        
        return response()->json([
            'database_connected' => $dbConnection ? true : false,
            'tables_exist' => $tablesExist,
            'patient_columns' => $patientColumns,
            'user_columns' => $userColumns,
            'recent_invitations' => $recentInvitations->map(function($inv) {
                return [
                    'id' => $inv->id,
                    'email' => $inv->email,
                    'status' => $inv->status,
                    'created_at' => $inv->created_at,
                ];
            }),
            'env_check' => [
                'app_url' => env('APP_URL'),
                'frontend_url' => env('FRONTEND_URL'),
                'db_connection' => env('DB_CONNECTION'),
            ]
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ], 500);
    }
});

// Test patient registration endpoint
Route::post('/debug/test-registration', function (Request $request) {
    try {
        \Log::info('Test registration attempt:', $request->all());
        
        // Validate basic input
        $request->validate([
            'email' => 'required|email',
            'token' => 'required|string',
            'first_name' => 'required|string',
            'last_name' => 'required|string',
            'password' => 'required|string|min:8',
        ]);
        
        // Check if invitation exists
        $invitation = PatientInvitation::where('email', $request->email)
            ->where('invitation_token', $request->token)
            ->first();
            
        if (!$invitation) {
            return response()->json([
                'error' => 'Invitation not found',
                'debug' => [
                    'searched_email' => $request->email,
                    'searched_token' => $request->token,
                    'total_invitations' => PatientInvitation::count(),
                ]
            ], 404);
        }
        
        // Check if user already exists
        $existingUser = User::where('email', $request->email)->first();
        if ($existingUser) {
            return response()->json([
                'error' => 'User already exists',
                'user_id' => $existingUser->id,
            ], 422);
        }
        
        return response()->json([
            'success' => true,
            'message' => 'Validation passed',
            'invitation' => [
                'id' => $invitation->id,
                'status' => $invitation->status,
                'expires_at' => $invitation->expires_at,
            ]
        ]);
        
    } catch (\Exception $e) {
        \Log::error('Test registration failed:', [
            'error' => $e->getMessage(),
            'request' => $request->all(),
        ]);
        
        return response()->json([
            'error' => $e->getMessage(),
            'line' => $e->getLine(),
            'file' => $e->getFile(),
        ], 500);
    }
});