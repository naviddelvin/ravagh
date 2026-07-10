# رواق — ساختار پایه پروژه لاراول

این پکیج شامل **migrationها** و **modelهای Eloquent** برای تمام ۲۰ جدول اصلی سند پروژه رواق است.

## نحوه استفاده

1. یک پروژه لاراول ۱۲ جدید بسازید:
   ```bash
   composer create-project laravel/laravel ravagh
   ```
2. فایل‌های `database/migrations/*.php` این پکیج را داخل پوشه `database/migrations` پروژه خود کپی کنید.
3. فایل‌های `app/Models/*.php` را داخل پوشه `app/Models` پروژه خود کپی کنید.
4. `.env` را تنظیم و دیتابیس MySQL بسازید، سپس:
   ```bash
   php artisan migrate
   ```
5. برای احراز هویت API نصب کنید:
   ```bash
   composer require laravel/sanctum
   php artisan install:api
   ```

## نکات مهم پیاده‌سازی (مطابق سند)

- **کیف پول**: کلاس `Wallet` متدهای `credit()` و `debit()` دارد. برداشت نقدی توسط کاربر عادی مجاز نیست؛ فقط `WithdrawRequest` برای غرفه‌داران/ارائه‌دهندگان خدمات تعریف شده است.
- **تقسیم درآمد استوری (۷۰٪/۳۰٪)**: با متد استاتیک `Story::createWithSplit()` محاسبه می‌شود. تعرفه‌های مجاز در `Story::TARIFFS` تعریف شده‌اند (۱۰۰هزار، ۵۰۰هزار، ۱ میلیون، ۱۰ میلیون تومان).
- **ضدتقلب پاداش**: جدول `story_views` دارای `unique(story_id, user_id)` است تا هر کاربر فقط یک‌بار برای هر استوری پاداش بگیرد؛ فیلدهای `ip_address` و `device_id` نیز برای تشخیص بازدید جعلی ذخیره می‌شوند.
- **چندریختی (Polymorphic)**: `reviews` برای Product/Service/Shop، `payments` و `wallet_transactions` برای اشاره به منبع تراکنش (سفارش، استوری و ...) از polymorphic relations استفاده می‌کنند.
- **نقش‌ها**: ستون `role` در جدول `users` (user | shop_owner | admin) — پیشنهاد می‌شود برای کنترل دسترسی از Middleware یا پکیج `spatie/laravel-permission` استفاده شود.

## مراحل ساخته‌شده تا اینجا

**مرحله ۱ — ساختار پایه:** ۲۱ migration (شامل otp_codes) و ۲۱ model با روابط کامل.

**مرحله ۲ — احراز هویت:** `OtpService` + `AuthController` (ثبت‌نام خودکار با موبایل، ارجاع دوستان، توکن Sanctum) + `EnsureUserHasRole` middleware.

**مرحله ۳ — کاتالوگ:** `CategoryController`، `ShopController`، `ProductController`، `ServiceController`.

**مرحله ۴ — سبد خرید و سفارش:** `CartController` + `OrderService` (چک‌اوت به تفکیک غرفه، کوپن، کسر موجودی، پرداخت کیف‌پول/درگاه) + `OrderController`.

**مرحله ۵ — استوری و پاداش:** `StoryRewardService` + `StoryController` (انتشار با تقسیم خودکار ۷۰/۳۰، تمدید، آمار) + `WalletController`.

**مرحله ۶ — نظرات، کوپن، تبلیغات، اعلان‌ها، تسویه:** `ReviewController`، `CouponController`، `AdvertisementController`، `NotificationController`، `WithdrawRequestController`.

**مرحله ۷ — پنل مدیریت:** `Admin\UserManagementController`، `Admin\ShopManagementController`، `Admin\WithdrawManagementController`، `Admin\ReportController`، `Admin\AdvertisementManagementController`، `Admin\SettingController`.

**مرحله ۸ — اتصال نهایی:** `routes/api.php` کامل، `bootstrap-app-example.php`، و `DatabaseSeeder`.

## نصب و اجرا

1. `composer create-project laravel/laravel ravagh` و کپی فایل‌های این پکیج در جای خودشان.
2. محتوای `bootstrap-app-example.php` را در `bootstrap/app.php` پروژه اصلی اعمال کنید.
3. `composer require laravel/sanctum`.
4. `.env` دیتابیس را تنظیم کنید، سپس: `php artisan migrate --seed`
5. اطلاعات نمونه (رمز همه: `password`): ادمین `09120000000` / غرفه‌دار `09121111111` / کاربر `09123333333`.

