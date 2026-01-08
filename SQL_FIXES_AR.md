# إصلاحات قاعدة البيانات - SQL Bugs Fixed

## ملخص التغييرات

تم إصلاح جميع أخطاء SQL في النظام بنجاح.

### 1. إصلاح جداول stock_transfers

**المشكلة:**
- جدول `stock_transfers` الرئيسي غير موجود
- جدول `stock_transfer_items` غير موجود
- الـforeign keys في 3 جداول تشير إلى جدول خاطئ (`transfers` بدلاً من `stock_transfers`)

**الحل:**
- تم تعديل migration الأصلي: `2026_01_04_100002_add_advanced_features_tables.php`
- تم إنشاء جدول `stock_transfers` مع 51 عمود
- تم إنشاء جدول `stock_transfer_items` مع 17 عمود
- تم تصحيح foreign keys في:
  - `stock_transfer_approvals`
  - `stock_transfer_documents`
  - `stock_transfer_history`

### 2. إصلاح Model

**المشكلة:**
- Laravel ينشئ اسم جدول تلقائي `stock_transfer_histories` 
- لكن الـmigration أنشأت `stock_transfer_history` (مفرد)

**الحل:**
- تم إضافة `protected $table = 'stock_transfer_history';` في `StockTransferHistory.php`

### 3. حذف Dead Code

**الملفات المحذوفة:**
- `app/Livewire/Inventory/ServiceProductForm.php` (deprecated)
- `resources/views/livewire/inventory/service-product-form.blade.php` (deprecated)

هذه الملفات كانت مُعلّمة بـ `@deprecated` وتم استبدالها بـ `App\Livewire\Inventory\Services\Form`

## البنية الصحيحة الآن

### نظامان منفصلان للتحويلات:

1. **النظام البسيط (Basic):**
   - Model: `Transfer`
   - Table: `transfers`
   - Items: `transfer_items`

2. **النظام المتقدم (Advanced):**
   - Model: `StockTransfer`
   - Table: `stock_transfers` ✅
   - Items: `stock_transfer_items` ✅
   - Approvals: `stock_transfer_approvals` ✅
   - Documents: `stock_transfer_documents` ✅
   - History: `stock_transfer_history` ✅

## ملاحظات

- ❌ **لا توجد** tables أو models للـ "trials" في النظام
- ✅ **تم التأكد** من عدم وجود تكرار أو تضارب في الـservices
- ✅ **تم التأكد** من عدم وجود SQL bugs أخرى

## التشغيل

للتطبيق في قاعدة بيانات جديدة:

```bash
php artisan migrate:fresh --seed
```

## كلمة المرور الافتراضية

- **Email:** admin@ghanem-lvju-egypt.com
- **Username:** admin
- **Password:** 0150386787

تم التحقق من أن كلمة المرور صحيحة ومشفرة بشكل صحيح في `UsersSeeder.php`

---

**تاريخ الإصلاح:** 2026-01-08
**الحالة:** ✅ تم بنجاح
