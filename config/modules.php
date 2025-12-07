<?php

declare(strict_types=1);

return [

    'inventory' => [
        'entities' => [
            'products' => [
                'fields' => [
                    [
                        'name' => 'engine_capacity',
                        'label' => 'Engine capacity (CC)',
                        'type' => 'number',
                        'rules' => ['nullable', 'numeric', 'min:0'],
                        'options' => [],
                        'required' => false,
                        'visible' => true,
                        'show_in_list' => true,
                        'show_in_export' => true,
                        'order' => 10,
                    ],
                    [
                        'name' => 'color',
                        'label' => 'Color',
                        'type' => 'text',
                        'rules' => ['nullable', 'string', 'max:100'],
                        'options' => [],
                        'required' => false,
                        'visible' => true,
                        'show_in_list' => true,
                        'show_in_export' => true,
                        'order' => 20,
                    ],
                    [
                        'name' => 'notes',
                        'label' => 'Internal notes',
                        'type' => 'textarea',
                        'rules' => ['nullable', 'string'],
                        'options' => [],
                        'required' => false,
                        'visible' => true,
                        'show_in_list' => false,
                        'show_in_export' => false,
                        'order' => 30,
                    ],
                ],
            ],
        ],
    ],

];
