<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

class AppointmentController extends Controller
{
    /**
     * Display a listing of appointments
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = auth()->user();
            $query = Appointment::query()
                ->where('business_id', $user->business_id)
                ->with(['patient', 'staff', 'creator']);

            // Filter by ownership based on user role
            if (!$user->isAdmin()) {
                // Non-admin users can only see appointments they created or are assigned to
                $query->where(function($q) use ($user) {
                    $q->where('created_by', $user->id)
                      ->orWhere('staff_id', $user->id);
                });
            }

            // Apply filters
            if ($request->has('date')) {
                $query->whereDate('appointment_date', $request->get('date'));
            }

            if ($request->has('staff_id')) {
                $query->where('staff_id', $request->get('staff_id'));
            }

            if ($request->has('patient_id')) {
                $query->where('patient_id', $request->get('patient_id'));
            }

            if ($request->has('status')) {
                $query->where('status', $request->get('status'));
            }

            if ($request->has('type')) {
                $query->where('type', $request->get('type'));
            }

            // Date range filter
            if ($request->has('start_date') && $request->has('end_date')) {
                $query->whereBetween('appointment_date', [
                    $request->get('start_date'),
                    $request->get('end_date')
                ]);
            }

            // Default to upcoming appointments if no filters
            if (!$request->hasAny(['date', 'start_date', 'status'])) {
                $query->where('appointment_date', '>=', now()->toDateString());
            }

            $appointments = $query->orderBy('appointment_date')
                                 ->orderBy('start_time')
                                 ->paginate($request->get('per_page', 15));

            // Transform the data
            $appointments->getCollection()->transform(function ($appointment) {
                return [
                    'id' => $appointment->id,
                    'appointment_number' => $appointment->appointment_number,
                    'appointment_date' => Carbon::parse($appointment->appointment_date)->format('Y-m-d'),
                    'formatted_date' => Carbon::parse($appointment->appointment_date)->format('M j, Y'),
                    'start_time' => $appointment->start_time ? Carbon::parse($appointment->start_time)->format('H:i') : null,
                    'end_time' => $appointment->end_time ? Carbon::parse($appointment->end_time)->format('H:i') : null,
                    'formatted_time' => $appointment->start_time && $appointment->end_time ?
                        Carbon::parse($appointment->start_time)->format('g:i A') . ' - ' .
                        Carbon::parse($appointment->end_time)->format('g:i A') : null,
                    'duration_minutes' => $appointment->duration_minutes,
                    'type' => $appointment->type,
                    'status' => $appointment->status,
                    'reason_for_visit' => $appointment->reason_for_visit,
                    'notes' => $appointment->notes,
                    'fee' => $appointment->fee,
                    'patient' => [
                        'id' => $appointment->patient?->id,
                        'name' => $appointment->patient ?
                            $appointment->patient->first_name . ' ' . $appointment->patient->last_name :
                            'Unknown Patient',
                        'email' => $appointment->patient?->email,
                        'phone' => $appointment->patient?->phone
                    ],
                    'staff' => [
                        'id' => $appointment->staff?->id,
                        'name' => $appointment->staff ?
                            $appointment->staff->first_name . ' ' . $appointment->staff->last_name :
                            'Unknown Staff',
                        'role' => $appointment->staff?->role
                    ],
                    'created_by' => [
                        'id' => $appointment->creator?->id,
                        'name' => $appointment->creator ?
                            $appointment->creator->first_name . ' ' . $appointment->creator->last_name :
                            'System'
                    ],
                    'can_cancel' => $appointment->canBeCancelled(),
                    'can_reschedule' => $appointment->canBeRescheduled(),
                    'can_complete' => $appointment->canBeCompleted(),
                    'created_at' => $appointment->created_at?->format('Y-m-d H:i:s')
                ];
            });

            return response()->json($appointments);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch appointments: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Store a newly created appointment
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $user = auth()->user();

            $validated = $request->validate([
                'patient_id' => 'required|exists:patients,id',
                'staff_id' => 'required|exists:users,id',
                'appointment_date' => 'required|date',
                'start_time' => 'required|date_format:H:i',
                'end_time' => 'required|date_format:H:i',
                'duration_minutes' => 'required|integer|min:15|max:480',
                'type' => 'required|in:in-person,telehealth',
                'reason_for_visit' => 'nullable|string',
                'notes' => 'nullable|string',
                'fee' => 'nullable|numeric|min:0',
            ]);

            // Check if patient belongs to the same business
            $patient = Patient::find($validated['patient_id']);
            if (!$patient || $patient->business_id !== $user->business_id) {
                return response()->json(['error' => 'Patient not found'], 404);
            }

            // Check if staff belongs to the same business
            $staff = User::find($validated['staff_id']);
            if (!$staff || $staff->business_id !== $user->business_id) {
                return response()->json(['error' => 'Staff member not found'], 404);
            }

            // Check for scheduling conflicts
            $conflictExists = Appointment::where('staff_id', $validated['staff_id'])
                ->where('appointment_date', $validated['appointment_date'])
                ->whereIn('status', ['scheduled', 'confirmed', 'in_progress'])
                ->where(function($query) use ($validated) {
                    $query->whereBetween('start_time', [$validated['start_time'], $validated['end_time']])
                          ->orWhereBetween('end_time', [$validated['start_time'], $validated['end_time']])
                          ->orWhere(function($q) use ($validated) {
                              $q->where('start_time', '<=', $validated['start_time'])
                                ->where('end_time', '>=', $validated['end_time']);
                          });
                })
                ->exists();

            if ($conflictExists) {
                return response()->json([
                    'error' => 'Time slot conflict detected. Please choose a different time.'
                ], 422);
            }

            // Add business and creator info
            $validated['business_id'] = $user->business_id;
            $validated['created_by'] = $user->id;
            $validated['appointment_number'] = 'APT' . time(); // Simpler unique number
            $validated['status'] = $validated['status'] ?? 'scheduled';

            $appointment = Appointment::create($validated);
            $appointment->load(['patient', 'staff', 'creator']);

            return response()->json($appointment, 201);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to create appointment: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Display the specified appointment
     */
    public function show(Appointment $appointment): JsonResponse
    {
        try {
            $user = auth()->user();

            // Ensure appointment belongs to user's business
            if ($appointment->business_id !== $user->business_id) {
                return response()->json(['error' => 'Appointment not found'], 404);
            }

            // Check permissions
            if (!$user->isAdmin() &&
                $appointment->created_by !== $user->id &&
                $appointment->staff_id !== $user->id) {
                return response()->json(['error' => 'Unauthorized access to this appointment'], 403);
            }

            $appointment->load(['patient', 'staff', 'creator']);
            return response()->json($appointment);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch appointment'], 500);
        }
    }

