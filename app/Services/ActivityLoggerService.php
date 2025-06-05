<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Models\Activity;

use function activity;

class ActivityLoggerService
{
    public function log(
        $subject = null,
        string $description = '',
        array $properties = [],
        $causer = null,
        string $logName = 'default'
    ): Activity {
        $logger = activity($logName)
            ->causedBy($causer)
            ->withProperties($properties);

        if ($subject instanceof Model) {
            $logger->performedOn($subject);
        }

        return $logger->log($description);
    }
}
