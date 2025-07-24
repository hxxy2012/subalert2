<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * 提醒记录模型
 * 
 * @property int $id
 * @property string $uuid
 * @property int $reminder_id
 * @property int $subscription_id
 * @property string $channel
 * @property string $recipient
 * @property string|null $subject
 * @property string|null $content
 * @property int $status
 * @property string|null $error_message
 * @property array|null $response_data
 * @property \Illuminate\Support\Carbon|null $sent_at
 * @property int $retry_count
 * @property \Illuminate\Support\Carbon|null $next_retry_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class ReminderLog extends Model
{
    use HasFactory;

    /**
     * 可批量赋值的属性
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'uuid',
        'reminder_id',
        'subscription_id',
        'channel',
        'recipient',
        'subject',
        'content',
        'status',
        'error_message',
        'response_data',
        'sent_at',
        'retry_count',
        'next_retry_at',
    ];

    /**
     * 属性类型转换
     *
     * @var array<string, string>
     */
    protected $casts = [
        'status' => 'integer',
        'response_data' => 'array',
        'sent_at' => 'datetime',
        'retry_count' => 'integer',
        'next_retry_at' => 'datetime',
    ];

    /**
     * 状态常量
     */
    const STATUS_SUCCESS = 1;
    const STATUS_FAILED = 2;
    const STATUS_PENDING = 3;

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
    }

    /**
     * 提醒关联
     */
    public function reminder()
    {
        return $this->belongsTo(Reminder::class);
    }

    /**
     * 订阅关联
     */
    public function subscription()
    {
        return $this->belongsTo(Subscription::class);
    }

    /**
     * 检查状态
     */
    public function isSuccess()
    {
        return $this->status === self::STATUS_SUCCESS;
    }

    public function isFailed()
    {
        return $this->status === self::STATUS_FAILED;
    }

    public function isPending()
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * 获取状态文本
     */
    public function getStatusTextAttribute()
    {
        return match ($this->status) {
            self::STATUS_SUCCESS => '成功',
            self::STATUS_FAILED => '失败',
            self::STATUS_PENDING => '处理中',
            default => '未知',
        };
    }

    /**
     * 获取状态颜色
     */
    public function getStatusColorAttribute()
    {
        return match ($this->status) {
            self::STATUS_SUCCESS => 'success',
            self::STATUS_FAILED => 'danger',
            self::STATUS_PENDING => 'warning',
            default => 'secondary',
        };
    }

    /**
     * 获取渠道文本
     */
    public function getChannelTextAttribute()
    {
        return match ($this->channel) {
            'email' => '邮件',
            'feishu' => '飞书',
            'wechat' => '企微',
            'sms' => '短信',
            default => $this->channel,
        };
    }

    /**
     * 标记为成功
     */
    public function markAsSuccess($responseData = null)
    {
        $this->update([
            'status' => self::STATUS_SUCCESS,
            'sent_at' => now(),
            'response_data' => $responseData,
            'next_retry_at' => null,
        ]);
    }

    /**
     * 标记为失败
     */
    public function markAsFailed($errorMessage, $shouldRetry = true)
    {
        $updateData = [
            'status' => self::STATUS_FAILED,
            'error_message' => $errorMessage,
        ];

        if ($shouldRetry && $this->canRetry()) {
            $updateData['retry_count'] = $this->retry_count + 1;
            $updateData['next_retry_at'] = $this->calculateNextRetryTime();
        } else {
            $updateData['next_retry_at'] = null;
        }

        $this->update($updateData);
    }

    /**
     * 检查是否可以重试
     */
    public function canRetry()
    {
        $maxRetries = config('subalert.reminder.max_retries', 3);
        return $this->retry_count < $maxRetries;
    }

    /**
     * 计算下次重试时间
     */
    protected function calculateNextRetryTime()
    {
        $delay = config('subalert.reminder.retry_delay', 300); // 5分钟
        $backoffMultiplier = 2; // 指数退避
        
        $actualDelay = $delay * pow($backoffMultiplier, $this->retry_count);
        
        return now()->addSeconds($actualDelay);
    }

    /**
     * 检查是否应该重试
     */
    public function shouldRetry()
    {
        return $this->isFailed() 
            && $this->next_retry_at 
            && $this->next_retry_at <= now()
            && $this->canRetry();
    }

    /**
     * 获取响应数据
     */
    public function getResponseData($key = null, $default = null)
    {
        if ($key) {
            return data_get($this->response_data, $key, $default);
        }
        return $this->response_data ?? [];
    }

    /**
     * 设置响应数据
     */
    public function setResponseData($key, $value = null)
    {
        if (is_array($key)) {
            $this->response_data = $key;
        } else {
            $data = $this->response_data ?? [];
            data_set($data, $key, $value);
            $this->response_data = $data;
        }
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
     * 成功的记录
     */
    public function scopeSuccess($query)
    {
        return $query->where('status', self::STATUS_SUCCESS);
    }

    /**
     * 失败的记录
     */
    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    /**
     * 处理中的记录
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * 需要重试的记录
     */
    public function scopeShouldRetry($query)
    {
        return $query->failed()
            ->whereNotNull('next_retry_at')
            ->where('next_retry_at', '<=', now());
    }

    /**
     * 按渠道过滤
     */
    public function scopeByChannel($query, $channel)
    {
        return $query->where('channel', $channel);
    }

    /**
     * 按提醒过滤
     */
    public function scopeByReminder($query, $reminderId)
    {
        return $query->where('reminder_id', $reminderId);
    }

    /**
     * 按订阅过滤
     */
    public function scopeBySubscription($query, $subscriptionId)
    {
        return $query->where('subscription_id', $subscriptionId);
    }

    /**
     * 按日期范围过滤
     */
    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * 最近的记录
     */
    public function scopeRecent($query, $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * 搜索记录
     */
    public function scopeSearch($query, $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('recipient', 'like', "%{$term}%")
              ->orWhere('subject', 'like', "%{$term}%")
              ->orWhere('channel', 'like', "%{$term}%");
        });
    }
}