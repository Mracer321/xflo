<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
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
     * Scope the query to leads the given user is allowed to see.
     *
     * Developers see only leads assigned to them; all other roles see everything.
     */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->isDeveloper()) {
            return $query->whereHas('developerTask', function (Builder $q) use ($user) {
                $q->where('developer_id', $user->id);
            });
        }

        return $query;
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
