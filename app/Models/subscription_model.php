<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Support\Str;
use Carbon\Carbon;

/**
 * 订阅模型
 * 
 * @property int $id
 * @property string $uuid
 * @property int $user_id
 * @property int $subscription_type_id
 * @property string $service_name
 * @property string|null $description
 * @property string|null $website_url
 * @property string|null $logo
 * @property float $price
 * @property string $currency
 * @property string $billing_cycle
 * @property \Carbon\Carbon $start_date
 * @property \Carbon\Carbon $next_billing_date
 * @property \Carbon\Carbon|null $end_date
 * @property int|null $auto_renew_days
 * @property int $status
 * @property int $auto_renew
 * @property int $reminder_enabled
 * @property array|null $custom_fields
 * @property array|null $tags
 * @property string|null $notes
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 */
class Subscription extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    /**
     * 可批量赋值的属性
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'uuid',
        'user_id',
        'subscription_type_id',
        'service_name',
        'description',
        'website_url',
        'logo',
        'price',
        'currency',
        'billing_cycle',
        'start_date',
        'next_billing_date',
        'end_date',
        'auto_renew_days',
        'status',
        'auto_renew',
        'reminder_enabled',
        'custom_fields',
        'tags',
        'notes',
    ];

    /**
     * 属性类型转换
     *
     * @var array<string, string>
     */
    protected $casts = [
        'price' => 'decimal:2',
        'start_date' => 'date',
        'next_billing_date' => 'date',
        'end_date' => 'date',
        'status' => 'integer',
        'auto_renew' => 'boolean',
        'reminder_enabled' => 'boolean',
        'custom_fields' => 'array',
        'tags' => 'array',
        'auto_renew_days' => 'integer',
    ];

    /**
     * 日期字段
     *
     * @var array
     */
    protected $dates = [
        'deleted_at',
        'start_date',
        'next_billing_date',
        'end_date',
    ];

    /**
     * 活动日志配置
     */
    protected static $logAttributes = [
        'service_name', 'price', 'status', 'next_billing_date'
    ];
    protected static $logOnlyDirty = true;
    protected static $logName = 'subscription';

    /**
     * 状态常量
     */
    const STATUS_ACTIVE = 1;
    const STATUS_EXPIRED = 2;
    const STATUS_CANCELLED = 3;
    const STATUS_SUSPENDED = 4;

    /**
     * 计费周期常量
     */
    const BILLING_MONTHLY = 'monthly';
    const BILLING_QUARTERLY = 'quarterly';
    const BILLING_SEMI_ANNUALLY = 'semi_annually';
    const BILLING_ANNUALLY = 'annually';
    const BILLING_LIFETIME = 'lifetime';

    /**
     * 模型启动
     */
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });

        static::saved(function ($model) {
            // 订阅状态变更时更新提醒
            if ($model->isDirty('status') || $model->isDirty('next_billing_date')) {
                $model->updateReminders();
            }
        });
    }

    /**
     * 用户关联
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 订阅类型关联
     */
    public function subscriptionType()
    {
        return $this->belongsTo(SubscriptionType::class);
    }

    /**
     * 提醒关联
     */
    public function reminders()
    {
        return $this->hasMany(Reminder::class);
    }

    /**
     * 活跃提醒
     */
    public function activeReminders()
    {
        return $this->reminders()->where('status', Reminder::STATUS_ENABLED);
    }

    /**
     * 提醒日志关联
     */
    public function reminderLogs()
    {
        return $this->hasMany(ReminderLog::class);
    }

    /**
     * 检查订阅状态
     */
    public function isActive()
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function isExpired()
    {
        return $this->status === self::STATUS_EXPIRED;
    }

    public function isCancelled()
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    public function isSuspended()
    {
        return $this->status === self::STATUS_SUSPENDED;
    }

    /**
     * 检查是否即将到期
     */
    public function isExpiring($days = 7)
    {
        if (!$this->isActive()) {
            return false;
        }

        return $this->next_billing_date <= now()->addDays($days);
    }

    /**
     * 检查是否已过期
     */
    public function isPastDue()
    {
        return $this->next_billing_date < now()->startOfDay();
    }

    /**
     * 获取状态文本
     */
    public function getStatusTextAttribute()
    {
        return match ($this->status) {
            self::STATUS_ACTIVE => '活跃',
            self::STATUS_EXPIRED => '过期',
            self::STATUS_CANCELLED => '取消',
            self::STATUS_SUSPENDED => '暂停',
            default => '未知',
        };
    }

    /**
     * 获取状态颜色
     */
    public function getStatusColorAttribute()
    {
        return match ($this->status) {
            self::STATUS_ACTIVE => 'success',
            self::STATUS_EXPIRED => 'danger',
            self::STATUS_CANCELLED => 'secondary',
            self::STATUS_SUSPENDED => 'warning',
            default => 'secondary',
        };
    }

    /**
     * 获取计费周期文本
     */
    public function getBillingCycleTextAttribute()
    {
        return config('subalert.subscription.billing_cycles')[$this->billing_cycle] ?? $this->billing_cycle;
    }

    /**
     * 获取Logo URL
     */
    public function getLogoUrlAttribute()
    {
        if ($this->logo) {
            return \Storage::url($this->logo);
        }
        
        return asset('images/default-service-logo.png');
    }

    /**
     * 获取格式化价格
     */
    public function getFormattedPriceAttribute()
    {
        $symbol = match ($this->currency) {
            'CNY' => '¥',
            'USD' => '$',
            'EUR' => '€',
            default => $this->currency,
        };

        return $symbol . number_format($this->price, 2);
    }

    /**
     * 获取剩余天数
     */
    public function getDaysUntilBillingAttribute()
    {
        return max(0, now()->diffInDays($this->next_billing_date, false));
    }

    /**
     * 获取年费用
     */
    public function getAnnualCostAttribute()
    {
        return match ($this->billing_cycle) {
            self::BILLING_MONTHLY => $this->price * 12,
            self::BILLING_QUARTERLY => $this->price * 4,
            self::BILLING_SEMI_ANNUALLY => $this->price * 2,
            self::BILLING_ANNUALLY => $this->price,
            default => $this->price,
        };
    }

    /**
     * 更新下次计费日期
     */
    public function updateNextBillingDate()
    {
        if ($this->billing_cycle === self::BILLING_LIFETIME) {
            return;
        }

        $nextDate = match ($this->billing_cycle) {
            self::BILLING_MONTHLY => $this->next_billing_date->addMonth(),
            self::BILLING_QUARTERLY => $this->next_billing_date->addMonths(3),
            self::BILLING_SEMI_ANNUALLY => $this->next_billing_date->addMonths(6),
            self::BILLING_ANNUALLY => $this->next_billing_date->addYear(),
            default => $this->next_billing_date,
        };

        $this->update(['next_billing_date' => $nextDate]);
    }

    /**
     * 更新提醒
     */
    public function updateReminders()
    {
        // 重新计算所有提醒的下次发送时间
        $this->activeReminders->each(function ($reminder) {
            $reminder->calculateNextSendTime();
        });
    }

    /**
     * 设置自定义字段
     */
    public function setCustomField($key, $value)
    {
        $fields = $this->custom_fields ?? [];
        $fields[$key] = $value;
        $this->custom_fields = $fields;
        return $this;
    }

    /**
     * 获取自定义字段
     */
    public function getCustomField($key, $default = null)
    {
        return data_get($this->custom_fields, $key, $default);
    }

    /**
     * 添加标签
     */
    public function addTag($tag)
    {
        $tags = $this->tags ?? [];
        if (!in_array($tag, $tags)) {
            $tags[] = $tag;
            $this->tags = $tags;
        }
        return $this;
    }

    /**
     * 移除标签
     */
    public function removeTag($tag)
    {
        $tags = $this->tags ?? [];
        $this->tags = array_values(array_diff($tags, [$tag]));
        return $this;
    }

    /**
     * 按状态过滤
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * 活跃订阅
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * 即将到期的订阅
     */
    public function scopeExpiring($query, $days = 7)
    {
        return $query->active()
            ->where('next_billing_date', '<=', now()->addDays($days))
            ->where('next_billing_date', '>', now());
    }

    /**
     * 已过期的订阅
     */
    public function scopePastDue($query)
    {
        return $query->active()
            ->where('next_billing_date', '<', now()->startOfDay());
    }

    /**
     * 按用户过滤
     */
    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * 按类型过滤
     */
    public function scopeByType($query, $typeId)
    {
        return $query->where('subscription_type_id', $typeId);
    }

    /**
     * 搜索订阅
     */
    public function scopeSearch($query, $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('service_name', 'like', "%{$term}%")
              ->orWhere('description', 'like', "%{$term}%")
              ->orWhere('notes', 'like', "%{$term}%");
        });
    }

    /**
     * 按标签过滤
     */
    public function scopeByTag($query, $tag)
    {
        return $query->whereJsonContains('tags', $tag);
    }

    /**
     * 按日期范围过滤
     */
    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('next_billing_date', [$startDate, $endDate]);
    }
}