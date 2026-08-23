# بسته نهایی سایت اتحادیار 6.5

این پوشه نسخه نهایی، استاتیک و بدون Build سایت **etehadyar.ir** است. برای اجرا به npm، Composer، CDN یا کتابخانه خارجی نیاز ندارد.

## انتشار روی دامنه اصلی

محتویات پوشه را داخل روت دامنه آپلود کنید:

```text
public_html/
├── index.html
├── changelog/
├── assets/
├── .htaccess
├── robots.txt
└── sitemap.xml
```

آدرس‌ها:

- صفحه اصلی: `https://etehadyar.ir/`
- تاریخچه کامل: `https://etehadyar.ir/changelog/`
- Sitemap: `https://etehadyar.ir/sitemap.xml`

## مهم: لینک Checkout را تنظیم کنید

فایل زیر را باز کنید:

```text
assets/js/config.js
```

و URLهای خرید را با لینک واقعی پرداخت جایگزین کنید:

```js
purchaseUrlIR: "https://example.com/checkout-ir",
purchaseUrlInternational: "https://example.com/checkout-usd",
```

تمام دکمه‌های خرید در صفحه اصلی و Changelog از همین تنظیم استفاده می‌کنند.

## قیمت تنظیم‌شده

- پرداخت داخلی: **۱۵٬۰۰۰٬۰۰۰ تومان**
- پرداخت بین‌المللی: **۱۵۰ دلار**

قیمت‌ها در HTML، Config و Structured Data ثبت شده‌اند. در صورت تغییر قیمت، هر سه قسمت را هماهنگ کنید.

## نسخه و تاریخچه

- نسخه فعلی: **6.5.0**
- نسخه‌های اصلی مستندشده: **54 نسخه**
- مسیر Alpha جداگانه: **1 نسخه**
- تعداد تغییرات ثبت‌شده در Changelog: **361 مورد**

## قابلیت‌های سایت

- Hero سینمایی با تصویر بنیان‌گذار و معماری محصول
- نمایش واقعی پنل‌های Dashboard، Factory، Video، WooCommerce و Reports
- Product Tour تعاملی
- نمایش تصاویر Fullscreen با Lightbox
- صفحه فارسی و انگلیسی
- قیمت تومان و دلار
- Changelog قابل جستجو و فیلتر
- فونت Local فارسی Estedad و انگلیسی Inter
- تصاویر WebP بهینه‌شده
- Open Graph اختصاصی
- JSON-LD محصول و TechArticle
- Sitemap و Robots
- Cache و Compression در `.htaccess`
- Responsive کامل برای موبایل و دسکتاپ
- Reduced Motion و کنترل کیبورد

## ساختار فایل‌ها

```text
etehadyar-final/
├── index.html
├── CHANGELOG.md
├── README-FA.md
├── robots.txt
├── sitemap.xml
├── .htaccess
├── changelog/
│   └── index.html
└── assets/
    ├── css/
    │   ├── site.css
    │   └── changelog.css
    ├── js/
    │   ├── config.js
    │   ├── site.js
    │   └── changelog.js
    ├── fonts/
    ├── icons/
    └── images/
        ├── app-icon.webp
        ├── founder-hero.webp
        ├── brand-cover.webp
        ├── og-cover.jpg
        └── ui/
```

## اگر در زیرپوشه منتشر می‌کنید

نسخه فعلی برای روت `https://etehadyar.ir/` تنظیم شده است. اگر سایت را مثلاً در `/ai/` قرار می‌دهید، Canonical و Open Graph را در فایل‌های زیر اصلاح کنید:

- `index.html`
- `changelog/index.html`
- `sitemap.xml`
- `robots.txt`
- `assets/js/config.js`

## تست بعد از آپلود

1. صفحه اصلی و `/changelog/` را باز کنید.
2. تغییر زبان فارسی/انگلیسی را تست کنید.
3. Currency Switch تومان/دلار را تست کنید.
4. لینک تمام دکمه‌های خرید را بررسی کنید.
5. Product Tour و Lightbox تصاویر را تست کنید.
6. در Changelog عبارت‌هایی مثل `ویدیو`، `Automation` یا `Quality Gate` را جستجو کنید.
7. حالت موبایل و CTA ثابت پایین صفحه را بررسی کنید.
8. کش WordPress، سرور و CDN را پاک کنید.

## نکته امنیتی

فایل سایت هیچ API Key یا اطلاعات حساس ندارد. کلیدهای افزونه باید فقط در WordPress و Vault سمت سرور نگهداری شوند.
