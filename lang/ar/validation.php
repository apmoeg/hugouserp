<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines contain the default error messages used by
    | the validator class. Some of these rules have multiple versions such
    | as the size rules. Feel free to tweak each of these messages here.
    |
    */

    'accepted' => 'يجب قبول حقل :attribute.',
    'accepted_if' => 'يجب قبول حقل :attribute عندما يكون :other هو :value.',
    'active_url' => 'حقل :attribute يجب أن يكون رابط URL صالحاً.',
    'after' => 'حقل :attribute يجب أن يكون تاريخاً بعد :date.',
    'after_or_equal' => 'حقل :attribute يجب أن يكون تاريخاً بعد أو يساوي :date.',
    'alpha' => 'حقل :attribute يجب أن يحتوي على حروف فقط.',
    'alpha_dash' => 'حقل :attribute يجب أن يحتوي على حروف وأرقام وشرطات وشرطات سفلية فقط.',
    'alpha_num' => 'حقل :attribute يجب أن يحتوي على حروف وأرقام فقط.',
    'any_of' => 'حقل :attribute غير صالح.',
    'array' => 'حقل :attribute يجب أن يكون مصفوفة.',
    'ascii' => 'حقل :attribute يجب أن يحتوي على أحرف ورموز أحادية البايت فقط.',
    'before' => 'حقل :attribute يجب أن يكون تاريخاً قبل :date.',
    'before_or_equal' => 'حقل :attribute يجب أن يكون تاريخاً قبل أو يساوي :date.',
    'between' => [
        'array' => 'حقل :attribute يجب أن يحتوي على :min إلى :max عناصر.',
        'file' => 'حقل :attribute يجب أن يكون بين :min و :max كيلوبايت.',
        'numeric' => 'حقل :attribute يجب أن يكون بين :min و :max.',
        'string' => 'حقل :attribute يجب أن يكون بين :min و :max حرفاً.',
    ],
    'boolean' => 'حقل :attribute يجب أن يكون صحيحاً أو خاطئاً.',
    'can' => 'حقل :attribute يحتوي على قيمة غير مصرح بها.',
    'confirmed' => 'تأكيد حقل :attribute غير متطابق.',
    'contains' => 'حقل :attribute يفتقد قيمة مطلوبة.',
    'current_password' => 'كلمة المرور غير صحيحة.',
    'date' => 'حقل :attribute يجب أن يكون تاريخاً صالحاً.',
    'date_equals' => 'حقل :attribute يجب أن يكون تاريخاً مساوياً لـ :date.',
    'date_format' => 'حقل :attribute يجب أن يتطابق مع الصيغة :format.',
    'decimal' => 'حقل :attribute يجب أن يحتوي على :decimal منازل عشرية.',
    'declined' => 'يجب رفض حقل :attribute.',
    'declined_if' => 'يجب رفض حقل :attribute عندما يكون :other هو :value.',
    'different' => 'حقل :attribute و :other يجب أن يكونا مختلفين.',
    'digits' => 'حقل :attribute يجب أن يكون :digits رقماً.',
    'digits_between' => 'حقل :attribute يجب أن يكون بين :min و :max رقماً.',
    'dimensions' => 'حقل :attribute يحتوي على أبعاد صورة غير صالحة.',
    'distinct' => 'حقل :attribute يحتوي على قيمة مكررة.',
    'doesnt_contain' => 'حقل :attribute يجب ألا يحتوي على أي من التالي: :values.',
    'doesnt_end_with' => 'حقل :attribute يجب ألا ينتهي بأحد القيم التالية: :values.',
    'doesnt_start_with' => 'حقل :attribute يجب ألا يبدأ بأحد القيم التالية: :values.',
    'email' => 'حقل :attribute يجب أن يكون عنوان بريد إلكتروني صالحاً.',
    'ends_with' => 'حقل :attribute يجب أن ينتهي بأحد القيم التالية: :values.',
    'enum' => 'القيمة المحددة في :attribute غير صالحة.',
    'exists' => 'القيمة المحددة في :attribute غير صالحة.',
    'extensions' => 'حقل :attribute يجب أن يحتوي على أحد الامتدادات التالية: :values.',
    'file' => 'حقل :attribute يجب أن يكون ملفاً.',
    'filled' => 'حقل :attribute يجب أن يحتوي على قيمة.',
    'gt' => [
        'array' => 'حقل :attribute يجب أن يحتوي على أكثر من :value عنصراً.',
        'file' => 'حقل :attribute يجب أن يكون أكبر من :value كيلوبايت.',
        'numeric' => 'حقل :attribute يجب أن يكون أكبر من :value.',
        'string' => 'حقل :attribute يجب أن يكون أكبر من :value حرفاً.',
    ],
    'gte' => [
        'array' => 'حقل :attribute يجب أن يحتوي على :value عنصراً أو أكثر.',
        'file' => 'حقل :attribute يجب أن يكون أكبر من أو يساوي :value كيلوبايت.',
        'numeric' => 'حقل :attribute يجب أن يكون أكبر من أو يساوي :value.',
        'string' => 'حقل :attribute يجب أن يكون أكبر من أو يساوي :value حرفاً.',
    ],
    'hex_color' => 'حقل :attribute يجب أن يكون لوناً سداسي عشري صالحاً.',
    'image' => 'حقل :attribute يجب أن يكون صورة.',
    'in' => 'القيمة المحددة في :attribute غير صالحة.',
    'in_array' => 'حقل :attribute يجب أن يكون موجوداً في :other.',
    'in_array_keys' => 'حقل :attribute يجب أن يحتوي على واحد على الأقل من المفاتيح التالية: :values.',
    'integer' => 'حقل :attribute يجب أن يكون عدداً صحيحاً.',
    'ip' => 'حقل :attribute يجب أن يكون عنوان IP صالحاً.',
    'ipv4' => 'حقل :attribute يجب أن يكون عنوان IPv4 صالحاً.',
    'ipv6' => 'حقل :attribute يجب أن يكون عنوان IPv6 صالحاً.',
    'json' => 'حقل :attribute يجب أن يكون نص JSON صالحاً.',
    'list' => 'حقل :attribute يجب أن يكون قائمة.',
    'lowercase' => 'حقل :attribute يجب أن يكون بأحرف صغيرة.',
    'lt' => [
        'array' => 'حقل :attribute يجب أن يحتوي على أقل من :value عنصراً.',
        'file' => 'حقل :attribute يجب أن يكون أقل من :value كيلوبايت.',
        'numeric' => 'حقل :attribute يجب أن يكون أقل من :value.',
        'string' => 'حقل :attribute يجب أن يكون أقل من :value حرفاً.',
    ],
    'lte' => [
        'array' => 'حقل :attribute يجب ألا يحتوي على أكثر من :value عنصراً.',
        'file' => 'حقل :attribute يجب أن يكون أقل من أو يساوي :value كيلوبايت.',
        'numeric' => 'حقل :attribute يجب أن يكون أقل من أو يساوي :value.',
        'string' => 'حقل :attribute يجب أن يكون أقل من أو يساوي :value حرفاً.',
    ],
    'mac_address' => 'حقل :attribute يجب أن يكون عنوان MAC صالحاً.',
    'max' => [
        'array' => 'حقل :attribute يجب ألا يحتوي على أكثر من :max عنصراً.',
        'file' => 'حقل :attribute يجب ألا يكون أكبر من :max كيلوبايت.',
        'numeric' => 'حقل :attribute يجب ألا يكون أكبر من :max.',
        'string' => 'حقل :attribute يجب ألا يكون أكبر من :max حرفاً.',
    ],
    'max_digits' => 'حقل :attribute يجب ألا يحتوي على أكثر من :max رقماً.',
    'mimes' => 'حقل :attribute يجب أن يكون ملفاً من نوع: :values.',
    'mimetypes' => 'حقل :attribute يجب أن يكون ملفاً من نوع: :values.',
    'min' => [
        'array' => 'حقل :attribute يجب أن يحتوي على الأقل :min عنصراً.',
        'file' => 'حقل :attribute يجب أن يكون على الأقل :min كيلوبايت.',
        'numeric' => 'حقل :attribute يجب أن يكون على الأقل :min.',
        'string' => 'حقل :attribute يجب أن يكون على الأقل :min حرفاً.',
    ],
    'min_digits' => 'حقل :attribute يجب أن يحتوي على الأقل :min رقماً.',
    'missing' => 'حقل :attribute يجب أن يكون مفقوداً.',
    'missing_if' => 'حقل :attribute يجب أن يكون مفقوداً عندما يكون :other هو :value.',
    'missing_unless' => 'حقل :attribute يجب أن يكون مفقوداً إلا إذا كان :other هو :value.',
    'missing_with' => 'حقل :attribute يجب أن يكون مفقوداً عندما يكون :values موجوداً.',
    'missing_with_all' => 'حقل :attribute يجب أن يكون مفقوداً عندما تكون :values موجودة.',
    'multiple_of' => 'حقل :attribute يجب أن يكون مضاعفاً لـ :value.',
    'not_in' => 'القيمة المحددة في :attribute غير صالحة.',
    'not_regex' => 'صيغة حقل :attribute غير صالحة.',
    'numeric' => 'حقل :attribute يجب أن يكون رقماً.',
    'password' => [
        'letters' => 'حقل :attribute يجب أن يحتوي على حرف واحد على الأقل.',
        'mixed' => 'حقل :attribute يجب أن يحتوي على حرف كبير واحد وحرف صغير واحد على الأقل.',
        'numbers' => 'حقل :attribute يجب أن يحتوي على رقم واحد على الأقل.',
        'symbols' => 'حقل :attribute يجب أن يحتوي على رمز واحد على الأقل.',
        'uncompromised' => 'ظهر :attribute المعطى في تسرب بيانات. يرجى اختيار :attribute مختلف.',
    ],
    'present' => 'حقل :attribute يجب أن يكون موجوداً.',
    'present_if' => 'حقل :attribute يجب أن يكون موجوداً عندما يكون :other هو :value.',
    'present_unless' => 'حقل :attribute يجب أن يكون موجوداً إلا إذا كان :other هو :value.',
    'present_with' => 'حقل :attribute يجب أن يكون موجوداً عندما يكون :values موجوداً.',
    'present_with_all' => 'حقل :attribute يجب أن يكون موجوداً عندما تكون :values موجودة.',
    'prohibited' => 'حقل :attribute ممنوع.',
    'prohibited_if' => 'حقل :attribute ممنوع عندما يكون :other هو :value.',
    'prohibited_unless' => 'حقل :attribute ممنوع إلا إذا كان :other في :values.',
    'prohibits' => 'حقل :attribute يمنع وجود :other.',
    'regex' => 'صيغة حقل :attribute غير صالحة.',
    'required' => 'حقل :attribute مطلوب.',
    'required_array_keys' => 'حقل :attribute يجب أن يحتوي على إدخالات لـ: :values.',
    'required_if' => 'حقل :attribute مطلوب عندما يكون :other هو :value.',
    'required_if_accepted' => 'حقل :attribute مطلوب عندما يتم قبول :other.',
    'required_if_declined' => 'حقل :attribute مطلوب عندما يتم رفض :other.',
    'required_unless' => 'حقل :attribute مطلوب إلا إذا كان :other في :values.',
    'required_with' => 'حقل :attribute مطلوب عندما يكون :values موجوداً.',
    'required_with_all' => 'حقل :attribute مطلوب عندما تكون :values موجودة.',
    'required_without' => 'حقل :attribute مطلوب عندما لا يكون :values موجوداً.',
    'required_without_all' => 'حقل :attribute مطلوب عندما لا تكون أي من :values موجودة.',
    'same' => 'حقل :attribute يجب أن يطابق :other.',
    'size' => [
        'array' => 'حقل :attribute يجب أن يحتوي على :size عنصراً.',
        'file' => 'حقل :attribute يجب أن يكون :size كيلوبايت.',
        'numeric' => 'حقل :attribute يجب أن يكون :size.',
        'string' => 'حقل :attribute يجب أن يكون :size حرفاً.',
    ],
    'starts_with' => 'حقل :attribute يجب أن يبدأ بأحد القيم التالية: :values.',
    'string' => 'حقل :attribute يجب أن يكون نصاً.',
    'timezone' => 'حقل :attribute يجب أن يكون منطقة زمنية صالحة.',
    'ulid' => 'حقل :attribute يجب أن يكون ULID صالحاً.',
    'unique' => 'قيمة :attribute مستخدمة مسبقاً.',
    'uploaded' => 'فشل رفع :attribute.',
    'uppercase' => 'حقل :attribute يجب أن يكون بأحرف كبيرة.',
    'url' => 'حقل :attribute يجب أن يكون رابط URL صالحاً.',
    'uuid' => 'حقل :attribute يجب أن يكون UUID صالحاً.',

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | Here you may specify custom validation messages for attributes using the
    | convention "attribute.rule" to name the lines. This makes it quick to
    | specify a specific custom language line for a given attribute rule.
    |
    */

    'custom' => [
        'attribute-name' => [
            'rule-name' => 'رسالة مخصصة',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Attributes
    |--------------------------------------------------------------------------
    |
    | The following language lines are used to swap our attribute placeholder
    | with something more reader friendly such as "E-Mail Address" instead
    | of "email". This simply helps us make our message more expressive.
    |
    */

    'attributes' => [
        'name' => 'الاسم',
        'username' => 'اسم المستخدم',
        'email' => 'البريد الإلكتروني',
        'password' => 'كلمة المرور',
        'password_confirmation' => 'تأكيد كلمة المرور',
        'current_password' => 'كلمة المرور الحالية',
        'phone' => 'الهاتف',
        'mobile' => 'الجوال',
        'address' => 'العنوان',
        'city' => 'المدينة',
        'country' => 'الدولة',
        'postal_code' => 'الرمز البريدي',
        'date' => 'التاريخ',
        'time' => 'الوقت',
        'subject' => 'الموضوع',
        'message' => 'الرسالة',
        'description' => 'الوصف',
        'status' => 'الحالة',
        'type' => 'النوع',
        'category' => 'الفئة',
        'price' => 'السعر',
        'quantity' => 'الكمية',
        'amount' => 'المبلغ',
        'total' => 'الإجمالي',
        'company' => 'الشركة',
        'company_name' => 'اسم الشركة',
        'contact_person' => 'شخص الاتصال',
        'title' => 'العنوان',
        'content' => 'المحتوى',
        'file' => 'الملف',
        'image' => 'الصورة',
        'notes' => 'ملاحظات',
        'balance' => 'الرصيد',
        'is_active' => 'نشط',
        'role' => 'الدور',
        'permissions' => 'الصلاحيات',
    ],

];
