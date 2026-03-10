<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\AdminUser;

class MissionPolicy
{
    public function viewAny(AdminUser $user): bool
    {
        return in_array($user->role, ['super_admin', 'admin', 'manager', 'analyst', 'viewer']);
    }

    public function view(AdminUser $user): bool
    {
        return $this->viewAny($user);
    }

    public function create(AdminUser $user): bool
    {
        return in_array($user->role, ['super_admin', 'admin', 'manager']);
    }

    public function update(AdminUser $user): bool
    {
        return in_array($user->role, ['super_admin', 'admin', 'manager']);
    }

    public function delete(AdminUser $user): bool
    {
        return in_array($user->role, ['super_admin', 'admin']);
    }
}
