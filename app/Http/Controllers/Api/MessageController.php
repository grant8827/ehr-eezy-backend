<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Models\User;
use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class MessageController extends Controller
{
    /**
     * Display a listing of messages for the authenticated user
     */
    public function index(Request $request)
    {
        try {
            $user = Auth::user();
            $perPage = $request->get('per_page', 15);
            $conversationWith = $request->get('conversation_with');

            $query = Message::with(['sender', 'receiver', 'parentMessage'])
                ->where(function($q) use ($user) {
                    $q->where('sender_id', $user->id)
                      ->orWhere('receiver_id', $user->id);
                });

            // Filter by conversation partner if specified
            if ($conversationWith) {
                $query->betweenUsers($user->id, $conversationWith);
            }

            $messages = $query->orderBy('created_at', 'desc')
                            ->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $messages
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching messages: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get conversations list (unique users the current user has messaged with)
     */
    public function conversations(Request $request)
    {
        try {
            $user = Auth::user();

            // Get all unique conversation partners
            $conversations = Message::with(['sender', 'receiver'])
                ->where(function($q) use ($user) {
                    $q->where('sender_id', $user->id)
                      ->orWhere('receiver_id', $user->id);
                })
                ->get()
                ->groupBy(function($message) use ($user) {
                    // Group by the other participant in the conversation
                    return $message->sender_id === $user->id
                        ? $message->receiver_id
                        : $message->sender_id;
                })
                ->map(function($messages, $participantId) use ($user) {
                    $latestMessage = $messages->sortByDesc('created_at')->first();
                    $participant = $latestMessage->sender_id === $user->id
                        ? $latestMessage->receiver
                        : $latestMessage->sender;

                    $unreadCount = $messages->where('receiver_id', $user->id)
                                           ->where('is_read', false)
                                           ->count();

                    return [
                        'participant_id' => $participantId,
                        'participant' => [
                            'id' => $participant->id,
                            'name' => $participant->first_name . ' ' . $participant->last_name,
                            'role' => $participant->role,
                            'email' => $participant->email
                        ],
                        'latest_message' => [
                            'id' => $latestMessage->id,
                            'body' => $latestMessage->body,
                            'created_at' => $latestMessage->created_at,
                            'is_sender' => $latestMessage->sender_id === $user->id
                        ],
                        'unread_count' => $unreadCount
                    ];
                })
                ->values();

            return response()->json([
                'success' => true,
                'data' => $conversations
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching conversations: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get available users to message (patients for doctors/admins, doctors/admins for patients)
     */
    public function availableUsers(Request $request)
    {
        try {
            $user = Auth::user();
            $search = $request->get('search', '');

            $query = User::select('id', 'first_name', 'last_name', 'email', 'role', 'business_id')
                        ->where('id', '!=', $user->id)
                        ->where('is_active', true);

            // Role-based filtering
            if ($user->role === 'patient') {
                // Patients can message doctors, admins, and staff from their business
                $patientRecord = Patient::where('user_id', $user->id)->first();
                if (!$patientRecord) {
                    return response()->json([
                        'success' => true,
                        'data' => [],
                        'message' => 'No patient record found'
                    ]);
                }

                $query->where('business_id', $patientRecord->business_id)
                      ->whereIn('role', ['doctor', 'admin', 'staff', 'nurse', 'therapist']);
            } else {
                // Doctors, admins, staff can message patients from their business and each other
                $businessId = $user->business_id;
                if (!$businessId) {
                    return response()->json([
                        'success' => true, 
                        'data' => [],
                        'message' => 'No business assigned to user'
                    ]);
                }

                $query->where(function($q) use ($businessId) {
                    // Users from same business
                    $q->where('business_id', $businessId)
                      // OR patients from same business
                      ->orWhereExists(function($subQuery) use ($businessId) {
                          $subQuery->select('id')
                                   ->from('patients')
                                   ->where('business_id', $businessId)
                                   ->whereColumn('patients.user_id', 'users.id');
                      });
                });
            }

            // Search filter
            if ($search) {
                $query->where(function($q) use ($search) {
                    $q->where('first_name', 'like', "%{$search}%")
                      ->orWhere('last_name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
                });
            }

            $users = $query->orderBy('role')
                          ->orderBy('first_name')
                          ->orderBy('last_name')
                          ->get()
                          ->map(function($user) {
                              return [
                                  'id' => $user->id,
                                  'name' => $user->first_name . ' ' . $user->last_name,
                                  'email' => $user->email,
                                  'role' => $user->role
                              ];
                          });

            return response()->json([
                'success' => true,
                'data' => $users
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching available users: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created message
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'receiver_id' => 'required|exists:users,id',
                'subject' => 'nullable|string|max:255',
                'body' => 'required|string',
                'priority' => 'nullable|in:low,normal,high,urgent',
                'reply_to' => 'nullable|exists:messages,id'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = Auth::user();
            $receiverId = $request->receiver_id;

            // Validate messaging permissions
            $canMessage = $this->canUserMessage($user, $receiverId);
            if (!$canMessage['allowed']) {
                return response()->json([
                    'success' => false,
                    'message' => $canMessage['reason']
                ], 403);
            }

            $message = Message::create([
                'sender_id' => $user->id,
                'receiver_id' => $receiverId,
                'subject' => $request->subject,
                'body' => $request->body,
                'priority' => $request->priority ?? 'normal',
                'reply_to' => $request->reply_to
            ]);

            $message->load(['sender', 'receiver', 'parentMessage']);

            return response()->json([
                'success' => true,
                'message' => 'Message sent successfully',
                'data' => $message
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error sending message: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified message
     */
    public function show($id)
    {
        try {
            $user = Auth::user();

            $message = Message::with(['sender', 'receiver', 'parentMessage', 'replies.sender'])
                             ->where(function($q) use ($user) {
                                 $q->where('sender_id', $user->id)
                                   ->orWhere('receiver_id', $user->id);
                             })
                             ->findOrFail($id);

            // Mark as read if user is the receiver
            if ($message->receiver_id === $user->id && !$message->is_read) {
                $message->markAsRead();
            }

            return response()->json([
                'success' => true,
                'data' => $message
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Message not found'
            ], 404);
        }
    }

    /**
     * Mark message as read
     */
    public function markAsRead($id)
    {
        try {
            $user = Auth::user();

            $message = Message::where('receiver_id', $user->id)
                             ->findOrFail($id);

            $message->markAsRead();

            return response()->json([
                'success' => true,
                'message' => 'Message marked as read'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Message not found or access denied'
            ], 404);
        }
    }

    /**
     * Get unread message count for authenticated user
     */
    public function unreadCount()
    {
        try {
            $user = Auth::user();

            $count = Message::where('receiver_id', $user->id)
                           ->where('is_read', false)
                           ->count();

            return response()->json([
                'success' => true,
                'data' => ['count' => $count]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching unread count'
            ], 500);
        }
    }

    /**
     * Delete a message
     */
    public function destroy($id)
    {
        try {
            $user = Auth::user();

            $message = Message::where('sender_id', $user->id)
                             ->findOrFail($id);

            $message->delete();

            return response()->json([
                'success' => true,
                'message' => 'Message deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Message not found or access denied'
            ], 404);
        }
    }

    /**
     * Check if user can message another user
     */
    private function canUserMessage($sender, $receiverId)
    {
        $receiver = User::find($receiverId);

        if (!$receiver) {
            return ['allowed' => false, 'reason' => 'Receiver not found'];
        }

        // Patients can message doctors, admins, and staff from their business
        if ($sender->role === 'patient') {
            $patientRecord = Patient::where('user_id', $sender->id)->first();
            if (!$patientRecord) {
                return ['allowed' => false, 'reason' => 'Patient record not found'];
            }

            // Check if receiver is a healthcare provider from the same business
            if ($receiver->business_id !== $patientRecord->business_id) {
                return ['allowed' => false, 'reason' => 'Cannot message users from different healthcare providers'];
            }

            if (!in_array($receiver->role, ['doctor', 'admin', 'staff', 'nurse', 'therapist'])) {
                return ['allowed' => false, 'reason' => 'Patients can only message healthcare providers'];
            }
        } else {
            // Doctors, admins, staff can message within their business
            $senderBusinessId = $sender->business_id;

            if ($receiver->role === 'patient') {
                // Check if patient belongs to sender's business
                $patientRecord = Patient::where('user_id', $receiver->id)
                                       ->where('business_id', $senderBusinessId)
                                       ->first();
                if (!$patientRecord) {
                    return ['allowed' => false, 'reason' => 'Cannot message patients from different organizations'];
                }
            } else {
                // Check if both staff/doctors are from same business
                if ($receiver->business_id !== $senderBusinessId) {
                    return ['allowed' => false, 'reason' => 'Cannot message users from different organizations'];
                }
            }
        }

        return ['allowed' => true, 'reason' => ''];
    }
}
