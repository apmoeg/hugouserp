<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Component Locations (Livewire v4)
    |--------------------------------------------------------------------------
    */
    'component_locations' => [
        resource_path('views/livewire'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Component Namespaces (Livewire v4)
    |--------------------------------------------------------------------------
    */
    'component_namespaces' => [
        'layouts' => resource_path('views/layouts'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Page Layout (Livewire v4)
    |--------------------------------------------------------------------------
    */
    'component_layout' => 'layouts.app',

    /*
    |--------------------------------------------------------------------------
    | Class Namespace
    |--------------------------------------------------------------------------
    */
    'class_namespace' => 'App\\Livewire',

    /*
    |--------------------------------------------------------------------------
    | View Path (backward compatible)
    |--------------------------------------------------------------------------
    */
    'view_path' => resource_path('views/livewire'),

    /*
    |--------------------------------------------------------------------------
    | Layout (backward compatible alias)
    |--------------------------------------------------------------------------
    */
    'layout' => 'layouts.app',

    /*
    |--------------------------------------------------------------------------
    | Auto-inject Frontend Assets (Livewire v4)
    |--------------------------------------------------------------------------
    | Setting this to true means Livewire automatically injects its JavaScript
    | and Alpine.js. We keep @livewireStyles/@livewireScripts in layouts for
    | explicit control and to avoid duplicate Alpine instances.
    |--------------------------------------------------------------------------
    */
    'inject_assets' => false,

    /*
    |--------------------------------------------------------------------------
    | Navigate (SPA mode)
    |--------------------------------------------------------------------------
    */
    'navigate' => [
        'show_progress_bar' => true,
        'progress_bar_color' => '#22c55e',
    ],

    /*
    |--------------------------------------------------------------------------
    | HTML Morph Markers
    |--------------------------------------------------------------------------
    */
    'inject_morph_markers' => true,

    /*
    |--------------------------------------------------------------------------
    | Smart Wire Keys (Livewire v4)
    |--------------------------------------------------------------------------
    */
    'smart_wire_keys' => true,

    /*
    |--------------------------------------------------------------------------
    | Pagination Theme
    |--------------------------------------------------------------------------
    */
    'pagination_theme' => 'tailwind',

    /*
    |--------------------------------------------------------------------------
    | Temporary File Upload
    |--------------------------------------------------------------------------
    */
    'temporary_file_upload' => [
        'disk' => 'local',
        'rules' => ['file', 'max:12288'],
        'directory' => 'livewire-tmp',
        'middleware' => 'throttle:60,1',
        'preview_mimes' => [
            'png', 'gif', 'bmp', 'svg', 'svg+xml',
            'jpeg', 'webp', 'mp4', 'mov', 'avi',
        ],
        'max_upload_time' => 5,
        'cleanup' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | CSP Safe (Livewire v4)
    |--------------------------------------------------------------------------
    */
    'csp_safe' => false,

    /*
    |--------------------------------------------------------------------------
    | Payload Guards (Livewire v4)
    |--------------------------------------------------------------------------
    */
    'payload' => [
        'max_size' => 1024 * 1024,   // 1MB - maximum request payload size in bytes
        'max_nesting_depth' => 10,   // Maximum depth of dot-notation property paths
        'max_calls' => 50,           // Maximum method calls per request
        'max_components' => 20,      // Maximum components per batch request
    ],

];
