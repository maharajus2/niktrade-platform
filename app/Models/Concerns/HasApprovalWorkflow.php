<?php

namespace App\Models\Concerns;

use App\Models\ApprovalWorkflow;
use Illuminate\Database\Eloquent\Relations\MorphOne;

trait HasApprovalWorkflow
{
    public function approvalWorkflow(): MorphOne
    {
        return $this->morphOne(ApprovalWorkflow::class, 'approvable');
    }
}
