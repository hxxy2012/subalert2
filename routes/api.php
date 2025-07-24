<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\{
    AuthController,
    UserController,
    SubscriptionController,
    SubscriptionTypeController,
    ReminderController,
    ReminderLogController,
    NotificationController,
    StatisticsController,
    SystemController,
};

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| API路由配置 - RESTful API
|
*/

$apiPrefix = config('subalert.app.api_prefix', 'api/v1');

// API版本1
Route::prefix('v1')->name('api.v1.')->group(function () {
    
    // 公开API（不需要认证）
    Route::prefix('public')->name('public.')->group(function () {
        // 系统信息
        Route::get('/system/info', [SystemController::class, 'info'])->name('system.info');
        Route::get('/system/status', [SystemController::class, 'status'])->name('system.status');
        
        // 订阅类型
        Route::get('/subscription-types', [SubscriptionTypeController::class, 'public'])->name('subscription-types');
        
        // 服务目录
        Route::get('/services', [SubscriptionController::class, 'publicServices'])->name('services');
        Route::get('/services/search', [SubscriptionController::class, 'searchServices'])->name('services.search');
    });
    
    // 认证相关API
    Route::prefix('auth')->name('auth.')->group(function () {
        // 登录注册
        Route::post('/register', [AuthController::class, 'register'])->name('register');
        Route::post('/login', [AuthController::class, 'login'])->name('login');
        Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum')->name('logout');
        
        // 密码重置
        Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])->name('forgot-password');
        Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('reset-password');
        
        // 邮箱验证
        Route::post('/email/verification-notification', [AuthController::class, 'sendVerificationEmail'])->middleware('auth:sanctum')->name('verification.send');
        Route::get('/email/verify/{id}/{hash}', [AuthController::class, 'verifyEmail'])->name('verification.verify');
        
        // Token刷新
        Route::post('/refresh', [AuthController::class, 'refresh'])->middleware('auth:sanctum')->name('refresh');
        
        // 用户信息
        Route::get('/me', [AuthController::class, 'me'])->middleware('auth:sanctum')->name('me');
    });
    
    // 需要认证的API
    Route::middleware(['auth:sanctum', 'verified'])->group(function () {
        
        // 用户管理
        Route::prefix('user')->name('user.')->group(function () {
            Route::get('/profile', [UserController::class, 'profile'])->name('profile');
            Route::put('/profile', [UserController::class, 'updateProfile'])->name('profile.update');
            Route::post('/change-password', [UserController::class, 'changePassword'])->name('change-password');
            Route::post('/upload-avatar', [UserController::class, 'uploadAvatar'])->name('upload-avatar');
            Route::delete('/avatar', [UserController::class, 'deleteAvatar'])->name('delete-avatar');
            
            // 通知设置
            Route::get('/notification-settings', [UserController::class, 'getNotificationSettings'])->name('notification-settings');
            Route::put('/notification-settings', [UserController::class, 'updateNotificationSettings'])->name('notification-settings.update');
            
            // 用户统计
            Route::get('/statistics', [UserController::class, 'statistics'])->name('statistics');
            Route::get('/dashboard', [UserController::class, 'dashboard'])->name('dashboard');
        });
        
        // 订阅管理
        Route::apiResource('subscriptions', SubscriptionController::class);
        Route::prefix('subscriptions')->name('subscriptions.')->group(function () {
            // 订阅操作
            Route::post('/{subscription}/toggle-status', [SubscriptionController::class, 'toggleStatus'])->name('toggle-status');
            Route::post('/{subscription}/renew', [SubscriptionController::class, 'renew'])->name('renew');
            Route::post('/{subscription}/cancel', [SubscriptionController::class, 'cancel'])->name('cancel');
            Route::post('/{subscription}/suspend', [SubscriptionController::class, 'suspend'])->name('suspend');
            Route::post('/{subscription}/resume', [SubscriptionController::class, 'resume'])->name('resume');
            
            // 批量操作
            Route::post('/batch/update-status', [SubscriptionController::class, 'batchUpdateStatus'])->name('batch.update-status');
            Route::post('/batch/delete', [SubscriptionController::class, 'batchDelete'])->name('batch.delete');
            
            // 导入导出
            Route::post('/import', [SubscriptionController::class, 'import'])->name('import');
            Route::get('/export', [SubscriptionController::class, 'export'])->name('export');
            
            // 统计分析
            Route::get('/statistics', [SubscriptionController::class, 'statistics'])->name('statistics');
            Route::get('/calendar', [SubscriptionController::class, 'calendar'])->name('calendar');
            Route::get('/upcoming', [SubscriptionController::class, 'upcoming'])->name('upcoming');
            Route::get('/expired', [SubscriptionController::class, 'expired'])->name('expired');
            
            // 搜索和过滤
            Route::get('/search', [SubscriptionController::class, 'search'])->name('search');
            Route::get('/filter', [SubscriptionController::class, 'filter'])->name('filter');
        });
        
        // 订阅类型
        Route::get('/subscription-types', [SubscriptionTypeController::class, 'index'])->name('subscription-types.index');
        Route::get('/subscription-types/{subscriptionType}', [SubscriptionTypeController::class, 'show'])->name('subscription-types.show');
        Route::get('/subscription-types/{subscriptionType}/fields', [SubscriptionTypeController::class, 'fields'])->name('subscription-types.fields');
        
        // 提醒管理
        Route::apiResource('reminders', ReminderController::class);
        Route::prefix('reminders')->name('reminders.')->group(function () {
            // 提醒操作
            Route::post('/{reminder}/toggle-status', [ReminderController::class, 'toggleStatus'])->name('toggle-status');
            Route::post('/{reminder}/test-send', [ReminderController::class, 'testSend'])->name('test-send');
            Route::post('/{reminder}/duplicate', [ReminderController::class, 'duplicate'])->name('duplicate');
            
            // 批量操作
            Route::post('/batch/toggle-status', [ReminderController::class, 'batchToggleStatus'])->name('batch.toggle-status');
            Route::post('/batch/delete', [ReminderController::class, 'batchDelete'])->name('batch.delete');
            
            // 提醒日志
            Route::get('/{reminder}/logs', [ReminderController::class, 'logs'])->name('logs');
        });
        
        // 提醒日志
        Route::prefix('reminder-logs')->name('reminder-logs.')->group(function () {
            Route::get('/', [ReminderLogController::class, 'index'])->name('index');
            Route::get('/{reminderLog}', [ReminderLogController::class, 'show'])->name('show');
            Route::post('/{reminderLog}/retry', [ReminderLogController::class, 'retry'])->name('retry');
            
            // 统计分析
            Route::get('/statistics', [ReminderLogController::class, 'statistics'])->name('statistics');
            Route::get('/success-rate', [ReminderLogController::class, 'successRate'])->name('success-rate');
        });
        
        // 通知管理
        Route::prefix('notifications')->name('notifications.')->group(function () {
            Route::get('/', [NotificationController::class, 'index'])->name('index');
            Route::get('/unread', [NotificationController::class, 'unread'])->name('unread');
            Route::post('/{notification}/read', [NotificationController::class, 'markAsRead'])->name('read');
            Route::post('/read-all', [NotificationController::class, 'markAllAsRead'])->name('read-all');
            Route::delete('/{notification}', [NotificationController::class, 'destroy'])->name('destroy');
            Route::delete('/clear-all', [NotificationController::class, 'clearAll'])->name('clear-all');
            
            // 测试通知
            Route::post('/test', [NotificationController::class, 'testNotification'])->name('test');
            
            // 通知统计
            Route::get('/statistics', [NotificationController::class, 'statistics'])->name('statistics');
        });
        
        // 统计分析
        Route::prefix('statistics')->name('statistics.')->group(function () {
            Route::get('/overview', [StatisticsController::class, 'overview'])->name('overview');
            Route::get('/subscriptions', [StatisticsController::class, 'subscriptions'])->name('subscriptions');
            Route::get('/reminders', [StatisticsController::class, 'reminders'])->name('reminders');
            Route::get('/spending', [StatisticsController::class, 'spending'])->name('spending');
            Route::get('/trends', [StatisticsController::class, 'trends'])->name('trends');
            
            // 图表数据
            Route::get('/charts/subscription-status', [StatisticsController::class, 'subscriptionStatusChart'])->name('charts.subscription-status');
            Route::get('/charts/spending-trend', [StatisticsController::class, 'spendingTrendChart'])->name('charts.spending-trend');
            Route::get('/charts/reminder-success', [StatisticsController::class, 'reminderSuccessChart'])->name('charts.reminder-success');
            Route::get('/charts/subscription-types', [StatisticsController::class, 'subscriptionTypesChart'])->name('charts.subscription-types');
            
            // 报表生成
            Route::post('/reports/generate', [StatisticsController::class, 'generateReport'])->name('reports.generate');
            Route::get('/reports/{report}/download', [StatisticsController::class, 'downloadReport'])->name('reports.download');
        });
        
        // 文件上传
        Route::prefix('upload')->name('upload.')->group(function () {
            Route::post('/image', [SystemController::class, 'uploadImage'])->name('image');
            Route::post('/file', [SystemController::class, 'uploadFile'])->name('file');
            Route::post('/avatar', [SystemController::class, 'uploadAvatar'])->name('avatar');
            Route::post('/logo', [SystemController::class, 'uploadLogo'])->name('logo');
        });
        
        // 搜索API
        Route::prefix('search')->name('search.')->group(function () {
            Route::get('/global', [SystemController::class, 'globalSearch'])->name('global');
            Route::get('/subscriptions', [SubscriptionController::class, 'search'])->name('subscriptions');
            Route::get('/reminders', [ReminderController::class, 'search'])->name('reminders');
            Route::get('/suggestions', [SystemController::class, 'searchSuggestions'])->name('suggestions');
        });
    });
});

