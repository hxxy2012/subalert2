<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * 订阅类型模型
 * 
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property string|null $icon
 * @property string $color
 * @property array|null $fields
 * @property int $sort_order
 * @property int $status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class SubscriptionType extends Model
{
    use HasFactory, LogsActivity;

    /**
     * 可批量赋值的属性
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'slug',
        'description',
        'icon',
        'color',
        'fields',
        'sort_order',
        'status',
    ];

    /**
     * 属性类型转换
     *
     * @var array<string, string>
     */
    protected $casts = [
        'fields' => 'array',
        'sort_order' => 'integer',
        'status' => 'integer',
    ];

    /**
     * 活动日志配置
     */
    protected static $logAttributes = [
        'name', 'slug', 'status'
    ];
    protected static $logOnlyDirty = true;
    protected static $logName = 'subscription_type';

    /**
     * 状态常量
     */
    const STATUS_ENABLED = 1;
    const STATUS_DISABLED = 2;

    /**
     * 订阅关联
     */
    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }

    /**
     * 活跃订阅关联
     */
    public function activeSubscriptions()
    {
        return $this->subscriptions()->where('status', Subscription::STATUS_ACTIVE);
    }

    /**
     * 检查类型是否启用
     */
    public function isEnabled()
    {
        return $this->status === self::STATUS_ENABLED;
    }

    /**
     * 检查类型是否禁用
     */
    public function isDisabled()
    {
        return $this->status === self::STATUS_DISABLED;
    }

    /**
     * 获取状态文本
     */
    public function getStatusTextAttribute()
    {
        return match ($this->status) {
            self::STATUS_ENABLED => '启用',
            self::STATUS_DISABLED => '禁用',
            default => '未知',
        };
    }

    /**
     * 获取图标URL
     */
    public function getIconUrlAttribute()
    {
        if ($this->icon) {
            return \Storage::url($this->icon);
        }
        
        return asset('images/default-type-icon.png');
    }

    /**
     * 获取自定义字段配置
     */
    public function getCustomFields()
    {
        return $this->fields ?? [];
    }

    /**
     * 获取字段配置
     */
    public function getFieldConfig($fieldName)
    {
        $fields = $this->getCustomFields();
        return collect($fields)->firstWhere('name', $fieldName);
    }

    /**
     * 验证自定义字段数据
     */
    public function validateCustomData($data)
    {
        $fields = $this->getCustomFields();
        $errors = [];

        foreach ($fields as $field) {
            $name = $field['name'];
            $required = $field['required'] ?? false;
            $type = $field['type'] ?? 'text';

            if ($required && (!isset($data[$name]) || empty($data[$name]))) {
                $errors[$name] = "{$field['label']}不能为空";
                continue;
            }

            if (isset($data[$name]) && !empty($data[$name])) {
                $value = $data[$name];
                
                switch ($type) {
                    case 'email':
                        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                            $errors[$name] = "{$field['label']}格式不正确";
                        }
                        break;
                    case 'url':
                        if (!filter_var($value, FILTER_VALIDATE_URL)) {
                            $errors[$name] = "{$field['label']}格式不正确";
                        }
                        break;
                    case 'number':
                        if (!is_numeric($value)) {
                            $errors[$name] = "{$field['label']}必须是数字";
                        }
                        break;
                    case 'date':
                        if (!strtotime($value)) {
                            $errors[$name] = "{$field['label']}格式不正确";
                        }
                        break;
                }
            }
        }

        return $errors;
    }

    /**
     * 按状态过滤
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * 启用的类型
     */
    public function scopeEnabled($query)
    {
        return $query->where('status', self::STATUS_ENABLED);
    }

    /**
     * 按排序
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    /**
     * 搜索类型
     */
    public function scopeSearch($query, $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('name', 'like', "%{$term}%")
              ->orWhere('slug', 'like', "%{$term}%")
              ->orWhere('description', 'like', "%{$term}%");
        });
    }

    /**
     * 统计订阅数量
     */
    public function getSubscriptionsCountAttribute()
    {
        return $this->subscriptions()->count();
    }

    /**
     * 统计活跃订阅数量
     */
    public function getActiveSubscriptionsCountAttribute()
    {
        return $this->activeSubscriptions()->count();
    }
}