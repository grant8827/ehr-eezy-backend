<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class PatientController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = auth()->user();
            $query = Patient::query()
                ->where('business_id', $user->business_id)
                ->with(['creator']);

            // Filter by ownership based on user role
            if (!$user->isAdmin()) {
                // Non-admin users can only see patients they created
                $query->where('created_by', $user->id);
            }
            // Admin users can see all patients in their business

            // Apply search filters if provided
            if ($request->has('search')) {
                $search = $request->get('search');
                $query->where(function($q) use ($search) {
                    $q->where('first_name', 'like', "%{$search}%")
                      ->orWhere('last_name', 'like', "%{$search}%")
                      ->orWhere('patient_id', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%")
                      ->orWhere('phone', 'like', "%{$search}%");
                });
            }

            // Filter by status if provided
            if ($request->has('status')) {
                $query->where('status', $request->get('status'));
            }

            $patients = $query->orderBy('created_at', 'desc')
                             ->paginate($request->get('per_page', 15));

            // Transform the data to include creator information
            $patients->getCollection()->transform(function ($patient) {
                return [
                    'id' => $patient->id,
                    'patient_id' => $patient->patient_id,
                    'first_name' => $patient->first_name,
                    'last_name' => $patient->last_name,
                    'middle_name' => $patient->middle_name,
                    'email' => $patient->email,
                    'phone' => $patient->phone,
                    'date_of_birth' => $patient->date_of_birth?->format('Y-m-d'),
                    'gender' => $patient->gender,
                    'address' => $patient->address,
                    'city' => $patient->city,
                    'state' => $patient->state,
                    'zip_code' => $patient->zip_code,
                    'status' => $patient->status ?? 'active',
                    'created_at' => $patient->created_at?->format('Y-m-d H:i:s'),
                    'created_by' => [
                        'id' => $patient->creator?->id,
                        'name' => $patient->creator ?
                            $patient->creator->first_name . ' ' . $patient->creator->last_name :
                            'Unknown',
                        'role' => $patient->creator?->role
                    ],
                    'last_visit' => null, // TODO: Add when appointments are implemented
                ];
            });

            return response()->json($patients);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch patients: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        try {
            \Log::info('Patient creation request data:', $request->all());

            $validated = $request->validate([
                'first_name' => 'required|string|max:255',
                'last_name' => 'required|string|max:255',
                'middle_name' => 'nullable|string|max:255',
                'date_of_birth' => 'required|date',
                'gender' => 'required|in:male,female,other',
                'email' => 'nullable|email|unique:patients,email',
                'phone' => 'nullable|string|max:20',
                'address' => 'nullable|string',
                'city' => 'nullable|string|max:255',
                'state' => 'nullable|string|max:255',
                'zip_code' => 'nullable|string|max:10',
                'country' => 'nullable|string|max:255',
                'emergency_contact_name' => 'nullable|string|max:255',
                'emergency_contact_phone' => 'nullable|string|max:20',
                'emergency_contact_relationship' => 'nullable|string|max:255',
                'insurance_provider' => 'nullable|string|max:255',
                'insurance_policy_number' => 'nullable|string|max:255',
                'blood_type' => 'nullable|string|max:10',
                'allergies' => 'nullable|string',
                'medications' => 'nullable|array',
                'medical_history' => 'nullable|string',
                'family_history' => 'nullable|string',
                'surgical_history' => 'nullable|string',
                'social_history' => 'nullable|string',
                'preferred_pharmacy' => 'nullable|string|max:255',
                'primary_care_physician' => 'nullable|string|max:255',
                'referring_physician' => 'nullable|string|max:255',
                'occupation' => 'nullable|string|max:255',
                'employer' => 'nullable|string|max:255',
                'notes' => 'nullable|string',
                'marital_status' => 'nullable|string|max:255',
                'social_security' => 'nullable|string|max:255',
                'preferred_language' => 'nullable|string|max:255',
                'alternate_phone' => 'nullable|string|max:20',
                'emergency_contact_address' => 'nullable|string',
                'insurance_group_number' => 'nullable|string|max:255',
                'insurance_subscriber_name' => 'nullable|string|max:255',
                'insurance_subscriber_dob' => 'nullable|date',
                'secondary_insurance_provider' => 'nullable|string|max:255',
                'secondary_insurance_policy' => 'nullable|string|max:255',
                'height' => 'nullable|numeric',
                'weight' => 'nullable|numeric',
            ]);

            $validated['business_id'] = auth()->user()->business_id;
            $validated['created_by'] = auth()->user()->id;
            $validated['patient_id'] = 'PAT' . str_pad(Patient::count() + 1, 6, '0', STR_PAD_LEFT);

            // Handle medications as JSON
            if (isset($validated['medications'])) {
                $validated['medications'] = json_encode($validated['medications']);
            }

            $patient = Patient::create($validated);

            return response()->json($patient, 201);
        } catch (ValidationException $e) {
            \Log::error('Patient validation failed:', $e->errors());
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            \Log::error('Patient creation error: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            return response()->json([
                'error' => 'Failed to create patient',
                'message' => $e->getMessage(),
                'details' => config('app.debug') ? $e->getTraceAsString() : null
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Patient $patient): JsonResponse
    {
        try {
            $user = auth()->user();

            // Ensure patient belongs to user's business
            if ($patient->business_id !== $user->business_id) {
                return response()->json(['error' => 'Patient not found'], 404);
            }

            // Check ownership permissions
            if (!$user->isAdmin() && $patient->created_by !== $user->id) {
                return response()->json(['error' => 'Unauthorized access to this patient'], 403);
            }

            $patient->load(['business', 'creator']);

            // Decode medications if it exists
            if ($patient->medications) {
                $patient->medications = json_decode($patient->medications, true);
            }

            return response()->json($patient);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch patient'], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Patient $patient): JsonResponse
    {
        try {
            $user = auth()->user();

            // Ensure patient belongs to user's business
            if ($patient->business_id !== $user->business_id) {
                return response()->json(['error' => 'Patient not found'], 404);
            }

            // Check ownership permissions
            if (!$user->isAdmin() && $patient->created_by !== $user->id) {
                return response()->json(['error' => 'Unauthorized to update this patient'], 403);
            }

            $validated = $request->validate([
                'first_name' => 'required|string|max:255',
                'last_name' => 'required|string|max:255',
                'middle_name' => 'nullable|string|max:255',
                'date_of_birth' => 'required|date',
                'gender' => 'required|in:male,female,other',
                'email' => 'nullable|email|unique:patients,email,' . $patient->id,
                'phone' => 'nullable|string|max:20',
                'address' => 'nullable|string',
                'city' => 'nullable|string|max:255',
                'state' => 'nullable|string|max:255',
                'zip_code' => 'nullable|string|max:10',
                'country' => 'nullable|string|max:255',
                'emergency_contact_name' => 'nullable|string|max:255',
                'emergency_contact_phone' => 'nullable|string|max:20',
                'emergency_contact_relationship' => 'nullable|string|max:255',
                'insurance_provider' => 'nullable|string|max:255',
                'insurance_policy_number' => 'nullable|string|max:255',
                'blood_type' => 'nullable|string|max:10',
                'allergies' => 'nullable|string',
                'medications' => 'nullable|array',
                'medical_history' => 'nullable|string',
                'family_history' => 'nullable|string',
                'surgical_history' => 'nullable|string',
                'social_history' => 'nullable|string',
                'preferred_pharmacy' => 'nullable|string|max:255',
                'primary_care_physician' => 'nullable|string|max:255',
                'referring_physician' => 'nullable|string|max:255',
                'occupation' => 'nullable|string|max:255',
                'employer' => 'nullable|string|max:255',
                'notes' => 'nullable|string',
                'marital_status' => 'nullable|string|max:255',
                'social_security' => 'nullable|string|max:255',
                'preferred_language' => 'nullable|string|max:255',
                'alternate_phone' => 'nullable|string|max:20',
                'emergency_contact_address' => 'nullable|string',
                'insurance_group_number' => 'nullable|string|max:255',
                'insurance_subscriber_name' => 'nullable|string|max:255',
                'insurance_subscriber_dob' => 'nullable|date',
                'secondary_insurance_provider' => 'nullable|string|max:255',
                'secondary_insurance_policy' => 'nullable|string|max:255',
                'height' => 'nullable|numeric',
                'weight' => 'nullable|numeric',
            ]);

            // Handle medications as JSON
            if (isset($validated['medications'])) {
                $validated['medications'] = json_encode($validated['medications']);
            }

            $patient->update($validated);

            return response()->json($patient);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to update patient'], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Patient $patient): JsonResponse
    {
        try {
            $user = auth()->user();

            // Ensure patient belongs to user's business
            if ($patient->business_id !== $user->business_id) {
                return response()->json(['error' => 'Patient not found'], 404);
            }

            // Check ownership permissions
            if (!$user->isAdmin() && $patient->created_by !== $user->id) {
                return response()->json(['error' => 'Unauthorized to delete this patient'], 403);
            }

            $patient->delete();

            return response()->json(['message' => 'Patient deleted successfully']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to delete patient'], 500);
        }
    }
}
