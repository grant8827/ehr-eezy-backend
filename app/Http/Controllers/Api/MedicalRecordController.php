<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MedicalRecord;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

class MedicalRecordController extends Controller
{
    /**
     * Display a listing of medical records
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = auth()->user();
            $query = MedicalRecord::query()
                ->where('business_id', $user->business_id)
                ->with(['patient', 'doctor', 'appointment', 'creator']);

            // Filter by ownership based on user role
            if (!$user->isAdmin()) {
                // Non-admin users can only see records they created or are assigned to
                $query->where(function($q) use ($user) {
                    $q->where('created_by', $user->id)
                      ->orWhere('doctor_id', $user->id);
                });
            }

            // Apply filters
            if ($request->has('patient_id')) {
                $query->where('patient_id', $request->get('patient_id'));
            }

            if ($request->has('doctor_id')) {
                $query->where('doctor_id', $request->get('doctor_id'));
            }

            if ($request->has('record_type')) {
                $query->where('record_type', $request->get('record_type'));
            }

            if ($request->has('status')) {
                $query->where('status', $request->get('status'));
            }

            // Date range filter
            if ($request->has('start_date') && $request->has('end_date')) {
                $query->whereBetween('created_at', [
                    $request->get('start_date'),
                    $request->get('end_date')
                ]);
            }

            // Default to recent records if no filters
            if (!$request->hasAny(['patient_id', 'start_date', 'status'])) {
                $query->where('created_at', '>=', now()->subDays(30));
            }

            $records = $query->orderBy('created_at', 'desc')
                           ->paginate($request->get('per_page', 15));

            // Transform the data
            $records->getCollection()->transform(function ($record) {
                return [
                    'id' => $record->id,
                    'record_type' => $record->record_type,
                    'record_type_label' => MedicalRecord::getRecordTypes()[$record->record_type] ?? $record->record_type,
                    'chief_complaint' => $record->chief_complaint,
                    'diagnosis' => $record->diagnosis,
                    'treatment_plan' => $record->treatment_plan,
                    'vital_signs' => $record->vital_signs,
                    'formatted_vital_signs' => $record->formatted_vital_signs,
                    'attachments' => $record->attachments,
                    'status' => $record->status,
                    'notes' => $record->notes,
                    'patient' => [
                        'id' => $record->patient?->id,
                        'name' => $record->patient ?
                            $record->patient->first_name . ' ' . $record->patient->last_name :
                            'Unknown Patient',
                        'patient_id' => $record->patient?->patient_id,
                    ],
                    'doctor' => [
                        'id' => $record->doctor?->id,
                        'name' => $record->doctor ?
                            $record->doctor->first_name . ' ' . $record->doctor->last_name :
                            'Unknown Doctor',
                        'role' => $record->doctor?->role
                    ],
                    'appointment' => [
                        'id' => $record->appointment?->id,
                        'appointment_date' => $record->appointment?->appointment_date,
                        'start_time' => $record->appointment?->start_time,
                    ],
                    'created_by' => [
                        'id' => $record->creator?->id,
                        'name' => $record->creator ?
                            $record->creator->first_name . ' ' . $record->creator->last_name :
                            'System'
                    ],
                    'created_at' => $record->created_at?->format('Y-m-d H:i:s'),
                    'updated_at' => $record->updated_at?->format('Y-m-d H:i:s'),
                ];
            });

            return response()->json($records);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch medical records: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Store a newly created medical record
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $user = auth()->user();

            $validated = $request->validate([
                'patient_id' => 'required|exists:patients,id',
                'doctor_id' => 'required|exists:users,id',
                'appointment_id' => 'nullable|exists:appointments,id',
                'record_type' => 'required|in:consultation,lab_result,imaging,prescription,vital_signs,procedure,follow_up',
                'chief_complaint' => 'nullable|string',
                'history_of_present_illness' => 'nullable|string',
                'physical_examination' => 'nullable|string',
                'diagnosis' => 'nullable|string',
                'treatment_plan' => 'nullable|string',
                'follow_up_instructions' => 'nullable|string',
                'vital_signs' => 'nullable|array',
                'vital_signs.blood_pressure' => 'nullable|string',
                'vital_signs.heart_rate' => 'nullable|numeric',
                'vital_signs.temperature' => 'nullable|numeric',
                'vital_signs.respiratory_rate' => 'nullable|numeric',
                'vital_signs.weight' => 'nullable|numeric',
                'vital_signs.height' => 'nullable|numeric',
                'attachments' => 'nullable|array',
                'notes' => 'nullable|string',
                'status' => 'nullable|in:active,draft,completed,archived'
            ]);

            // Check if patient belongs to the same business
            $patient = Patient::find($validated['patient_id']);
            if (!$patient || $patient->business_id !== $user->business_id) {
                return response()->json(['error' => 'Patient not found'], 404);
            }

            // Check if doctor belongs to the same business
            $doctor = User::find($validated['doctor_id']);
            if (!$doctor || $doctor->business_id !== $user->business_id) {
                return response()->json(['error' => 'Doctor not found'], 404);
            }

            // Add business and creator info
            $validated['business_id'] = $user->business_id;
            $validated['created_by'] = $user->id;
            $validated['status'] = $validated['status'] ?? 'active';

            $record = MedicalRecord::create($validated);
            $record->load(['patient', 'doctor', 'appointment', 'creator']);

            return response()->json($record, 201);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to create medical record: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Display the specified medical record
     */
    public function show(MedicalRecord $medicalRecord): JsonResponse
    {
        try {
            $user = auth()->user();

            // Ensure record belongs to user's business
            if ($medicalRecord->business_id !== $user->business_id) {
                return response()->json(['error' => 'Medical record not found'], 404);
            }

            // Check permissions
            if (!$user->isAdmin() &&
                $medicalRecord->created_by !== $user->id &&
                $medicalRecord->doctor_id !== $user->id) {
                return response()->json(['error' => 'Unauthorized access to this record'], 403);
            }

            $medicalRecord->load(['patient', 'doctor', 'appointment', 'creator']);
            return response()->json($medicalRecord);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch medical record'], 500);
        }
    }

