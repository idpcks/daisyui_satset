<?php

use App\Models\Activity;

return [

    /*
     * Model kustom agar activity_log memakai primary key UUID.
     * Kunci lain mengikuti default dari paket spatie/laravel-activitylog.
     */
    'activity_model' => Activity::class,

];
