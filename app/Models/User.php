<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Activitylog\LogsActivity;
use Spatie\Activitylog\Traits\LogsActivity as LogsActivityTrait;
use Illuminate\Support\Str;

/**
 * 用户模型
 * 
 * @property int $id
 * @property string $uuid
 * @property string $username
 * @property string $email
 * @property string $password
 * @property string|null $real_name
 * @property string|null $phone
 * @property string|null $avatar
 * @property int $gender
 * @property string|null $birthday
 * @property string $timezone
 * @property string $language
 * @property array|null $notification_settings
 * @property int $status
 * @property \Illuminate\Support\Carbon|null $last_login_at
 * @property string|null $last_login_ip
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 */
class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes, LogsActivityTrait;

    /**
     * 可批量赋值的属性
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'uuid',
        'username',
        'email',
        'password',
        'real_name',
        'phone',
        'avatar',
        'gender',
        'birthday',
        'timezone',
        'language',
        'notification_settings',
        'status',
        'last_login_at',
        'last_login_ip',
    ];

    /**
     * 隐藏的属性
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * 属性类型转换
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'notification_settings' => 'array',
        'birthday' => 'date',
        'last_login_at' => 'datetime',
        'status' => 'integer',
        'gender' => 'integer',
    ];

    /**
     * 日期字段
     *
     * @var array
     */
    protected $dates = [
        'deleted_at',
        'last_login_at',
    ];

    /**
     * 活动日志配置
     */
    protected static $logAttributes = [
        'username', 'email', 'real_name', 'phone', 'status'
    ];
    protected static $logOnlyDirty = true;
    protected static $logName = 'user';

    /**
     * 状态常量
     */
    const STATUS_ACTIVE = 1;
    const STATUS_DISABLED = 2;

    /**
     * 性别常量
     */
    const GENDER_UNKNOWN = 0;
    const GENDER_MALE = 1;
    const GENDER_FEMALE = 2;

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
     * 用户订阅关联
     */
    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }

    /**
     * 活跃订阅
     */
    public function activeSubscriptions()
    {
        return $this->subscriptions()->where('status', Subscription::STATUS_ACTIVE);
    }

    /**
     * 即将到期的订阅
     */
    public function expiringSubscriptions($days = 7)
    {
        return $this->activeSubscriptions()
            ->where('next_billing_date', '<=', now()->addDays($days))
            ->where('next_billing_date', '>', now());
    }

    /**
     * 用户提醒记录
     */
    public function reminderLogs()
    {
        return $this->hasManyThrough(
            ReminderLog::class,
            Subscription::class,
            'user_id',
            'subscription_id'
        );
    }

    /**
     * 获取通知设置
     */
    public function getNotificationSetting($key, $default = null)
    {
        return data_get($this->notification_settings, $key, $default);
    }

    /**
     * 设置通知配置
     */
    public function setNotificationSetting($key, $value)
    {
        $settings = $this->notification_settings ?? [];
        data_set($settings, $key, $value);
        $this->notification_settings = $settings;
        return $this;
    }

    /**
     * 是否启用邮件通知
     */
    public function isEmailNotificationEnabled()
    {
        return $this->getNotificationSetting('email.enabled', true);
    }

    /**
     * 是否启用飞书通知
     */
    public function isFeishuNotificationEnabled()
    {
        return $this->getNotificationSetting('feishu.enabled', false);
    }

    /**
     * 是否启用企微通知
     */
    public function isWechatNotificationEnabled()
    {
        return $this->getNotificationSetting('wechat.enabled', false);
    }

    /**
     * 检查用户是否活跃
     */
    public function isActive()
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * 检查用户是否被禁用
     */
    public function isDisabled()
    {
        return $this->status === self::STATUS_DISABLED;
    }

    /**
     * 获取性别文本
     */
    public function getGenderTextAttribute()
    {
        return match ($this->gender) {
            self::GENDER_MALE => '男',
            self::GENDER_FEMALE => '女',
            default => '未知',
        };
    }

    /**
     * 获取状态文本
     */
    public function getStatusTextAttribute()
    {
        return match ($this->status) {
            self::STATUS_ACTIVE => '正常',
            self::STATUS_DISABLED => '禁用',
            default => '未知',
        };
    }

    /**
     * 获取完整头像URL
     */
    public function getAvatarUrlAttribute()
    {
        if ($this->avatar) {
            return \Storage::url($this->avatar);
        }
        
        // 默认头像
        return asset('images/default-avatar.png');
    }

    /**
     * 更新最后登录信息
     */
    public function updateLastLogin($ip = null)
    {
        $this->update([
            'last_login_at' => now(),
            'last_login_ip' => $ip ?? request()->ip(),
        ]);
    }

    /**
     * 搜索用户
     */
    public function scopeSearch($query, $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('username', 'like', "%{$term}%")
              ->orWhere('email', 'like', "%{$term}%")
              ->orWhere('real_name', 'like', "%{$term}%")
              ->orWhere('phone', 'like', "%{$term}%");
        });
    }

    /**
     * 按状态过滤
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * 活跃用户
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * 最近登录的用户
     */
    public function scopeRecentlyLoggedIn($query, $days = 30)
    {
        return $query->where('last_login_at', '>=', now()->subDays($days));
    }
}