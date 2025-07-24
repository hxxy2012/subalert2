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
        Schema::create('subscription_types', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->comment('类型名称');
            $table->string('slug', 100)->unique()->comment('标识符');
            $table->text('description')->nullable()->comment('描述');
            $table->string('icon')->nullable()->comment('图标');
            $table->string('color', 7)->default('#3B82F6')->comment('主题色');
            $table->json('fields')->nullable()->comment('自定义字段配置');
            $table->integer('sort_order')->default(0)->comment('排序');
            $table->tinyInteger('status')->default(1)->comment('状态：1-启用，2-禁用');
            $table->timestamps();

            // 索引
            $table->index(['status', 'sort_order'], 'idx_status_sort');
            $table->index('slug', 'idx_slug');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('subscription_types');
    }
};