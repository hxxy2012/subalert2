<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\{Cache, Log, DB};
use Illuminate\Support\Collection;
use Exception;
use Throwable;

/**
 * 基础服务类
 * 
 * 提供通用的服务方法和错误处理
 */
abstract class BaseService
{
    /**
     * 模型实例
     */
    protected Model $model;

    /**
     * 缓存前缀
     */
    protected string $cachePrefix = '';

    /**
     * 缓存时间（秒）
     */
    protected int $cacheTtl = 3600;

    /**
     * 日志通道
     */
    protected string $logChannel = 'default';

    /**
     * 构造函数
     */
    public function __construct()
    {
        if (method_exists($this, 'setModel')) {
            $this->setModel();
        }
        
        if (empty($this->cachePrefix)) {
            $this->cachePrefix = strtolower(class_basename($this));
        }
    }

    /**
     * 执行数据库事务
     */
    protected function executeTransaction(callable $callback, string $errorMessage = '操作失败')
    {
        try {
            return DB::transaction($callback);
        } catch (Throwable $e) {
            $this->logError('通知发送失败', $e, [
                'notifiable' => get_class($notifiable),
                'notification' => get_class($notification),
                'channels' => $channels,
            ]);
        }
    }

    /**
     * 获取配置值
     */
    protected function getConfig(string $key, $default = null)
    {
        return config($key, $default);
    }

    /**
     * 设置响应格式
     */
    protected function success($data = null, string $message = '操作成功', int $code = 200)
    {
        return $this->formatResponse($data, $message, $code);
    }

