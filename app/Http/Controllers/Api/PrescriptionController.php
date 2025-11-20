<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Prescription;
use App\Models\Pharmacy;
use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class PrescriptionController extends Controller
{
    /**
     * Display a listing of prescriptions.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = Prescription::query()->with(['patient', 'prescriber', 'pharmacy', 'appointment']);

        // Multi-tenancy: Filter by business
        if ($user->business_id) {
            $query->where('business_id', $user->business_id);
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by pharmacy status
        if ($request->has('pharmacy_status')) {
            $query->where('pharmacy_status', $request->pharmacy_status);
        }

        // Filter by patient
        if ($request->has('patient_id')) {
            $query->where('patient_id', $request->patient_id);
        }

        // Filter by pharmacy
        if ($request->has('pharmacy_id')) {
            $query->where('pharmacy_id', $request->pharmacy_id);
        }

        // Filter by prescriber
        if ($request->has('prescribed_by')) {
            $query->where('prescribed_by', $request->prescribed_by);
        }

        // Filter by drug class
        if ($request->has('drug_class')) {
            $query->where('drug_class', $request->drug_class);
        }

        // Search by medication name
        if ($request->has('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('medication_name', 'LIKE', "%{$request->search}%")
                  ->orWhere('generic_name', 'LIKE', "%{$request->search}%");
            });
        }

        $perPage = $request->input('per_page', 15);
        $prescriptions = $query->latest('prescribed_at')->paginate($perPage);

        return response()->json($prescriptions);
    }

    /**
     * Store a newly created prescription.
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'patient_id' => 'required|exists:patients,id',
            'pharmacy_id' => 'nullable|exists:pharmacies,id',
            'appointment_id' => 'nullable|exists:appointments,id',
            'medication_name' => 'required|string|max:255',
            'generic_name' => 'nullable|string|max:255',
            'strength' => 'nullable|string|max:50',
            'dosage_form' => 'nullable|string|max:50',
            'quantity' => 'required|numeric|min:0',
            'directions' => 'required|string',
            'frequency' => 'nullable|string|max:255',
            'duration' => 'nullable|string|max:255',
            'refills' => 'required|integer|min:0|max:12',
            'drug_class' => 'nullable|string|max:255',
            'indication' => 'nullable|string',
            'notes' => 'nullable|string',
            'side_effects' => 'nullable|string',
            'interactions' => 'nullable|string',
            'contraindications' => 'nullable|string',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'send_to_pharmacy' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $validator->validated();
        $data['prescribed_by'] = $user->id;
        $data['business_id'] = $user->business_id;
        $data['refills_remaining'] = $data['refills'];
        $data['prescribed_at'] = now();
        $data['status'] = 'active';
        $data['pharmacy_status'] = 'pending';

        // Auto-send to pharmacy if requested
        if ($request->boolean('send_to_pharmacy') && isset($data['pharmacy_id'])) {
            $data['pharmacy_status'] = 'sent';
            $data['sent_to_pharmacy_at'] = now();
        }

        unset($data['send_to_pharmacy']);

        $prescription = Prescription::create($data);
        $prescription->load(['patient', 'prescriber', 'pharmacy', 'appointment']);

        return response()->json([
            'message' => 'Prescription created successfully',
            'prescription' => $prescription
        ], 201);
    }

    /**
     * Display the specified prescription.
     */
    public function show(Prescription $prescription): JsonResponse
    {
        $prescription->load(['patient', 'prescriber', 'pharmacy', 'appointment', 'business']);

        return response()->json(['prescription' => $prescription]);
    }

    /**
     * Update the specified prescription.
     */
    public function update(Request $request, Prescription $prescription): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'pharmacy_id' => 'nullable|exists:pharmacies,id',
            'medication_name' => 'sometimes|string|max:255',
            'generic_name' => 'nullable|string|max:255',
            'strength' => 'nullable|string|max:50',
            'dosage_form' => 'nullable|string|max:50',
            'quantity' => 'sometimes|numeric|min:0',
            'directions' => 'sometimes|string',
            'frequency' => 'nullable|string|max:255',
            'duration' => 'nullable|string|max:255',
            'refills' => 'sometimes|integer|min:0|max:12',
            'drug_class' => 'nullable|string|max:255',
            'indication' => 'nullable|string',
            'notes' => 'nullable|string',
            'side_effects' => 'nullable|string',
            'interactions' => 'nullable|string',
            'contraindications' => 'nullable|string',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $prescription->update($validator->validated());
        $prescription->load(['patient', 'prescriber', 'pharmacy', 'appointment']);

        return response()->json([
            'message' => 'Prescription updated successfully',
            'prescription' => $prescription
        ]);
    }

    /**
     * Remove the specified prescription.
     */
    public function destroy(Prescription $prescription): JsonResponse
    {
        $prescription->delete();

        return response()->json([
            'message' => 'Prescription deleted successfully'
        ]);
    }

    /**
     * Get prescriptions for a specific patient.
     */
    public function patientPrescriptions(Request $request, Patient $patient): JsonResponse
    {
        $query = $patient->prescriptions()
            ->with(['prescriber', 'pharmacy', 'appointment']);

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $perPage = $request->input('per_page', 15);
        $prescriptions = $query->latest('prescribed_at')->paginate($perPage);

        return response()->json($prescriptions);
    }

    /**
     * Update prescription status.
     */
    public function updateStatus(Request $request, Prescription $prescription): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'status' => ['required', Rule::in(['active', 'pending', 'completed', 'expired', 'cancelled', 'discontinued'])],
            'reason' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $prescription->update(['status' => $request->status]);

        if ($request->has('reason')) {
            $prescription->notes = $prescription->notes . "\nStatus changed to {$request->status}: " . $request->reason;
            $prescription->save();
        }

        return response()->json([
            'message' => 'Prescription status updated successfully',
            'prescription' => $prescription->fresh(['patient', 'prescriber', 'pharmacy'])
        ]);
    }

    /**
     * Get list of active prescriptions.
     */
    public function activeList(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = Prescription::query()
            ->with(['patient', 'prescriber', 'pharmacy'])
            ->where('status', 'active');

        if ($user->business_id) {
            $query->where('business_id', $user->business_id);
        }

        $prescriptions = $query->latest('prescribed_at')->get();

        return response()->json(['prescriptions' => $prescriptions]);
    }

    /**
     * Get list of expiring prescriptions.
     */
    public function expiringList(Request $request): JsonResponse
    {
        $user = $request->user();
        $days = $request->input('days', 7);

        $query = Prescription::query()
            ->with(['patient', 'prescriber', 'pharmacy'])
            ->expiringSoon($days);

        if ($user->business_id) {
            $query->where('business_id', $user->business_id);
        }

        $prescriptions = $query->latest('end_date')->get();

        return response()->json([
            'prescriptions' => $prescriptions,
            'days' => $days
        ]);
    }

    /**
     * Process prescription refill.
     */
    public function refill(Request $request, Prescription $prescription): JsonResponse
    {
        if (!$prescription->canBeRefilled()) {
            return response()->json([
                'message' => 'Prescription cannot be refilled',
                'reason' => $prescription->refills_remaining <= 0 
                    ? 'No refills remaining' 
                    : 'Prescription is not active'
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'pharmacy_id' => 'nullable|exists:pharmacies,id',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $prescription->processRefill();

        if ($request->has('pharmacy_id')) {
            $prescription->pharmacy_id = $request->pharmacy_id;
            $prescription->pharmacy_status = 'sent';
            $prescription->sent_to_pharmacy_at = now();
        }

        if ($request->has('notes')) {
            $prescription->notes = $prescription->notes . "\n" . now()->format('Y-m-d H:i') . ' - Refill: ' . $request->notes;
        }

        $prescription->save();
        $prescription->load(['patient', 'prescriber', 'pharmacy']);

        return response()->json([
            'message' => 'Prescription refilled successfully',
            'prescription' => $prescription,
            'refills_remaining' => $prescription->refills_remaining
        ]);
    }

    /**
     * Send prescription to pharmacy.
     */
    public function sendToPharmacy(Request $request, Prescription $prescription): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'pharmacy_id' => 'required|exists:pharmacies,id',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $pharmacy = Pharmacy::findOrFail($request->pharmacy_id);

        if (!$pharmacy->accepts_electronic_prescriptions) {
            return response()->json([
                'message' => 'This pharmacy does not accept electronic prescriptions',
            ], 400);
        }

        $prescription->update([
            'pharmacy_id' => $request->pharmacy_id,
            'pharmacy_status' => 'sent',
            'sent_to_pharmacy_at' => now(),
        ]);

        if ($request->has('notes')) {
            $prescription->notes = $prescription->notes . "\n" . now()->format('Y-m-d H:i') . ' - Sent to pharmacy: ' . $request->notes;
            $prescription->save();
        }

        $prescription->load(['patient', 'prescriber', 'pharmacy']);

        return response()->json([
            'message' => 'Prescription sent to pharmacy successfully',
            'prescription' => $prescription,
            'pharmacy' => $pharmacy
        ]);
    }
}
