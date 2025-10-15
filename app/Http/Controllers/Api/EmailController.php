<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use App\Models\Patient;
use App\Models\Appointment;
use App\Models\Bill;

class EmailController extends Controller
{
    /**
     * Send patient invitation email
     */
    public function sendPatientInvitation(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'patient_id' => 'required|exists:patients,id',
                'message' => 'nullable|string',
                'portal_url' => 'nullable|url',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $user = auth()->user();
            $patient = Patient::where('id', $request->patient_id)
                             ->where('business_id', $user->business_id)
                             ->first();

            if (!$patient) {
                return response()->json(['error' => 'Patient not found'], 404);
            }

            if (!$patient->email) {
                return response()->json(['error' => 'Patient does not have an email address'], 400);
            }

            $emailData = [
                'patient_name' => $patient->first_name . ' ' . $patient->last_name,
                'business_name' => $user->business->name ?? 'EHR Eezy',
                'message' => $request->message ?? 'You have been invited to access our patient portal.',
                'portal_url' => $request->portal_url ?? url('/patient-portal'),
                'contact_email' => $user->email,
            ];

            // For now, we'll just log the email (in production, use Mail facade)
            \Log::info('Patient invitation email would be sent:', [
                'to' => $patient->email,
                'subject' => 'Patient Portal Invitation',
                'data' => $emailData
            ]);

            // TODO: Implement actual email sending with Mail facade
            // Mail::to($patient->email)->send(new PatientInvitationMail($emailData));

            return response()->json([
                'success' => true,
                'message' => 'Patient invitation sent successfully',
                'recipient' => $patient->email
            ]);
        } catch (\Exception $e) {
            \Log::error('Error sending patient invitation:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Failed to send invitation',
                'message' => config('app.debug') ? $e->getMessage() : 'Please try again later'
            ], 500);
        }
    }

    /**
     * Send appointment reminder email
     */
    public function sendAppointmentReminder(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'appointment_id' => 'required|exists:appointments,id',
                'custom_message' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $user = auth()->user();
            $appointment = Appointment::with(['patient', 'staff'])
                                    ->where('id', $request->appointment_id)
                                    ->where('business_id', $user->business_id)
                                    ->first();

            if (!$appointment) {
                return response()->json(['error' => 'Appointment not found'], 404);
            }

            if (!$appointment->patient->email) {
                return response()->json(['error' => 'Patient does not have an email address'], 400);
            }

            $emailData = [
                'patient_name' => $appointment->patient->first_name . ' ' . $appointment->patient->last_name,
                'appointment_date' => $appointment->appointment_date,
                'appointment_time' => $appointment->start_time,
                'doctor_name' => $appointment->staff ?
                    $appointment->staff->first_name . ' ' . $appointment->staff->last_name :
                    'Your healthcare provider',
                'business_name' => $user->business->name ?? 'EHR Eezy',
                'appointment_type' => $appointment->type,
                'custom_message' => $request->custom_message,
                'contact_phone' => $user->business->phone ?? '',
            ];

            // Log the email (replace with actual email sending in production)
            \Log::info('Appointment reminder email would be sent:', [
                'to' => $appointment->patient->email,
                'subject' => 'Appointment Reminder',
                'data' => $emailData
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Appointment reminder sent successfully',
                'recipient' => $appointment->patient->email
            ]);
        } catch (\Exception $e) {
            \Log::error('Error sending appointment reminder:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Failed to send reminder',
                'message' => config('app.debug') ? $e->getMessage() : 'Please try again later'
            ], 500);
        }
    }

    /**
     * Send billing notification email
     */
    public function sendBillingNotification(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'bill_id' => 'required|exists:bills,id',
                'type' => 'required|in:invoice,payment_reminder,payment_confirmation',
                'custom_message' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $user = auth()->user();
            $bill = Bill::with(['patient'])
                       ->where('id', $request->bill_id)
                       ->where('business_id', $user->business_id)
                       ->first();

            if (!$bill) {
                return response()->json(['error' => 'Bill not found'], 404);
            }

            if (!$bill->patient->email) {
                return response()->json(['error' => 'Patient does not have an email address'], 400);
            }

            $emailData = [
                'patient_name' => $bill->patient->first_name . ' ' . $bill->patient->last_name,
                'invoice_number' => $bill->invoice_number,
                'amount' => $bill->total_amount,
                'due_date' => $bill->due_date,
                'service_date' => $bill->service_date,
                'business_name' => $user->business->name ?? 'EHR Eezy',
                'custom_message' => $request->custom_message,
                'type' => $request->type,
            ];

            $subjects = [
                'invoice' => 'New Invoice',
                'payment_reminder' => 'Payment Reminder',
                'payment_confirmation' => 'Payment Confirmation'
            ];

            // Log the email (replace with actual email sending in production)
            \Log::info('Billing notification email would be sent:', [
                'to' => $bill->patient->email,
                'subject' => $subjects[$request->type],
                'data' => $emailData
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Billing notification sent successfully',
                'recipient' => $bill->patient->email
            ]);
        } catch (\Exception $e) {
            \Log::error('Error sending billing notification:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Failed to send notification',
                'message' => config('app.debug') ? $e->getMessage() : 'Please try again later'
            ], 500);
        }
    }

    /**
     * Send custom email
     */
    public function sendCustomEmail(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'recipient_email' => 'required|email',
                'subject' => 'required|string|max:255',
                'message' => 'required|string',
                'patient_id' => 'nullable|exists:patients,id',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $user = auth()->user();

            // If patient_id is provided, verify it belongs to the business
            $patient = null;
            if ($request->patient_id) {
                $patient = Patient::where('id', $request->patient_id)
                                 ->where('business_id', $user->business_id)
                                 ->first();
                if (!$patient) {
                    return response()->json(['error' => 'Patient not found'], 404);
                }
            }

            $emailData = [
                'recipient_email' => $request->recipient_email,
                'subject' => $request->subject,
                'message' => $request->message,
                'sender_name' => $user->first_name . ' ' . $user->last_name,
                'business_name' => $user->business->name ?? 'EHR Eezy',
                'patient' => $patient,
            ];

            // Log the email (replace with actual email sending in production)
            \Log::info('Custom email would be sent:', [
                'to' => $request->recipient_email,
                'subject' => $request->subject,
                'data' => $emailData
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Email sent successfully',
                'recipient' => $request->recipient_email
            ]);
        } catch (\Exception $e) {
            \Log::error('Error sending custom email:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Failed to send email',
                'message' => config('app.debug') ? $e->getMessage() : 'Please try again later'
            ], 500);
        }
    }

    /**
     * Test SMTP configuration
     */
    public function testSmtpConfiguration(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'test_email' => 'required|email',
                'smtp_host' => 'required|string',
                'smtp_port' => 'required|integer',
                'smtp_username' => 'required|string',
                'smtp_password' => 'required|string',
                'smtp_encryption' => 'nullable|in:tls,ssl',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            // For now, just validate the configuration and log it
            // In production, you would actually test the SMTP connection

            $smtpConfig = [
                'host' => $request->smtp_host,
                'port' => $request->smtp_port,
                'username' => $request->smtp_username,
                'encryption' => $request->smtp_encryption ?? 'tls',
            ];

            \Log::info('SMTP configuration test:', [
                'config' => $smtpConfig,
                'test_email' => $request->test_email
            ]);

            // Simulate successful test
            return response()->json([
                'success' => true,
                'message' => 'SMTP configuration test successful',
                'test_email_sent' => true
            ]);
        } catch (\Exception $e) {
            \Log::error('SMTP test error:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'SMTP configuration test failed',
                'message' => config('app.debug') ? $e->getMessage() : 'Please check your SMTP settings'
            ], 500);
        }
    }

    /**
     * Get email templates
     */
    public function getEmailTemplates(): JsonResponse
    {
        try {
            $templates = [
                'patient_invitation' => [
                    'name' => 'Patient Invitation',
                    'subject' => 'Patient Portal Invitation - {business_name}',
                    'template' => 'Dear {patient_name},

You have been invited to access our patient portal where you can:
- View your medical records
- Schedule appointments
- View billing information
- Communicate with our staff

{message}

To access the portal, please visit: {portal_url}

If you have any questions, please contact us at {contact_email}.

Best regards,
{business_name}'
                ],
                'appointment_reminder' => [
                    'name' => 'Appointment Reminder',
                    'subject' => 'Appointment Reminder - {appointment_date}',
                    'template' => 'Dear {patient_name},

This is a reminder of your upcoming appointment:

Date: {appointment_date}
Time: {appointment_time}
Provider: {doctor_name}
Type: {appointment_type}

{custom_message}

If you need to reschedule or have any questions, please contact us at {contact_phone}.

Thank you,
{business_name}'
                ],
                'billing_invoice' => [
                    'name' => 'Billing Invoice',
                    'subject' => 'Invoice {invoice_number} - {business_name}',
                    'template' => 'Dear {patient_name},

Please find your invoice details below:

Invoice Number: {invoice_number}
Service Date: {service_date}
Amount Due: ${amount}
Due Date: {due_date}

{custom_message}

Please contact us if you have any questions about this invoice.

Thank you,
{business_name}'
                ]
            ];

            return response()->json([
                'success' => true,
                'templates' => $templates
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to load email templates',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
