<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\AdminUser;
use Illuminate\Support\Str;

class AdminUserSeeder extends Seeder
{
    /**
     * 运行数据库种子
     *
     * @return void
     */
    public function run()
    {
        // 创建超级管理员
        AdminUser::updateOrCreate(
            ['email' => 'admin@subalert.com'],
            [
                'uuid' => (string) Str::uuid(),
                'username' => 'admin',
                'password' => Hash::make('123456'),
                'real_name' => '超级管理员',
                'phone' => '13800138000',
                'status' => AdminUser::STATUS_ACTIVE,
                'is_super' => 1,
                'department' => '技术部',
                'position' => '系统管理员',
                'remarks' => '系统默认超级管理员账户',
            ]
        );

        // 创建普通管理员
        AdminUser::updateOrCreate(
            ['email' => 'manager@subalert.com'],
            [
                'uuid' => (string) Str::uuid(),
                'username' => 'manager',
                'password' => Hash::make('123456'),
                'real_name' => '运营管理员',
                'phone' => '13800138001',
                'status' => AdminUser::STATUS_ACTIVE,
                'is_super' => 0,
                'department' => '运营部',
                'position' => '运营经理',
                'permissions' => [
                    'users.view',
                    'users.create',
                    'users.edit',
                    'subscriptions.view',
                    'subscriptions.edit',
                    'reminders.view',
                    'statistics.view',
                ],
                'remarks' => '运营管理员账户',
            ]
        );

        // 创建客服管理员
        AdminUser::updateOrCreate(
            ['email' => 'support@subalert.com'],
            [
                'uuid' => (string) Str::uuid(),
                'username' => 'support',
                'password' => Hash::make('123456'),
                'real_name' => '客服管理员',
                'phone' => '13800138002',
                'status' => AdminUser::STATUS_ACTIVE,
                'is_super' => 0,
                'department' => '客服部',
                'position' => '客服专员',
                'permissions' => [
                    'users.view',
                    'subscriptions.view',
                    'reminders.view',
                    'reminder-logs.view',
                ],
                'remarks' => '客服管理员账户，只有查看权限',
            ]
        );

        $this->command->info('管理员用户创建完成:');
        $this->command->info('超级管理员: admin@subalert.com / 123456');
        $this->command->info('运营管理员: manager@subalert.com / 123456');
        $this->command->info('客服管理员: support@subalert.com / 123456');
    }
}