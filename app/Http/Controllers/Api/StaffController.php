<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class StaffController extends Controller
{
    /**
     * Display a listing of staff members.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        // Only admin users can manage staff
        if (!$user->isAdmin()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $query = User::staff()
            ->forBusiness($user->business_id)
            ->with(['doctorProfile', 'therapistProfile']);

        // Filter by role if provided
        if ($request->has('role')) {
            $query->where('role', $request->role);
        }

        // Search functionality
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        $staff = $query->paginate($request->get('per_page', 15));

        return response()->json($staff);
    }

    /**
     * Store a newly created staff member.
     */
    public function store(Request $request)
    {
        $user = $request->user();

        // Only admin users can create staff
        if (!$user->isAdmin()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'role' => ['required', Rule::in(['admin', 'doctor', 'nurse', 'therapist', 'receptionist'])],
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
            'date_of_birth' => 'nullable|date',
            'gender' => 'nullable|in:male,female,other',
            'license_number' => 'nullable|string|max:100',
            'specialization' => 'nullable|string|max:255',
            'qualifications' => 'nullable|string|max:1000',
            'years_of_experience' => 'nullable|integer|min:0|max:50',
            'profile_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $staffData = $request->only([
            'first_name', 'last_name', 'email', 'role', 'phone',
            'address', 'date_of_birth', 'gender', 'license_number',
            'specialization', 'qualifications', 'years_of_experience'
        ]);

        // Handle profile image upload
        if ($request->hasFile('profile_image')) {
            $image = $request->file('profile_image');
            $imageName = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
            $image->storeAs('public/profile-images', $imageName);
            $staffData['profile_image'] = 'storage/profile-images/' . $imageName;
        }

        $staffData['password'] = Hash::make($request->password);
        $staffData['business_id'] = $user->business_id;
        $staffData['is_active'] = true;
        $staffData['is_business_owner'] = false;

        $staff = User::create($staffData);

        // Load relationships for response
        $staff->load(['doctorProfile', 'therapistProfile']);

        return response()->json([
            'message' => 'Staff member created successfully',
            'data' => $staff
        ], 201);
    }

    /**
     * Display the specified staff member.
     */
    public function show(Request $request, string $id)
    {
        $user = $request->user();

        // Only admin users can view staff details
        if (!$user->isAdmin()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $staff = User::staff()
            ->forBusiness($user->business_id)
            ->with(['doctorProfile', 'therapistProfile'])
            ->find($id);

        if (!$staff) {
            return response()->json(['error' => 'Staff member not found'], 404);
        }

        return response()->json(['data' => $staff]);
    }

    /**
     * Update the specified staff member.
     */
    public function update(Request $request, string $id)
    {
        $user = $request->user();

        // Only admin users can update staff
        if (!$user->isAdmin()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $staff = User::staff()
            ->forBusiness($user->business_id)
            ->find($id);

        if (!$staff) {
            return response()->json(['error' => 'Staff member not found'], 404);
        }

        $request->validate([
            'first_name' => 'sometimes|required|string|max:255',
            'last_name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|email|unique:users,email,' . $id,
            'password' => 'sometimes|string|min:8',
            'role' => ['sometimes', 'required', Rule::in(['admin', 'doctor', 'nurse', 'therapist', 'receptionist'])],
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
            'date_of_birth' => 'nullable|date',
            'gender' => 'nullable|in:male,female,other',
            'is_active' => 'sometimes|boolean',
            'license_number' => 'nullable|string|max:100',
            'specialization' => 'nullable|string|max:255',
            'qualifications' => 'nullable|string|max:1000',
            'years_of_experience' => 'nullable|integer|min:0|max:50',
            'profile_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $updateData = $request->only([
            'first_name', 'last_name', 'email', 'role', 'phone',
            'address', 'date_of_birth', 'gender', 'is_active',
            'license_number', 'specialization', 'qualifications', 'years_of_experience'
        ]);

        // Handle profile image upload
        if ($request->hasFile('profile_image')) {
            // Delete old image if exists
            if ($staff->profile_image && file_exists(public_path($staff->profile_image))) {
                unlink(public_path($staff->profile_image));
            }

            $image = $request->file('profile_image');
            $imageName = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
            $image->storeAs('public/profile-images', $imageName);
            $updateData['profile_image'] = 'storage/profile-images/' . $imageName;
        }

        if ($request->has('password')) {
            $updateData['password'] = Hash::make($request->password);
        }

        $staff->update($updateData);
        $staff->load(['doctorProfile', 'therapistProfile']);

        return response()->json([
            'message' => 'Staff member updated successfully',
            'data' => $staff
        ]);
    }

    /**
     * Remove the specified staff member.
     */
    public function destroy(Request $request, string $id)
    {
        $user = $request->user();

        // Only admin users can delete staff
        if (!$user->isAdmin()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $staff = User::staff()
            ->forBusiness($user->business_id)
            ->find($id);

        if (!$staff) {
            return response()->json(['error' => 'Staff member not found'], 404);
        }

        // Prevent deleting business owner
        if ($staff->is_business_owner) {
            return response()->json(['error' => 'Cannot delete business owner'], 400);
        }

        // Soft delete by deactivating instead of hard delete
        $staff->update(['is_active' => false]);

        return response()->json([
            'message' => 'Staff member deactivated successfully'
        ]);
    }

    /**
     * Get staff statistics for dashboard.
     */
    public function stats(Request $request)
    {
        $user = $request->user();

        // Only admin users can view staff stats
        if (!$user->isAdmin()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $businessId = $user->business_id;

        $stats = [
            'total' => User::staff()->forBusiness($businessId)->count(),
            'active' => User::staff()->forBusiness($businessId)->where('is_active', true)->count(),
            'doctors' => User::forBusiness($businessId)->where('role', 'doctor')->count(),
            'nurses' => User::forBusiness($businessId)->where('role', 'nurse')->count(),
            'therapists' => User::forBusiness($businessId)->where('role', 'therapist')->count(),
            'receptionists' => User::forBusiness($businessId)->where('role', 'receptionist')->count(),
        ];

        return response()->json($stats);
    }
}
