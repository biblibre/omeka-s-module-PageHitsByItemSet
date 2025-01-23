<?php

namespace PageHitsByItemSet;

return [
    'controllers' => [
        'factories' => [
            'PageHitsByItemSet\Controller\Admin\Index' => Service\Controller\Admin\IndexControllerFactory::class,
        ],
    ],
    'entity_manager' => [
        'mapping_classes_paths' => [
            dirname(__DIR__) . '/src/Entity',
        ],
    ],
    'listeners' => [
        'PageHitsByItemSet\MvcListeners',
    ],
    'navigation' => [
        'AdminModule' => [
            [
                'label' => 'Page hits by item set', // @translate
                'class' => 'o-icon-chart-bar fa-chart-bar',
                'route' => 'admin/page-hits-by-item-set',
                'resource' => 'PageHitsByItemSet\Controller\Admin\Index',
                'privilege' => 'browse',
            ],
        ],
    ],
    'router' => [
        'routes' => [
            'admin' => [
                'child_routes' => [
                    'page-hits-by-item-set' => [
                        'type' => \Laminas\Router\Http\Segment::class,
                        'options' => [
                            'route' => '/page-hits-by-item-set',
                            'defaults' => [
                                '__NAMESPACE__' => 'PageHitsByItemSet\Controller\Admin',
                                'controller' => 'Index',
                                'action' => 'browse',
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ],
    'service_manager' => [
        'factories' => [
            'PageHitsByItemSet\MvcListeners' => Service\Mvc\MvcListenersFactory::class,
        ]
    ],
    'translator' => [
        'translation_file_patterns' => [
            [
                'type' => 'gettext',
                'base_dir' => dirname(__DIR__) . '/language',
                'pattern' => '%s.mo',
                'text_domain' => null,
            ],
        ],
    ],
    'view_manager' => [
        'template_path_stack' => [
            dirname(__DIR__) . '/view',
        ],
    ],
];
