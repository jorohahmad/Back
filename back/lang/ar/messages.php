<?php

return [
    // 🔐 المصادقة والمستخدمون (Auth & Users)
    'user_registered_successfully'     => 'تم تسجيل المستخدم بنجاح.',
    'email_send_failed'                => 'فشل في إرسال البريد الإلكتروني.',
    'invalid_credentials'              => 'البريد الإلكتروني أو كلمة المرور غير صحيحة.',
    'login_successful'                 => 'تم تسجيل الدخول بنجاح.',
    'logout_successful'                => 'تم تسجيل الخروج بنجاح.',
    'account_created_pending_approval' => 'تم إنشاء حسابك بنجاح. يرجى الانتظار حتى تتم الموافقة عليه من قبل الإدارة.',
    'verification_code_sent'           => 'تم إرسال كود التحقق إلى بريدك الإلكتروني. يرجى التحقق من صندوق الوارد أو البريد العشوائي.',
    'invalid_verification_code'        => 'الكود المدخل غير صحيح. يرجى التأكد والمحاولة.',
    'code_is_valid'                    => 'الكود صحيح.',
    'account_already_verified'         => 'حسابك موثق مسبقاً.',
    'account_verified_successfully'    => 'تم توثيق حسابك بنجاح. شكراً لتعاونك.',
    'email_not_registered'             => 'هذا البريد الإلكتروني غير مسجل لدينا.',
    'password_reset_code_sent'         => 'تم إرسال كود إعادة تعيين كلمة المرور إلى بريدك الإلكتروني.',
    'password_changed_successfully'    => 'تم تغيير كلمة المرور بنجاح. يمكنك الآن تسجيل الدخول.',
    'old_password_incorrect'           => 'كلمة المرور القديمة غير صحيحة.',
    'password_updated_successfully'    => 'تم تحديث كلمة المرور بنجاح.',
    'profile_updated_successfully'     => 'تم تحديث بياناتك الشخصية بنجاح.',
    'profile_image_updated'            => 'تم تحديث الصورة الشخصية بنجاح.',

    // ❤️ المفضلة (Favorites)
    'added_to_favorites'     => 'تمت الإضافة إلى المفضلة بنجاح.',
    'removed_from_favorites' => 'تمت الإزالة من المفضلة.',

    // 👨‍💻 لوحة الإدارة (Admin Panel)
    'not_admin'             => 'عذراً، أنت لا تملك صلاحية مدير النظام.',
    'user_approved'         => 'تم الموافقة على المستخدم.',
    'admin_approved'        => 'تم الموافقة على المدير.',
    'user_rejected'         => 'تم رفض المستخدم.',
    'admin_rejected'        => 'تم رفض المدير.',
    'accepted_successfully' => 'تم القبول بنجاح.',
    'rejected_successfully' => 'تم الرفض بنجاح.',

    // 🛒 السلة والدفع (Cart & Checkout)
    'checkout_successful'             => 'تمت عملية الدفع وتوثيق العقود والعمولات بنجاح فائق!',
    'checkout_error'                  => 'حدث خطأ أثناء عملية الدفع.',
    'added_to_cart_successfully'      => 'تمت إضافة العنصر إلى السلة بنجاح.',
    'cart_is_empty'                   => 'السلة فارغة حالياً.',
    'error_fetching_cart'             => 'حدث خطأ أثناء جلب محتويات السلة.',
    'items_not_found_or_unauthorized' => 'لم يتم العثور على العناصر أو أنك لا تملك صلاحية حذفها.',
    'items_deleted_from_cart'         => 'تم حذف العناصر من السلة بنجاح.',
    'error_deleting_from_cart'        => 'حدث خطأ أثناء محاولة الحذف من السلة.',
    'quantity_updated_successfully'   => 'تم تحديث الكمية بنجاح.',
    'error_fetching_receipts'         => 'حدث خطأ أثناء جلب الإيصالات.',

    // 📦 المنتجات (Products)
    'product_created_successfully' => 'تم إضافة المنتج بنجاح.',

    // 📊 الداشبورد والإحصائيات (Dashboard)
    'dashboard_data_success'            => 'تم جلب بيانات لوحة التحكم بنجاح.',
    'dashboard_data_error'              => 'حدث خطأ أثناء جلب بيانات لوحة التحكم.',
    'error_processing_stats'            => 'حدث خطأ أثناء معالجة الإحصائيات المقارنة.',
    'error_calculating_supply_demand'   => 'حدث خطأ أثناء حساب مؤشر العرض والطلب.',
    'error_fetching_performance'        => 'حدث خطأ أثناء جلب ملخص الأداء.',
    'transactions_distribution_success' => 'تم جلب بيانات توزيع العمليات بنجاح.',
    'error_fetching_data'               => 'حدث خطأ أثناء جلب البيانات.',
    'quick_stats_success'               => 'تم جلب الإحصائيات السريعة بنجاح.',
    'error_fetching_stats'              => 'حدث خطأ أثناء جلب الإحصائيات.',
    'top_machines_success'              => 'تم جلب أفضل الآلات بنجاح لحالة: :type',
    'error_fetching_dashboard_data'     => 'حدث خطأ أثناء جلب بيانات الداشبورد.',

    // 🔄 عام (General)
    'added_successfully'   => 'تمت الإضافة بنجاح.',
    'updated_successfully' => 'تم التحديث بنجاح.',
    'deleted_successfully' => 'تم الحذف بنجاح.',
    'success'              => 'عملية ناجحة',
    
    //Mail
    'welcome_subject'             => 'مرحباً بك في تطبيق SwiftCart',
    'verification_subject'        => 'بريد التحقق من الحساب',
    'accept_subject'              => 'قبول الحساب',
    'admin_validate_subject'      => 'كود التحقق الخاص بالإدارة',
    'approved_subject'            => 'تمت الموافقة على حسابك',
    'forget_password_subject'     => 'إعادة تعيين كلمة المرور',
    'rejected_subject'            => 'تم رفض الحساب',
    'reject_verification_subject' => 'تم رفض توثيق الحساب',

    //Rating
    'cannot_rate_self'  => 'لا يمكنك تقييم نفسك.',
    'rating_successful' => 'تم إرسال التقييم بنجاح.',

    //Notifications
    'new_sale_title'   => 'مبيع جديد! ',
    'new_sale_body'    => 'تم بيع منتج لك بنجاح. تمت إضافة مبلغ :amount$ إلى رصيدك.',
    'new_rental_title' => 'طلب إيجار جديد! ',
    'new_rental_body'  => 'تم استئجار آلتك بنجاح. تمت إضافة مبلغ :amount$ إلى رصيدك.',
];