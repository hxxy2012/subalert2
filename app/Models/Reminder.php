<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Support\Str;
use Carbon\Carbon;

/**
 * 提醒模型
 * 
 * @property int $id
 * @property string $uuid
 * @property int $subscription_id
 * @property string $type
 * @property string $name
 * @property int $advance_days
 * @property array $channels
 * @property string $reminder_time
 * @property array|null $recipient_config
 * @property array|null $template_config
 * @property int $status
 * @property int $repeat_enabled
 * @property array|null $repeat_config
 * @property \Illuminate\Support\Carbon|null $last_sent_at
 * @property \Illuminate\Support\Carbon|null $next_send_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class Reminder extends Model
{
    use HasFactory, LogsActivity;

    /**
     * 可批量赋值的属性
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'uuid',
        'subscription_id',
        'type',
        'name',
        'advance_days',
        'channels',
        'reminder_time',
        'recipient_config',
        'template_config',
        'status',
        'repeat_enabled',
        'repeat_config',
        'last_sent_at',
        'next_send_at',
    ];

    /**
     * 属性类型转换
     *
     * @var array<string, string>
     */
    protected $casts = [
        'advance_days' => 'integer',
        'channels' => 'array',
        'recipient_config' => 'array',
        'template_config' => 'array',
        'status' => 'integer',
        'repeat_enabled' => 'boolean',
        'repeat_config' => 'array',
        'last_sent_at' => 'datetime',
        'next_send_at' => 'datetime',
    ];

    /**
     * 活动日志配置
     */
    protected static $logAttributes = [
        'name', 'type', 'advance_days', 'status'
    ];
    protected static $logOnlyDirty = true;
    protected static $logName = 'reminder';

    /**
     * 状态常量
     */
    const STATUS_ENABLED = 1;
    const STATUS_DISABLED = 2;

    /**
     * 提醒类型常量
     */
    const TYPE_BILLING = 'billing';
    const TYPE_EXPIRY = 'expiry';
    const TYPE_CUSTOM = 'custom';

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
            // 重新计算下次发送时间
            if ($model->isDirty(['advance_days', 'reminder_time', 'status'])) {
                $model->calculateNextSendTime();
            }
        });
    }

    /**
     * 订阅关联
     */
    public function subscription()
    {
        return $this->belongsTo(Subscription::class);
    }

    /**
     * 提醒日志关联
     */
    public function reminderLogs()
    {
        return $this->hasMany(ReminderLog::class);
    }

    /**
     * 成功的提醒日志
     */
    public function successLogs()
    {
        return $this->reminderLogs()->where('status', ReminderLog::STATUS_SUCCESS);
    }

    /**
     * 失败的提醒日志
     */
    public function failedLogs()
    {
        return $this->reminderLogs()->where('status', ReminderLog::STATUS_FAILED);
    }

    /**
     * 检查提醒是否启用
     */
    public function isEnabled()
    {
        return $this->status === self::STATUS_ENABLED;
    }

    /**
     * 检查提醒是否禁用
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
     * 获取类型文本
     */
    public function getTypeTextAttribute()
    {
        return match ($this->type) {
            self::TYPE_BILLING => '计费提醒',
            self::TYPE_EXPIRY => '到期提醒',
            self::TYPE_CUSTOM => '自定义提醒',
            default => $this->type,
        };
    }

    /**
     * 获取渠道文本
     */
    public function getChannelsTextAttribute()
    {
        $channelMap = [
            'email' => '邮件',
            'feishu' => '飞书',
            'wechat' => '企微',
            'sms' => '短信',
        ];

        return collect($this->channels)
            ->map(fn($channel) => $channelMap[$channel] ?? $channel)
            ->implode(', ');
    }

    /**
     * 计算下次发送时间
     */
    public function calculateNextSendTime()
    {
        if (!$this->isEnabled() || !$this->subscription) {
            $this->next_send_at = null;
            $this->saveQuietly();
            return;
        }

        $subscription = $this->subscription;
        $targetDate = null;

        switch ($this->type) {
            case self::TYPE_BILLING:
                $targetDate = $subscription->next_billing_date;
                break;
            case self::TYPE_EXPIRY:
                $targetDate = $subscription->end_date;
                break;
            case self::TYPE_CUSTOM:
                // 自定义提醒需要在配置中指定目标日期
                $targetDate = $this->getTemplateConfig('target_date');
                if ($targetDate) {
                    $targetDate = Carbon::parse($targetDate);
                }
                break;
        }

        if (!$targetDate) {
            $this->next_send_at = null;
            $this->saveQuietly();
            return;
        }

        // 计算提醒日期和时间
        $reminderDate = $targetDate->subDays($this->advance_days);
        $reminderTime = Carbon::parse($this->reminder_time);
        
        $nextSendTime = $reminderDate->setTime(
            $reminderTime->hour,
            $reminderTime->minute,
            $reminderTime->second
        );

        // 如果已经过了这个时间且不是重复提醒，则设为null
        if ($nextSendTime->isPast() && !$this->repeat_enabled) {
            $this->next_send_at = null;
        } else {
            $this->next_send_at = $nextSendTime;
        }

        $this->saveQuietly();
    }

    /**
     * 获取收件人配置
     */
    public function getRecipientConfig($key = null, $default = null)
    {
        if ($key) {
            return data_get($this->recipient_config, $key, $default);
        }
        return $this->recipient_config ?? [];
    }

    /**
     * 设置收件人配置
     */
    public function setRecipientConfig($key, $value = null)
    {
        if (is_array($key)) {
            $this->recipient_config = $key;
        } else {
            $config = $this->recipient_config ?? [];
            data_set($config, $key, $value);
            $this->recipient_config = $config;
        }
        return $this;
    }

    /**
     * 获取模板配置
     */
    public function getTemplateConfig($key = null, $default = null)
    {
        if ($key) {
            return data_get($this->template_config, $key, $default);
        }
        return $this->template_config ?? [];
    }

    /**
     * 设置模板配置
     */
    public function setTemplateConfig($key, $value = null)
    {
        if (is_array($key)) {
            $this->template_config = $key;
        } else {
            $config = $this->template_config ?? [];
            data_set($config, $key, $value);
            $this->template_config = $config;
        }
        return $this;
    }

    /**
     * 获取重复配置
     */
    public function getRepeatConfig($key = null, $default = null)
    {
        if ($key) {
            return data_get($this->repeat_config, $key, $default);
        }
        return $this->repeat_config ?? [];
    }

    /**
     * 检查是否应该发送提醒
     */
    public function shouldSend()
    {
        if (!$this->isEnabled()) {
            return false;
        }

        if (!$this->next_send_at) {
            return false;
        }

        return $this->next_send_at <= now();
    }

    /**
     * 标记为已发送
     */
    public function markAsSent()
    {
        $this->last_sent_at = now();
        
        // 如果启用重复提醒，计算下次发送时间
        if ($this->repeat_enabled) {
            $this->calculateRepeatSendTime();
        } else {
            $this->next_send_at = null;
        }
        
        $this->saveQuietly();
    }

    /**
     * 计算重复发送时间
     */
    protected function calculateRepeatSendTime()
    {
        $repeatConfig = $this->getRepeatConfig();
        $interval = $repeatConfig['interval'] ?? 'daily';
        $count = $repeatConfig['count'] ?? 1;

        if (!$this->next_send_at) {
            return;
        }

        $nextTime = $this->next_send_at;

        switch ($interval) {
            case 'hourly':
                $nextTime = $nextTime->addHours($count);
                break;
            case 'daily':
                $nextTime = $nextTime->addDays($count);
                break;
            case 'weekly':
                $nextTime = $nextTime->addWeeks($count);
                break;
            case 'monthly':
                $nextTime = $nextTime->addMonths($count);
                break;
        }

        $this->next_send_at = $nextTime;
    }

    /**
     * 获取发送统计
     */
    public function getSendStatistics()
    {
        return [
            'total' => $this->reminderLogs()->count(),
            'success' => $this->successLogs()->count(),
            'failed' => $this->failedLogs()->count(),
            'success_rate' => $this->reminderLogs()->count() > 0 
                ? round($this->successLogs()->count() / $this->reminderLogs()->count() * 100, 2)
                : 0,
        ];
    }

    /**
     * 按状态过滤
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * 启用的提醒
     */
    public function scopeEnabled($query)
    {
        return $query->where('status', self::STATUS_ENABLED);
    }

    /**
     * 应该发送的提醒
     */
    public function scopeShouldSend($query)
    {
        return $query->enabled()
            ->whereNotNull('next_send_at')
            ->where('next_send_at', '<=', now());
    }

    /**
     * 按类型过滤
     */
    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * 按订阅过滤
     */
    public function scopeBySubscription($query, $subscriptionId)
    {
        return $query->where('subscription_id', $subscriptionId);
    }

    /**
     * 搜索提醒
     */
    public function scopeSearch($query, $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('name', 'like', "%{$term}%")
              ->orWhere('type', 'like', "%{$term}%");
        });
    }
}