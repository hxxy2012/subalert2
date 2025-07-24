<?php

return [
    /*
    |--------------------------------------------------------------------------
    | SubAlert 系统配置
    |--------------------------------------------------------------------------
    |
    | 这里包含了SubAlert订阅提醒管理系统的所有自定义配置项
    |
    */

    // 系统基础配置
    'app' => [
        'name' => env('APP_NAME', 'SubAlert'),
        'version' => '1.0.0',
        'timezone' => env('SUBALERT_DEFAULT_TIMEZONE', 'Asia/Shanghai'),
        'language' => env('SUBALERT_DEFAULT_LANGUAGE', 'zh_CN'),
        'admin_prefix' => env('SUBALERT_ADMIN_PREFIX', 'admin'),
        'api_prefix' => env('SUBALERT_API_PREFIX', 'api/v1'),
    ],

    // 提醒配置
    'reminder' => [
        'max_retries' => env('REMINDER_MAX_RETRIES', 3),
        'retry_delay' => env('REMINDER_RETRY_DELAY', 300), // 秒
        'batch_size' => env('REMINDER_BATCH_SIZE', 100),
        'advance_days' => [1, 3, 7, 15, 30], // 提前提醒天数选项
        'channels' => [
            'email' => [
                'enabled' => true,
                'driver' => 'mail',
                'name' => '邮件通知',
            ],
            'feishu' => [
                'enabled' => !empty(env('FEISHU_WEBHOOK_URL')),
                'driver' => 'feishu',
                'name' => '飞书通知',
                'webhook_url' => env('FEISHU_WEBHOOK_URL'),
                'secret' => env('FEISHU_SECRET'),
                'app_id' => env('FEISHU_APP_ID'),
                'app_secret' => env('FEISHU_APP_SECRET'),
            ],
            'wechat' => [
                'enabled' => !empty(env('WECHAT_CORP_ID')),
                'driver' => 'wechat',
                'name' => '企业微信通知',
                'corp_id' => env('WECHAT_CORP_ID'),
                'agent_id' => env('WECHAT_AGENT_ID'),
                'secret' => env('WECHAT_SECRET'),
                'webhook_url' => env('WECHAT_WEBHOOK_URL'),
            ],
            'sms' => [
                'enabled' => !empty(env('SMS_ACCESS_KEY_ID')),
                'driver' => 'sms',
                'name' => '短信通知',
                'access_key_id' => env('SMS_ACCESS_KEY_ID'),
                'access_key_secret' => env('SMS_ACCESS_KEY_SECRET'),
                'sign_name' => env('SMS_SIGN_NAME'),
                'template_code' => env('SMS_TEMPLATE_CODE'),
            ],
        ],
    ],

    // 订阅配置
    'subscription' => [
        'status' => [
            'active' => 1,
            'expired' => 2,
            'cancelled' => 3,
            'suspended' => 4,
        ],
        'billing_cycles' => [
            'monthly' => '月付',
            'quarterly' => '季付',
            'semi_annually' => '半年付',
            'annually' => '年付',
            'lifetime' => '终身',
        ],
        'currencies' => [
            'CNY' => '人民币',
            'USD' => '美元',
            'EUR' => '欧元',
        ],
    ],

    // 系统限制
    'limits' => [
        'user' => [
            'max_subscriptions' => 50, // 每用户最大订阅数
            'max_reminders_per_subscription' => 10, // 每订阅最大提醒数
        ],
        'file' => [
            'max_size' => 5 * 1024 * 1024, // 5MB
            'allowed_types' => ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx'],
        ],
    ],

    // 安全配置
    'security' => [
        'password_min_length' => 8,
        'session_timeout' => 7200, // 2小时
        'max_login_attempts' => 5,
        'lockout_duration' => 900, // 15分钟
    ],

    // 日志配置
    'logging' => [
        'channels' => [
            'reminder' => 'daily',
            'notification' => 'daily',
            'subscription' => 'daily',
            'security' => 'daily',
        ],
        'retention_days' => 30,
    ],

    // 缓存配置
    'cache' => [
        'ttl' => [
            'user_subscriptions' => 1800, // 30分钟
            'system_settings' => 3600, // 1小时
            'subscription_types' => 7200, // 2小时
        ],
        'keys' => [
            'user_subscriptions' => 'user_subscriptions_%s',
            'system_settings' => 'system_settings',
            'subscription_types' => 'subscription_types',
        ],
    ],

    // 队列配置
    'queue' => [
        'reminder' => 'reminder',
        'notification' => 'notification',
        'export' => 'export',
        'import' => 'import',
    ],

    // 导出配置
    'export' => [
        'max_rows' => 10000,
        'chunk_size' => 1000,
        'formats' => ['xlsx', 'csv', 'pdf'],
    ],

    // 统计配置
    'statistics' => [
        'cache_duration' => 3600, // 1小时
        'chart_colors' => [
            '#3B82F6', '#EF4444', '#10B981', '#F59E0B',
            '#8B5CF6', '#F97316', '#06B6D4', '#84CC16'
        ],
    ],

    // API配置
    'api' => [
        'rate_limit' => [
            'default' => '60,1', // 每分钟60次
            'auth' => '1000,1', // 认证用户每分钟1000次
        ],
        'version' => 'v1',
        'pagination' => [
            'default_per_page' => 15,
            'max_per_page' => 100,
        ],
    ],

    // 第三方集成
    'integrations' => [
        'calendar' => [
            'google' => [
                'enabled' => false,
                'client_id' => env('GOOGLE_CLIENT_ID'),
                'client_secret' => env('GOOGLE_CLIENT_SECRET'),
            ],
            'outlook' => [
                'enabled' => false,
                'client_id' => env('OUTLOOK_CLIENT_ID'),
                'client_secret' => env('OUTLOOK_CLIENT_SECRET'),
            ],
        ],
    ],
];