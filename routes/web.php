<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Web\{
    HomeController,
    AuthController,
    DashboardController,
    SubscriptionController,
    ReminderController,
    ProfileController,
    NotificationController,
};

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| 用户端Web路由配置
|
*/

// 首页和公共页面
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/about', [HomeController::class, 'about'])->name('about');
Route::get('/features', [HomeController::class, 'features'])->name('features');
Route::get('/pricing', [HomeController::class, 'pricing'])->name('pricing');
Route::get('/contact', [HomeController::class, 'contact'])->name('contact');

// 认证路由
Route::prefix('auth')->name('auth.')->group(function () {
    // 登录
    Route::get('login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('login', [AuthController::class, 'login'])->name('login.submit');
    
    // 注册
    Route::get('register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('register', [AuthController::class, 'register'])->name('register.submit');
    
    // 找回密码
    Route::get('forgot-password', [AuthController::class, 'showForgotPassword'])->name('forgot-password');
    Route::post('forgot-password', [AuthController::class, 'sendResetLink'])->name('forgot-password.submit');
    
    // 重置密码
    Route::get('reset-password/{token}', [AuthController::class, 'showResetPassword'])->name('reset-password');
    Route::post('reset-password', [AuthController::class, 'resetPassword'])->name('reset-password.submit');
    
    // 邮箱验证
    Route::get('email/verify', [AuthController::class, 'showVerifyEmail'])->name('verification.notice');
    Route::get('email/verify/{id}/{hash}', [AuthController::class, 'verifyEmail'])->name('verification.verify');
    Route::post('email/verification-notification', [AuthController::class, 'sendVerificationEmail'])->name('verification.send');
    
    // 登出
    Route::post('logout', [AuthController::class, 'logout'])->name('logout');
});

// 需要认证的路由
Route::middleware(['auth', 'verified'])->group(function () {
    
    // 仪表板
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/statistics', [DashboardController::class, 'statistics'])->name('dashboard.statistics');
    Route::get('/dashboard/chart-data', [DashboardController::class, 'chartData'])->name('dashboard.chart-data');
    
    // 订阅管理
    Route::prefix('subscriptions')->name('subscriptions.')->group(function () {
        Route::get('/', [SubscriptionController::class, 'index'])->name('index');
        Route::get('/create', [SubscriptionController::class, 'create'])->name('create');
        Route::post('/', [SubscriptionController::class, 'store'])->name('store');
        Route::get('/{subscription}', [SubscriptionController::class, 'show'])->name('show');
        Route::get('/{subscription}/edit', [SubscriptionController::class, 'edit'])->name('edit');
        Route::put('/{subscription}', [SubscriptionController::class, 'update'])->name('update');
        Route::delete('/{subscription}', [SubscriptionController::class, 'destroy'])->name('destroy');
        
        // 订阅操作
        Route::post('/{subscription}/toggle-status', [SubscriptionController::class, 'toggleStatus'])->name('toggle-status');
        Route::post('/{subscription}/renew', [SubscriptionController::class, 'renew'])->name('renew');
        Route::post('/{subscription}/cancel', [SubscriptionController::class, 'cancel'])->name('cancel');
        Route::post('/{subscription}/suspend', [SubscriptionController::class, 'suspend'])->name('suspend');
        Route::post('/{subscription}/resume', [SubscriptionController::class, 'resume'])->name('resume');
        
        // 批量操作
        Route::post('/batch/delete', [SubscriptionController::class, 'batchDelete'])->name('batch.delete');
        Route::post('/batch/update-status', [SubscriptionController::class, 'batchUpdateStatus'])->name('batch.update-status');
        
        // 导入导出
        Route::get('/export', [SubscriptionController::class, 'export'])->name('export');
        Route::post('/import', [SubscriptionController::class, 'import'])->name('import');
        Route::get('/import/template', [SubscriptionController::class, 'downloadTemplate'])->name('import.template');
        
        // 统计
        Route::get('/statistics', [SubscriptionController::class, 'statistics'])->name('statistics');
        Route::get('/calendar', [SubscriptionController::class, 'calendar'])->name('calendar');
    });
    
    // 提醒管理
    Route::prefix('reminders')->name('reminders.')->group(function () {
        Route::get('/', [ReminderController::class, 'index'])->name('index');
        Route::get('/create', [ReminderController::class, 'create'])->name('create');
        Route::post('/', [ReminderController::class, 'store'])->name('store');
        Route::get('/{reminder}', [ReminderController::class, 'show'])->name('show');
        Route::get('/{reminder}/edit', [ReminderController::class, 'edit'])->name('edit');
        Route::put('/{reminder}', [ReminderController::class, 'update'])->name('update');
        Route::delete('/{reminder}', [ReminderController::class, 'destroy'])->name('destroy');
        
        // 提醒操作
        Route::post('/{reminder}/toggle-status', [ReminderController::class, 'toggleStatus'])->name('toggle-status');
        Route::post('/{reminder}/test-send', [ReminderController::class, 'testSend'])->name('test-send');
        Route::post('/{reminder}/duplicate', [ReminderController::class, 'duplicate'])->name('duplicate');
        
        // 批量操作
        Route::post('/batch/delete', [ReminderController::class, 'batchDelete'])->name('batch.delete');
        Route::post('/batch/toggle-status', [ReminderController::class, 'batchToggleStatus'])->name('batch.toggle-status');
        
        // 提醒日志
        Route::get('/{reminder}/logs', [ReminderController::class, 'logs'])->name('logs');
        Route::get('/logs', [ReminderController::class, 'allLogs'])->name('all-logs');
    });
    
    // 通知管理
    Route::prefix('notifications')->name('notifications.')->group(function () {
        Route::get('/', [NotificationController::class, 'index'])->name('index');
        Route::post('/{notification}/read', [NotificationController::class, 'markAsRead'])->name('read');
        Route::post('/read-all', [NotificationController::class, 'markAllAsRead'])->name('read-all');
        Route::delete('/{notification}', [NotificationController::class, 'destroy'])->name('destroy');
        Route::delete('/clear-all', [NotificationController::class, 'clearAll'])->name('clear-all');
        
        // 通知设置
        Route::get('/settings', [NotificationController::class, 'settings'])->name('settings');
        Route::post('/settings', [NotificationController::class, 'updateSettings'])->name('settings.update');
        
        // 测试通知
        Route::post('/test', [NotificationController::class, 'testNotification'])->name('test');
    });
    
    // 个人资料
    Route::prefix('profile')->name('profile.')->group(function () {
        Route::get('/', [ProfileController::class, 'index'])->name('index');
        Route::get('/edit', [ProfileController::class, 'edit'])->name('edit');
        Route::put('/', [ProfileController::class, 'update'])->name('update');
        
        // 安全设置
        Route::get('/security', [ProfileController::class, 'security'])->name('security');
        Route::post('/change-password', [ProfileController::class, 'changePassword'])->name('change-password');
        Route::post('/enable-2fa', [ProfileController::class, 'enable2FA'])->name('enable-2fa');
        Route::post('/disable-2fa', [ProfileController::class, 'disable2FA'])->name('disable-2fa');
        
        // 头像上传
        Route::post('/avatar', [ProfileController::class, 'uploadAvatar'])->name('avatar');
        Route::delete('/avatar', [ProfileController::class, 'deleteAvatar'])->name('avatar.delete');
        
        // 账户管理
        Route::get('/account', [ProfileController::class, 'account'])->name('account');
        Route::post('/deactivate', [ProfileController::class, 'deactivate'])->name('deactivate');
        Route::delete('/delete', [ProfileController::class, 'deleteAccount'])->name('delete');
        
        // 数据导出
        Route::get('/export-data', [ProfileController::class, 'exportData'])->name('export-data');
        Route::post('/request-data-export', [ProfileController::class, 'requestDataExport'])->name('request-data-export');
    });
    
    // 帮助和支持
    Route::prefix('help')->name('help.')->group(function () {
        Route::get('/', [HomeController::class, 'help'])->name('index');
        Route::get('/faq', [HomeController::class, 'faq'])->name('faq');
        Route::get('/docs', [HomeController::class, 'docs'])->name('docs');
        Route::get('/support', [HomeController::class, 'support'])->name('support');
        Route::post('/support', [HomeController::class, 'submitSupport'])->name('support.submit');
    });
});

// Ajax路由（需要认证）
Route::middleware(['auth'])->prefix('ajax')->name('ajax.')->group(function () {
    // 搜索建议
    Route::get('/search/subscriptions', [SubscriptionController::class, 'searchSuggestions'])->name('search.subscriptions');
    Route::get('/search/services', [SubscriptionController::class, 'searchServices'])->name('search.services');
    
    // 获取订阅类型字段
    Route::get('/subscription-types/{type}/fields', [SubscriptionController::class, 'getTypeFields'])->name('subscription-types.fields');
    
    // 获取统计数据
    Route::get('/dashboard/quick-stats', [DashboardController::class, 'quickStats'])->name('dashboard.quick-stats');
    
    // 文件上传
    Route::post('/upload/image', [HomeController::class, 'uploadImage'])->name('upload.image');
    Route::post('/upload/file', [HomeController::class, 'uploadFile'])->name('upload.file');
});

// Webhook路由（不需要认证）
Route::prefix('webhooks')->name('webhooks.')->group(function () {
    Route::post('/feishu', [NotificationController::class, 'feishuWebhook'])->name('feishu');
    Route::post('/wechat', [NotificationController::class, 'wechatWebhook'])->name('wechat');
    Route::post('/payment/{provider}', [HomeController::class, 'paymentWebhook'])->name('payment');
});

// 公共API路由（不需要认证）
Route::prefix('public')->name('public.')->group(function () {
    Route::get('/subscription-types', [SubscriptionController::class, 'getPublicTypes'])->name('subscription-types');
    Route::get('/system-info', [HomeController::class, 'systemInfo'])->name('system-info');
});

// 开发环境路由
if (app()->environment('local')) {
    Route::prefix('dev')->name('dev.')->group(function () {
        Route::get('/test-mail', function () {
            return 'Mail test route';
        })->name('test-mail');
        
        Route::get('/test-notification', function () {
            return 'Notification test route';
        })->name('test-notification');
    });
}