<?php

namespace App\Observers;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;

class AuditObserver
{
    protected function logAction(Model $model, $action)
    {
        // Don't audit the audit log itself to avoid loops
        if ($model instanceof AuditLog) {
            return;
        }

        // Skip audit during migrations or when table doesn't exist yet
        if (!\Illuminate\Support\Facades\Schema::hasTable('audit_logs')) {
            return;
        }

        try {
            $userId = Auth::id();

            // Obtener IP de forma segura (maneja proxies como Cloudflare, Nginx, etc.)
            $ip = request()->header('X-Forwarded-For')
                ? explode(',', request()->header('X-Forwarded-For'))[0]
                : request()->ip();

            // Sanitizar IP
            $ip = trim($ip);
            if (!filter_var($ip, FILTER_VALIDATE_IP)) {
                $ip = '0.0.0.0';
            }

            AuditLog::create([
                'user_id' => $userId,
                'action' => $action,
                'model_type' => get_class($model),
                'model_id' => $model->id ?? null,
                'old_values' => $action !== 'created' ? $model->getOriginal() : null,
                'new_values' => $action !== 'deleted' ? $model->getAttributes() : null,
                'ip_address' => $ip,
                'user_agent' => request()->userAgent(),
            ]);
        } catch (\Throwable $e) {
            // Silently fail during migrations or seeding
        }
    }

    public function created(Model $model)
    {
        $this->logAction($model, 'created');
    }

    public function updated(Model $model)
    {
        $this->logAction($model, 'updated');
    }

    public function deleted(Model $model)
    {
        $this->logAction($model, 'deleted');
    }
}