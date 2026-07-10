<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\Service;
use App\Models\Setting;
use App\Models\Shop;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // تنظیمات پیش‌فرض برنامه‌های رشد و جذب غرفه‌دار
        Setting::set('cashback_percent', 5);              // درصد بازگشت اعتبار خرید
        Setting::set('referral_referrer_amount', 50000);  // پاداش معرف
        Setting::set('referral_referred_amount', 50000);  // پاداش کاربر معرفی‌شده
        Setting::set('default_commission_percent', 10);   // کمیسیون پیش‌فرض پس از پایان دوره رایگان
        Setting::set('loyalty_silver_threshold', 15000000);
        Setting::set('loyalty_gold_threshold', 50000000);

        // مدیر سیستم
        $admin = User::create([
            'name' => 'مدیر رواق',
            'mobile' => '09120000000',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'referral_code' => Str::upper(Str::random(8)),
            'mobile_verified_at' => now(),
        ]);
        Wallet::create(['user_id' => $admin->id]);

        // دسته‌بندی‌های نمونه
        $categoryShop = Category::create([
            'name' => 'سالن زیبایی بانوان',
            'slug' => 'beauty-salon-women',
            'type' => 'shop',
        ]);
        $categoryProduct = Category::create([
            'name' => 'لوازم آرایشی',
            'slug' => 'cosmetics',
            'type' => 'product',
        ]);
        $categoryService = Category::create([
            'name' => 'خدمات پوست و مو',
            'slug' => 'skin-hair-services',
            'type' => 'service',
        ]);

        // غرفه‌دار نمونه
        $owner = User::create([
            'name' => 'سالن آرایشی ملیکا',
            'mobile' => '09121111111',
            'password' => bcrypt('password'),
            'role' => 'shop_owner',
            'referral_code' => Str::upper(Str::random(8)),
            'mobile_verified_at' => now(),
        ]);
        Wallet::create(['user_id' => $owner->id, 'balance' => 500000]);

        $shop = Shop::create([
            'user_id' => $owner->id,
            'category_id' => $categoryShop->id,
            'name' => 'سالن زیبایی ملیکا',
            'slug' => 'melika-beauty-' . Str::random(5),
            'description' => 'ارائه خدمات آرایشی و زیبایی با بهترین کیفیت',
            'phone' => '09121111111',
            'address' => 'شیراز، خیابان زند',
            'status' => 'active',
            'trial_ends_at' => now()->addMonths(3),
            'commission_percent' => 10,
            'verified_at' => now(),
        ]);

        Product::create([
            'shop_id' => $shop->id,
            'category_id' => $categoryProduct->id,
            'name' => 'رژ لب مات مدل رزگلد',
            'slug' => 'lipstick-rosegold-' . Str::random(5),
            'description' => 'رژ لب مات با ماندگاری بالا',
            'price' => 250000,
            'discount_price' => 199000,
            'stock' => 50,
        ]);

        Service::create([
            'shop_id' => $shop->id,
            'category_id' => $categoryService->id,
            'name' => 'اصلاح و میکاپ عروس',
            'slug' => 'bridal-makeup-' . Str::random(5),
            'description' => 'خدمات کامل آرایش عروس',
            'price' => 1500000,
            'duration_minutes' => 120,
        ]);

        // کاربر عادی نمونه
        $user = User::create([
            'name' => 'سارا احمدی',
            'mobile' => '09123333333',
            'password' => bcrypt('password'),
            'role' => 'user',
            'referral_code' => Str::upper(Str::random(8)),
            'mobile_verified_at' => now(),
        ]);
        Wallet::create(['user_id' => $user->id]);

        $this->command->info('داده‌های نمونه رواق با موفقیت ساخته شد.');
        $this->command->info('ادمین: 09120000000 / غرفه‌دار: 09121111111 / کاربر: 09123333333 (رمز همه: password)');
    }
}
