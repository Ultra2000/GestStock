<?php

namespace App\Observers;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class AuditObserver
{
    public function created(Model $model): void
    {
        $this->log($model, 'created', null, $model->toArray());
    }

    public function updated(Model $model): void
    {
        // Ignore if only updated_at changed
        if ($model->wasChanged() && count($model->getChanges()) === 1 && $model->wasChanged('updated_at')) {
            return;
        }

        $oldValues = [];
        $newValues = [];

        foreach ($model->getDirty() as $key => $value) {
            if ($key === 'updated_at') continue;
            
            $oldValues[$key] = $model->getOriginal($key);
            $newValues[$key] = $value;
        }

        if (empty($newValues)) {
            return;
        }

        $this->log($model, 'updated', $oldValues, $newValues);
    }

    public function deleted(Model $model): void
    {
        $this->log($model, 'deleted', $model->toArray(), null);
    }

    protected function log(Model $model, string $event, ?array $oldValues, ?array $newValues): void
    {
        AuditLog::create([
            'company_id' => $model->company_id ?? (Auth::check() ? Auth::user()->company_id : null),
            'user_id' => Auth::id(),
            'auditable_type' => get_class($model),
            'auditable_id' => $model->id,
            'event' => $event,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'url' => Request::fullUrl(),
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
        ]);
    }
}
