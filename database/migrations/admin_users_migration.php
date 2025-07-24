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
        Schema::create('admin_users', function (Blueprint $table) {
            $table->id();
            $table->string('uuid', 36)->unique()->comment('管理员唯一标识');
            $table->string('username', 50)->unique()->comment('用户名');
            $table->string('email')->unique()->comment('邮箱地址');
            $table->string('password')->comment('密码');
            $table->string('real_name', 100)->comment('真实姓名');
            $table->string('phone', 20)->nullable()->comment('手机号码');
            $table->string('avatar')->nullable()->comment('头像地址');
            $table->json('permissions')->nullable()->comment('权限配置');
            $table->tinyInteger('status')->default(1)->comment('状态：1-正常，2-禁用');
            $table->tinyInteger('is_super')->default(0)->comment('是否超级管理员：0-否，1-是');
            $table->string('department', 100)->nullable()->comment('部门');
            $table->string('position', 100)->nullable()->comment('职位');
            $table->timestamp('last_login_at')->nullable()->comment('最后登录时间');
            $table->string('last_login_ip', 45)->nullable()->comment('最后登录IP');
            $table->text('remarks')->nullable()->comment('备注');
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();

            // 索引
            $table->index(['status', 'is_super'], 'idx_status_super');
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
        Schema::dropIfExists('admin_users');
    }
};