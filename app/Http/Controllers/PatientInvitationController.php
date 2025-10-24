<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use App\Mail\PatientInvitationMail;
use App\Models\PatientInvitation;
use Carbon\Carbon;

class PatientInvitationController extends Controller
{
    /**
     * Send a patient invitation email
     */
    public function sendInvitation(Request $request)
    {
        try {
            // Validate request data
            $validator = Validator::make($request->all(), [
                'first_name' => 'required|string|max:255',
                'last_name' => 'required|string|max:255',
                'email' => 'required|email|max:255',
                'phone' => 'nullable|string|max:20',
                'date_of_birth' => 'nullable|date',
                'gender' => 'nullable|string|in:male,female,other,prefer_not_to_say',
                'personal_message' => 'nullable|string|max:1000',
                'patient_id' => 'nullable|integer' // For existing patients
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $validatedData = $validator->validated();

            // Generate invitation token
            $token = $this->generateInvitationToken();

            // Create invitation record
            $invitation = $this->createInvitationRecord($validatedData, $token);

            // Prepare email data
            $emailData = [
                'patient_name' => $validatedData['first_name'] . ' ' . $validatedData['last_name'],
                'patient_email' => $validatedData['email'],
                'patient_phone' => $validatedData['phone'] ?? null,
                'date_of_birth' => $validatedData['date_of_birth'] ?? null,
                'gender' => $validatedData['gender'] ?? null,
                'personal_message' => $validatedData['personal_message'] ?? null,
                'registration_url' => $this->generateRegistrationUrl($validatedData['email'], $token),
                'business_name' => config('app.name', 'EHR-Eezy'),
                'invitation_token' => $token,
                'expires_at' => now()->addDays(7)->format('M j, Y'),
                'sent_at' => now()->format('M j, Y g:i A')
            ];

            // Send email
            Mail::to($validatedData['email'])->send(new PatientInvitationMail($emailData));

            // Update invitation status
            $invitation->update(['status' => 'sent', 'sent_at' => now()]);

            return response()->json([
                'success' => true,
                'message' => 'Invitation sent successfully',
                'invitation' => [
                    'id' => $invitation->id,
                    'first_name' => $invitation->first_name,
                    'last_name' => $invitation->last_name,
                    'email' => $invitation->email,
                    'phone' => $invitation->phone,
                    'date_of_birth' => $invitation->date_of_birth,
                    'gender' => $invitation->gender,
                    'personal_message' => $invitation->message,
                    'status' => 'sent',
                    'created_at' => $invitation->created_at,
                    'expires_at' => $invitation->expires_at
                ],
                'registration_url' => $emailData['registration_url']
            ]);

        } catch (\Exception $e) {
            \Log::error('Failed to send patient invitation', [
                'error' => $e->getMessage(),
                'request_data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to send invitation: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all invitations
     */
    public function getAllInvitations(Request $request)
    {
        try {
            $invitations = PatientInvitation::orderBy('created_at', 'desc')
                ->paginate(20);

            return response()->json([
                'success' => true,
                'invitations' => $invitations->items(),
                'pagination' => [
                    'total' => $invitations->total(),
                    'per_page' => $invitations->perPage(),
                    'current_page' => $invitations->currentPage(),
                    'last_page' => $invitations->lastPage()
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch invitations: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get invitation by ID
     */
    public function getInvitationById($id)
    {
        try {
            $invitation = PatientInvitation::findOrFail($id);

            return response()->json([
                'success' => true,
                'invitation' => [
                    'id' => $invitation->id,
                    'first_name' => $invitation->first_name,
                    'last_name' => $invitation->last_name,
                    'email' => $invitation->email,
                    'phone' => $invitation->phone,
                    'date_of_birth' => $invitation->date_of_birth,
                    'gender' => $invitation->gender,
                    'personal_message' => $invitation->message,
                    'status' => $invitation->status,
                    'created_at' => $invitation->created_at,
                    'expires_at' => $invitation->expires_at,
                    'resent_at' => $invitation->updated_at,
                    'registered_at' => $invitation->registered_at
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Invitation not found'
            ], 404);
        }
    }

    /**
     * Resend an invitation
     */
    public function resendInvitation($id)
    {
        try {
            $invitation = PatientInvitation::findOrFail($id);

            // Prepare email data
            $emailData = [
                'patient_name' => $invitation->first_name . ' ' . $invitation->last_name,
                'patient_email' => $invitation->email,
                'patient_phone' => $invitation->phone,
                'date_of_birth' => $invitation->date_of_birth,
                'gender' => $invitation->gender,
                'personal_message' => $invitation->message,
                'registration_url' => $this->generateRegistrationUrl($invitation->email, $invitation->invitation_token),
                'business_name' => config('app.name', 'EHR-Eezy'),
                'invitation_token' => $invitation->invitation_token,
                'expires_at' => $invitation->expires_at->format('M j, Y'),
                'sent_at' => now()->format('M j, Y g:i A')
            ];

            // Send email
            Mail::to($invitation->email)->send(new PatientInvitationMail($emailData));

            // Update invitation
            $invitation->update([
                'status' => 'sent',
                'sent_at' => now(),
                'resent_count' => $invitation->resent_count + 1
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Invitation resent successfully',
                'invitation' => [
                    'id' => $invitation->id,
                    'first_name' => $invitation->first_name,
                    'last_name' => $invitation->last_name,
                    'email' => $invitation->email,
                    'phone' => $invitation->phone,
                    'date_of_birth' => $invitation->date_of_birth,
                    'gender' => $invitation->gender,
                    'personal_message' => $invitation->message,
                    'status' => $invitation->status,
                    'created_at' => $invitation->created_at,
                    'updated_at' => $invitation->updated_at,
                    'expires_at' => $invitation->expires_at,
                    'registered_at' => $invitation->registered_at
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to resend invitation: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cancel an invitation
     */
    public function cancelInvitation($id)
    {
        try {
            $invitation = PatientInvitation::findOrFail($id);
            $invitation->update(['status' => 'cancelled']);

            return response()->json([
                'success' => true,
                'message' => 'Invitation cancelled successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel invitation: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate invitation token
     */
    private function generateInvitationToken()
    {
        return Str::random(32);
    }

    /**
     * Create invitation record in database
     */
    private function createInvitationRecord($data, $token)
    {
        return PatientInvitation::create([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'date_of_birth' => $data['date_of_birth'] ?? null,
            'gender' => $data['gender'] ?? null,
            'message' => $data['personal_message'] ?? null,
            'patient_id' => $data['patient_id'] ?? null,
            'invitation_token' => $token,
            'status' => 'pending',
            'expires_at' => now()->addDays(7),
            'created_by' => auth()->id() ?? 1 // Default to user ID 1 if not authenticated
        ]);
    }

    /**
     * Check invitation status by email and token
     */
    public function checkInvitation(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email',
                'token' => 'required|string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'valid' => false,
                    'message' => 'Invalid parameters',
                    'errors' => $validator->errors()
                ], 422);
            }

            $invitation = PatientInvitation::where('email', $request->email)
                ->where('invitation_token', $request->token)
                ->first();

            if (!$invitation) {
                return response()->json([
                    'valid' => false,
                    'message' => 'Invitation not found',
                    'invitation' => null
                ]);
            }

            if ($invitation->status === 'registered') {
                return response()->json([
                    'valid' => false,
                    'message' => 'Invitation already used',
                    'invitation' => null
                ]);
            }

            if ($invitation->expires_at < now()) {
                return response()->json([
                    'valid' => false,
                    'message' => 'Invitation has expired',
                    'invitation' => null
                ]);
            }

            return response()->json([
                'valid' => true,
                'message' => 'Valid invitation',
                'invitation' => [
                    'id' => $invitation->id,
                    'first_name' => $invitation->first_name,
                    'last_name' => $invitation->last_name,
                    'email' => $invitation->email,
                    'phone' => $invitation->phone,
                    'date_of_birth' => $invitation->date_of_birth,
                    'gender' => $invitation->gender,
                    'personal_message' => $invitation->message,
                    'status' => $invitation->status,
                    'created_at' => $invitation->created_at,
                    'expires_at' => $invitation->expires_at
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'valid' => false,
                'message' => 'Error checking invitation: ' . $e->getMessage(),
                'invitation' => null
            ], 500);
        }
    }

    /**
     * Complete patient registration from invitation
     */
    public function completeRegistration(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email',
                'token' => 'required|string',
                'password' => 'required|string|min:8|confirmed',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $invitation = PatientInvitation::where('email', $request->email)
                ->where('invitation_token', $request->token)
                ->where('status', 'sent')
                ->where('expires_at', '>', now())
                ->first();

            if (!$invitation) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid or expired invitation'
                ], 404);
            }

            // Check if user already exists
            $existingUser = \App\Models\User::where('email', $request->email)->first();
            if ($existingUser) {
                return response()->json([
                    'success' => false,
                    'message' => 'An account with this email already exists'
                ], 422);
            }

            // Create user account using invitation data
            $user = \App\Models\User::create([
                'first_name' => $invitation->first_name,
                'last_name' => $invitation->last_name,
                'email' => $invitation->email,
                'password' => \Hash::make($request->password),
                'role' => 'patient',
                'email_verified_at' => now(),
                'business_id' => $invitation->created_by ?
                    \App\Models\User::find($invitation->created_by)->business_id ?? 1 : 1
            ]);

            // Generate unique patient ID
            $patientId = $this->generateUniquePatientId();

            // Create patient record using invitation data
            $patient = \App\Models\Patient::create([
                'user_id' => $user->id,
                'patient_id' => $patientId,
                'first_name' => $invitation->first_name,
                'last_name' => $invitation->last_name,
                'email' => $invitation->email,
                'phone' => $invitation->phone,
                'date_of_birth' => $invitation->date_of_birth,
                'gender' => $invitation->gender,
                'business_id' => $user->business_id,
                'status' => 'active'
            ]);

            // Update invitation status
            $invitation->update([
                'status' => 'registered',
                'registered_at' => now(),
                'patient_id' => $patient->id
            ]);

            // Generate Sanctum token for automatic login
            $token = $user->createToken('patient-registration')->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'Registration completed successfully! You are now logged in.',
                'token' => $token,
                'user' => [
                    'id' => $user->id,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'email' => $user->email,
                    'role' => $user->role
                ],
                'patient' => [
                    'id' => $patient->id,
                    'patient_id' => $patient->patient_id,
                    'user_id' => $user->id,
                    'name' => $user->first_name . ' ' . $user->last_name,
                    'email' => $user->email,
                    'phone' => $patient->phone,
                    'date_of_birth' => $patient->date_of_birth,
                    'status' => $patient->status
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Patient registration completion failed', [
                'error' => $e->getMessage(),
                'request_data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error completing registration: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get invitation statistics
     */
    public function getStats()
    {
        try {
            $total = PatientInvitation::count();
            $sent = PatientInvitation::where('status', 'sent')->count();
            $registered = PatientInvitation::where('status', 'registered')->count();
            $cancelled = PatientInvitation::where('status', 'cancelled')->count();
            $expired = PatientInvitation::where('expires_at', '<', now())->where('status', 'sent')->count();
            $pending = PatientInvitation::where('status', 'sent')->where('expires_at', '>', now())->count();

            $conversionRate = $total > 0 ? round(($registered / $total) * 100, 2) : 0;

            return response()->json([
                'success' => true,
                'total' => $total,
                'sent' => $sent,
                'registered' => $registered,
                'cancelled' => $cancelled,
                'expired' => $expired,
                'pending' => $pending,
                'conversion_rate' => $conversionRate
            ]);

        } catch (\Exception $e) {
            \Log::error('Failed to get invitation stats', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to get statistics: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate registration URL
     */
    private function generateRegistrationUrl($email, $token)
    {
        $frontendUrl = env('FRONTEND_URL', 'http://localhost:5173');
        return $frontendUrl . '/patient-setup?' . http_build_query([
            'email' => $email,
            'token' => $token
        ]);
    }

    /**
     * Generate unique patient ID
     */
    private function generateUniquePatientId()
    {
        do {
            // Generate patient ID in format: PAT-YYYYMMDD-XXXX
            $patientId = 'PAT-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        } while (\App\Models\Patient::where('patient_id', $patientId)->exists());

        return $patientId;
    }
}