    /**
     * 错误响应
     */
    protected function error(string $message = '操作失败', int $code = 400, array $errors = [])
    {
        return $this->formatErrorResponse($message, $code, $errors);
    }
}able $e) {
            $this->logError($errorMessage, $e);
            throw new Exception($errorMessage . ': ' . $e->getMessage());
        }
    }

    /**
     * 安全执行操作
     */
    protected function safeExecute(callable $callback, string $errorMessage = '操作失败', $defaultReturn = null)
    {
        try {
            return $callback();
        } catch (Throwable $e) {
            $this->logError($errorMessage, $e);
            return $defaultReturn;
        }
    }

    /**
     * 记录错误日志
     */
    protected function logError(string $message, Throwable $exception = null, array $context = [])
    {
        $logData = array_merge([
            'service' => static::class,
            'message' => $message,
        ], $context);

        if ($exception) {
            $logData['exception'] = [
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTraceAsString(),
            ];
        }

        Log::channel($this->logChannel)->error($message, $logData);
    }

    /**
     * 记录信息日志
     */
    protected function logInfo(string $message, array $context = [])
    {
        $logData = array_merge([
            'service' => static::class,
        ], $context);

        Log::channel($this->logChannel)->info($message, $logData);
    }

    /**
     * 获取缓存键
     */
    protected function getCacheKey(string $key, array $params = []): string
    {
        $keyParts = [$this->cachePrefix, $key];
        
        if (!empty($params)) {
            $keyParts[] = md5(serialize($params));
        }
        
        return implode(':', $keyParts);
    }

    /**
     * 从缓存获取数据
     */
    protected function getFromCache(string $key, callable $callback = null, int $ttl = null)
    {
        $cacheKey = $this->getCacheKey($key);
        $ttl = $ttl ?? $this->cacheTtl;

        if ($callback) {
            return Cache::remember($cacheKey, $ttl, $callback);
        }

        return Cache::get($cacheKey);
    }

    /**
     * 设置缓存数据
     */
    protected function setCache(string $key, $value, int $ttl = null): bool
    {
        $cacheKey = $this->getCacheKey($key);
        $ttl = $ttl ?? $this->cacheTtl;
        
        return Cache::put($cacheKey, $value, $ttl);
    }

    /**
     * 删除缓存数据
     */
    protected function forgetCache(string $key): bool
    {
        $cacheKey = $this->getCacheKey($key);
        return Cache::forget($cacheKey);
    }

    /**
     * 清除相关缓存
     */
    protected function clearRelatedCache(array $keys = []): void
    {
        if (empty($keys)) {
            $keys = [$this->cachePrefix . '*'];
        }

        foreach ($keys as $key) {
            if (str_contains($key, '*')) {
                // 使用模式匹配删除缓存
                $this->clearCacheByPattern($key);
            } else {
                $this->forgetCache($key);
            }
        }
    }

    /**
     * 按模式清除缓存
     */
    protected function clearCacheByPattern(string $pattern): void
    {
        $keys = Cache::getRedis()->keys($pattern);
        if (!empty($keys)) {
            Cache::getRedis()->del($keys);
        }
    }

    /**
     * 验证必需参数
     */
    protected function validateRequired(array $data, array $required): void
    {
        $missing = [];
        
        foreach ($required as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                $missing[] = $field;
            }
        }
        
        if (!empty($missing)) {
            throw new Exception('缺少必需参数: ' . implode(', ', $missing));
        }
    }

    /**
     * 过滤数据
     */
    protected function filterData(array $data, array $allowed): array
    {
        return array_intersect_key($data, array_flip($allowed));
    }

    /**
     * 分页处理
     */
    protected function paginateResults($query, int $perPage = 15, array $options = [])
    {
        $perPage = min($perPage, config('subalert.api.pagination.max_per_page', 100));
        $perPage = max($perPage, 1);
        
        return $query->paginate($perPage, ['*'], 'page', $options['page'] ?? null);
    }

    /**
     * 格式化响应数据
     */
    protected function formatResponse($data, string $message = 'success', int $code = 200): array
    {
        return [
            'code' => $code,
            'message' => $message,
            'data' => $data,
            'timestamp' => now()->toISOString(),
        ];
    }

    /**
     * 格式化错误响应
     */
    protected function formatErrorResponse(string $message, int $code = 400, array $errors = []): array
    {
        $response = [
            'code' => $code,
            'message' => $message,
            'timestamp' => now()->toISOString(),
        ];

        if (!empty($errors)) {
            $response['errors'] = $errors;
        }

        return $response;
    }

    /**
     * 构建查询条件
     */
    protected function buildQuery($query, array $filters = [])
    {
        foreach ($filters as $field => $value) {
            if ($value === null || $value === '') {
                continue;
            }

            switch ($field) {
                case 'search':
                    if (method_exists($query, 'search')) {
                        $query->search($value);
                    }
                    break;
                    
                case 'status':
                    $query->where('status', $value);
                    break;
                    
                case 'date_from':
                    $query->where('created_at', '>=', $value);
                    break;
                    
                case 'date_to':
                    $query->where('created_at', '<=', $value);
                    break;
                    
                case 'sort':
                    $this->applySorting($query, $value);
                    break;
                    
                default:
                    if (is_array($value)) {
                        $query->whereIn($field, $value);
                    } else {
                        $query->where($field, $value);
                    }
                    break;
            }
        }

        return $query;
    }

    /**
     * 应用排序
     */
    protected function applySorting($query, $sort)
    {
        if (is_string($sort)) {
            $parts = explode(':', $sort);
            $field = $parts[0];
            $direction = $parts[1] ?? 'asc';
            
            $query->orderBy($field, $direction);
        } elseif (is_array($sort)) {
            foreach ($sort as $field => $direction) {
                $query->orderBy($field, $direction);
            }
        }
        
        return $query;
    }

    /**
     * 获取统计数据
     */
    protected function getStatistics(array $metrics = []): array
    {
        $stats = [];
        
        foreach ($metrics as $metric => $callback) {
            try {
                $stats[$metric] = is_callable($callback) ? $callback() : $callback;
            } catch (Throwable $e) {
                $this->logError("统计数据获取失败: {$metric}", $e);
                $stats[$metric] = 0;
            }
        }
        
        return $stats;
    }

    /**
     * 批量处理
     */
    protected function batchProcess(Collection $items, callable $callback, int $chunkSize = 100)
    {
        $results = [];
        $errors = [];
        
        $items->chunk($chunkSize)->each(function ($chunk) use ($callback, &$results, &$errors) {
            foreach ($chunk as $item) {
                try {
                    $results[] = $callback($item);
                } catch (Throwable $e) {
                    $errors[] = [
                        'item' => $item,
                        'error' => $e->getMessage(),
                    ];
                    $this->logError('批量处理失败', $e, ['item' => $item]);
                }
            }
        });
        
        return [
            'success_count' => count($results),
            'error_count' => count($errors),
            'results' => $results,
            'errors' => $errors,
        ];
    }

    /**
     * 异步任务调度
     */
    protected function dispatchJob(string $jobClass, array $data = [], string $queue = 'default')
    {
        if (!class_exists($jobClass)) {
            throw new Exception("Job class not found: {$jobClass}");
        }
        
        dispatch(new $jobClass($data))->onQueue($queue);
        
        $this->logInfo("任务已调度", [
            'job' => $jobClass,
            'queue' => $queue,
            'data' => $data,
        ]);
    }

    /**
     * 生成唯一标识符
     */
    protected function generateUniqueId(string $prefix = ''): string
    {
        return $prefix . uniqid() . mt_rand(1000, 9999);
    }

    /**
     * 转换数据格式
     */
    protected function transformData($data, callable $transformer)
    {
        if ($data instanceof Collection) {
            return $data->map($transformer);
        } elseif (is_array($data)) {
            return array_map($transformer, $data);
        } else {
            return $transformer($data);
        }
    }

    /**
     * 检查权限
     */
    protected function checkPermission(string $permission, $user = null): bool
    {
        $user = $user ?? auth()->user();
        
        if (!$user) {
            return false;
        }
        
        if (method_exists($user, 'hasPermission')) {
            return $user->hasPermission($permission);
        }
        
        if (method_exists($user, 'can')) {
            return $user->can($permission);
        }
        
        return false;
    }

    /**
     * 发送通知
     */
    protected function sendNotification($notifiable, $notification, array $channels = [])
    {
        try {
            if (empty($channels)) {
                $notifiable->notify($notification);
            } else {
                $notifiable->notify($notification, $channels);
            }
            
            $this->logInfo('通知已发送', [
                'notifiable' => get_class($notifiable),
                'notification' => get_class($notification),
                'channels' => $channels,
            ]);
        } catch (Throw