    /**
     * Update the specified medical record
     */
    public function update(Request $request, MedicalRecord $medicalRecord): JsonResponse
    {
        try {
            $user = auth()->user();

            // Ensure record belongs to user's business
            if ($medicalRecord->business_id !== $user->business_id) {
                return response()->json(['error' => 'Medical record not found'], 404);
            }

            // Check permissions
            if (!$user->isAdmin() &&
                $medicalRecord->created_by !== $user->id &&
                $medicalRecord->doctor_id !== $user->id) {
                return response()->json(['error' => 'Unauthorized to update this record'], 403);
            }

            $validated = $request->validate([
                'patient_id' => 'sometimes|exists:patients,id',
                'doctor_id' => 'sometimes|exists:users,id',
                'appointment_id' => 'nullable|exists:appointments,id',
                'record_type' => 'sometimes|in:consultation,lab_result,imaging,prescription,vital_signs,procedure,follow_up',
                'chief_complaint' => 'nullable|string',
                'history_of_present_illness' => 'nullable|string',
                'physical_examination' => 'nullable|string',
                'diagnosis' => 'nullable|string',
                'treatment_plan' => 'nullable|string',
                'follow_up_instructions' => 'nullable|string',
                'vital_signs' => 'nullable|array',
                'vital_signs.blood_pressure' => 'nullable|string',
                'vital_signs.heart_rate' => 'nullable|numeric',
                'vital_signs.temperature' => 'nullable|numeric',
                'vital_signs.respiratory_rate' => 'nullable|numeric',
                'vital_signs.weight' => 'nullable|numeric',
                'vital_signs.height' => 'nullable|numeric',
                'attachments' => 'nullable|array',
                'notes' => 'nullable|string',
                'status' => 'nullable|in:active,draft,completed,archived'
            ]);

            // Check business constraints if patient or doctor is being changed
            if (isset($validated['patient_id'])) {
                $patient = Patient::find($validated['patient_id']);
                if (!$patient || $patient->business_id !== $user->business_id) {
                    return response()->json(['error' => 'Patient not found'], 404);
                }
            }

            if (isset($validated['doctor_id'])) {
                $doctor = User::find($validated['doctor_id']);
                if (!$doctor || $doctor->business_id !== $user->business_id) {
                    return response()->json(['error' => 'Doctor not found'], 404);
                }
            }

            $medicalRecord->update($validated);
            $medicalRecord->load(['patient', 'doctor', 'appointment', 'creator']);

            return response()->json($medicalRecord);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to update medical record'], 500);
        }
    }

    /**
     * Remove the specified medical record
     */
    public function destroy(MedicalRecord $medicalRecord): JsonResponse
    {
        try {
            $user = auth()->user();

            // Ensure record belongs to user's business
            if ($medicalRecord->business_id !== $user->business_id) {
                return response()->json(['error' => 'Medical record not found'], 404);
            }

            // Check permissions
            if (!$user->isAdmin() &&
                $medicalRecord->created_by !== $user->id &&
                $medicalRecord->doctor_id !== $user->id) {
                return response()->json(['error' => 'Unauthorized to delete this record'], 403);
            }

            $medicalRecord->delete();
            return response()->json(['message' => 'Medical record deleted successfully']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to delete medical record'], 500);
        }
    }

    /**
     * Get medical records for a specific patient
     */
    public function patientRecords(Request $request, Patient $patient): JsonResponse
    {
        try {
            $user = auth()->user();

            // Ensure patient belongs to user's business
            if ($patient->business_id !== $user->business_id) {
                return response()->json(['error' => 'Patient not found'], 404);
            }

            $query = MedicalRecord::where('patient_id', $patient->id)
                ->where('business_id', $user->business_id)
                ->with(['doctor', 'appointment', 'creator']);

            // Filter by record type if specified
            if ($request->has('record_type')) {
                $query->where('record_type', $request->get('record_type'));
            }

            // Filter by date range if specified
            if ($request->has('start_date') && $request->has('end_date')) {
                $query->whereBetween('created_at', [
                    $request->get('start_date'),
                    $request->get('end_date')
                ]);
            }

            $records = $query->orderBy('created_at', 'desc')
                           ->paginate($request->get('per_page', 15));

            return response()->json($records);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch patient records'], 500);
        }
    }

    /**
     * Get medical record statistics
     */
    public function statistics(Request $request): JsonResponse
    {
        try {
            $user = auth()->user();

            $query = MedicalRecord::where('business_id', $user->business_id);

            // Filter by date range if specified
            if ($request->has('start_date') && $request->has('end_date')) {
                $query->whereBetween('created_at', [
                    $request->get('start_date'),
                    $request->get('end_date')
                ]);
            } else {
                // Default to last 30 days
                $query->where('created_at', '>=', now()->subDays(30));
            }

            $totalRecords = $query->count();
            $recordsByType = $query->groupBy('record_type')
                                  ->selectRaw('record_type, count(*) as count')
                                  ->pluck('count', 'record_type')
                                  ->toArray();

            $recentRecords = MedicalRecord::where('business_id', $user->business_id)
                ->where('created_at', '>=', now()->subDays(7))
                ->count();

            return response()->json([
                'total_records' => $totalRecords,
                'records_by_type' => $recordsByType,
                'recent_records' => $recentRecords,
                'record_types' => MedicalRecord::getRecordTypes(),
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch statistics'], 500);
        }
    }
}
