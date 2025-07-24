<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\{
    DashboardController,
    AuthController,
    UserController,
    AdminUserController,
    SubscriptionController,
    SubscriptionTypeController,
    ReminderController,
    ReminderLogController,
    SystemSettingController,
    StatisticsController,
    BackupController,
    LogController,
    SecurityController,
};

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
|
| 管理后台路由配置
|
*/

$adminPrefix = config('subalert.app.admin_prefix', 'admin');

// 管理员认证路由（不需要认证）
Route::prefix($adminPrefix)->name('admin.')->group(function () {
    Route::get('/', [AuthController::class, 'showLogin'])->name('login');
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login.form');
    Route::post('/login', [AuthController::class, 'login'])->name('login.submit');
    Route::get('/forgot-password', [AuthController::class, 'showForgotPassword'])->name('forgot-password');
    Route::post('/forgot-password', [AuthController::class, 'sendResetLink'])->name('forgot-password.submit');
    Route::get('/reset-password/{token}', [AuthController::class, 'showResetPassword'])->name('reset-password');
    Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('reset-password.submit');
});

// 需要管理员认证的路由
Route::prefix($adminPrefix)->name('admin.')->middleware(['auth:admin'])->group(function () {
    
    // 登出
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    
    // 仪表板
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/overview', [DashboardController::class, 'overview'])->name('dashboard.overview');
    Route::get('/dashboard/recent-activities', [DashboardController::class, 'recentActivities'])->name('dashboard.recent-activities');
    
    // 用户管理
    Route::prefix('users')->name('users.')->group(function () {
        Route::get('/', [UserController::class, 'index'])->name('index');
        Route::get('/create', [UserController::class, 'create'])->name('create');
        Route::post('/', [UserController::class, 'store'])->name('store');
        Route::get('/{user}', [UserController::class, 'show'])->name('show');
        Route::get('/{user}/edit', [UserController::class, 'edit'])->name('edit');
        Route::put('/{user}', [UserController::class, 'update'])->name('update');
        Route::delete('/{user}', [UserController::class, 'destroy'])->name('destroy');
        
        // 用户操作
        Route::post('/{user}/toggle-status', [UserController::class, 'toggleStatus'])->name('toggle-status');
        Route::post('/{user}/reset-password', [UserController::class, 'resetPassword'])->name('reset-password');
        Route::post('/{user}/send-verification', [UserController::class, 'sendVerification'])->name('send-verification');
        Route::post('/{user}/impersonate', [UserController::class, 'impersonate'])->name('impersonate');
        
        // 批量操作
        Route::post('/batch/delete', [UserController::class, 'batchDelete'])->name('batch.delete');
        Route::post('/batch/update-status', [UserController::class, 'batchUpdateStatus'])->name('batch.update-status');
        Route::post('/batch/export', [UserController::class, 'batchExport'])->name('batch.export');
        
        // 用户统计
        Route::get('/{user}/statistics', [UserController::class, 'statistics'])->name('statistics');
        Route::get('/{user}/subscriptions', [UserController::class, 'subscriptions'])->name('subscriptions');
        Route::get('/{user}/activity-log', [UserController::class, 'activityLog'])->name('activity-log');
    });
    
    // 管理员管理
    Route::prefix('admin-users')->name('admin-users.')->middleware('permission:manage_admins')->group(function () {
        Route::get('/', [AdminUserController::class, 'index'])->name('index');
        Route::get('/create', [AdminUserController::class, 'create'])->name('create');
        Route::post('/', [AdminUserController::class, 'store'])->name('store');
        Route::get('/{adminUser}', [AdminUserController::class, 'show'])->name('show');
        Route::get('/{adminUser}/edit', [AdminUserController::class, 'edit'])->name('edit');
        Route::put('/{adminUser}', [AdminUserController::class, 'update'])->name('update');
        Route::delete('/{adminUser}', [AdminUserController::class, 'destroy'])->name('destroy');
        
        // 管理员操作
        Route::post('/{adminUser}/toggle-status', [AdminUserController::class, 'toggleStatus'])->name('toggle-status');
        Route::post('/{adminUser}/reset-password', [AdminUserController::class, 'resetPassword'])->name('reset-password');
        
        // 权限管理
        Route::get('/{adminUser}/permissions', [AdminUserController::class, 'permissions'])->name('permissions');
        Route::post('/{adminUser}/permissions', [AdminUserController::class, 'updatePermissions'])->name('permissions.update');
        
        // 角色管理
        Route::get('/roles', [AdminUserController::class, 'roles'])->name('roles');
        Route::post('/roles', [AdminUserController::class, 'createRole'])->name('roles.create');
        Route::put('/roles/{role}', [AdminUserController::class, 'updateRole'])->name('roles.update');
        Route::delete('/roles/{role}', [AdminUserController::class, 'deleteRole'])->name('roles.delete');
    });
    
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
        Route::post('/{subscription}/extend', [SubscriptionController::class, 'extend'])->name('extend');
        Route::post('/{subscription}/transfer', [SubscriptionController::class, 'transfer'])->name('transfer');
        
        // 批量操作
        Route::post('/batch/delete', [SubscriptionController::class, 'batchDelete'])->name('batch.delete');
        Route::post('/batch/update-status', [SubscriptionController::class, 'batchUpdateStatus'])->name('batch.update-status');
        Route::post('/batch/extend', [SubscriptionController::class, 'batchExtend'])->name('batch.extend');
        
        // 导出导入
        Route::get('/export', [SubscriptionController::class, 'export'])->name('export');
        Route::post('/import', [SubscriptionController::class, 'import'])->name('import');
        
        // 统计分析
        Route::get('/analytics', [SubscriptionController::class, 'analytics'])->name('analytics');
        Route::get('/reports', [SubscriptionController::class, 'reports'])->name('reports');
    });
    
    // 订阅类型管理
    Route::prefix('subscription-types')->name('subscription-types.')->group(function () {
        Route::get('/', [SubscriptionTypeController::class, 'index'])->name('index');
        Route::get('/create', [SubscriptionTypeController::class, 'create'])->name('create');
        Route::post('/', [SubscriptionTypeController::class, 'store'])->name('store');
        Route::get('/{subscriptionType}', [SubscriptionTypeController::class, 'show'])->name('show');
        Route::get('/{subscriptionType}/edit', [SubscriptionTypeController::class, 'edit'])->name('edit');
        Route::put('/{subscriptionType}', [SubscriptionTypeController::class, 'update'])->name('update');
        Route::delete('/{subscriptionType}', [SubscriptionTypeController::class, 'destroy'])->name('destroy');
        
        // 类型操作
        Route::post('/{subscriptionType}/toggle-status', [SubscriptionTypeController::class, 'toggleStatus'])->name('toggle-status');
        Route::post('/{subscriptionType}/duplicate', [SubscriptionTypeController::class, 'duplicate'])->name('duplicate');
        Route::post('/reorder', [SubscriptionTypeController::class, 'reorder'])->name('reorder');
        
        // 字段管理
        Route::get('/{subscriptionType}/fields', [SubscriptionTypeController::class, 'fields'])->name('fields');
        Route::post('/{subscriptionType}/fields', [SubscriptionTypeController::class, 'updateFields'])->name('fields.update');
    });
    
    // 提醒管理
    Route::prefix('reminders')->name('reminders.')->group(function () {
        Route::get('/', [ReminderController::class, 'index'])->name('index');
        Route::get('/{reminder}', [ReminderController::class, 'show'])->name('show');
        Route::get('/{reminder}/edit', [ReminderController::class, 'edit'])->name('edit');
        Route::put('/{reminder}', [ReminderController::class, 'update'])->name('update');
        Route::delete('/{reminder}', [ReminderController::class, 'destroy'])->name('destroy');
        
        // 提醒操作
        Route::post('/{reminder}/toggle-status', [ReminderController::class, 'toggleStatus'])->name('toggle-status');
        Route::post('/{reminder}/test-send', [ReminderController::class, 'testSend'])->name('test-send');
        Route::post('/{reminder}/force-send', [ReminderController::class, 'forceSend'])->name('force-send');
        
        // 批量操作
        Route::post('/batch/delete', [ReminderController::class, 'batchDelete'])->name('batch.delete');
        Route::post('/batch/toggle-status', [ReminderController::class, 'batchToggleStatus'])->name('batch.toggle-status');
        
        // 提醒模板
        Route::get('/templates', [ReminderController::class, 'templates'])->name('templates');
        Route::post('/templates', [ReminderController::class, 'createTemplate'])->name('templates.create');
        Route::put('/templates/{template}', [ReminderController::class, 'updateTemplate'])->name('templates.update');
        Route::delete('/templates/{template}', [ReminderController::class, 'deleteTemplate'])->name('templates.delete');
    });
    
    // 提醒日志
    Route::prefix('reminder-logs')->name('reminder-logs.')->group(function () {
        Route::get('/', [ReminderLogController::class, 'index'])->name('index');
        Route::get('/{reminderLog}', [ReminderLogController::class, 'show'])->name('show');
        Route::delete('/{reminderLog}', [ReminderLogController::class, 'destroy'])->name('destroy');
        
        // 日志操作
        Route::post('/{reminderLog}/retry', [ReminderLogController::class, 'retry'])->name('retry');
        Route::post('/batch/retry', [ReminderLogController::class, 'batchRetry'])->name('batch.retry');
        Route::post('/batch/delete', [ReminderLogController::class, 'batchDelete'])->name('batch.delete');
        
        // 统计分析
        Route::get('/statistics', [ReminderLogController::class, 'statistics'])->name('statistics');
        Route::get('/export', [ReminderLogController::class, 'export'])->name('export');
        
        // 清理操作
        Route::post('/cleanup', [ReminderLogController::class, 'cleanup'])->name('cleanup');
    });
    
    // 统计分析
    Route::prefix('statistics')->name('statistics.')->group(function () {
        Route::get('/', [StatisticsController::class, 'index'])->name('index');
        Route::get('/overview', [StatisticsController::class, 'overview'])->name('overview');
        Route::get('/users', [StatisticsController::class, 'users'])->name('users');
        Route::get('/subscriptions', [StatisticsController::class, 'subscriptions'])->name('subscriptions');
        Route::get('/reminders', [StatisticsController::class, 'reminders'])->name('reminders');
        Route::get('/revenue', [StatisticsController::class, 'revenue'])->name('revenue');
        
        // 报表生成
        Route::get('/reports', [StatisticsController::class, 'reports'])->name('reports');
        Route::post('/reports/generate', [StatisticsController::class, 'generateReport'])->name('reports.generate');
        Route::get('/reports/{report}/download', [StatisticsController::class, 'downloadReport'])->name('reports.download');
        
        // 数据导出
        Route::post('/export', [StatisticsController::class, 'export'])->name('export');
    });
    
    // 系统设置
    Route::prefix('settings')->name('settings.')->middleware('permission:manage_settings')->group(function () {
        Route::get('/', [SystemSettingController::class, 'index'])->name('index');
        Route::get('/general', [SystemSettingController::class, 'general'])->name('general');
        Route::get('/notification', [SystemSettingController::class, 'notification'])->name('notification');
        Route::get('/security', [SystemSettingController::class, 'security'])->name('security');
        Route::get('/integration', [SystemSettingController::class, 'integration'])->name('integration');
        Route::get('/advanced', [SystemSettingController::class, 'advanced'])->name('advanced');
        
        // 设置更新
        Route::post('/update', [SystemSettingController::class, 'update'])->name('update');
        Route::post('/reset', [SystemSettingController::class, 'reset'])->name('reset');
        
        // 配置测试
        Route::post('/test/email', [SystemSettingController::class, 'testEmail'])->name('test.email');
        Route::post('/test/feishu', [SystemSettingController::class, 'testFeishu'])->name('test.feishu');
        Route::post('/test/wechat', [SystemSettingController::class, 'testWechat'])->name('test.wechat');
        Route::post('/test/sms', [SystemSettingController::class, 'testSms'])->name('test.sms');
        
        // 缓存管理
        Route::post('/cache/clear', [SystemSettingController::class, 'clearCache'])->name('cache.clear');
        Route::post('/cache/optimize', [SystemSettingController::class, 'optimizeCache'])->name('cache.optimize');
    });
    
    // 备份管理
    Route::prefix('backups')->name('backups.')->middleware('permission:manage_backups')->group(function () {
        Route::get('/', [BackupController::class, 'index'])->name('index');
        Route::post('/create', [BackupController::class, 'create'])->name('create');
        Route::get('/{backup}/download', [BackupController::class, 'download'])->name('download');
        Route::delete('/{backup}', [BackupController::class, 'destroy'])->name('destroy');
        
        // 备份配置
        Route::get('/settings', [BackupController::class, 'settings'])->name('settings');
        Route::post('/settings', [BackupController::class, 'updateSettings'])->name('settings.update');
        
        // 恢复操作
        Route::get('/restore', [BackupController::class, 'showRestore'])->name('restore');
        Route::post('/restore', [BackupController::class, 'restore'])->name('restore.submit');
    });
    
    // 日志管理
    Route::prefix('logs')->name('logs.')->middleware('permission:view_logs')->group(function () {
        Route::get('/', [LogController::class, 'index'])->name('index');
        Route::get('/system', [LogController::class, 'system'])->name('system');
        Route::get('/security', [LogController::class, 'security'])->name('security');
        Route::get('/activity', [LogController::class, 'activity'])->name('activity');
        Route::get('/api', [LogController::class, 'api'])->name('api');
        
        // 日志操作
        Route::get('/{logFile}/view', [LogController::class, 'view'])->name('view');
        Route::get('/{logFile}/download', [LogController::class, 'download'])->name('download');
        Route::delete('/{logFile}', [LogController::class, 'delete'])->name('delete');
        
        // 日志清理
        Route::post('/cleanup', [LogController::class, 'cleanup'])->name('cleanup');
    });
    
    // 安全管理
    Route::prefix('security')->name('security.')->middleware('permission:manage_security')->group(function () {
        Route::get('/', [SecurityController::class, 'index'])->name('index');
        Route::get('/blocked-ips', [SecurityController::class, 'blockedIps'])->name('blocked-ips');
        Route::get('/login-attempts', [SecurityController::class, 'loginAttempts'])->name('login-attempts');
        Route::get('/suspicious-activities', [SecurityController::class, 'suspiciousActivities'])->name('suspicious-activities');
        
        // IP管理
        Route::post('/block-ip', [SecurityController::class, 'blockIp'])->name('block-ip');
        Route::post('/unblock-ip', [SecurityController::class, 'unblockIp'])->name('unblock-ip');
        
        // 安全扫描
        Route::post('/scan', [SecurityController::class, 'securityScan'])->name('scan');
        Route::get('/scan-results', [SecurityController::class, 'scanResults'])->name('scan-results');
    });
});

// Ajax API路由（需要管理员认证）
Route::prefix($adminPrefix . '/ajax')->name('admin.ajax.')->middleware(['auth:admin'])->group(function () {
    // 搜索建议
    Route::get('/search/users', [UserController::class, 'searchSuggestions'])->name('search.users');
    Route::get('/search/subscriptions', [SubscriptionController::class, 'searchSuggestions'])->name('search.subscriptions');
    
    // 快速统计
    Route::get('/quick-stats', [DashboardController::class, 'quickStats'])->name('quick-stats');
    Route::get('/chart-data/{type}', [StatisticsController::class, 'chartData'])->name('chart-data');
    
    // 系统信息
    Route::get('/system-info', [DashboardController::class, 'systemInfo'])->name('system-info');
    Route::get('/server-status', [DashboardController::class, 'serverStatus'])->name('server-status');
});