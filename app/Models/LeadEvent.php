<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeadEvent extends Model
{
    /**
     * Timeline event types.
     */
    public const TYPE_CREATED = 'created';
    public const TYPE_ASSIGNED = 'assigned';
    public const TYPE_DEMO_STARTED = 'demo_started';
    public const TYPE_DEMO_READY = 'demo_ready';
    public const TYPE_DEMO_SENT = 'demo_sent';
    public const TYPE_FOLLOW_UP = 'follow_up';
    public const TYPE_CONVERTED = 'converted';
    public const TYPE_REJECTED = 'rejected';
    public const TYPE_NOTE = 'note';
    // Phase 5.1 demo lifecycle events
    public const TYPE_DEMO_CREATED = 'demo_created';
    public const TYPE_DEMO_OFFLINE = 'demo_offline';
    public const TYPE_DEMO_REACTIVATED = 'demo_reactivated';
    public const TYPE_DEMO_DELETED = 'demo_deleted';

    /**
     * Map of event types to human-readable labels.
     *
     * @var array<string, string>
     */
    public const TYPES = [
        self::TYPE_CREATED => 'Lead Created',
        self::TYPE_ASSIGNED => 'Assigned to Developer',
        self::TYPE_DEMO_STARTED => 'Demo Started',
        self::TYPE_DEMO_READY => 'Demo Ready',
        self::TYPE_DEMO_SENT => 'Demo Sent',
        self::TYPE_FOLLOW_UP => 'Follow Up',
        self::TYPE_CONVERTED => 'Converted',
        self::TYPE_REJECTED => 'Rejected',
        self::TYPE_NOTE => 'Note',
        self::TYPE_DEMO_CREATED => 'Demo Created',
        self::TYPE_DEMO_OFFLINE => 'Demo Went Offline',
        self::TYPE_DEMO_REACTIVATED => 'Demo Reactivated',
        self::TYPE_DEMO_DELETED => 'Demo Deleted',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'lead_id',
        'user_id',
        'type',
        'description',
    ];

    /**
     * The lead this event belongs to.
     */
    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    /**
     * The user who triggered the event.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Human-readable label for the event type.
     */
    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }
}
