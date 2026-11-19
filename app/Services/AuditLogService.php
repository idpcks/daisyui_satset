<?php

namespace App\Services;

use App\Models\Activity;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class AuditLogService
{
    /**
     * @return LengthAwarePaginator<int, array<string, mixed>>
     */
    public function paginateImpersonationLogs(): LengthAwarePaginator
    {
        return Activity::query()
            ->where('log_name', 'impersonation')
            ->latest()
            ->paginate(20)
            ->withQueryString()
            ->through(function (Activity $activity): array {
                $properties = $activity->properties;

                return [
                    'id' => $activity->id,
                    'event' => $activity->event,
                    'description' => $activity->description,
                    'impersonator_name' => $properties->get('impersonator_name'),
                    'impersonator_username' => $properties->get('impersonator_username'),
                    'impersonated_name' => $properties->get('impersonated_name'),
                    'impersonated_username' => $properties->get('impersonated_username'),
                    'ip_address' => $properties->get('ip_address'),
                    'duration_seconds' => $properties->get('duration_seconds'),
                    'created_at' => $activity->created_at?->toIso8601String(),
                ];
            });
    }
}
