<?php

namespace App\Traits;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

trait LogsActivity
{
    /**
     * Log an activity to the audit logs.
     */
    protected function logActivity(
        string $action,
        ?Model $model = null,
        array $changes = [],
        string $result = 'success',
        ?string $description = null
    ): AuditLog {
        return AuditLog::create([
            'user_id' => Auth::id(),
            'action' => $action,
            'model' => $model ? get_class($model) : null,
            'model_id' => $model ? $model->getKey() : null,
            'changes' => $changes,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'result' => $result,
            'description' => $description,
        ]);
    }
}
