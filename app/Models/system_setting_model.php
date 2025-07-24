<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * 系统设置模型
 * 
 * @property int $id
 * @property string $group
 * @property string $key
 * @property string|null $value
 * @property string $type
 * @property string $title
 * @property string|null $description
 * @property array|null $options
 * @property int $is_public
 * @property int $sort_order
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class SystemSetting extends Model
{
    use HasFactory, LogsActivity;

    /**
     * 可批量赋值的属性
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'group',
        'key',
        'value',
        'type',
        'title',
        'description',
        'options',
        'is_public',
        'sort_order',
    ];

    /**
     * 属性类型转换
     *
     * @var array<string, string>
     */
    protected $casts = [
        'options' => 'array',
        'is_public' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * 活动日志配置
     */
    protected static $logAttributes = [
        'group', 'key', 'value', 'is_public'
    ];
    protected static $logOnlyDirty = true;
    protected static $logName = 'system_setting';

    /**
     * 数据类型常量
     */
    const TYPE_STRING = 'string';
    const TYPE_NUMBER = 'number';
    const TYPE_BOOLEAN = 'boolean';
    const TYPE_JSON = 'json';
    const TYPE_TEXT = 'text';
    const TYPE_SELECT = 'select';
    const TYPE_MULTISELECT = 'multiselect';
    const TYPE_FILE = 'file';
    const TYPE_IMAGE = 'image';

    /**
     * 模型启动
     */
    protected static function boot()
    {
        parent::boot();
        
        // 更新时清除缓存
        static::saved(function ($model) {
            static::clearSettingsCache();
        });

        static::deleted(function ($model) {
            static::clearSettingsCache();
        });
    }

    /**
     * 获取设置值（经过类型转换）
     */
    public function getTypedValue()
    {
        return match ($this->type) {
            self::TYPE_BOOLEAN => filter_var($this->value, FILTER_VALIDATE_BOOLEAN),
            self::TYPE_NUMBER => is_numeric($this->value) ? (float) $this->value : $this->value,
            self::TYPE_JSON, self::TYPE_MULTISELECT => json_decode($this->value, true),
            default => $this->value,
        };
    }

    /**
     * 设置值（自动类型转换）
     */
    public function setTypedValue($value)
    {
        $this->value = match ($this->type) {
            self::TYPE_JSON, self::TYPE_MULTISELECT => is_array($value) ? json_encode($value) : $value,
            self::TYPE_BOOLEAN => $value ? '1' : '0',
            default => (string) $value,
        };
        
        return $this;
    }

    /**
     * 获取选项
     */
    public function getOptions()
    {
        return $this->options ?? [];
    }

    /**
     * 检查是否为公开设置
     */
    public function isPublic()
    {
        return $this->is_public;
    }

    /**
     * 验证设置值
     */
    public function validateValue($value)
    {
        $errors = [];

        switch ($this->type) {
            case self::TYPE_NUMBER:
                if (!is_numeric($value)) {
                    $errors[] = '必须是数字';
                }
                break;
            case self::TYPE_BOOLEAN:
                if (!in_array($value, [true, false, 0, 1, '0', '1'])) {
                    $errors[] = '必须是布尔值';
                }
                break;
            case self::TYPE_JSON:
                if (is_string($value) && !json_decode($value)) {
                    $errors[] = '必须是有效的JSON格式';
                }
                break;
            case self::TYPE_SELECT:
                $options = collect($this->getOptions())->pluck('value');
                if (!$options->contains($value)) {
                    $errors[] = '值不在允许的选项中';
                }
                break;
        }

        return $errors;
    }

    /**
     * 按分组过滤
     */
    public function scopeByGroup($query, $group)
    {
        return $query->where('group', $group);
    }

    /**
     * 公开设置
     */
    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    /**
     * 按排序
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    /**
     * 搜索设置
     */
    public function scopeSearch($query, $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('title', 'like', "%{$term}%")
              ->orWhere('key', 'like', "%{$term}%")
              ->orWhere('group', 'like', "%{$term}%")
              ->orWhere('description', 'like', "%{$term}%");
        });
    }

    /**
     * 静态方法：获取设置值
     */
    public static function get($key, $default = null, $group = null)
    {
        $cacheKey = 'system_settings';
        
        $settings = Cache::remember($cacheKey, 3600, function () {
            return static::all()->keyBy(function ($item) {
                return $item->group . '.' . $item->key;
            });
        });

        $fullKey = $group ? $group . '.' . $key : $key;
        
        // 如果指定了分组但没找到，尝试不带分组的key
        if ($group && !$settings->has($fullKey)) {
            $setting = $settings->firstWhere('key', $key);
        } else {
            $setting = $settings->get($fullKey);
        }

        return $setting ? $setting->getTypedValue() : $default;
    }

    /**
     * 静态方法：设置值
     */
    public static function set($key, $value, $group = 'general')
    {
        $setting = static::where('group', $group)->where('key', $key)->first();
        
        if ($setting) {
            $setting->setTypedValue($value);
            $setting->save();
        } else {
            static::create([
                'group' => $group,
                'key' => $key,
                'value' => $value,
                'type' => static::guessType($value),
                'title' => ucfirst(str_replace('_', ' ', $key)),
                'is_public' => false,
                'sort_order' => 0,
            ]);
        }

        static::clearSettingsCache();
    }

    /**
     * 静态方法：获取分组设置
     */
    public static function getGroup($group)
    {
        $settings = static::byGroup($group)->get();
        
        return $settings->mapWithKeys(function ($setting) {
            return [$setting->key => $setting->getTypedValue()];
        });
    }

    /**
     * 静态方法：批量设置
     */
    public static function setMany(array $settings, $group = 'general')
    {
        foreach ($settings as $key => $value) {
            static::set($key, $value, $group);
        }
    }

    /**
     * 静态方法：删除设置
     */
    public static function remove($key, $group = null)
    {
        $query = static::where('key', $key);
        
        if ($group) {
            $query->where('group', $group);
        }
        
        $query->delete();
        static::clearSettingsCache();
    }

    /**
     * 静态方法：清除设置缓存
     */
    public static function clearSettingsCache()
    {
        Cache::forget('system_settings');
    }

    /**
     * 静态方法：获取所有公开设置
     */
    public static function getPublicSettings()
    {
        return static::public()->get()->mapWithKeys(function ($setting) {
            return [$setting->group . '.' . $setting->key => $setting->getTypedValue()];
        });
    }

    /**
     * 猜测数据类型
     */
    protected static function guessType($value)
    {
        if (is_bool($value)) {
            return static::TYPE_BOOLEAN;
        }
        
        if (is_numeric($value)) {
            return static::TYPE_NUMBER;
        }
        
        if (is_array($value) || is_object($value)) {
            return static::TYPE_JSON;
        }
        
        return static::TYPE_STRING;
    }
}