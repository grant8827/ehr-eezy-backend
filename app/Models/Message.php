<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends Model
{
    protected $fillable = [
        'sender_id',
        'receiver_id',
        'subject',
        'body',
        'priority',
        'is_read',
        'read_at',
        'attachments',
        'reply_to'
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'read_at' => 'datetime',
        'attachments' => 'array'
    ];

    /**
     * Get the sender of the message
     */
    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    /**
     * Get the receiver of the message
     */
    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }

    /**
     * Get the parent message if this is a reply
     */
    public function parentMessage(): BelongsTo
    {
        return $this->belongsTo(Message::class, 'reply_to');
    }

    /**
     * Get all replies to this message
     */
    public function replies()
    {
        return $this->hasMany(Message::class, 'reply_to');
    }

    /**
     * Mark message as read
     */
    public function markAsRead()
    {
        $this->update([
            'is_read' => true,
            'read_at' => now()
        ]);
    }

    /**
     * Scope for unread messages
     */
    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    /**
     * Scope for messages between specific users
     */
    public function scopeBetweenUsers($query, $user1, $user2)
    {
        return $query->where(function($q) use ($user1, $user2) {
            $q->where('sender_id', $user1)->where('receiver_id', $user2);
        })->orWhere(function($q) use ($user1, $user2) {
            $q->where('sender_id', $user2)->where('receiver_id', $user1);
        });
    }
}
