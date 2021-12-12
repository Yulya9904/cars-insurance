<?php

/**
 * @see       https://github.com/laminas/laminas-mvc-skeleton for the canonical source repository
 * @copyright https://github.com/laminas/laminas-mvc-skeleton/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-mvc-skeleton/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Insurance;

use Insurance\Factories;
use Insurance\Controller;
use Laminas\Router\Http\Literal;
use Laminas\Router\Http\Segment;


return [
    'service_manager' => [
        'factories' => [],
    ],
    'router' => [
        'routes' => [
            'insurances' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/insurances[/:car_id]',
                    'constraints' => [
                        'id' => '[0-9]+'
                    ],
                    'defaults' => [
                        'controller' => Controller\InsuranceController::class,
                        'action' => 'index'
                    ]
                ],
            ],
            'insurance' => [
                'type' => Literal::class,
                'options' => [
                    'route' => '/insurance',
                ],
                'may_terminate' => false,
                'child_routes' => [
                    'view' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '/view[/:id]',
                            'constraints' => [
                                'id' => '[0-9]+'
                            ],
                            'defaults' => [
                                'controller' => Controller\InsuranceController::class,
                                'action' => 'view'
                            ]
                        ]
                    ],
                    'change' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '/change[/:id]',
                            'constraints' => [
                                'id' => '[0-9]+'
                            ],
                            'defaults' => [
                                'controller' => Controller\InsuranceController::class,
                                'action' => 'change'
                            ]
                        ]
                    ],
                    'delete' => [
                        'type' => Segment::class,
                        'options' => [
                            'route' => '/delete[/:id]',
                            'constraints' => [
                                'id' => '[0-9]+'
                            ],
                            'defaults' => [
                                'controller' => Controller\InsuranceController::class,
                                'action' => 'delete'
                            ]
                        ]
                    ],
                ],
            ],
        ],
    ],
    'view_manager' => [
        'template_path_stack' => [
            __DIR__ . '/../view',
        ],
    ],
    'controllers' => [
        'factories' => [
            Controller\InsuranceController::class  => Factories\InsuranceControllerFactory::class,
        ],
    ],
];
