<?php

declare(strict_types=1);

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;

class AdminUser extends Authenticatable implements FilamentUser
{
    use HasUuids;

    protected $table = 'admin_users';

    protected $fillable = [
        'email',
        'name',
        'password',
        'role',
        'is_active',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->is_active;
    }

    public function campaigns(): HasMany
    {
        return $this->hasMany(Campaign::class, 'created_by');
    }

    public function sequences(): HasMany
    {
        return $this->hasMany(Sequence::class, 'created_by');
    }

    public function missions(): HasMany
    {
        return $this->hasMany(Mission::class, 'created_by');
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class, 'admin_id');
    }
}
