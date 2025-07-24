<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\{DB, Hash};

class DatabaseSeeder extends Seeder
{
    /**
     * 填充应用的数据库
     *
     * @return void
     */
    public function run()
    {
        // 禁用外键检查
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        $this->command->info('开始数据填充...');

        // 系统设置
        $this->call(SystemSettingSeeder::class);
        $this->command->info('✓ 系统设置数据填充完成');

        // 订阅类型
        $this->call(SubscriptionTypeSeeder::class);
        $this->command->info('✓ 订阅类型数据填充完成');

        // 管理员用户
        $this->call(AdminUserSeeder::class);
        $this->command->info('✓ 管理员用户数据填充完成');

        // 根据环境决定是否填充测试数据
        if (app()->environment(['local', 'testing'])) {
            $this->call([
                UserSeeder::class,
                SubscriptionSeeder::class,
                ReminderSeeder::class,
            ]);
            $this->command->info('✓ 测试数据填充完成');
        }

        // 重新启用外键检查
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $this->command->info('所有数据填充完成！');
    }
}