<?php

// إعدادات النسخة التجريبية (Demo) الخاصة بعرض الداشبورد للشركات
return [
    // تفعيل وضع العرض التجريبي
    'enabled' => env('DEMO_MODE', false),

    // إيميل حساب الأدمن التجريبي الذي يدخل به الزوار
    'email' => env('DEMO_EMAIL', 'demo.admin@gmail.com'),

    // مسارات GET تقوم بتعديل البيانات ويجب منعها على الحساب التجريبي
    'blocked_get_routes' => [
        'api/admin/admins/approve/*',
        'api/admin/admins/reject/*',
        'api/admin/users/approve/*',
        'api/admin/users/reject/*',
        'api/admin/product/accept/*',
        'api/admin/product/reject/*',
        'api/j',
    ],

    // مسارات كتابة مسموحة للحساب التجريبي
    'allowed_write_routes' => [
        'api/admin/logout',
        'api/logout',
        'api/broadcasting/auth',
    ],

    // مسارات عامة ممنوعة في الوضع التجريبي (لمنع الزوار من إنشاء حسابات أدمن)
    'blocked_public_routes' => [
        'api/admin/register',
    ],
];
