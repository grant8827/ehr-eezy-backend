<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Business;
use App\Models\Patient;
use App\Models\Doctor;
use App\Models\Therapist;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function setupBusiness(Request $request)
    {
        $validator = Validator::make($request->all(), [
            // Business Information
            'business_name' => 'required|string|max:255',
            'business_type' => 'required|in:healthcare,clinic,hospital,therapy_center',
            'business_email' => 'required|string|email|max:255|unique:businesses,email',
            'business_phone' => 'nullable|string|max:20',
            'business_address' => 'nullable|string',
            'business_website' => 'nullable|url',
            'license_number' => 'nullable|string|max:255',

            // Owner Information
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'phone' => 'nullable|string|max:20',
            'date_of_birth' => 'nullable|date',
            'gender' => 'nullable|in:male,female,other',

            // Subscription
            'subscription_plan' => 'required|in:free,basic,premium,enterprise',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Create the business
            $business = Business::create([
                'name' => $request->business_name,
                'business_type' => $request->business_type,
                'email' => $request->business_email,
                'phone' => $request->business_phone,
                'address' => $request->business_address,
                'website' => $request->business_website,
                'license_number' => $request->license_number,
                'subscription_plan' => $request->subscription_plan,
                'subscription_expires_at' => $request->subscription_plan !== 'free' ? now()->addYear() : null,
                'is_active' => true,
            ]);

            // Create the business owner
            $owner = User::create([
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role' => 'admin',
                'phone' => $request->phone,
                'date_of_birth' => $request->date_of_birth,
                'gender' => $request->gender,
                'business_id' => $business->id,
                'is_business_owner' => true,
                'is_active' => true,
            ]);

            $token = $owner->createToken('auth_token')->plainTextToken;

            DB::commit();

            return response()->json([
                'message' => 'Business setup successful',
                'business' => $business,
                'user' => $owner,
                'token' => $token
            ], 201);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'message' => 'Business setup failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function register(Request $request)
    {
        // Check if this is a business registration (has business info) or staff registration
        $isBusinessRegistration = $request->has('business_name');

        if ($isBusinessRegistration) {
            // Business registration with owner information
            $validator = Validator::make($request->all(), [
                // Business Information
                'business_name' => 'required|string|max:255',
                'business_type' => 'required|in:healthcare,clinic,hospital,therapy_center',
                'business_email' => 'required|string|email|max:255|unique:businesses,email',
                'business_phone' => 'nullable|string|max:20',
                'business_address' => 'nullable|string',
                'business_website' => 'nullable|url',
                'license_number' => 'nullable|string|max:255',
                'subscription_plan' => 'nullable|in:free,basic,premium,enterprise',

                // Owner Information
                'first_name' => 'required|string|max:255',
                'last_name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:8|confirmed',
                'phone' => 'nullable|string|max:20',
                'date_of_birth' => 'nullable|date',
                'gender' => 'nullable|in:male,female,other',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            try {
                DB::beginTransaction();

                // Create the business
                $business = Business::create([
                    'name' => $request->business_name,
                    'business_type' => $request->business_type,
                    'email' => $request->business_email,
                    'phone' => $request->business_phone,
                    'address' => $request->business_address,
                    'website' => $request->business_website,
                    'license_number' => $request->license_number,
                    'subscription_plan' => $request->subscription_plan ?? 'free',
                    'subscription_expires_at' => ($request->subscription_plan ?? 'free') !== 'free' ? now()->addYear() : null,
                    'is_active' => true,
                ]);

                // Create the business owner
                $user = User::create([
                    'first_name' => $request->first_name,
                    'last_name' => $request->last_name,
                    'email' => $request->email,
                    'password' => Hash::make($request->password),
                    'role' => 'admin',
                    'phone' => $request->phone,
                    'date_of_birth' => $request->date_of_birth,
                    'gender' => $request->gender,
                    'business_id' => $business->id,
                    'is_business_owner' => true,
                    'is_active' => true,
                ]);

                $token = $user->createToken('auth_token')->plainTextToken;

                DB::commit();

                return response()->json([
                    'message' => 'Business registration successful',
                    'business' => $business,
                    'user' => $user,
                    'token' => $token
                ], 201);

            } catch (\Exception $e) {
                DB::rollback();
                return response()->json([
                    'message' => 'Business registration failed',
                    'error' => $e->getMessage()
                ], 500);
            }
        } else {
            // Staff registration for existing business
            $validator = Validator::make($request->all(), [
                'first_name' => 'required|string|max:255',
                'last_name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:8|confirmed',
                'role' => 'required|in:admin,doctor,nurse,therapist,patient,receptionist',
                'phone' => 'nullable|string|max:20',
                'date_of_birth' => 'nullable|date',
                'gender' => 'nullable|in:male,female,other',
                'business_id' => 'required|exists:businesses,id',
                'specialization' => 'nullable|string',
                'qualifications' => 'nullable|string',
                'years_of_experience' => 'nullable|integer|min:0',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = User::create([
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role' => $request->role,
                'phone' => $request->phone,
                'date_of_birth' => $request->date_of_birth,
                'gender' => $request->gender,
                'business_id' => $request->business_id,
                'specialization' => $request->specialization,
                'qualifications' => $request->qualifications,
                'years_of_experience' => $request->years_of_experience,
            ]);

            // Create role-specific records
            if ($user->role === 'patient') {
                Patient::create([
                    'user_id' => $user->id,
                    'business_id' => $user->business_id,
                    'patient_id' => 'PAT' . str_pad($user->id, 6, '0', STR_PAD_LEFT),
                ]);
            } elseif ($user->role === 'doctor') {
                Doctor::create([
                    'user_id' => $user->id,
                    'business_id' => $user->business_id,
                    'license_number' => 'DOC' . str_pad($user->id, 6, '0', STR_PAD_LEFT),
                    'specialization' => $request->specialization ?? 'General Practice',
                ]);
            } elseif ($user->role === 'therapist') {
                Therapist::create([
                    'user_id' => $user->id,
                    'business_id' => $user->business_id,
                    'license_number' => 'THR' . str_pad($user->id, 6, '0', STR_PAD_LEFT),
                    'specialization' => $request->specialization ?? 'Physical Therapy',
                    'qualifications' => $request->qualifications ?? '',
                    'years_of_experience' => $request->years_of_experience ?? 0,
                ]);
            }

            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'message' => 'Staff registration successful',
                'user' => $user->load('business'),
                'token' => $token
            ], 201);
        }
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::with('business')->where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Invalid credentials'
            ], 401);
        }

        if (!$user->is_active) {
            return response()->json([
                'message' => 'Account is inactive. Please contact administrator.'
            ], 401);
        }

        // Check if business is active (skip for pharmacy users)
        if (!$user->isPharmacy() && $user->business && !$user->business->is_active) {
            return response()->json([
                'message' => 'Business account is inactive. Please contact support.'
            ], 401);
        }

        // Check subscription status (skip for pharmacy users)
        if (!$user->isPharmacy() && $user->business && !$user->business->isSubscriptionActive()) {
            return response()->json([
                'message' => 'Business subscription has expired. Please renew to continue.'
            ], 401);
        }

        // Load pharmacy relationship for pharmacy users
        if ($user->isPharmacy()) {
            $user->load('pharmacy');
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Login successful',
            'user' => $user,
            'business' => $user->business,
            'pharmacy' => $user->isPharmacy() ? $user->pharmacy : null,
            'token' => $token
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully'
        ]);
    }

    public function me(Request $request)
    {
        $user = $request->user()->load('business');

        return response()->json([
            'user' => $user,
            'business' => $user->business
        ]);
    }

    public function refresh(Request $request)
    {
        $user = $request->user();
        $user->currentAccessToken()->delete();

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Token refreshed successfully',
            'token' => $token
        ]);
    }

    public function updateProfile(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'sometimes|required|string|max:255',
            'last_name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|email|unique:users,email,' . $request->user()->id,
            'phone' => 'nullable|string|max:20',
            'date_of_birth' => 'nullable|date',
            'gender' => 'nullable|in:male,female,other,prefer_not_to_say',
            'address' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'zip_code' => 'nullable|string|max:20',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = $request->user();
            $user->update($request->only([
                'first_name', 'last_name', 'email', 'phone', 'date_of_birth',
                'gender', 'address', 'city', 'state', 'zip_code'
            ]));

            return response()->json([
                'message' => 'Profile updated successfully',
                'user' => $user->fresh()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update profile',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function changePassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = $request->user();

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'message' => 'Current password is incorrect'
            ], 422);
        }

        try {
            $user->update([
                'password' => Hash::make($request->new_password)
            ]);

            return response()->json([
                'message' => 'Password changed successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to change password',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function uploadProfilePicture(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'profile_picture' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048', // 2MB max
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = $request->user();

            // Delete old profile picture if exists
            if ($user->profile_picture) {
                $oldPath = storage_path('app/public/' . $user->profile_picture);
                if (file_exists($oldPath)) {
                    unlink($oldPath);
                }
            }

            // Store the new profile picture
            $file = $request->file('profile_picture');
            $fileName = time() . '_' . $user->id . '.' . $file->getClientOriginalExtension();
            $filePath = $file->storeAs('profile-pictures', $fileName, 'public');

            // Update user's profile picture path
            $user->update([
                'profile_picture' => $filePath
            ]);

            return response()->json([
                'message' => 'Profile picture uploaded successfully',
                'user' => $user->fresh(),
                'profile_picture_url' => asset('storage/' . $filePath)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to upload profile picture',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function updateNotificationPreferences(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email_appointments' => 'boolean',
            'email_reminders' => 'boolean',
            'email_results' => 'boolean',
            'sms_appointments' => 'boolean',
            'sms_reminders' => 'boolean',
            'push_notifications' => 'boolean',
            'marketing_emails' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = $request->user();

            // Update notification preferences (you might want to store this in a separate table)
            $preferences = $request->only([
                'email_appointments', 'email_reminders', 'email_results',
                'sms_appointments', 'sms_reminders', 'push_notifications', 'marketing_emails'
            ]);

            // For now, store in user table. Later you might create a separate notification_preferences table
            $user->update([
                'notification_preferences' => json_encode($preferences)
            ]);

            return response()->json([
                'message' => 'Notification preferences updated successfully',
                'preferences' => $preferences
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update notification preferences',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function deactivateAccount(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'password' => 'required|string',
            'reason' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = $request->user();

        if (!Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Password is incorrect'
            ], 422);
        }

        try {
            // Deactivate the account
            $user->update([
                'is_active' => false,
                'deactivated_at' => now(),
                'deactivation_reason' => $request->reason
            ]);

            // Revoke all tokens
            $user->tokens()->delete();

            return response()->json([
                'message' => 'Account deactivated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to deactivate account',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
