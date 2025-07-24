<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('uuid', 36)->unique()->comment('用户唯一标识');
            $table->string('username', 50)->unique()->comment('用户名');
            $table->string('email')->unique()->comment('邮箱地址');
            $table->timestamp('email_verified_at')->nullable()->comment('邮箱验证时间');
            $table->string('password')->comment('密码');
            $table->string('real_name', 100)->nullable()->comment('真实姓名');
            $table->string('phone', 20)->nullable()->comment('手机号码');
            $table->string('avatar')->nullable()->comment('头像地址');
            $table->tinyInteger('gender')->default(0)->comment('性别：0-未知，1-男，2-女');
            $table->date('birthday')->nullable()->comment('生日');
            $table->string('timezone', 50)->default('Asia/Shanghai')->comment('时区');
            $table->string('language', 10)->default('zh_CN')->comment('语言');
            $table->json('notification_settings')->nullable()->comment('通知设置');
            $table->tinyInteger('status')->default(1)->comment('状态：1-正常，2-禁用');
            $table->timestamp('last_login_at')->nullable()->comment('最后登录时间');
            $table->string('last_login_ip', 45)->nullable()->comment('最后登录IP');
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();

            // 索引
            $table->index(['status', 'created_at'], 'idx_status_created');
            $table->index('last_login_at', 'idx_last_login');
            $table->index('phone', 'idx_phone');
            $table->index('deleted_at', 'idx_deleted_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('users');
    }
};