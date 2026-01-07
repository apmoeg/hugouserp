<?php

declare(strict_types=1);

return [
    // Labels
    'fixed_assets' => 'الأصول الثابتة',
    'asset' => 'أصل',
    'asset_name' => 'اسم الأصل',
    'asset_code' => 'رمز الأصل',
    'category' => 'الفئة',
    'purchase_date' => 'تاريخ الشراء',
    'purchase_cost' => 'تكلفة الشراء',
    'depreciation' => 'الإهلاك',
    'depreciation_method' => 'طريقة الإهلاك',
    'useful_life' => 'العمر الإنتاجي',
    'residual_value' => 'القيمة المتبقية',
    'book_value' => 'القيمة الدفترية',
    'accumulated_depreciation' => 'الإهلاك المتراكم',
    'location' => 'الموقع',
    'status' => 'الحالة',
    'condition' => 'الحالة المادية',
    'serial_number' => 'الرقم التسلسلي',
    'supplier' => 'المورد',
    'warranty_expiry' => 'انتهاء الضمان',

    // Depreciation Methods
    'straight_line' => 'القسط الثابت',
    'declining_balance' => 'الرصيد المتناقص',
    'sum_of_years' => 'مجموع السنوات',

    // Status Values
    'active' => 'نشط',
    'inactive' => 'غير نشط',
    'disposed' => 'متخلص منه',
    'under_maintenance' => 'تحت الصيانة',

    // Condition Values
    'excellent' => 'ممتاز',
    'good' => 'جيد',
    'fair' => 'مقبول',
    'poor' => 'سيئ',

    // Messages
    'asset_created' => 'تم إنشاء الأصل بنجاح',
    'asset_updated' => 'تم تحديث الأصل بنجاح',
    'asset_deleted' => 'تم حذف الأصل',
    'asset_disposed' => 'تم التخلص من الأصل',
    'depreciation_calculated' => 'تم حساب الإهلاك',

    // Buttons
    'add_asset' => 'إضافة أصل',
    'calculate_depreciation' => 'حساب الإهلاك',
    'dispose_asset' => 'التخلص من الأصل',
    'view_depreciation' => 'عرض الإهلاك',

    // Navigation
    'all_assets' => 'جميع الأصول',
    'by_category' => 'حسب الفئة',
    'depreciation_schedule' => 'جدول الإهلاك',

    // Validation Messages
    'asset_name_required' => 'اسم الأصل مطلوب',
    'purchase_cost_required' => 'تكلفة الشراء مطلوبة',
    'depreciation_method_required' => 'طريقة الإهلاك مطلوبة',
];
