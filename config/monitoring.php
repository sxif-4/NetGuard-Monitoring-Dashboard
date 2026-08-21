<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Device Metrics Retention
    |--------------------------------------------------------------------------
    |
    | How many days of `device_metrics` history to keep. A small buffer past
    | the longest historical chart range (30 days) so the 30d view never runs
    | dry right at the edge.
    |
    */

    'metrics_retention_days' => env('METRICS_RETENTION_DAYS', 35),

];
