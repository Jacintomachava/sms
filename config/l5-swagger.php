<?php

return [

    'default' => 'default',

    'documentations' => [
        'default' => [
            'api' => [
                'title' => 'Temtalento API Documentation',
            ],

            'routes' => [
                'api' => 'api/documentation', // rota para Swagger UI
            ],

            'paths' => [
                'use_absolute_path' => true, // usar URLs absolutas

                'docs_json' => 'api-docs.json', // arquivo JSON
                'docs_yaml' => 'api-docs.yaml', // arquivo YAML

                'format_to_use_for_docs' => 'json', // formato padrão

                'annotations' => [
                    base_path('app'), // diretório das annotations
                ],
            ],
        ],
    ],

    'defaults' => [
        'routes' => [
            'docs' => 'docs',
            'oauth2_callback' => 'api/oauth2-callback',
            'middleware' => [
                'api' => [],
                'asset' => [],
                'docs' => [],
                'oauth2_callback' => [],
            ],
            'group_options' => [],
        ],

        'paths' => [
            'docs' => storage_path('api-docs'),
            'views' => base_path('resources/views/vendor/l5-swagger'),
            'base' => env('L5_SWAGGER_BASE_PATH', null),
            'swagger_ui_assets_path' => env('L5_SWAGGER_UI_ASSETS_PATH', 'vendor/swagger-api/swagger-ui/dist/'),
            'excludes' => [],
        ],

        'scanOptions' => [
            'analyser' => null,
            'analysis' => null,
            'processors' => [],
            'pattern' => null,
            'exclude' => [],
            'open_api_spec_version' => env('L5_SWAGGER_OPEN_API_SPEC_VERSION', \L5Swagger\Generator::OPEN_API_DEFAULT_SPEC_VERSION),
        ],

        'securityDefinitions' => [
            'securitySchemes' => [],
            'security' => [],
        ],

        // ⚡ Geração automática de docs a cada request
        'generate_always' => true,

        // gerar cópia YAML
        'generate_yaml_copy' => true,

        'proxy' => false,
        'additional_config_url' => null,
        'operations_sort' => null,
        'validator_url' => null,

        'ui' => [
            'display' => [
                'doc_expansion' => 'none',
                'filter' => true,
            ],

            'authorization' => [
                'persist_authorization' => true,
                'oauth2' => [
                    'use_pkce_with_authorization_code_grant' => false,
                ],
            ],
        ],

        'constants' => [
            'L5_SWAGGER_CONST_HOST' => env('L5_SWAGGER_CONST_HOST', 'http://127.0.0.1:8000'),
        ],
    ],
];
