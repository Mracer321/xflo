<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Lead extends Model
{
    /**
     * Lead pipeline statuses (stored directly in the leads.status column).
     */
    public const STATUS_NEW = 'new';

    public const STATUS_CONTACTED = 'contacted';

    public const STATUS_DEMO_REQUESTED = 'demo_requested';

    public const STATUS_DEMO_SENT = 'demo_sent';

    public const STATUS_FOLLOW_UP_1 = 'follow_up_1';

    public const STATUS_FOLLOW_UP_2 = 'follow_up_2';

    public const STATUS_INTERESTED = 'interested';

    public const STATUS_MEETING_SCHEDULED = 'meeting_scheduled';

    public const STATUS_WON = 'won';

    public const STATUS_LOST = 'lost';

    /**
     * Map of status keys to human-readable labels (also defines display order).
     *
     * @var array<string, string>
     */
    public const STATUSES = [
        self::STATUS_NEW => 'New',
        self::STATUS_CONTACTED => 'Contacted',
        self::STATUS_DEMO_REQUESTED => 'Demo Requested',
        self::STATUS_DEMO_SENT => 'Demo Sent',
        self::STATUS_FOLLOW_UP_1 => 'Follow Up 1',
        self::STATUS_FOLLOW_UP_2 => 'Follow Up 2',
        self::STATUS_INTERESTED => 'Interested',
        self::STATUS_MEETING_SCHEDULED => 'Meeting Scheduled',
        self::STATUS_WON => 'Won',
        self::STATUS_LOST => 'Lost',
    ];

    /**
     * Phase 5 demo-website workflow statuses (stored in leads.workflow_status).
     */
    public const WF_NEW_LEAD = 'new_lead';

    public const WF_ASSIGNED = 'assigned';

    public const WF_DEMO_IN_PROGRESS = 'demo_in_progress';

    public const WF_DEMO_READY = 'demo_ready';

    public const WF_DEMO_SENT = 'demo_sent';

    public const WF_FOLLOW_UP = 'follow_up';

    public const WF_CONVERTED = 'converted';

    public const WF_REJECTED = 'rejected';

    /**
     * Map of workflow statuses to labels (also defines display order).
     *
     * @var array<string, string>
     */
    public const WORKFLOW_STATUSES = [
        self::WF_NEW_LEAD => 'New Lead',
        self::WF_ASSIGNED => 'Assigned',
        self::WF_DEMO_IN_PROGRESS => 'Demo In Progress',
        self::WF_DEMO_READY => 'Demo Ready',
        self::WF_DEMO_SENT => 'Demo Sent',
        self::WF_FOLLOW_UP => 'Follow Up',
        self::WF_CONVERTED => 'Converted',
        self::WF_REJECTED => 'Rejected',
    ];

    /**
     * Workflow stages a developer owns — the ones they may both set and filter
     * the lead list by (their part of the demo-build pipeline).
     *
     * @var array<int, string>
     */
    public const DEV_WORKFLOW_STATUSES = [
        self::WF_ASSIGNED,
        self::WF_DEMO_IN_PROGRESS,
        self::WF_DEMO_READY,
    ];

    /**
     * Workflow stages a salesperson owns — the ones they may both set and filter
     * by, from the point a demo is ready through to the final outcome.
     *
     * @var array<int, string>
     */
    public const SALES_WORKFLOW_STATUSES = [
        self::WF_DEMO_READY,
        self::WF_DEMO_SENT,
        self::WF_FOLLOW_UP,
        self::WF_CONVERTED,
        self::WF_REJECTED,
    ];

    /**
     * Demo website lifecycle statuses (leads.demo_status) — separate from the sales workflow.
     */
    public const DEMO_LIVE = 'live';

    public const DEMO_OFFLINE = 'offline';

    public const DEMO_DELETED = 'deleted';

    /**
     * Map of demo statuses to labels.
     *
     * @var array<string, string>
     */
    public const DEMO_STATUSES = [
        self::DEMO_LIVE => 'Live',
        self::DEMO_OFFLINE => 'Offline',
        self::DEMO_DELETED => 'Deleted',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'business_name',
        'owner_name',
        'mobile_number',
        'whatsapp_number',
        'email',
        'category',
        'address',
        'google_business_url',
        'website_exists',
        'facebook_url',
        'instagram_url',
        'status',
        'notes',
        // Phase 5 demo workflow
        'workflow_status',
        'developer_id',
        'demo_url',
        'demo_created_at',
        'demo_sent_at',
        'demo_notes',
        'sales_notes',
        // Phase 5.1 demo lifecycle
        'demo_status',
        'offline_reason',
        'offline_at',
        'deleted_at_demo',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'website_exists' => 'boolean',
            'demo_created_at' => 'datetime',
            'demo_sent_at' => 'datetime',
            'offline_at' => 'datetime',
            'deleted_at_demo' => 'datetime',
        ];
    }

    /**
     * Get the human-readable label for the lead's status.
     */
    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    /**
     * Get the human-readable label for the lead's workflow status.
     */
    public function getWorkflowStatusLabelAttribute(): string
    {
        return self::WORKFLOW_STATUSES[$this->workflow_status] ?? $this->workflow_status;
    }

    /**
     * Get the human-readable label for the lead's demo lifecycle status.
     */
    public function getDemoStatusLabelAttribute(): string
    {
        return self::DEMO_STATUSES[$this->demo_status] ?? $this->demo_status;
    }

    /**
     * Scope the query to leads the given user is allowed to see.
     *
     * Developers see only leads assigned to them; all other roles see everything.
     */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->isDeveloper()) {
            return $query->where(function (Builder $q) use ($user) {
                // Assigned via the Phase 5 workflow...
                $q->where('developer_id', $user->id)
                    // ...or via the Phase 3 developer task.
                    ->orWhereHas('developerTask', function (Builder $sub) use ($user) {
                        $sub->where('developer_id', $user->id);
                    });
            });
        }

        return $query;
    }

    /**
     * Workflow-status options (key => label) the given user may filter by.
     *
     * Developers and salespeople see only the stages relevant to their part of
     * the pipeline; admins see every stage. Returned in canonical display order.
     *
     * @return array<string, string>
     */
    public static function workflowStatusOptionsFor(User $user): array
    {
        $keys = match (true) {
            $user->isDeveloper() => self::DEV_WORKFLOW_STATUSES,
            $user->hasRole(User::ROLE_SALES) => self::SALES_WORKFLOW_STATUSES,
            default => array_keys(self::WORKFLOW_STATUSES), // super_admin, leads_admin
        };

        return array_intersect_key(self::WORKFLOW_STATUSES, array_flip($keys));
    }

    /**
     * Demo-status options (key => label) the given user may filter by.
     *
     * Only administrators manage (and therefore filter by) the force-deleted
     * state; everyone else sees just the live/offline lifecycle. Returned in
     * canonical display order.
     *
     * @return array<string, string>
     */
    public static function demoStatusOptionsFor(User $user): array
    {
        if ($user->hasAnyRole([User::ROLE_SUPER_ADMIN, User::ROLE_LEADS_ADMIN])) {
            return self::DEMO_STATUSES;
        }

        return array_intersect_key(self::DEMO_STATUSES, array_flip([self::DEMO_LIVE, self::DEMO_OFFLINE]));
    }

    /**
     * The developer assigned to this lead's demo workflow (Phase 5).
     */
    public function developer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'developer_id');
    }

    /**
     * Timeline events for this lead, newest first.
     */
    public function events(): HasMany
    {
        return $this->hasMany(LeadEvent::class)->latest();
    }

    /**
     * Record a timeline event for this lead.
     */
    public function recordEvent(string $type, ?string $description = null): LeadEvent
    {
        return $this->events()->create([
            'user_id' => auth()->id(),
            'type' => $type,
            'description' => $description,
        ]);
    }

    /**
     * Files uploaded for this lead (logo, images, documents, screenshots).
     */
    public function assets(): HasMany
    {
        return $this->hasMany(LeadAsset::class);
    }

    /**
     * The single developer workflow record for this lead.
     */
    public function developerTask(): HasOne
    {
        return $this->hasOne(DeveloperTask::class);
    }

    /**
     * Assets of a given category (logo | image | document | screenshot).
     */
    public function assetsOfType(string $type): HasMany
    {
        return $this->assets()->where('file_type', $type);
    }
}
