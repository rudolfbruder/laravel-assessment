<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Task Filter Engine
    |--------------------------------------------------------------------------
    |
    | Selects the implementation used to filter and search tasks on the index
    | endpoint. "classic" uses hand-written Eloquent queries via the Task model
    | scopes; "spatie" uses spatie/laravel-query-builder. Both engines honor the
    | same normalized filter shape and produce equivalent results.
    |
    | Supported: "classic", "spatie"
    |
    */

    'filter_engine' => env('TASK_FILTER_ENGINE', 'classic'),

];
