<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Support\Str;

/**
 * 管理员模型
 * 
 * @property int $id
 * @property string $uuid
 * @property string $username
 * @property string $email
 * @property string $password
 * @property string $real_name
 * @property string|null $phone
 * @property string|null $avatar
 * @property array|null $permissions
 * @property int $status
 * @property int $is_super
 * @property string|null $department
 * @property string|null $position
 * @property \Illuminate\Support\Carbon|null $last_login_at
 * @property string|null $last_login_ip
 * @property string|null $remarks
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 */
class AdminUser extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes, LogsActivity, HasRoles;

    /**
     * 数据库表名
     */
    protected $table = 'admin_users';

    /**
     * 权限守卫
     */
    protected $guard_name = 'admin';

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
        'permissions',
        'status',
        'is_super',
        'department',
        'position',
        'last_login_at',
        'last_login_ip',
        'remarks',
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
        'permissions' => 'array',
        'last_login_at' => 'datetime',
        'status' => 'integer',
        'is_super' => 'integer',
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
        'username', 'email', 'real_name', 'status', 'is_super'
    ];
    protected static $logOnlyDirty = true;
    protected static $logName = 'admin';

    /**
     * 状态常量
     */
    const STATUS_ACTIVE = 1;
    const STATUS_DISABLED = 2;

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
     * 检查是否为超级管理员
     */
    public function isSuperAdmin()
    {
        return $this->is_super === 1;
    }

    /**
     * 检查管理员是否活跃
     */
    public function isActive()
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * 检查管理员是否被禁用
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
        
        return asset('images/default-avatar.png');
    }

    /**
     * 检查权限
     */
    public function hasPermission($permission)
    {
        // 超级管理员拥有所有权限
        if ($this->isSuperAdmin()) {
            return true;
        }

        // 检查角色权限
        if ($this->hasPermissionTo($permission)) {
            return true;
        }

        // 检查自定义权限
        return in_array($permission, $this->permissions ?? []);
    }

    /**
     * 获取权限列表
     */
    public function getPermissionsList()
    {
        if ($this->isSuperAdmin()) {
            return ['*']; // 超级管理员拥有所有权限
        }

        $permissions = [];
        
        // 角色权限
        foreach ($this->getAllPermissions() as $permission) {
            $permissions[] = $permission->name;
        }

        // 自定义权限
        if ($this->permissions) {
            $permissions = array_merge($permissions, $this->permissions);
        }

        return array_unique($permissions);
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
     * 搜索管理员
     */
    public function scopeSearch($query, $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('username', 'like', "%{$term}%")
              ->orWhere('email', 'like', "%{$term}%")
              ->orWhere('real_name', 'like', "%{$term}%")
              ->orWhere('phone', 'like', "%{$term}%")
              ->orWhere('department', 'like', "%{$term}%");
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
     * 活跃管理员
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * 超级管理员
     */
    public function scopeSuperAdmin($query)
    {
        return $query->where('is_super', 1);
    }

    /**
     * 按部门过滤
     */
    public function scopeByDepartment($query, $department)
    {
        return $query->where('department', $department);
    }
}
    