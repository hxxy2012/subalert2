<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SubscriptionType;

class SubscriptionTypeSeeder extends Seeder
{
    /**
     * 运行数据库种子
     *
     * @return void
     */
    public function run()
    {
        $types = [
            [
                'name' => '软件服务',
                'slug' => 'software',
                'description' => '各类软件和SaaS服务订阅',
                'icon' => 'fas fa-laptop-code',
                'color' => '#3B82F6',
                'fields' => [
                    [
                        'name' => 'license_key',
                        'label' => '许可证密钥',
                        'type' => 'text',
                        'required' => false,
                        'placeholder' => '请输入软件许可证密钥',
                    ],
                    [
                        'name' => 'version',
                        'label' => '软件版本',
                        'type' => 'text',
                        'required' => false,
                        'placeholder' => '如：v2.1.0',
                    ],
                    [
                        'name' => 'support_email',
                        'label' => '技术支持邮箱',
                        'type' => 'email',
                        'required' => false,
                        'placeholder' => 'support@example.com',
                    ],
                ],
                'sort_order' => 1,
                'status' => SubscriptionType::STATUS_ENABLED,
            ],
            [
                'name' => '云服务',
                'slug' => 'cloud',
                'description' => '云计算、云存储等云服务订阅',
                'icon' => 'fas fa-cloud',
                'color' => '#10B981',
                'fields' => [
                    [
                        'name' => 'instance_id',
                        'label' => '实例ID',
                        'type' => 'text',
                        'required' => false,
                        'placeholder' => '云服务实例标识',
                    ],
                    [
                        'name' => 'region',
                        'label' => '服务区域',
                        'type' => 'select',
                        'required' => false,
                        'options' => [
                            ['value' => 'us-east-1', 'label' => '美国东部'],
                            ['value' => 'us-west-2', 'label' => '美国西部'],
                            ['value' => 'ap-southeast-1', 'label' => '亚太东南'],
                            ['value' => 'cn-north-1', 'label' => '中国北方'],
                        ],
                    ],
                    [
                        'name' => 'storage_size',
                        'label' => '存储容量',
                        'type' => 'text',
                        'required' => false,
                        'placeholder' => '如：100GB',
                    ],
                ],
                'sort_order' => 2,
                'status' => SubscriptionType::STATUS_ENABLED,
            ],
            [
                'name' => '域名主机',
                'slug' => 'hosting',
                'description' => '域名注册、主机托管等服务',
                'icon' => 'fas fa-server',
                'color' => '#F59E0B',
                'fields' => [
                    [
                        'name' => 'domain_name',
                        'label' => '域名',
                        'type' => 'text',
                        'required' => false,
                        'placeholder' => 'example.com',
                    ],
                    [
                        'name' => 'dns_provider',
                        'label' => 'DNS服务商',
                        'type' => 'text',
                        'required' => false,
                        'placeholder' => '如：Cloudflare',
                    ],
                    [
                        'name' => 'hosting_provider',
                        'label' => '主机服务商',
                        'type' => 'text',
                        'required' => false,
                        'placeholder' => '如：阿里云',
                    ],
                ],
                'sort_order' => 3,
                'status' => SubscriptionType::STATUS_ENABLED,
            ],
            [
                'name' => '媒体娱乐',
                'slug' => 'media',
                'description' => '视频、音乐、游戏等娱乐服务',
                'icon' => 'fas fa-play-circle',
                'color' => '#EF4444',
                'fields' => [
                    [
                        'name' => 'account_email',
                        'label' => '账户邮箱',
                        'type' => 'email',
                        'required' => false,
                        'placeholder' => '绑定的邮箱地址',
                    ],
                    [
                        'name' => 'device_limit',
                        'label' => '设备数量限制',
                        'type' => 'number',
                        'required' => false,
                        'placeholder' => '允许同时使用的设备数',
                    ],
                    [
                        'name' => 'quality',
                        'label' => '服务质量',
                        'type' => 'select',
                        'required' => false,
                        'options' => [
                            ['value' => 'hd', 'label' => '高清'],
                            ['value' => '4k', 'label' => '4K'],
                            ['value' => 'premium', 'label' => '高级版'],
                        ],
                    ],
                ],
                'sort_order' => 4,
                'status' => SubscriptionType::STATUS_ENABLED,
            ],
            [
                'name' => '教育学习',
                'slug' => 'education',
                'description' => '在线课程、培训等教育服务',
                'icon' => 'fas fa-graduation-cap',
                'color' => '#8B5CF6',
                'fields' => [
                    [
                        'name' => 'course_name',
                        'label' => '课程名称',
                        'type' => 'text',
                        'required' => false,
                        'placeholder' => '课程或培训名称',
                    ],
                    [
                        'name' => 'instructor',
                        'label' => '讲师',
                        'type' => 'text',
                        'required' => false,
                        'placeholder' => '主讲老师',
                    ],
                    [
                        'name' => 'certification',
                        'label' => '认证证书',
                        'type' => 'select',
                        'required' => false,
                        'options' => [
                            ['value' => 'yes', 'label' => '包含证书'],
                            ['value' => 'no', 'label' => '无证书'],
                        ],
                    ],
                ],
                'sort_order' => 5,
                'status' => SubscriptionType::STATUS_ENABLED,
            ],
            [
                'name' => '办公工具',
                'slug' => 'office',
                'description' => '办公软件、协作工具等',
                'icon' => 'fas fa-briefcase',
                'color' => '#06B6D4',
                'fields' => [
                    [
                        'name' => 'user_count',
                        'label' => '用户数量',
                        'type' => 'number',
                        'required' => false,
                        'placeholder' => '许可用户数',
                    ],
                    [
                        'name' => 'features',
                        'label' => '功能版本',
                        'type' => 'select',
                        'required' => false,
                        'options' => [
                            ['value' => 'basic', 'label' => '基础版'],
                            ['value' => 'standard', 'label' => '标准版'],
                            ['value' => 'premium', 'label' => '高级版'],
                            ['value' => 'enterprise', 'label' => '企业版'],
                        ],
                    ],
                ],
                'sort_order' => 6,
                'status' => SubscriptionType::STATUS_ENABLED,
            ],
            [
                'name' => '其他服务',
                'slug' => 'others',
                'description' => '其他类型的订阅服务',
                'icon' => 'fas fa-ellipsis-h',
                'color' => '#6B7280',
                'fields' => [
                    [
                        'name' => 'service_category',
                        'label' => '服务分类',
                        'type' => 'text',
                        'required' => false,
                        'placeholder' => '请描述服务类型',
                    ],
                    [
                        'name' => 'custom_field_1',
                        'label' => '自定义字段1',
                        'type' => 'text',
                        'required' => false,
                        'placeholder' => '可自定义使用',
                    ],
                    [
                        'name' => 'custom_field_2',
                        'label' => '自定义字段2',
                        'type' => 'text',
                        'required' => false,
                        'placeholder' => '可自定义使用',
                    ],
                ],
                'sort_order' => 99,
                'status' => SubscriptionType::STATUS_ENABLED,
            ],
        ];

        foreach ($types as $type) {
            SubscriptionType::updateOrCreate(
                ['slug' => $type['slug']],
                $type
            );
        }

        $this->command->info('订阅类型创建完成，共创建 ' . count($types) . ' 个类型');
    }
}