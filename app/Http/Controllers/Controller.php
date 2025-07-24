<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use App\Http\Responses\ApiResponse;

/**
 * 基础控制器类
 * 
 * 提供通用的控制器功能和响应处理
 */
class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    /**
     * 默认分页大小
     */
    protected int $defaultPerPage = 15;

    /**
     * 最大分页大小
     */
    protected int $maxPerPage = 100;

    /**
     * 返回成功响应
     */
    protected function success($data = null, string $message = '操作成功', int $code = 200): JsonResponse
    {
        return ApiResponse::success($data, $message, $code);
    }

    /**
     * 返回错误响应
     */
    protected function error(string $message = '操作失败', int $code = 400, array $errors = []): JsonResponse
    {
        return ApiResponse::error($message, $code, $errors);
    }

    /**
     * 返回分页响应
     */
    protected function paginated($data, string $message = '获取成功'): JsonResponse
    {
        return ApiResponse::paginated($data, $message);
    }

    /**
     * 验证请求数据
     */
    protected function validateRequest(Request $request, array $rules, array $messages = []): array
    {
        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            throw new \Illuminate\Validation\ValidationException($validator);
        }

        return $validator->validated();
    }

    /**
     * 获取分页参数
     */
    protected function getPaginationParams(Request $request): array
    {
        $perPage = (int) $request->get('per_page', $this->defaultPerPage);
        $perPage = min($perPage, $this->maxPerPage);
        $perPage = max($perPage, 1);

        return [
            'per_page' => $perPage,
            'page' => (int) $request->get('page', 1),
        ];
    }

    /**
     * 获取排序参数
     */
    protected function getSortParams(Request $request, string $defaultSort = 'created_at', string $defaultDirection = 'desc'): array
    {
        $sort = $request->get('sort', $defaultSort);
        $direction = $request->get('direction', $defaultDirection);

        // 验证排序方向
        if (!in_array(strtolower($direction), ['asc', 'desc'])) {
            $direction = $defaultDirection;
        }

        return [
            'sort' => $sort,
            'direction' => $direction,
        ];
    }

    /**
     * 获取过滤参数
     */
    protected function getFilterParams(Request $request, array $allowedFilters = []): array
    {
        $filters = [];

        foreach ($allowedFilters as $filter) {
            $value = $request->get($filter);
            if ($value !== null && $value !== '') {
                $filters[$filter] = $value;
            }
        }

        return $filters;
    }

    /**
     * 获取搜索参数
     */
    protected function getSearchParams(Request $request): array
    {
        return [
            'search' => $request->get('search', ''),
            'search_fields' => $request->get('search_fields', []),
        ];
    }

    /**
     * 构建查询条件
     */
    protected function buildQuery($query, Request $request, array $options = [])
    {
        // 应用搜索
        if ($request->filled('search')) {
            $searchTerm = $request->get('search');
            if (method_exists($query, 'search')) {
                $query->search($searchTerm);
            }
        }

        // 应用过滤
        $allowedFilters = $options['filters'] ?? [];
        $filters = $this->getFilterParams($request, $allowedFilters);
        foreach ($filters as $field => $value) {
            if (is_array($value)) {
                $query->whereIn($field, $value);
            } else {
                $query->where($field, $value);
            }
        }

        // 应用排序
        $sortParams = $this->getSortParams(
            $request,
            $options['default_sort'] ?? 'created_at',
            $options['default_direction'] ?? 'desc'
        );

        $allowedSorts = $options['sorts'] ?? [];
        if (empty($allowedSorts) || in_array($sortParams['sort'], $allowedSorts)) {
            $query->orderBy($sortParams['sort'], $sortParams['direction']);
        }

        return $query;
    }

    /**
     * 处理批量操作
     */
    protected function handleBatchOperation(Request $request, callable $operation, string $successMessage = '批量操作成功')
    {
        $ids = $request->get('ids', []);
        
        if (empty($ids)) {
            return $this->error('请选择要操作的项目');
        }

        try {
            $result = $operation($ids);
            
            if (is_array($result)) {
                return $this->success($result, $successMessage);
            }
            
            return $this->success(['affected_rows' => $result], $successMessage);
        } catch (\Throwable $e) {
            \Log::error('批量操作失败', [
                'error' => $e->getMessage(),
                'ids' => $ids,
                'trace' => $e->getTraceAsString(),
            ]);
            
            return $this->error('批量操作失败: ' . $e->getMessage());
        }
    }

    /**
     * 处理文件上传
     */
    protected function handleFileUpload(Request $request, string $fieldName, array $options = []): array
    {
        if (!$request->hasFile($fieldName)) {
            throw new \Exception('未找到上传文件');
        }

        $file = $request->file($fieldName);
        
        // 验证文件
        $maxSize = $options['max_size'] ?? 5 * 1024 * 1024; // 5MB
        $allowedTypes = $options['allowed_types'] ?? ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx'];
        
        if ($file->getSize() > $maxSize) {
            throw new \Exception('文件大小超出限制');
        }
        
        $extension = $file->getClientOriginalExtension();
        if (!in_array(strtolower($extension), $allowedTypes)) {
            throw new \Exception('不支持的文件类型');
        }

        // 存储文件
        $disk = $options['disk'] ?? 'public';
        $path = $options['path'] ?? 'uploads';
        
        $filename = time() . '_' . uniqid() . '.' . $extension;
        $filePath = $file->storeAs($path, $filename, $disk);

        return [
            'original_name' => $file->getClientOriginalName(),
            'filename' => $filename,
            'path' => $filePath,
            'size' => $file->getSize(),
            'type' => $file->getClientMimeType(),
            'extension' => $extension,
            'url' => \Storage::disk($disk)->url($filePath),
        ];
    }

    /**
     * 记录用户操作
     */
    protected function logUserAction(string $action, array $data = [], $user = null)
    {
        $user = $user ?? auth()->user();
        
        if ($user) {
            activity()
                ->performedOn($user)
                ->withProperties($data)
                ->log($action);
        }
    }

    /**
     * 检查用户权限
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
     * 获取当前用户
     */
    protected function getCurrentUser()
    {
        return auth()->user();
    }

    /**
     * 获取当前管理员
     */
    protected function getCurrentAdmin()
    {
        return auth()->guard('admin')->user();
    }

    /**
     * 处理异常响应
     */
    protected function handleException(\Throwable $e, string $defaultMessage = '操作失败'): JsonResponse
    {
        \Log::error($defaultMessage, [
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString(),
        ]);

        if ($e instanceof \Illuminate\Validation\ValidationException) {
            return $this->error('验证失败', 422, $e->errors());
        }

        if ($e instanceof \Illuminate\Auth\AuthenticationException) {
            return $this->error('未认证', 401);
        }

        if ($e instanceof \Illuminate\Auth\Access\AuthorizationException) {
            return $this->error('无权限', 403);
        }

        if ($e instanceof \Symfony\Component\HttpKernel\Exception\NotFoundHttpException) {
            return $this->error('资源不存在', 404);
        }

        // 生产环境隐藏具体错误信息
        $message = app()->environment('production') ? $defaultMessage : $e->getMessage();
        
        return $this->error($message, 500);
    }

    /**
     * 获取客户端IP
     */
    protected function getClientIp(Request $request): string
    {
        return $request->ip();
    }

    /**
     * 获取用户代理
     */
    protected function getUserAgent(Request $request): string
    {
        return $request->userAgent() ?? '';
    }

    /**
     * 生成缓存键
     */
    protected function generateCacheKey(string $prefix, array $params = []): string
    {
        $keyParts = [$prefix];
        
        if (!empty($params)) {
            $keyParts[] = md5(serialize($params));
        }
        
        return implode(':', $keyParts);
    }

    /**
     * 设置响应头
     */
    protected function setResponseHeaders(array $headers): void
    {
        foreach ($headers as $key => $value) {
            response()->header($key, $value);
        }
    }

    /**
     * 获取请求统计信息
     */
    protected function getRequestStats(Request $request): array
    {
        return [
            'ip' => $this->getClientIp($request),
            'user_agent' => $this->getUserAgent($request),
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'timestamp' => now()->toISOString(),
        ];
    }
}