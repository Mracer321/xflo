<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class LeadAsset extends Model
{
    /**
     * Asset categories (stored in the lead_assets.file_type column).
     */
    public const TYPE_LOGO = 'logo';
    public const TYPE_IMAGE = 'image';
    public const TYPE_DOCUMENT = 'document';
    public const TYPE_SCREENSHOT = 'screenshot';

    /**
     * Map of type keys to human-readable labels.
     *
     * @var array<string, string>
     */
    public const TYPES = [
        self::TYPE_LOGO => 'Logo',
        self::TYPE_IMAGE => 'Business Image',
        self::TYPE_DOCUMENT => 'Document',
        self::TYPE_SCREENSHOT => 'Social Media Screenshot',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'lead_id',
        'file_name',
        'file_path',
        'file_type',
        'uploaded_by',
    ];

    /**
     * The lead this asset belongs to.
     */
    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    /**
     * The user who uploaded this asset.
     */
    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * Public URL to the stored file (served via the storage symlink).
     */
    public function getUrlAttribute(): string
    {
        return Storage::disk('public')->url($this->file_path);
    }

    /**
     * Human-readable label for the asset category.
     */
    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->file_type] ?? $this->file_type;
    }

    /**
     * Whether the stored file is a previewable image.
     */
    public function getIsImageAttribute(): bool
    {
        $extension = strtolower(pathinfo($this->file_path, PATHINFO_EXTENSION));

        // SVG deliberately omitted — not an accepted upload type and unsafe to
        // render inline (embedded scripts).
        return in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true);
    }
}
