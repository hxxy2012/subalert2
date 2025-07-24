<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SystemSetting;

class SystemSettingSeeder extends Seeder
{
    /**
     * 运行数据库种子
     *
     * @return void
     */
    public function run()
    {
        $settings = [
            // 基础设置
            [
                'group' => 'general',
                'key' => 'site_name',
                'value' => 'SubAlert',
                'type' => SystemSetting::TYPE_STRING,
                'title' => '网站名称',
                'description' => '系统显示的网站名称',
                'is_public' => true,
                'sort_order' => 1,
            ],
            [
                'group' => 'general',
                'key' => 'site_description',
                'value' => '专业的订阅提醒管理工具',
                'type' => SystemSetting::TYPE_TEXT,
                'title' => '网站描述',
                'description' => '网站的简短描述',
                'is_public' => true,
                'sort_order' => 2,
            ],
            [
                'group' => 'general',
                'key' => 'site_keywords',
                'value' => '订阅管理,提醒服务,SubAlert',
                'type' => SystemSetting::TYPE_STRING,
                'title' => '网站关键词',
                'description' => '网站SEO关键词，用逗号分隔',
                'is_public' => true,
                'sort_order' => 3,
            ],
            [
                'group' => 'general',
                'key' => 'default_timezone',
                'value' => 'Asia/Shanghai',
                'type' => SystemSetting::TYPE_SELECT,
                'title' => '默认时区',
                'description' => '系统默认时区设置',
                'options' => [
                    ['value' => 'Asia/Shanghai', 'label' => '中国标准时间'],
                    ['value' => 'UTC', 'label' => '协调世界时'],
                    ['value' => 'America/New_York', 'label' => '美国东部时间'],
                    ['value' => 'Europe/London', 'label' => '英国时间'],
                ],
                'is_public' => true,
                'sort_order' => 4,
            ],
            [
                'group' => 'general',
                'key' => 'default_language',
                'value' => 'zh_CN',
                'type' => SystemSetting::TYPE_SELECT,
                'title' => '默认语言',
                'description' => '系统默认语言设置',
                'options' => [
                    ['value' => 'zh_CN', 'label' => '简体中文'],
                    ['value' => 'zh_TW', 'label' => '繁体中文'],
                    ['value' => 'en_US', 'label' => 'English'],
                ],
                'is_public' => true,
                'sort_order' => 5,
            ],

            // 用户设置
            [
                'group' => 'user',
                'key' => 'allow_registration',
                'value' => '1',
                'type' => SystemSetting::TYPE_BOOLEAN,
                'title' => '允许用户注册',
                'description' => '是否允许新用户注册',
                'is_public' => true,
                'sort_order' => 1,
            ],
            [
                'group' => 'user',
                'key' => 'require_email_verification',
                'value' => '1',
                'type' => SystemSetting::TYPE_BOOLEAN,
                'title' => '要求邮箱验证',
                'description' => '新用户是否需要验证邮箱',
                'is_public' => false,
                'sort_order' => 2,
            ],
            [
                'group' => 'user',
                'key' => 'max_subscriptions_per_user',
                'value' => '50',
                'type' => SystemSetting::TYPE_NUMBER,
                'title' => '用户最大订阅数',
                'description' => '每个用户最多可创建的订阅数量',
                'is_public' => false,
                'sort_order' => 3,
            ],
            [
                'group' => 'user',
                'key' => 'max_reminders_per_subscription',
                'value' => '10',
                'type' => SystemSetting::TYPE_NUMBER,
                'title' => '订阅最大提醒数',
                'description' => '每个订阅最多可设置的提醒数量',
                'is_public' => false,
                'sort_order' => 4,
            ],

            // 通知设置
            [
                'group' => 'notification',
                'key' => 'email_enabled',
                'value' => '1',
                'type' => SystemSetting::TYPE_BOOLEAN,
                'title' => '启用邮件通知',
                'description' => '是否启用邮件通知功能',
                'is_public' => false,
                'sort_order' => 1,
            ],
            [
                'group' => 'notification',
                'key' => 'feishu_enabled',
                'value' => '0',
                'type' => SystemSetting::TYPE_BOOLEAN,
                'title' => '启用飞书通知',
                'description' => '是否启用飞书通知功能',
                'is_public' => false,
                'sort_order' => 2,
            ],
            [
                'group' => 'notification',
                'key' => 'wechat_enabled',
                'value' => '0',
                'type' => SystemSetting::TYPE_BOOLEAN,
                'title' => '启用企微通知',
                'description' => '是否启用企业微信通知功能',
                'is_public' => false,
                'sort_order' => 3,
            ],
            [
                'group' => 'notification',
                'key' => 'sms_enabled',
                'value' => '0',
                'type' => SystemSetting::TYPE_BOOLEAN,
                'title' => '启用短信通知',
                'description' => '是否启用短信通知功能',
                'is_public' => false,
                'sort_order' => 4,
            ],
            [
                'group' => 'notification',
                'key' => 'default_reminder_advance_days',
                'value' => '7',
                'type' => SystemSetting::TYPE_NUMBER,
                'title' => '默认提醒天数',
                'description' => '创建提醒时的默认提前天数',
                'is_public' => false,
                'sort_order' => 5,
            ],

            // 安全设置
            [
                'group' => 'security',
                'key' => 'password_min_length',
                'value' => '8',
                'type' => SystemSetting::TYPE_NUMBER,
                'title' => '密码最小长度',
                'description' => '用户密码的最小字符数',
                'is_public' => false,
                'sort_order' => 1,
            ],
            [
                'group' => 'security',
                'key' => 'max_login_attempts',
                'value' => '5',
                'type' => SystemSetting::TYPE_NUMBER,
                'title' => '最大登录尝试次数',
                'description' => '账户锁定前的最大失败登录次数',
                'is_public' => false,
                'sort_order' => 2,
            ],
            [
                'group' => 'security',
                'key' => 'lockout_duration',
                'value' => '900',
                'type' => SystemSetting::TYPE_NUMBER,
                'title' => '账户锁定时长',
                'description' => '账户锁定的持续时间（秒）',
                'is_public' => false,
                'sort_order' => 3,
            ],
            [
                'group' => 'security',
                'key' => 'session_timeout',
                'value' => '7200',
                'type' => SystemSetting::TYPE_NUMBER,
                'title' => '会话超时时间',
                'description' => '用户会话的超时时间（秒）',
                'is_public' => false,
                'sort_order' => 4,
            ],

            // 系统设置
            [
                'group' => 'system',
                'key' => 'maintenance_mode',
                'value' => '0',
                'type' => SystemSetting::TYPE_BOOLEAN,
                'title' => '维护模式',
                'description' => '是否启用系统维护模式',
                'is_public' => true,
                'sort_order' => 1,
            ],
            [
                'group' => 'system',
                'key' => 'maintenance_message',
                'value' => '系统正在维护中，请稍后访问',
                'type' => SystemSetting::TYPE_TEXT,
                'title' => '维护提示信息',
                'description' => '维护模式下显示的提示信息',
                'is_public' => true,
                'sort_order' => 2,
            ],
            [
                'group' => 'system',
                'key' => 'debug_mode',
                'value' => '0',
                'type' => SystemSetting::TYPE_BOOLEAN,
                'title' => '调试模式',
                'description' => '是否启用系统调试模式',
                'is_public' => false,
                'sort_order' => 3,
            ],
            [
                'group' => 'system',
                'key' => 'log_retention_days',
                'value' => '30',
                'type' => SystemSetting::TYPE_NUMBER,
                'title' => '日志保留天数',
                'description' => '系统日志文件的保留天数',
                'is_public' => false,
                'sort_order' => 4,
            ],

            // 备份设置
            [
                'group' => 'backup',
                'key' => 'auto_backup_enabled',
                'value' => '1',
                'type' => SystemSetting::TYPE_BOOLEAN,
                'title' => '启用自动备份',
                'description' => '是否启用定时自动备份',
                'is_public' => false,
                'sort_order' => 1,
            ],
            [
                'group' => 'backup',
                'key' => 'backup_frequency',
                'value' => 'daily',
                'type' => SystemSetting::TYPE_SELECT,
                'title' => '备份频率',
                'description' => '自动备份的执行频率',
                'options' => [
                    ['value' => 'hourly', 'label' => '每小时'],
                    ['value' => 'daily', 'label' => '每天'],
                    ['value' => 'weekly', 'label' => '每周'],
                    ['value' => 'monthly', 'label' => '每月'],
                ],
                'is_public' => false,
                'sort_order' => 2,
            ],
            [
                'group' => 'backup',
                'key' => 'backup_retention_days',
                'value' => '7',
                'type' => SystemSetting::TYPE_NUMBER,
                'title' => '备份保留天数',
                'description' => '备份文件的保留天数',
                'is_public' => false,
                'sort_order' => 3,
            ],

            // API设置
            [
                'group' => 'api',
                'key' => 'api_enabled',
                'value' => '1',
                'type' => SystemSetting::TYPE_BOOLEAN,
                'title' => '启用API',
                'description' => '是否启用API接口',
                'is_public' => true,
                'sort_order' => 1,
            ],
            [
                'group' => 'api',
                'key' => 'api_rate_limit',
                'value' => '60',
                'type' => SystemSetting::TYPE_NUMBER,
                'title' => 'API频率限制',
                'description' => '每分钟API请求次数限制',
                'is_public' => false,
                'sort_order' => 2,
            ],
            [
                'group' => 'api',
                'key' => 'api_version',
                'value' => 'v1',
                'type' => SystemSetting::TYPE_STRING,
                'title' => 'API版本',
                'description' => '当前API版本号',
                'is_public' => true,
                'sort_order' => 3,
            ],

            // 文件上传设置
            [
                'group' => 'upload',
                'key' => 'max_file_size',
                'value' => '5242880',
                'type' => SystemSetting::TYPE_NUMBER,
                'title' => '最大文件大小',
                'description' => '允许上传的最大文件大小（字节）',
                'is_public' => false,
                'sort_order' => 1,
            ],
            [
                'group' => 'upload',
                'key' => 'allowed_file_types',
                'value' => 'jpg,jpeg,png,gif,pdf,doc,docx,xls,xlsx',
                'type' => SystemSetting::TYPE_STRING,
                'title' => '允许的文件类型',
                'description' => '允许上传的文件扩展名，用逗号分隔',
                'is_public' => false,
                'sort_order' => 2,
            ],
            [
                'group' => 'upload',
                'key' => 'upload_path',
                'value' => 'uploads',
                'type' => SystemSetting::TYPE_STRING,
                'title' => '上传路径',
                'description' => '文件上传的存储路径',
                'is_public' => false,
                'sort_order' => 3,
            ],
        ];

        foreach ($settings as $setting) {
            SystemSetting::updateOrCreate(
                [
                    'group' => $setting['group'],
                    'key' => $setting['key']
                ],
                $setting
            );
        }

        $this->command->info('系统设置创建完成，共创建 ' . count($settings) . ' 个设置项');
    }
}