<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Screen → Permission mapping
    |--------------------------------------------------------------------------
    |
    | هدف الملف ده توحيد أسماء الـ permissions اللي بنستخدمها مع middleware
    |  can:{module}.{action} على مستوى كل شاشات الواجهة (Livewire).
    |
    | تقدر تغيّر الأسماء هنا من غير ما تحتاج تلمس routes/web.php.
    |
    */

    // Dashboard
    'dashboard' => 'dashboard.view',

    // POS
    'pos' => [
        'terminal' => 'pos.use',
    ],

    // Admin area
    'admin' => [
        'users' => [
            'index' => 'users.manage',
        ],
        'branches' => [
            'index' => 'branches.view',
        ],
        'settings' => [
            'system' => 'settings.view',
            'branch' => 'settings.branch',
        ],
    ],

    // POS
    'pos' => [
        'terminal' => 'pos.use',
    ],

    // Notifications
    'notifications' => [
        'center' => 'system.view-notifications',
    ],

    // Inventory module
    'inventory' => [
        'products' => [
            'index' => 'inventory.products.view',
        ],
    ],

    // HRM module
    'hrm' => [
        'reports' => [
            'dashboard' => 'hr.view-reports',
        ],
    ],

    // Rental module
    'rental' => [
        'reports' => [
            'dashboard' => 'rental.view-reports',
        ],
    ],

    // Logs / audit
    'logs' => [
        'audit' => 'logs.audit.view',
    ],
];