    /**
     * Update the specified appointment
     */
    public function update(Request $request, Appointment $appointment): JsonResponse
    {
        try {
            $user = auth()->user();

            // Ensure appointment belongs to user's business
            if ($appointment->business_id !== $user->business_id) {
                return response()->json(['error' => 'Appointment not found'], 404);
            }

            // Check permissions
            if (!$user->isAdmin() &&
                $appointment->created_by !== $user->id &&
                $appointment->staff_id !== $user->id) {
                return response()->json(['error' => 'Unauthorized to update this appointment'], 403);
            }

            $validated = $request->validate([
                'patient_id' => 'sometimes|exists:patients,id',
                'staff_id' => 'sometimes|exists:users,id',
                'appointment_date' => 'sometimes|date',
                'start_time' => 'sometimes|date_format:H:i',
                'end_time' => 'sometimes|date_format:H:i|after:start_time',
                'duration_minutes' => 'sometimes|integer|min:15|max:480',
                'type' => 'sometimes|in:in-person,telehealth',
                'reason_for_visit' => 'nullable|string',
                'notes' => 'nullable|string',
                'private_notes' => 'nullable|string',
                'fee' => 'nullable|numeric|min:0',
                'status' => 'sometimes|in:scheduled,confirmed,in_progress,completed,cancelled,no_show,rescheduled'
            ]);

            // Check for conflicts if time/date is being changed
            if (isset($validated['appointment_date']) || isset($validated['start_time']) || isset($validated['end_time'])) {
                $staffId = $validated['staff_id'] ?? $appointment->staff_id;
                $appointmentDate = $validated['appointment_date'] ?? $appointment->appointment_date;
                $startTime = $validated['start_time'] ?? $appointment->start_time;
                $endTime = $validated['end_time'] ?? $appointment->end_time;

                $conflictExists = Appointment::where('staff_id', $staffId)
                    ->where('appointment_date', $appointmentDate)
                    ->whereIn('status', ['scheduled', 'confirmed', 'in_progress'])
                    ->where('id', '!=', $appointment->id) // Exclude current appointment
                    ->where(function($query) use ($startTime, $endTime) {
                        $query->whereBetween('start_time', [$startTime, $endTime])
                              ->orWhereBetween('end_time', [$startTime, $endTime])
                              ->orWhere(function($q) use ($startTime, $endTime) {
                                  $q->where('start_time', '<=', $startTime)
                                    ->where('end_time', '>=', $endTime);
                              });
                    })
                    ->exists();

                if ($conflictExists) {
                    return response()->json([
                        'error' => 'Time slot conflict detected. Please choose a different time.'
                    ], 422);
                }
            }

            $appointment->update($validated);
            $appointment->load(['patient', 'staff', 'creator']);

            return response()->json($appointment);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to update appointment'], 500);
        }
    }

