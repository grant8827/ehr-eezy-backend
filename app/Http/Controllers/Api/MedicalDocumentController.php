<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MedicalDocument;
use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class MedicalDocumentController extends Controller
{
    /**
     * Display a listing of medical documents.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = MedicalDocument::with(['patient', 'uploader', 'appointment', 'medicalRecord'])
            ->forBusiness($user->business_id);

        // Apply filters based on user role
        if ($user->role === 'patient') {
            // Patients can only see their own non-confidential documents or documents they uploaded
            $patient = Patient::where('user_id', $user->id)->first();
            if (!$patient) {
                return response()->json(['error' => 'Patient profile not found'], 404);
            }

            $query->where(function ($q) use ($patient, $user) {
                $q->where('patient_id', $patient->id)
                  ->where(function ($subQ) use ($user) {
                      $subQ->where('is_confidential', false)
                           ->orWhere('uploaded_by', $user->id);
                  });
            });
        } elseif ($user->role === 'doctor') {
            // Doctors can see documents for their patients
            $patientIds = Patient::where('user_id', $user->id)->pluck('id');
            $query->whereIn('patient_id', $patientIds);
        }

        // Apply additional filters
        if ($request->filled('patient_id')) {
            $query->where('patient_id', $request->patient_id);
        }

        if ($request->filled('document_type')) {
            $query->where('document_type', $request->document_type);
        }

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        if ($request->filled('is_confidential')) {
            $query->where('is_confidential', $request->boolean('is_confidential'));
        }

        if ($request->filled('date_from')) {
            $query->whereDate('document_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('document_date', '<=', $request->date_to);
        }

        // Search by title or description
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('original_file_name', 'like', "%{$search}%");
            });
        }

        $documents = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return response()->json($documents);
    }

    /**
     * Store a newly created medical document.
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:pdf,doc,docx,jpg,jpeg,png,gif,bmp,tiff|max:10240', // 10MB max
            'patient_id' => 'required|exists:patients,id',
            'document_type' => ['required', Rule::in(array_keys(MedicalDocument::getDocumentTypes()))],
            'category' => ['required', Rule::in(array_keys(MedicalDocument::getCategories()))],
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'document_date' => 'nullable|date|before_or_equal:today',
            'appointment_id' => 'nullable|exists:appointments,id',
            'medical_record_id' => 'nullable|exists:medical_records,id',
            'is_confidential' => 'boolean',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:50',
            'notes' => 'nullable|string|max:1000'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Check if patient belongs to the same business
        $patient = Patient::where('id', $request->patient_id)
            ->where('business_id', $user->business_id)
            ->first();

        if (!$patient) {
            return response()->json(['error' => 'Patient not found or access denied'], 404);
        }

        try {
            $file = $request->file('file');
            $originalFileName = $file->getClientOriginalName();
            $fileExtension = $file->getClientOriginalExtension();
            $mimeType = $file->getMimeType();
            $fileSize = $file->getSize();

            // Generate unique filename
            $fileName = Str::uuid() . '.' . $fileExtension;

            // Store file in patient-specific directory
            $filePath = $file->storeAs(
                "medical-documents/{$user->business_id}/{$patient->id}",
                $fileName,
                'private'
            );

            $document = MedicalDocument::create([
                'business_id' => $user->business_id,
                'patient_id' => $patient->id,
                'uploaded_by' => $user->id,
                'appointment_id' => $request->appointment_id,
                'medical_record_id' => $request->medical_record_id,
                'document_type' => $request->document_type,
                'category' => $request->category,
                'title' => $request->title,
                'description' => $request->description,
                'document_date' => $request->document_date ?: now()->toDateString(),
                'tags' => $request->tags,
                'notes' => $request->notes,
                'is_confidential' => $request->boolean('is_confidential', false),
                'file_name' => $fileName,
                'original_file_name' => $originalFileName,
                'file_path' => $filePath,
                'file_size' => $fileSize,
                'mime_type' => $mimeType,
                'file_extension' => $fileExtension
            ]);

            $document->load(['patient', 'uploader', 'appointment', 'medicalRecord']);

            return response()->json([
                'message' => 'Document uploaded successfully',
                'document' => $document
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to upload document',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified medical document.
     */
    public function show(Request $request, MedicalDocument $medicalDocument): JsonResponse
    {
        $user = $request->user();

        // Check access permissions
        if (!$medicalDocument->canBeViewedBy($user)) {
            return response()->json(['error' => 'Access denied'], 403);
        }

        $medicalDocument->load(['patient', 'uploader', 'appointment', 'medicalRecord']);
        $medicalDocument->markAsViewed($user);

        return response()->json($medicalDocument);
    }

    /**
     * Update the specified medical document.
     */
    public function update(Request $request, MedicalDocument $medicalDocument): JsonResponse
    {
        $user = $request->user();

        // Check if user can edit this document
        if ($medicalDocument->business_id !== $user->business_id) {
            return response()->json(['error' => 'Access denied'], 403);
        }

        $validator = Validator::make($request->all(), [
            'document_type' => ['sometimes', Rule::in(array_keys(MedicalDocument::getDocumentTypes()))],
            'category' => ['sometimes', Rule::in(array_keys(MedicalDocument::getCategories()))],
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string|max:1000',
            'document_date' => 'nullable|date|before_or_equal:today',
            'is_confidential' => 'boolean',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:50',
            'notes' => 'nullable|string|max:1000'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $medicalDocument->update($request->only([
            'document_type', 'category', 'title', 'description',
            'document_date', 'is_confidential', 'tags', 'notes'
        ]));

        $medicalDocument->load(['patient', 'uploader', 'appointment', 'medicalRecord']);

        return response()->json([
            'message' => 'Document updated successfully',
            'document' => $medicalDocument
        ]);
    }

    /**
     * Remove the specified medical document.
     */
    public function destroy(Request $request, MedicalDocument $medicalDocument): JsonResponse
    {
        $user = $request->user();

        // Check permissions - only admin or the uploader can delete
        if ($medicalDocument->business_id !== $user->business_id ||
            ($user->role !== 'admin' && $medicalDocument->uploaded_by !== $user->id)) {
            return response()->json(['error' => 'Access denied'], 403);
        }

        try {
            $medicalDocument->delete(); // File deletion handled in model boot method

            return response()->json([
                'message' => 'Document deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to delete document',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Download the specified medical document.
     */
    public function download(Request $request, MedicalDocument $medicalDocument)
    {
        $user = $request->user();

        // Check access permissions
        if (!$medicalDocument->canBeViewedBy($user)) {
            return response()->json(['error' => 'Access denied'], 403);
        }

        if (!Storage::exists($medicalDocument->file_path)) {
            return response()->json(['error' => 'File not found'], 404);
        }

        $medicalDocument->markAsViewed($user);

        return Storage::download(
            $medicalDocument->file_path,
            $medicalDocument->original_file_name
        );
    }

    /**
     * Get document types and categories.
     */
    public function metadata(): JsonResponse
    {
        return response()->json([
            'document_types' => MedicalDocument::getDocumentTypes(),
            'categories' => MedicalDocument::getCategories()
        ]);
    }

    /**
     * Get documents for a specific patient.
     */
    public function patientDocuments(Request $request, Patient $patient): JsonResponse
    {
        $user = $request->user();

        // Check if user can access this patient's documents
        if ($patient->business_id !== $user->business_id) {
            return response()->json(['error' => 'Access denied'], 403);
        }

        if ($user->role === 'patient') {
            $userPatient = Patient::where('user_id', $user->id)->first();
            if (!$userPatient || $userPatient->id !== $patient->id) {
                return response()->json(['error' => 'Access denied'], 403);
            }
        }

        $query = $patient->documents()->with(['uploader', 'appointment', 'medicalRecord']);

        // If patient user, filter confidential documents
        if ($user->role === 'patient') {
            $query->where(function ($q) use ($user) {
                $q->where('is_confidential', false)
                  ->orWhere('uploaded_by', $user->id);
            });
        }

        $documents = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return response()->json($documents);
    }
}
