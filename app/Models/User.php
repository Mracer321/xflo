<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Available roles (stored directly in the users.role column).
     */
    public const ROLE_SUPER_ADMIN = 'super_admin';
    public const ROLE_LEADS_ADMIN = 'leads_admin';
    public const ROLE_SALES = 'sales';
    public const ROLE_DEVELOPER = 'developer';

    /**
     * Map of role keys to human-readable labels.
     *
     * @var array<string, string>
     */
    public const ROLES = [
        self::ROLE_SUPER_ADMIN => 'Super Admin',
        self::ROLE_LEADS_ADMIN => 'Leads Admin',
        self::ROLE_SALES => 'Sales',
        self::ROLE_DEVELOPER => 'Developer',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'is_active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Determine if the user has the given role.
     */
    public function hasRole(string $role): bool
    {
        return $this->role === $role;
    }

    /**
     * Determine if the user has any of the given roles.
     *
     * @param  array<int, string>  $roles
     */
    public function hasAnyRole(array $roles): bool
    {
        return in_array($this->role, $roles, true);
    }

    /**
     * Convenience check for the Super Admin role.
     */
    public function isSuperAdmin(): bool
    {
        return $this->hasRole(self::ROLE_SUPER_ADMIN);
    }

    /**
     * Convenience check for the Developer role.
     */
    public function isDeveloper(): bool
    {
        return $this->hasRole(self::ROLE_DEVELOPER);
    }

    /**
     * Get the human-readable label for the user's role.
     */
    public function getRoleLabelAttribute(): string
    {
        return self::ROLES[$this->role] ?? $this->role;
    }

    /**
     * Get the human-readable label for the user's active status.
     */
    public function getStatusLabelAttribute(): string
    {
        return $this->is_active ? 'Active' : 'Inactive';
    }

    /**
     * Developer tasks assigned to this user (their assigned leads).
     */
    public function developerTasks(): HasMany
    {
        return $this->hasMany(DeveloperTask::class, 'developer_id');
    }

    /**
     * Leads assigned to this user via the Phase 5 demo workflow.
     */
    public function assignedLeads(): HasMany
    {
        return $this->hasMany(Lead::class, 'developer_id');
    }
}