> چون ورود بر پایه OTP است، کد ارسالی را از `storage/logs/laravel.log` بخوانید — تا زمان اتصال به سرویس پیامکی واقعی.

## مرحله ۹ — ویژگی‌های جذب و نگه‌داشت غرفه‌دار

- **بازگشت اعتبار خرید (Cashback):** `CashbackService` — پس از تحویل سفارش، درصدی (پیش‌فرض ۵٪، قابل تغییر از `settings.cashback_percent`) به کیف پول مشتری برمی‌گردد.
- **پاداش دوطرفه معرفی:** `ReferralService` + جدول `referral_rewards` — با اولین سفارش پرداخت‌شده‌ی کاربر معرفی‌شده، هم معرف و هم او اعتبار می‌گیرند (هرکاربر فقط یک‌بار).
- **باشگاه وفاداری غرفه‌داران:** `LoyaltyService` — نشان برنزی/نقره‌ای/طلایی بر اساس مجموع درآمد پرداخت‌شده غرفه؛ با ارتقای نشان، اعتبار استوری رایگان به کیف‌پول غرفه‌دار اضافه می‌شود. آستانه‌ها از `settings.loyalty_silver_threshold` و `loyalty_gold_threshold` قابل تغییرند.
- **۳ ماه اول رایگان:** هنگام ایجاد غرفه، `trial_ends_at` سه ماه جلوتر و `commission_percent` بر اساس `settings.default_commission_percent` تنظیم می‌شود؛ `Shop::effectiveCommissionPercent()` در دوره رایگان همیشه صفر برمی‌گرداند.
- **نشان «تأیید شده توسط رواق»:** فیلد `verified_at` روی غرفه + مسیر ادمین `POST /admin/shops/{shop}/toggle-verified`.

همه‌ی این‌ها هنگام تغییر وضعیت سفارش به «تحویل شد» (`PATCH /orders/{order}/status`) به‌صورت خودکار اجرا می‌شوند.

> نکته: چون بخش «خدمات» فعلاً جریان سفارش/تحویل ندارد (نوبت‌دهی زمان‌بندی‌شده در سند اولیه نبود)، cashback و پاداش معرفی فعلاً فقط روی خرید محصول فعال است. اگر بخواهید، می‌توانم جدول `bookings` برای خدمات هم اضافه کنم تا cashback شامل خدمات هم بشود.

## مرحله ۱۰ — رزرو/نوبت‌دهی خدمات (تعمیم Cashback و معرفی به خدمات)

- جدول و مدل `Booking` با چرخه وضعیت مشابه سفارش: `pending → confirmed → completed / cancelled`.
- `BookingService::book()` — رزرو نوبت برای یک خدمت با پرداخت کیف‌پول یا درگاه؛ `BookingController` مسیرهای مشتری (`POST /services/{service}/bookings`) و غرفه‌دار (`GET /shops/{shop}/bookings`, `PATCH /bookings/{booking}/status`) را فراهم می‌کند.
- `CashbackService::applyForPayable()` و `ReferralService::rewardIfEligible()` اکنون هم `Order` و هم `Booking` را می‌پذیرند (`Order|Booking`)، پس بازگشت اعتبار خرید و پاداش معرفی روی خرید محصول **و** رزرو خدمت به‌صورت یکسان اعمال می‌شود. تشخیص «اولین خرید کاربر» هم مجموع سفارش‌ها و رزروهای پرداخت‌شده را در نظر می‌گیرد.
- `LoyaltyService::recalculate()` درآمد رزرو خدمات را هم به مجموع درآمد غرفه برای محاسبه نشان وفاداری اضافه می‌کند.
- جدول `referral_rewards` اکنون رابطه‌ی چندریختی (`trigger_type` / `trigger_id`) دارد تا به سفارش یا رزرو اشاره کند.
- همه‌ی این پاداش‌ها هنگام تغییر وضعیت نوبت به «انجام شد» (`completed`) به‌صورت خودکار اجرا می‌شوند — دقیقاً مثل «تحویل شد» در سفارش‌ها.

> نکته: «پرداخت بیعانه» و «یادآوری خودکار نوبت» (که در ایده‌های اولیه مطرح شد) هنوز پیاده نشده؛ فیلد `reminder_sent_at` در جدول از قبل برای یادآوری در نظر گرفته شده تا با یک Job/Scheduler زمان‌بندی‌شده تکمیل شود.

## قدم بعدی پیشنهادی (اختیاری)

- اتصال واقعی به درگاه پرداخت و سرویس پیامکی
- پنل مدیریت تصویری (Filament)
- سیستم ضدتقلب پیشرفته‌تر (بند ۱۶ سند)
- تست‌های Feature برای مسیرهای حساس
