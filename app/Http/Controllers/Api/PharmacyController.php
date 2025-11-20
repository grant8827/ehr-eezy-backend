<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pharmacy;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class PharmacyController extends Controller
{
    /**
     * Display a listing of pharmacies.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Pharmacy::query()->with(['user', 'business']);

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by city
        if ($request->has('city')) {
            $query->inCity($request->city);
        }

        // Filter by state
        if ($request->has('state')) {
            $query->inState($request->state);
        }

        // Filter pharmacies that accept electronic prescriptions
        if ($request->boolean('accepts_electronic')) {
            $query->acceptsElectronic();
        }

        // Filter pharmacies that deliver
        if ($request->boolean('delivers')) {
            $query->delivers();
        }

        // Search by name
        if ($request->has('search')) {
            $query->where('name', 'LIKE', "%{$request->search}%");
        }

        // Nearby search
        if ($request->has('latitude') && $request->has('longitude')) {
            $radius = $request->input('radius', 10);
            $query->nearby($request->latitude, $request->longitude, $radius);
        }

        $perPage = $request->input('per_page', 15);
        $pharmacies = $query->paginate($perPage);

        return response()->json($pharmacies);
    }

    /**
     * Store a newly created pharmacy.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'license_number' => 'required|string|unique:pharmacies,license_number',
            'email' => 'required|email|unique:pharmacies,email',
            'phone' => 'required|string|max:20',
            'fax' => 'nullable|string|max:20',
            'address' => 'required|string',
            'city' => 'required|string|max:255',
            'state' => 'required|string|max:255',
            'zip_code' => 'required|string|max:20',
            'country' => 'nullable|string|max:255',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'operating_hours' => 'nullable|array',
            'pharmacist_in_charge' => 'nullable|string|max:255',
            'pharmacist_license' => 'nullable|string|max:255',
            'accepts_electronic_prescriptions' => 'boolean',
            'delivers' => 'boolean',
            'delivery_notes' => 'nullable|string',
            'accepted_insurances' => 'nullable|array',
            'status' => ['nullable', Rule::in(['active', 'inactive', 'suspended'])],
            'notes' => 'nullable|string',
            'business_id' => 'nullable|exists:businesses,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $pharmacy = Pharmacy::create($validator->validated());
        $pharmacy->load(['user', 'business']);

        return response()->json([
            'message' => 'Pharmacy created successfully',
            'pharmacy' => $pharmacy
        ], 201);
    }

    /**
     * Display the specified pharmacy.
     */
    public function show(Pharmacy $pharmacy): JsonResponse
    {
        $pharmacy->load(['user', 'business', 'prescriptions']);

        return response()->json([
            'pharmacy' => $pharmacy,
            'pending_prescriptions' => $pharmacy->getPendingPrescriptionsCount(),
            'received_prescriptions' => $pharmacy->getReceivedPrescriptionsCount(),
        ]);
    }

    /**
     * Update the specified pharmacy.
     */
    public function update(Request $request, Pharmacy $pharmacy): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'license_number' => ['sometimes', 'string', Rule::unique('pharmacies')->ignore($pharmacy->id)],
            'email' => ['sometimes', 'email', Rule::unique('pharmacies')->ignore($pharmacy->id)],
            'phone' => 'sometimes|string|max:20',
            'fax' => 'nullable|string|max:20',
            'address' => 'sometimes|string',
            'city' => 'sometimes|string|max:255',
            'state' => 'sometimes|string|max:255',
            'zip_code' => 'sometimes|string|max:20',
            'country' => 'nullable|string|max:255',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'operating_hours' => 'nullable|array',
            'pharmacist_in_charge' => 'nullable|string|max:255',
            'pharmacist_license' => 'nullable|string|max:255',
            'accepts_electronic_prescriptions' => 'boolean',
            'delivers' => 'boolean',
            'delivery_notes' => 'nullable|string',
            'accepted_insurances' => 'nullable|array',
            'status' => ['nullable', Rule::in(['active', 'inactive', 'suspended'])],
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $pharmacy->update($validator->validated());
        $pharmacy->load(['user', 'business']);

        return response()->json([
            'message' => 'Pharmacy updated successfully',
            'pharmacy' => $pharmacy
        ]);
    }

    /**
     * Remove the specified pharmacy.
     */
    public function destroy(Pharmacy $pharmacy): JsonResponse
    {
        $pharmacy->delete();

        return response()->json([
            'message' => 'Pharmacy deleted successfully'
        ]);
    }

    /**
     * Get prescriptions for a specific pharmacy.
     */
    public function prescriptions(Request $request, Pharmacy $pharmacy): JsonResponse
    {
        $query = $pharmacy->prescriptions()
            ->with(['patient', 'prescriber', 'appointment']);

        // Filter by pharmacy status
        if ($request->has('pharmacy_status')) {
            $query->where('pharmacy_status', $request->pharmacy_status);
        }

        // Filter by date range
        if ($request->has('start_date')) {
            $query->whereDate('sent_to_pharmacy_at', '>=', $request->start_date);
        }

        if ($request->has('end_date')) {
            $query->whereDate('sent_to_pharmacy_at', '<=', $request->end_date);
        }

        $perPage = $request->input('per_page', 15);
        $prescriptions = $query->latest('sent_to_pharmacy_at')->paginate($perPage);

        return response()->json($prescriptions);
    }

    /**
     * Update prescription status in pharmacy.
     */
    public function updatePrescriptionStatus(Request $request, Pharmacy $pharmacy, $prescriptionId): JsonResponse
    {
        $prescription = $pharmacy->prescriptions()->findOrFail($prescriptionId);

        $validator = Validator::make($request->all(), [
            'pharmacy_status' => ['required', Rule::in(['pending', 'sent', 'received', 'filled', 'picked_up', 'delivered', 'cancelled'])],
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $updateData = ['pharmacy_status' => $request->pharmacy_status];

        // Auto-update timestamps based on status
        switch ($request->pharmacy_status) {
            case 'sent':
                $updateData['sent_to_pharmacy_at'] = now();
                break;
            case 'filled':
                $updateData['filled_at'] = now();
                break;
            case 'picked_up':
                $updateData['picked_up_at'] = now();
                break;
        }

        if ($request->has('notes')) {
            $updateData['notes'] = $prescription->notes . "\n" . now()->format('Y-m-d H:i') . ': ' . $request->notes;
        }

        $prescription->update($updateData);

        return response()->json([
            'message' => 'Prescription status updated successfully',
            'prescription' => $prescription->fresh(['patient', 'prescriber', 'pharmacy'])
        ]);
    }

    /**
     * Get nearby pharmacies based on coordinates.
     */
    public function nearby(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'radius' => 'nullable|numeric|min:1|max:100',
            'limit' => 'nullable|integer|min:1|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $radius = $request->input('radius', 10);
        $limit = $request->input('limit', 10);

        $pharmacies = Pharmacy::active()
            ->nearby($request->latitude, $request->longitude, $radius)
            ->limit($limit)
            ->get();

        return response()->json([
            'pharmacies' => $pharmacies,
            'count' => $pharmacies->count(),
            'search_radius' => $radius,
            'coordinates' => [
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
            ]
        ]);
    }
}
