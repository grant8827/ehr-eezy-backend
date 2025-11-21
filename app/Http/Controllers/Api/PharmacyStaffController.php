<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PharmacyStaff;
use App\Models\Pharmacy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PharmacyStaffController extends Controller
{
    /**
     * Get all staff for a pharmacy
     */
    public function index(Request $request)
    {
        $query = PharmacyStaff::with('pharmacy');

        // Filter by pharmacy (for pharmacy users viewing their own staff)
        if ($request->has('pharmacy_id')) {
            $query->forPharmacy($request->pharmacy_id);
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by role
        if ($request->has('role')) {
            $query->byRole($request->role);
        }

        // Search by name or email
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('first_name', 'LIKE', "%{$search}%")
                  ->orWhere('last_name', 'LIKE', "%{$search}%")
                  ->orWhere('email', 'LIKE', "%{$search}%");
            });
        }

        // Check for expiring licenses
        if ($request->boolean('expiring_license')) {
            $query->whereNotNull('license_expiry')
                  ->where('license_expiry', '<=', now()->addDays(30));
        }

        $staff = $query->orderBy('created_at', 'desc')->paginate(15);

        return response()->json($staff);
    }

    /**
     * Store a new staff member
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'pharmacy_id' => 'required|exists:pharmacies,id',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:pharmacy_staff,email',
            'phone' => 'nullable|string|max:20',
            'role' => 'required|in:pharmacist,pharmacy_technician,pharmacy_assistant,manager',
            'license_number' => 'nullable|string|max:255',
            'license_expiry' => 'nullable|date',
            'status' => 'nullable|in:active,inactive,on_leave',
            'hire_date' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $staff = PharmacyStaff::create($request->all());
        $staff->load('pharmacy');

        return response()->json([
            'message' => 'Staff member added successfully',
            'staff' => $staff
        ], 201);
    }

    /**
     * Get a single staff member
     */
    public function show($id)
    {
        $staff = PharmacyStaff::with('pharmacy')->findOrFail($id);

        return response()->json($staff);
    }

    /**
     * Update a staff member
     */
    public function update(Request $request, $id)
    {
        $staff = PharmacyStaff::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'first_name' => 'sometimes|required|string|max:255',
            'last_name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|email|unique:pharmacy_staff,email,' . $id,
            'phone' => 'nullable|string|max:20',
            'role' => 'sometimes|required|in:pharmacist,pharmacy_technician,pharmacy_assistant,manager',
            'license_number' => 'nullable|string|max:255',
            'license_expiry' => 'nullable|date',
            'status' => 'sometimes|required|in:active,inactive,on_leave',
            'hire_date' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $staff->update($request->all());
        $staff->load('pharmacy');

        return response()->json([
            'message' => 'Staff member updated successfully',
            'staff' => $staff
        ]);
    }

    /**
     * Delete a staff member (soft delete)
     */
    public function destroy($id)
    {
        $staff = PharmacyStaff::findOrFail($id);
        $staff->delete();

        return response()->json([
            'message' => 'Staff member removed successfully'
        ]);
    }

    /**
     * Get staff by pharmacy
     */
    public function byPharmacy($pharmacyId)
    {
        $pharmacy = Pharmacy::findOrFail($pharmacyId);

        $staff = PharmacyStaff::forPharmacy($pharmacyId)
            ->orderBy('role')
            ->orderBy('last_name')
            ->get();

        return response()->json([
            'pharmacy' => $pharmacy,
            'staff' => $staff
        ]);
    }

    /**
     * Get staff members with expiring licenses
     */
    public function expiringLicenses(Request $request)
    {
        $days = $request->input('days', 30);

        $query = PharmacyStaff::with('pharmacy')
            ->whereNotNull('license_expiry')
            ->where('license_expiry', '<=', now()->addDays($days))
            ->where('status', 'active');

        if ($request->has('pharmacy_id')) {
            $query->forPharmacy($request->pharmacy_id);
        }

        $staff = $query->orderBy('license_expiry')->get();

        return response()->json([
            'expiring_count' => $staff->count(),
            'staff' => $staff
        ]);
    }

    /**
     * Update staff status
     */
    public function updateStatus(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:active,inactive,on_leave',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $staff = PharmacyStaff::findOrFail($id);
        $staff->update(['status' => $request->status]);
        $staff->load('pharmacy');

        return response()->json([
            'message' => 'Staff status updated successfully',
            'staff' => $staff
        ]);
    }
}
