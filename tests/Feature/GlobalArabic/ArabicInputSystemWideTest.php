<?php

declare(strict_types=1);

namespace Tests\Feature\GlobalArabic;

use App\Models\Branch;
use App\Models\Currency;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ArabicInputSystemWideTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();

        $this->branch = Branch::create(['name' => 'Test Branch', 'code' => 'TB001']);
        $this->user = User::factory()->create(['branch_id' => $this->branch->id]);
    }

    public function test_currency_accepts_arabic_name(): void
    {
        $currency = Currency::create([
            'code' => 'SAR',
            'name' => 'Saudi Riyal',
            'name_ar' => 'ريال سعودي',
            'symbol' => 'ر.س',
            'decimal_places' => 2,
            'is_active' => true,
            'is_base' => false,
            'sort_order' => 1,
        ]);

        $this->assertDatabaseHas('currencies', [
            'code' => 'SAR',
            'name_ar' => 'ريال سعودي',
        ]);

        $retrieved = Currency::find($currency->id);
        $this->assertEquals('ريال سعودي', $retrieved->name_ar);
    }

    public function test_expense_category_accepts_arabic_name(): void
    {
        $category = ExpenseCategory::create([
            'name' => 'Office Supplies',
            'name_ar' => 'مستلزمات مكتبية',
            'description' => 'Office supplies and stationery',
            'is_active' => true,
            'branch_id' => $this->branch->id,
        ]);

        $this->assertDatabaseHas('expense_categories', [
            'name_ar' => 'مستلزمات مكتبية',
        ]);

        $retrieved = ExpenseCategory::find($category->id);
        $this->assertEquals('مستلزمات مكتبية', $retrieved->name_ar);
    }

    public function test_expense_accepts_arabic_description(): void
    {
        $category = ExpenseCategory::create([
            'name' => 'Test Category',
            'branch_id' => $this->branch->id,
        ]);

        $expense = Expense::create([
            'expense_category_id' => $category->id,
            'branch_id' => $this->branch->id,
            'reference_number' => 'EXP-001',
            'expense_date' => now(),
            'amount' => 1000.00,
            'currency_id' => null,
            'payment_method' => 'cash',
            'description' => 'مصروفات مكتبية متنوعة',
            'created_by' => $this->user->id,
        ]);

        $this->assertDatabaseHas('expenses', [
            'description' => 'مصروفات مكتبية متنوعة',
        ]);

        $retrieved = Expense::find($expense->id);
        $this->assertEquals('مصروفات مكتبية متنوعة', $retrieved->description);
    }

    public function test_project_accepts_arabic_name_and_description(): void
    {
        $project = Project::create([
            'name' => 'مشروع تطوير النظام',
            'code' => 'PRJ-001',
            'description' => 'مشروع لتطوير نظام إدارة الموارد',
            'start_date' => now(),
            'end_date' => now()->addMonths(6),
            'status' => 'active',
            'branch_id' => $this->branch->id,
            'created_by' => $this->user->id,
        ]);

        $this->assertDatabaseHas('projects', [
            'name' => 'مشروع تطوير النظام',
            'description' => 'مشروع لتطوير نظام إدارة الموارد',
        ]);

        $retrieved = Project::find($project->id);
        $this->assertEquals('مشروع تطوير النظام', $retrieved->name);
        $this->assertEquals('مشروع لتطوير نظام إدارة الموارد', $retrieved->description);
    }

    public function test_mixed_arabic_english_text(): void
    {
        $project = Project::create([
            'name' => 'ERP System - نظام تخطيط الموارد',
            'code' => 'PRJ-002',
            'description' => 'A comprehensive ERP system - نظام شامل لإدارة الموارد',
            'start_date' => now(),
            'status' => 'active',
            'branch_id' => $this->branch->id,
            'created_by' => $this->user->id,
        ]);

        $this->assertDatabaseHas('projects', [
            'name' => 'ERP System - نظام تخطيط الموارد',
            'description' => 'A comprehensive ERP system - نظام شامل لإدارة الموارد',
        ]);
    }

    public function test_arabic_special_characters(): void
    {
        // Test various Arabic special characters and diacritics
        $text = 'اختبار الحروف: ء ئ ؤ أ إ آ ة ى التشكيل: َ ُ ِ ّ ْ';

        $project = Project::create([
            'name' => $text,
            'code' => 'PRJ-003',
            'start_date' => now(),
            'status' => 'active',
            'branch_id' => $this->branch->id,
            'created_by' => $this->user->id,
        ]);

        $retrieved = Project::find($project->id);
        $this->assertEquals($text, $retrieved->name);
    }

    public function test_long_arabic_text(): void
    {
        $longText = 'هذا نص طويل باللغة العربية يحتوي على عدة جمل. الهدف من هذا النص هو التأكد من أن النظام يمكنه التعامل مع النصوص العربية الطويلة بشكل صحيح. يجب أن يتم حفظ هذا النص بالكامل في قاعدة البيانات دون أي مشاكل. النظام يدعم اللغة العربية بشكل كامل باستخدام utf8mb4_unicode_ci collation.';

        $project = Project::create([
            'name' => 'Long Text Test',
            'code' => 'PRJ-004',
            'description' => $longText,
            'start_date' => now(),
            'status' => 'active',
            'branch_id' => $this->branch->id,
            'created_by' => $this->user->id,
        ]);

        $retrieved = Project::find($project->id);
        $this->assertEquals($longText, $retrieved->description);
    }

    public function test_arabic_numbers(): void
    {
        // Test Arabic-Indic numerals
        $expense = Expense::create([
            'expense_category_id' => ExpenseCategory::create(['name' => 'Test', 'branch_id' => $this->branch->id])->id,
            'branch_id' => $this->branch->id,
            'reference_number' => 'EXP-002',
            'expense_date' => now(),
            'amount' => 1500.00,
            'payment_method' => 'cash',
            'description' => 'المبلغ: ١٥٠٠ ريال - رقم الفاتورة: ١٢٣٤٥',
            'created_by' => $this->user->id,
        ]);

        $retrieved = Expense::find($expense->id);
        $this->assertEquals('المبلغ: ١٥٠٠ ريال - رقم الفاتورة: ١٢٣٤٥', $retrieved->description);
    }

    public function test_update_with_arabic_text(): void
    {
        $project = Project::create([
            'name' => 'Original Name',
            'code' => 'PRJ-005',
            'description' => 'Original description',
            'start_date' => now(),
            'status' => 'active',
            'branch_id' => $this->branch->id,
            'created_by' => $this->user->id,
        ]);

        $project->update([
            'name' => 'اسم محدث',
            'description' => 'وصف محدث باللغة العربية',
        ]);

        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'name' => 'اسم محدث',
            'description' => 'وصف محدث باللغة العربية',
        ]);
    }

    public function test_arabic_text_survives_multiple_updates(): void
    {
        $category = ExpenseCategory::create([
            'name' => 'مستلزمات',
            'name_ar' => 'مستلزمات مكتبية',
            'branch_id' => $this->branch->id,
        ]);

        // First update
        $category->update(['name' => 'مستلزمات محدثة']);
        $this->assertEquals('مستلزمات محدثة', $category->fresh()->name);

        // Second update
        $category->update(['name_ar' => 'مستلزمات مكتبية محدثة']);
        $this->assertEquals('مستلزمات مكتبية محدثة', $category->fresh()->name_ar);

        // Third update - ensure original Arabic is still intact
        $category->update(['description' => 'وصف جديد']);
        $this->assertEquals('مستلزمات محدثة', $category->fresh()->name);
        $this->assertEquals('مستلزمات مكتبية محدثة', $category->fresh()->name_ar);
    }
}
