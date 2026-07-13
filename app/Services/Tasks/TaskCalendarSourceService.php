<?php

namespace App\Services\Tasks;

use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class TaskCalendarSourceService
{
    /**
     * Calendar integration is intentionally read-only and empty in Phase 1.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function entriesFor(User $user, Carbon $start, Carbon $end): Collection
    {
        return collect();
    }
}
