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
        Schema::create('reminders', function (Blueprint $table) {
            $table->id();
            $table->string('uuid', 36)->unique()->comment('提醒唯一标识');
            $table->foreignId('subscription_id')->constrained()->onDelete('cascade')->comment('订阅ID');
            $table->string('type', 20)->comment('提醒类型：billing-计费提醒，expiry-到期提醒，custom-自定义');
            $table->string('name', 100)->comment('提醒名称');
            $table->integer('advance_days')->comment('提前天数');
            $table->json('channels')->comment('通知渠道');
            $table->time('reminder_time')->default('09:00:00')->comment('提醒时间');
            $table->json('recipient_config')->nullable()->comment('收件人配置');
            $table->json('template_config')->nullable()->comment('模板配置');
            $table->tinyInteger('status')->default(1)->comment('状态：1-启用，2-禁用');
            $table->tinyInteger('repeat_enabled')->default(0)->comment('重复提醒：0-否，1-是');
            $table->json('repeat_config')->nullable()->comment('重复配置');
            $table->timestamp('last_sent_at')->nullable()->comment('最后发送时间');
            $table->timestamp('next_send_at')->nullable()->comment('下次发送时间');
            $table->timestamps();

            // 索引
            $table->index(['subscription_id', 'status'], 'idx_subscription_status');
            $table->index(['status', 'next_send_at'], 'idx_status_next_send');
            $table->index('next_send_at', 'idx_next_send');
            $table->index('type', 'idx_type');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('reminders');
    }
};