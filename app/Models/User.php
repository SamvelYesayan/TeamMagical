<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, SoftDeletes;

    public const ACTIVE = 'active';
    public const DISABLED = 'disabled';

    protected $fillable = [
        'name', 'username', 'password', 'role_id', 'position', 'status',
        'must_change_password', 'last_login_at',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'must_change_password' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function assignedDeadlines(): HasMany
    {
        return $this->hasMany(Deadline::class, 'assigned_to');
    }

    /** Keyed fingerprint of the current password hash; stored in the session at login. */
    public function sessionStamp(): string
    {
        return hash_hmac('sha256', (string) $this->password, (string) config('app.key'));
    }

    public function isAdmin(): bool
    {
        return $this->role?->slug === Role::ADMIN;
    }

    public function isActive(): bool
    {
        return $this->status === self::ACTIVE;
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', self::ACTIVE);
    }

    public function scopeAdmins(Builder $query): Builder
    {
        return $query->whereHas('role', fn (Builder $q) => $q->where('slug', Role::ADMIN));
    }

    public static function activeAdminCount(): int
    {
        return static::query()->active()->admins()->count();
    }
}
