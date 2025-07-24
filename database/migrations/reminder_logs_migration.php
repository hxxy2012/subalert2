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
        Schema::create('reminder_logs', function (Blueprint $table) {
            $table->id();
            $table->string('uuid', 36)->unique()->comment('日志唯一标识');
            $table->foreignId('reminder_id')->constrained()->onDelete('cascade')->comment('提醒ID');
            $table->foreignId('subscription_id')->constrained()->onDelete('cascade')->comment('订阅ID');
            $table->string('channel', 20)->comment('发送渠道');
            $table->string('recipient')->comment('收件人');
            $table->string('subject')->nullable()->comment('主题');
            $table->text('content')->nullable()->comment('内容');
            $table->tinyInteger('status')->comment('状态：1-成功，2-失败，3-处理中');
            $table->text('error_message')->nullable()->comment('错误信息');
            $table->json('response_data')->nullable()->comment('响应数据');
            $table->timestamp('sent_at')->nullable()->comment('发送时间');
            $table->integer('retry_count')->default(0)->comment('重试次数');
            $table->timestamp('next_retry_at')->nullable()->comment('下次重试时间');
            $table->timestamps();

            // 索引
            $table->index(['reminder_id', 'status'], 'idx_reminder_status');
            $table->index(['subscription_id', 'sent_at'], 'idx_subscription_sent');
            $table->index(['status', 'next_retry_at'], 'idx_status_retry');
            $table->index('sent_at', 'idx_sent_at');
            $table->index('channel', 'idx_channel');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('reminder_logs');
    }
};