// 管理员API
Route::prefix('v1/admin')->name('api.v1.admin.')->middleware(['auth:sanctum', 'admin'])->group(function () {
    
    // 管理员认证
    Route::prefix('auth')->name('auth.')->group(function () {
        Route::post('/login', [AuthController::class, 'adminLogin'])->withoutMiddleware(['auth:sanctum', 'admin'])->name('login');
        Route::post('/logout', [AuthController::class, 'adminLogout'])->name('logout');
        Route::get('/me', [AuthController::class, 'adminMe'])->name('me');
    });
    
    // 用户管理
    Route::apiResource('users', UserController::class, ['except' => ['store', 'update']]);
    Route::prefix('users')->name('users.')->group(function () {
        Route::post('/', [UserController::class, 'adminStore'])->name('store');
        Route::put('/{user}', [UserController::class, 'adminUpdate'])->name('update');
        Route::post('/{user}/toggle-status', [UserController::class, 'toggleStatus'])->name('toggle-status');
        Route::post('/{user}/reset-password', [UserController::class, 'resetPassword'])->name('reset-password');
        Route::post('/batch/delete', [UserController::class, 'batchDelete'])->name('batch.delete');
        Route::post('/batch/update-status', [UserController::class, 'batchUpdateStatus'])->name('batch.update-status');
        Route::get('/{user}/subscriptions', [UserController::class, 'userSubscriptions'])->name('subscriptions');
        Route::get('/{user}/statistics', [UserController::class, 'userStatistics'])->name('statistics');
    });
    
    // 订阅管理
    Route::apiResource('subscriptions', SubscriptionController::class);
    Route::prefix('subscriptions')->name('subscriptions.')->group(function () {
        Route::post('/{subscription}/toggle-status', [SubscriptionController::class, 'adminToggleStatus'])->name('toggle-status');
        Route::post('/{subscription}/extend', [SubscriptionController::class, 'extend'])->name('extend');
        Route::post('/{subscription}/transfer', [SubscriptionController::class, 'transfer'])->name('transfer');
        Route::post('/batch/delete', [SubscriptionController::class, 'batchDelete'])->name('batch.delete');
        Route::post('/batch/extend', [SubscriptionController::class, 'batchExtend'])->name('batch.extend');
        Route::get('/analytics', [SubscriptionController::class, 'analytics'])->name('analytics');
    });
    
    // 订阅类型管理
    Route::apiResource('subscription-types', SubscriptionTypeController::class);
    Route::prefix('subscription-types')->name('subscription-types.')->group(function () {
        Route::post('/{subscriptionType}/toggle-status', [SubscriptionTypeController::class, 'toggleStatus'])->name('toggle-status');
        Route::post('/{subscriptionType}/duplicate', [SubscriptionTypeController::class, 'duplicate'])->name('duplicate');
        Route::post('/reorder', [SubscriptionTypeController::class, 'reorder'])->name('reorder');
    });
    
    // 提醒管理
    Route::apiResource('reminders', ReminderController::class, ['except' => ['store']]);
    Route::prefix('reminders')->name('reminders.')->group(function () {
        Route::post('/{reminder}/toggle-status', [ReminderController::class, 'adminToggleStatus'])->name('toggle-status');
        Route::post('/{reminder}/force-send', [ReminderController::class, 'forceSend'])->name('force-send');
        Route::post('/batch/delete', [ReminderController::class, 'batchDelete'])->name('batch.delete');
    });
    
    // 提醒日志管理
    Route::prefix('reminder-logs')->name('reminder-logs.')->group(function () {
        Route::get('/', [ReminderLogController::class, 'adminIndex'])->name('index');
        Route::get('/{reminderLog}', [ReminderLogController::class, 'adminShow'])->name('show');
        Route::post('/{reminderLog}/retry', [ReminderLogController::class, 'adminRetry'])->name('retry');
        Route::post('/batch/retry', [ReminderLogController::class, 'batchRetry'])->name('batch.retry');
        Route::post('/batch/delete', [ReminderLogController::class, 'batchDelete'])->name('batch.delete');
        Route::get('/statistics', [ReminderLogController::class, 'adminStatistics'])->name('statistics');
    });
    
    // 系统统计
    Route::prefix('statistics')->name('statistics.')->group(function () {
        Route::get('/dashboard', [StatisticsController::class, 'adminDashboard'])->name('dashboard');
        Route::get('/overview', [StatisticsController::class, 'adminOverview'])->name('overview');
        Route::get('/users', [StatisticsController::class, 'userStatistics'])->name('users');
        Route::get('/subscriptions', [StatisticsController::class, 'adminSubscriptionStatistics'])->name('subscriptions');
        Route::get('/reminders', [StatisticsController::class, 'adminReminderStatistics'])->name('reminders');
        Route::get('/revenue', [StatisticsController::class, 'revenueStatistics'])->name('revenue');
        
        // 图表数据
        Route::get('/charts/{type}', [StatisticsController::class, 'adminChartData'])->name('charts');
    });
    
    // 系统管理
    Route::prefix('system')->name('system.')->group(function () {
        Route::get('/info', [SystemController::class, 'adminSystemInfo'])->name('info');
        Route::get('/health', [SystemController::class, 'healthCheck'])->name('health');
        Route::post('/cache/clear', [SystemController::class, 'clearCache'])->name('cache.clear');
        Route::post('/optimize', [SystemController::class, 'optimize'])->name('optimize');
        
        // 备份管理
        Route::get('/backups', [SystemController::class, 'backups'])->name('backups');
        Route::post('/backups/create', [SystemController::class, 'createBackup'])->name('backups.create');
        Route::delete('/backups/{backup}', [SystemController::class, 'deleteBackup'])->name('backups.delete');
        
        // 日志管理
        Route::get('/logs', [SystemController::class, 'logs'])->name('logs');
        Route::get('/logs/{logFile}', [SystemController::class, 'viewLog'])->name('logs.view');
        Route::delete('/logs/{logFile}', [SystemController::class, 'deleteLog'])->name('logs.delete');
    });
});

// Webhook API（不需要认证）
Route::prefix('v1/webhooks')->name('api.v1.webhooks.')->group(function () {
    Route::post('/feishu', [NotificationController::class, 'feishuWebhook'])->name('feishu');
    Route::post('/wechat', [NotificationController::class, 'wechatWebhook'])->name('wechat');
    Route::post('/payment/{provider}', [SystemController::class, 'paymentWebhook'])->name('payment');
    Route::post('/notification/delivery', [NotificationController::class, 'deliveryWebhook'])->name('notification.delivery');
});

// API限流中间件
Route::middleware(['throttle:api'])->group(function () {
    // 公开API有更严格的限流
    Route::middleware(['throttle:60,1'])->group(function () {
        // 这里放置需要严格限流的公开API
    });
});

// API健康检查
Route::get('/health', [SystemController::class, 'health'])->name('health');
Route::get('/status', [SystemController::class, 'status'])->name('status');