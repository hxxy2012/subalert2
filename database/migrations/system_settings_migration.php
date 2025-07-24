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
        Schema::create('system_settings', function (Blueprint $table) {
            $table->id();
            $table->string('group', 50)->comment('设置分组');
            $table->string('key', 100)->comment('设置键');
            $table->text('value')->nullable()->comment('设置值');
            $table->string('type', 20)->default('string')->comment('数据类型');
            $table->string('title', 200)->comment('设置标题');
            $table->text('description')->nullable()->comment('设置描述');
            $table->json('options')->nullable()->comment('选项配置');
            $table->tinyInteger('is_public')->default(0)->comment('是否公开：0-否，1-是');
            $table->integer('sort_order')->default(0)->comment('排序');
            $table->timestamps();

            // 索引
            $table->unique(['group', 'key'], 'uk_group_key');
            $table->index('group', 'idx_group');
            $table->index('is_public', 'idx_public');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('system_settings');
    }
};