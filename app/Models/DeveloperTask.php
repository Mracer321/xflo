<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeveloperTask extends Model
{
    /**
     * Developer workflow statuses (stored in the developer_tasks.status column).
     */
    public const STATUS_NOT_STARTED = 'not_started';
    public const STATUS_DEVELOPING = 'developing';
    public const STATUS_DEPLOYING = 'deploying';
    public const STATUS_DEMO_READY = 'demo_ready';
    public const STATUS_OFFLINE = 'offline';
    public const STATUS_DELETED = 'deleted';

    /**
     * Map of status keys to human-readable labels (also defines display order).
     *
     * @var array<string, string>
     */
    public const STATUSES = [
        self::STATUS_NOT_STARTED => 'Not Started',
        self::STATUS_DEVELOPING => 'Developing',
        self::STATUS_DEPLOYING => 'Deploying',
        self::STATUS_DEMO_READY => 'Demo Ready',
        self::STATUS_OFFLINE => 'Offline',
        self::STATUS_DELETED => 'Deleted',
    ];

    /**
     * Statuses that require a mandatory reason.
     *
     * @var array<int, string>
     */
    public const REASON_REQUIRED_STATUSES = [
        self::STATUS_OFFLINE,
        self::STATUS_DELETED,
    ];

    /**
     * Deployment platforms.
     */
    public const PLATFORM_VERCEL = 'vercel';
    public const PLATFORM_NETLIFY = 'netlify';
    public const PLATFORM_CLOUDFLARE = 'cloudflare_pages';

    /**
     * Map of platform keys to human-readable labels.
     *
     * @var array<string, string>
     */
    public const PLATFORMS = [
        self::PLATFORM_VERCEL => 'Vercel',
        self::PLATFORM_NETLIFY => 'Netlify',
        self::PLATFORM_CLOUDFLARE => 'Cloudflare Pages',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'lead_id',
        'developer_id',
        'status',
        'notes',
        'demo_url',
        'deployment_platform',
        'deployment_date',
        'reason',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'deployment_date' => 'date',
        ];
    }

    /**
     * The lead this task belongs to.
     */
    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    /**
     * The developer assigned to this task.
     */
    public function developer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'developer_id');
    }

    /**
     * Whether the given status requires a mandatory reason.
     */
    public static function statusRequiresReason(?string $status): bool
    {
        return in_array($status, self::REASON_REQUIRED_STATUSES, true);
    }

    /**
     * Human-readable label for the task's status.
     */
    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    /**
     * Human-readable label for the deployment platform.
     */
    public function getPlatformLabelAttribute(): ?string
    {
        return self::PLATFORMS[$this->deployment_platform] ?? $this->deployment_platform;
    }
}
