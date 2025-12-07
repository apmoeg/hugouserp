<?php

namespace Database\Seeders;

use App\Models\Currency;
use Illuminate\Database\Seeder;

class CurrencySeeder extends Seeder
{
    public function run(): void
    {
        $currencies = [
            [
                'code' => 'EGP',
                'name' => 'Egyptian Pound',
                'name_ar' => 'الجنيه المصري',
                'symbol' => 'ج.م',
                'decimal_places' => 2,
                'is_active' => true,
                'is_base' => true,
                'sort_order' => 1,
            ],
            [
                'code' => 'USD',
                'name' => 'US Dollar',
                'name_ar' => 'الدولار الأمريكي',
                'symbol' => '$',
                'decimal_places' => 2,
                'is_active' => true,
                'is_base' => false,
                'sort_order' => 2,
            ],
            [
                'code' => 'EUR',
                'name' => 'Euro',
                'name_ar' => 'اليورو',
                'symbol' => '€',
                'decimal_places' => 2,
                'is_active' => true,
                'is_base' => false,
                'sort_order' => 3,
            ],
            [
                'code' => 'SAR',
                'name' => 'Saudi Riyal',
                'name_ar' => 'الريال السعودي',
                'symbol' => 'ر.س',
                'decimal_places' => 2,
                'is_active' => true,
                'is_base' => false,
                'sort_order' => 4,
            ],
            [
                'code' => 'GBP',
                'name' => 'British Pound',
                'name_ar' => 'الجنيه الإسترليني',
                'symbol' => '£',
                'decimal_places' => 2,
                'is_active' => true,
                'is_base' => false,
                'sort_order' => 5,
            ],
            [
                'code' => 'KWD',
                'name' => 'Kuwaiti Dinar',
                'name_ar' => 'الدينار الكويتي',
                'symbol' => 'د.ك',
                'decimal_places' => 3,
                'is_active' => true,
                'is_base' => false,
                'sort_order' => 6,
            ],
            [
                'code' => 'JPY',
                'name' => 'Japanese Yen',
                'name_ar' => 'الين الياباني',
                'symbol' => '¥',
                'decimal_places' => 0,
                'is_active' => true,
                'is_base' => false,
                'sort_order' => 7,
            ],
            [
                'code' => 'CHF',
                'name' => 'Swiss Franc',
                'name_ar' => 'الفرنك السويسري',
                'symbol' => 'CHF',
                'decimal_places' => 2,
                'is_active' => true,
                'is_base' => false,
                'sort_order' => 8,
            ],
            [
                'code' => 'TRY',
                'name' => 'Turkish Lira',
                'name_ar' => 'الليرة التركية',
                'symbol' => '₺',
                'decimal_places' => 2,
                'is_active' => true,
                'is_base' => false,
                'sort_order' => 9,
            ],
        ];

        foreach ($currencies as $currency) {
            Currency::updateOrCreate(
                ['code' => $currency['code']],
                $currency
            );
        }
    }
}
