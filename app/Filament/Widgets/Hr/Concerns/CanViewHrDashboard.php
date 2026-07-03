<?php

namespace App\Filament\Widgets\Hr\Concerns;

use App\Models\User;

trait CanViewHrDashboard
{
    public static function canView(): bool
    {
        $user = auth()->user();

        return $user instanceof User
            && ($user->hasRole('super_admin') || $user->hasRole('hr') || $user->can('dashboard.hr.view'));
    }
}
