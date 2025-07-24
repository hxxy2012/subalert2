<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use App\Http\Responses\ApiResponse;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * 不需要报告的异常类型
     *
     * @var array<int, class-string<Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * 不需要闪存到session的输入键
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * 注册异常处理回调
     *
     * @return void
     */
    public function register()
    {
        $this->reportable(function (Throwable $e) {
            // 自定义异常报告逻辑
            $this->logException($e);
        });

        $this->renderable(function (Throwable $e, Request $request) {
            // API请求的异常处理
            if ($request->is('api/*') || $request->expectsJson()) {
                return $this->handleApiException($e, $request);
            }

            // 管理后台的异常处理
            if ($request->is('admin/*')) {
                return $this->handleAdminException($e, $request);
            }
        });
    }

    /**
     * 处理API异常
     */
    protected function handleApiException(Throwable $e, Request $request): JsonResponse
    {
        // 验证异常
        if ($e instanceof ValidationException) {
            return ApiResponse::validationError(
                $e->errors(),
                $e->getMessage() ?: '验证失败'
            );
        }

        // 认证异常
        if ($e instanceof AuthenticationException) {
            return ApiResponse::unauthorized($e->getMessage() ?: '未认证');
        }

        // 授权异常
        if ($e instanceof AuthorizationException) {
            return ApiResponse::forbidden($e->getMessage() ?: '无权限访问');
        }

        // 模型未找到异常
        if ($e instanceof ModelNotFoundException) {
            return ApiResponse::notFound('请求的资源不存在');
        }

        // 路由未找到异常
        if ($e instanceof NotFoundHttpException) {
            return ApiResponse::notFound('请求的接口不存在');
        }

        // 方法不允许异常
        if ($e instanceof MethodNotAllowedHttpException) {
            return ApiResponse::error('请求方法不允许', 405);
        }

        // 请求频率限制异常
        if ($e instanceof TooManyRequestsHttpException) {
            return ApiResponse::tooManyRequests('请求过于频繁，请稍后再试');
        }

        // 数据库异常
        if ($this->isDatabaseException($e)) {
            return $this->handleDatabaseException($e);
        }

        // 自定义业务异常
        if ($e instanceof \App\Exceptions\BusinessException) {
            return ApiResponse::error($e->getMessage(), $e->getCode() ?: 400);
        }

        // 其他异常
        return $this->handleGenericException($e);
    }

    /**
     * 处理管理后台异常
     */
    protected function handleAdminException(Throwable $e, Request $request)
    {
        // 如果是AJAX请求，返回JSON响应
        if ($request->ajax() || $request->expectsJson()) {
            return $this->handleApiException($e, $request);
        }

        // 认证异常重定向到管理员登录页
        if ($e instanceof AuthenticationException) {
            return redirect()->guest(route('admin.login'));
        }

        // 其他异常使用默认处理
        return null;
    }

    /**
     * 处理数据库异常
     */
    protected function handleDatabaseException(Throwable $e): JsonResponse
    {
        $message = '数据库操作失败';
        $code = 500;

        // 根据异常类型提供更具体的错误信息
        if (str_contains($e->getMessage(), 'Duplicate entry')) {
            $message = '数据已存在，请检查重复项';
            $code = 409;
        } elseif (str_contains($e->getMessage(), 'foreign key constraint')) {
            $message = '存在关联数据，无法删除';
            $code = 409;
        } elseif (str_contains($e->getMessage(), 'Connection refused')) {
            $message = '数据库连接失败';
            $code = 503;
        }

        // 开发环境显示详细错误
        if (app()->environment('local', 'testing')) {
            $message .= ': ' . $e->getMessage();
        }

        return ApiResponse::error($message, $code);
    }

    /**
     * 处理通用异常
     */
    protected function handleGenericException(Throwable $e): JsonResponse
    {
        $code = method_exists($e, 'getStatusCode') ? $e->getStatusCode() : 500;
        $message = '服务器内部错误';

        // 开发环境显示详细错误信息
        if (app()->environment('local', 'testing')) {
            $message = $e->getMessage();
            
            return ApiResponse::debug(null, [
                'exception' => get_class($e),
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ], $message);
        }

        return ApiResponse::error($message, $code);
    }

    /**
     * 判断是否为数据库异常
     */
    protected function isDatabaseException(Throwable $e): bool
    {
        return $e instanceof \Illuminate\Database\QueryException ||
               $e instanceof \PDOException ||
               str_contains(get_class($e), 'Database');
    }

    /**
     * 记录异常日志
     */
    protected function logException(Throwable $e): void
    {
        // 获取请求信息
        $request = request();
        $context = [
            'exception' => get_class($e),
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'code' => $e->getCode(),
        ];

        if ($request) {
            $context['request'] = [
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'user_id' => auth()->id(),
            ];

            // 记录请求参数（排除敏感信息）
            $input = $request->except([
                'password',
                'password_confirmation',
                'current_password',
                'token',
                'api_token',
            ]);
            
            if (!empty($input)) {
                $context['request']['input'] = $input;
            }
        }

        // 根据异常类型选择不同的日志级别
        $level = $this->getLogLevel($e);
        
        \Log::log($level, "Exception: {$e->getMessage()}", $context);

        // 特殊异常发送通知
        if ($this->shouldNotify($e)) {
            $this->sendExceptionNotification($e, $context);
        }
    }

    /**
     * 获取日志级别
     */
    protected function getLogLevel(Throwable $e): string
    {
        // 验证异常和认证异常使用info级别
        if ($e instanceof ValidationException || 
            $e instanceof AuthenticationException || 
            $e instanceof AuthorizationException) {
            return 'info';
        }

        // 404异常使用warning级别
        if ($e instanceof NotFoundHttpException || 
            $e instanceof ModelNotFoundException) {
            return 'warning';
        }

        // 其他异常使用error级别
        return 'error';
    }

    /**
     * 判断是否需要发送通知
     */
    protected function shouldNotify(Throwable $e): bool
    {
        // 生产环境的严重错误需要通知
        if (!app()->environment('production')) {
            return false;
        }

        // 数据库异常需要通知
        if ($this->isDatabaseException($e)) {
            return true;
        }

        // 5xx错误需要通知
        $code = method_exists($e, 'getStatusCode') ? $e->getStatusCode() : 500;
        return $code >= 500;
    }

    /**
     * 发送异常通知
     */
    protected function sendExceptionNotification(Throwable $e, array $context): void
    {
        try {
            // 这里可以集成钉钉、飞书、邮件等通知方式
            // 示例：发送到日志监控系统
            \Log::channel('alert')->critical('严重异常需要关注', [
                'exception' => get_class($e),
                'message' => $e->getMessage(),
                'context' => $context,
                'environment' => app()->environment(),
                'timestamp' => now()->toISOString(),
            ]);
        } catch (Throwable $notificationException) {
            // 通知发送失败，记录到默认日志
            \Log::error('异常通知发送失败', [
                'original_exception' => $e->getMessage(),
                'notification_exception' => $notificationException->getMessage(),
            ]);
        }
    }

    /**
     * 自定义渲染认证异常
     */
    protected function unauthenticated($request, AuthenticationException $exception)
    {
        if ($request->expectsJson() || $request->is('api/*')) {
            return ApiResponse::unauthorized('认证失败，请先登录');
        }

        // 管理后台重定向到管理员登录页
        if ($request->is('admin/*')) {
            return redirect()->guest(route('admin.login'));
        }

        // 用户端重定向到用户登录页
        return redirect()->guest(route('auth.login'));
    }

    /**
     * 自定义验证异常响应
     */
    protected function invalidJson($request, ValidationException $exception)
    {
        return ApiResponse::validationError(
            $exception->errors(),
            $exception->getMessage()
        );
    }

    /**
     * 转换验证异常为响应
     */
    protected function convertValidationExceptionToResponse(ValidationException $e, $request)
    {
        if ($e->response) {
            return $e->response;
        }

        if ($request->expectsJson() || $request->is('api/*')) {
            return $this->invalidJson($request, $e);
        }

        return $this->invalid($request, $e);
    }

    /**
     * 准备异常响应
     */
    protected function prepareResponse($request, Throwable $e)
    {
        if (!$this->isHttpException($e) && config('app.debug')) {
            return $this->toIlluminateResponse($this->convertExceptionToResponse($e), $e);
        }

        if (!$this->isHttpException($e)) {
            $e = new \Symfony\Component\HttpKernel\Exception\HttpException(500, $e->getMessage());
        }

        return $this->toIlluminateResponse(
            $this->renderHttpException($e), $e
        );
    }

    /**
     * 获取异常的HTTP状态码
     */
    protected function getHttpStatusCode(Throwable $e): int
    {
        if (method_exists($e, 'getStatusCode')) {
            return $e->getStatusCode();
        }

        if ($e instanceof ValidationException) {
            return 422;
        }

        if ($e instanceof AuthenticationException) {
            return 401;
        }

        if ($e instanceof AuthorizationException) {
            return 403;
        }

        if ($e instanceof ModelNotFoundException || $e instanceof NotFoundHttpException) {
            return 404;
        }

        if ($e instanceof MethodNotAllowedHttpException) {
            return 405;
        }

        if ($e instanceof TooManyRequestsHttpException) {
            return 429;
        }

        return 500;
    }

    /**
     * 获取用户友好的错误消息
     */
    protected function getUserFriendlyMessage(Throwable $e): string
    {
        $messages = [
            ValidationException::class => '提交的数据不符合要求',
            AuthenticationException::class => '请先登录后再操作',
            AuthorizationException::class => '您没有权限执行此操作',
            ModelNotFoundException::class => '请求的数据不存在',
            NotFoundHttpException::class => '请求的页面不存在',
            MethodNotAllowedHttpException::class => '请求方法不被允许',
            TooManyRequestsHttpException::class => '请求过于频繁，请稍后再试',
        ];

        $exceptionClass = get_class($e);
        
        if (isset($messages[$exceptionClass])) {
            return $messages[$exceptionClass];
        }

        // 数据库相关错误
        if ($this->isDatabaseException($e)) {
            if (str_contains($e->getMessage(), 'Duplicate entry')) {
                return '数据已存在';
            }
            if (str_contains($e->getMessage(), 'foreign key constraint')) {
                return '存在关联数据，无法删除';
            }
            return '数据操作失败';
        }

        return '系统暂时无法处理您的请求';
    }

    /**
     * 检查是否为可忽略的异常
     */
    protected function shouldIgnoreException(Throwable $e): bool
    {
        $ignoredExceptions = [
            \Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class,
            \Illuminate\Session\TokenMismatchException::class,
        ];

        return in_array(get_class($e), $ignoredExceptions);
    }
}
            