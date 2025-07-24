<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * API统一响应格式类
 */
class ApiResponse
{
    /**
     * 成功响应
     */
    public static function success($data = null, string $message = '操作成功', int $code = 200): JsonResponse
    {
        $response = [
            'success' => true,
            'code' => $code,
            'message' => $message,
            'timestamp' => now()->toISOString(),
        ];

        if ($data !== null) {
            $response['data'] = $data;
        }

        return response()->json($response, $code);
    }

    /**
     * 错误响应
     */
    public static function error(string $message = '操作失败', int $code = 400, array $errors = []): JsonResponse
    {
        $response = [
            'success' => false,
            'code' => $code,
            'message' => $message,
            'timestamp' => now()->toISOString(),
        ];

        if (!empty($errors)) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $code);
    }

    /**
     * 分页响应
     */
    public static function paginated($data, string $message = '获取成功'): JsonResponse
    {
        if (!$data instanceof LengthAwarePaginator) {
            return static::error('数据格式错误，需要分页对象');
        }

        $response = [
            'success' => true,
            'code' => 200,
            'message' => $message,
            'data' => $data->items(),
            'pagination' => [
                'current_page' => $data->currentPage(),
                'per_page' => $data->perPage(),
                'total' => $data->total(),
                'last_page' => $data->lastPage(),
                'from' => $data->firstItem(),
                'to' => $data->lastItem(),
                'has_more_pages' => $data->hasMorePages(),
                'next_page_url' => $data->nextPageUrl(),
                'prev_page_url' => $data->previousPageUrl(),
            ],
            'timestamp' => now()->toISOString(),
        ];

        return response()->json($response);
    }

    /**
     * 集合响应
     */
    public static function collection($data, string $message = '获取成功'): JsonResponse
    {
        $response = [
            'success' => true,
            'code' => 200,
            'message' => $message,
            'data' => $data instanceof Collection ? $data->values() : $data,
            'meta' => [
                'count' => $data instanceof Collection ? $data->count() : count($data),
            ],
            'timestamp' => now()->toISOString(),
        ];

        return response()->json($response);
    }

    /**
     * 创建成功响应
     */
    public static function created($data = null, string $message = '创建成功'): JsonResponse
    {
        return static::success($data, $message, 201);
    }

    /**
     * 更新成功响应
     */
    public static function updated($data = null, string $message = '更新成功'): JsonResponse
    {
        return static::success($data, $message, 200);
    }

    /**
     * 删除成功响应
     */
    public static function deleted(string $message = '删除成功'): JsonResponse
    {
        return static::success(null, $message, 200);
    }

    /**
     * 无内容响应
     */
    public static function noContent(): JsonResponse
    {
        return response()->json(null, 204);
    }

    /**
     * 验证错误响应
     */
    public static function validationError(array $errors, string $message = '验证失败'): JsonResponse
    {
        return static::error($message, 422, $errors);
    }

    /**
     * 未认证响应
     */
    public static function unauthorized(string $message = '未认证'): JsonResponse
    {
        return static::error($message, 401);
    }

    /**
     * 无权限响应
     */
    public static function forbidden(string $message = '无权限'): JsonResponse
    {
        return static::error($message, 403);
    }

    /**
     * 资源不存在响应
     */
    public static function notFound(string $message = '资源不存在'): JsonResponse
    {
        return static::error($message, 404);
    }

    /**
     * 服务器错误响应
     */
    public static function serverError(string $message = '服务器错误'): JsonResponse
    {
        return static::error($message, 500);
    }

    /**
     * 请求过于频繁响应
     */
    public static function tooManyRequests(string $message = '请求过于频繁'): JsonResponse
    {
        return static::error($message, 429);
    }

    /**
     * 自定义状态码响应
     */
    public static function custom($data, string $message, int $code, bool $success = true): JsonResponse
    {
        $response = [
            'success' => $success,
            'code' => $code,
            'message' => $message,
            'timestamp' => now()->toISOString(),
        ];

        if ($data !== null) {
            $response['data'] = $data;
        }

        return response()->json($response, $code);
    }

    /**
     * 批量操作响应
     */
    public static function batch(array $results, string $message = '批量操作完成'): JsonResponse
    {
        $successCount = $results['success_count'] ?? 0;
        $errorCount = $results['error_count'] ?? 0;
        $total = $successCount + $errorCount;

        $response = [
            'success' => $errorCount === 0,
            'code' => 200,
            'message' => $message,
            'data' => [
                'total' => $total,
                'success_count' => $successCount,
                'error_count' => $errorCount,
                'success_rate' => $total > 0 ? round($successCount / $total * 100, 2) : 0,
            ],
            'timestamp' => now()->toISOString(),
        ];

        if (isset($results['results'])) {
            $response['data']['results'] = $results['results'];
        }

        if (isset($results['errors']) && !empty($results['errors'])) {
            $response['errors'] = $results['errors'];
        }

        return response()->json($response);
    }

    /**
     * 文件上传响应
     */
    public static function fileUploaded(array $fileInfo, string $message = '文件上传成功'): JsonResponse
    {
        return static::success($fileInfo, $message, 201);
    }

    /**
     * 导出响应
     */
    public static function export(string $downloadUrl, string $message = '导出成功'): JsonResponse
    {
        return static::success([
            'download_url' => $downloadUrl,
            'expires_at' => now()->addHours(24)->toISOString(),
        ], $message);
    }

    /**
     * 异步任务响应
     */
    public static function async(string $taskId, string $message = '任务已提交'): JsonResponse
    {
        return static::success([
            'task_id' => $taskId,
            'status' => 'pending',
            'message' => '任务正在处理中，请稍后查询结果',
        ], $message, 202);
    }

    /**
     * 统计数据响应
     */
    public static function statistics(array $stats, string $message = '统计数据获取成功'): JsonResponse
    {
        return static::success([
            'statistics' => $stats,
            'generated_at' => now()->toISOString(),
        ], $message);
    }

    /**
     * 健康检查响应
     */
    public static function health(array $status, bool $healthy = true): JsonResponse
    {
        $code = $healthy ? 200 : 503;
        $message = $healthy ? '系统正常' : '系统异常';

        return static::custom([
            'status' => $healthy ? 'healthy' : 'unhealthy',
            'checks' => $status,
            'timestamp' => now()->toISOString(),
        ], $message, $code, $healthy);
    }

    /**
     * 带元数据的响应
     */
    public static function withMeta($data, array $meta, string $message = '操作成功'): JsonResponse
    {
        $response = [
            'success' => true,
            'code' => 200,
            'message' => $message,
            'data' => $data,
            'meta' => $meta,
            'timestamp' => now()->toISOString(),
        ];

        return response()->json($response);
    }

    /**
     * 带调试信息的响应（仅开发环境）
     */
    public static function debug($data, array $debugInfo = [], string $message = '调试响应'): JsonResponse
    {
        $response = [
            'success' => true,
            'code' => 200,
            'message' => $message,
            'data' => $data,
            'timestamp' => now()->toISOString(),
        ];

        if (app()->environment('local', 'testing') && !empty($debugInfo)) {
            $response['debug'] = $debugInfo;
        }

        return response()->json($response);
    }
}