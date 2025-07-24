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
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->string('uuid', 36)->unique()->comment('订阅唯一标识');
            $table->foreignId('user_id')->constrained()->onDelete('cascade')->comment('用户ID');
            $table->foreignId('subscription_type_id')->constrained()->comment('订阅类型ID');
            $table->string('service_name', 200)->comment('服务名称');
            $table->text('description')->nullable()->comment('描述');
            $table->string('website_url')->nullable()->comment('官网地址');
            $table->string('logo')->nullable()->comment('服务Logo');
            
            // 费用信息
            $table->decimal('price', 10, 2)->default(0)->comment('价格');
            $table->string('currency', 3)->default('CNY')->comment('货币');
            $table->string('billing_cycle', 20)->comment('计费周期');
            
            // 时间信息
            $table->date('start_date')->comment('开始日期');
            $table->date('next_billing_date')->comment('下次计费日期');
            $table->date('end_date')->nullable()->comment('结束日期');
            $table->integer('auto_renew_days')->nullable()->comment('自动续费天数');
            
            // 状态信息
            $table->tinyInteger('status')->default(1)->comment('状态：1-活跃，2-过期，3-取消，4-暂停');
            $table->tinyInteger('auto_renew')->default(0)->comment('自动续费：0-否，1-是');
            $table->tinyInteger('reminder_enabled')->default(1)->comment('提醒开启：0-否，1-是');
            
            // 扩展信息
            $table->json('custom_fields')->nullable()->comment('自定义字段');
            $table->json('tags')->nullable()->comment('标签');
            $table->text('notes')->nullable()->comment('备注');
            
            $table->timestamps();
            $table->softDeletes();

            // 索引
            $table->index(['user_id', 'status'], 'idx_user_status');
            $table->index(['status', 'next_billing_date'], 'idx_status_billing');
            $table->index('next_billing_date', 'idx_next_billing');
            $table->index('end_date', 'idx_end_date');
            $table->index('subscription_type_id', 'idx_type');
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
        Schema::dropIfExists('subscriptions');
    }
};