    /**
     * Remove the specified appointment
     */
    public function destroy(Appointment $appointment): JsonResponse
    {
        try {
            $user = auth()->user();

            // Ensure appointment belongs to user's business
            if ($appointment->business_id !== $user->business_id) {
                return response()->json(['error' => 'Appointment not found'], 404);
            }

            // Check permissions
            if (!$user->isAdmin() &&
                $appointment->created_by !== $user->id &&
                $appointment->staff_id !== $user->id) {
                return response()->json(['error' => 'Unauthorized to delete this appointment'], 403);
            }

            $appointment->delete();
            return response()->json(['message' => 'Appointment deleted successfully']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to delete appointment'], 500);
        }
    }

    /**
     * Get availability for a staff member on a specific date
     */
    public function checkAvailability(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'staff_id' => 'required|exists:users,id',
                'date' => 'required|date',
                'duration_minutes' => 'sometimes|integer|min:15|max:480'
            ]);            $user = auth()->user();
            $staffId = $request->get('staff_id');
            $date = $request->get('date');
            $duration = (int) $request->get('duration_minutes', 60); // Default 1 hour, ensure integer

            // Check if staff belongs to same business
            $staff = User::find($staffId);
            if (!$staff || $staff->business_id !== $user->business_id) {
                return response()->json(['error' => 'Staff member not found'], 404);
            }

            // Get existing appointments for the staff member on the date
            $existingAppointments = Appointment::where('staff_id', $staffId)
                ->whereDate('appointment_date', $date)
                ->whereIn('status', ['scheduled', 'confirmed', 'in_progress'])
                ->orderBy('start_time')
                ->get(['start_time', 'end_time']);

            // Define business hours (9 AM to 5 PM by default)
            $businessStart = '09:00';
            $businessEnd = '17:00';

            // Generate available time slots
            $availableSlots = [];

            // Create time slots every 30 minutes from 9 AM to 5 PM
            for ($hour = 9; $hour < 17; $hour++) {
                for ($minute = 0; $minute < 60; $minute += 30) {
                    $slotStartTime = sprintf('%02d:%02d', $hour, $minute);

                    // Calculate end time by adding duration
                    $startDateTime = Carbon::createFromFormat('Y-m-d H:i', $date . ' ' . $slotStartTime);
                    $endDateTime = $startDateTime->copy()->addMinutes($duration);

                    // Skip if slot would extend beyond business hours
                    if ($endDateTime->hour > 17 || ($endDateTime->hour == 17 && $endDateTime->minute > 0)) {
                        continue;
                    }

                    $slotEndTime = $endDateTime->format('H:i');

                    // Check for conflicts with existing appointments
                    $hasConflict = false;
                    foreach ($existingAppointments as $appointment) {
                        $appointmentStart = $appointment->start_time;
                        $appointmentEnd = $appointment->end_time;

                        // Check if times overlap
                        if ($slotStartTime < $appointmentEnd && $slotEndTime > $appointmentStart) {
                            $hasConflict = true;
                            break;
                        }
                    }

                    if (!$hasConflict) {
                        $availableSlots[] = [
                            'start_time' => $slotStartTime,
                            'end_time' => $slotEndTime,
                            'formatted' => $startDateTime->format('g:i A') . ' - ' . $endDateTime->format('g:i A')
                        ];
                    }
                }
            }

            return response()->json([
                'date' => $date,
                'staff_id' => $staffId,
                'staff_name' => $staff->first_name . ' ' . $staff->last_name,
                'available_slots' => $availableSlots,
                'existing_appointments' => $existingAppointments->map(function($apt) {
                    return [
                        'start_time' => $apt->start_time,
                        'end_time' => $apt->end_time,
                        'formatted' => $apt->start_time . ' - ' . $apt->end_time
                    ];
                })
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to check availability: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Update appointment status
     */
    public function updateStatus(Request $request, Appointment $appointment): JsonResponse
    {
        try {
            $user = auth()->user();

            // Ensure appointment belongs to user's business
            if ($appointment->business_id !== $user->business_id) {
                return response()->json(['error' => 'Appointment not found'], 404);
            }

            $validated = $request->validate([
                'status' => 'required|in:scheduled,confirmed,in_progress,completed,cancelled,no_show',
                'cancellation_reason' => 'required_if:status,cancelled|string'
            ]);

            switch ($validated['status']) {
                case 'confirmed':
                    $appointment->confirm();
                    break;
                case 'completed':
                    $appointment->markAsCompleted();
                    break;
                case 'cancelled':
                    $appointment->markAsCancelled($validated['cancellation_reason'] ?? null);
                    break;
                case 'no_show':
                    $appointment->markAsNoShow();
                    break;
                default:
                    $appointment->update(['status' => $validated['status']]);
            }

            $appointment->load(['patient', 'staff', 'creator']);
            return response()->json($appointment);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to update appointment status'], 500);
        }
    }
}
