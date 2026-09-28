# HAMISTA

<div dir="rtl" lang="fa">

## معرفی (فارسی)

هامیستا یک اکوسیستم وردپرسی برای یک «استودیوی خلاق کسب‌وکار» است: فروش افزونه، قالب، نرم‌افزار و خدمات برندینگ. زبان اصلی فارسی (راست‌به‌چپ) و زبان دوم انگلیسی است؛ حالت روشن پیش‌فرض است و حالت تیره هم پشتیبانی می‌شود.

### بسته‌ها

| بسته | کاربرد |
|---|---|
| قالب `hamista` | ظاهر سایت و سیستم طراحی (توکن‌ها و مؤلفه‌های رابط کاربری) که همه‌ی افزونه‌ها از آن استفاده می‌کنند |
| `hamista-core` | افزونه‌ی پایه: پنل «تنظیمات هامیستا»، ماژول‌ها، مؤلفه‌ها، تاریخ شمسی، امنیت و کارایی |
| `hamista-license-manager` | صدور و مدیریت لایسنس، API فعال‌سازی و به‌روزرسانی، دانلود امن نسخه‌ها |
| `hamista-customer-dashboard` | پنل مشتری روی «حساب کاربری» ووکامرس: تیکت، فاکتور و اعلان |
| `hamista-service-orders` | سفارش خدمات با پرسش‌نامه، پرداخت و پیگیری مرحله‌به‌مرحله |

### پیش‌نیازها

PHP نسخه‌ی ۸٫۱ یا بالاتر، وردپرس ۶٫۵ یا بالاتر (آزموده‌شده تا ۷٫۱)، ووکامرس ۸ یا بالاتر (آزموده‌شده تا ۱۱٫۳). المنتور اختیاری است. برای ساخت فایل‌ها Node.js نسخه‌ی ۲۲ لازم است.

### ساخت

```bash
npm install        # ابزارهای ساخت و فونت‌ها
npm run build      # فونت‌ها، ساخت CSS قالب و فایل‌های ‎.min
npm run zip        # بسته‌های نصبی در پوشه‌ی dist/
```

راهنمای نصب و راهنمای کاربر فارسی در پوشه‌ی `docs/fa/` قرار دارد.

</div>

## Overview (English)

HAMISTA is a WordPress ecosystem for a "Creative Business Studio" that sells plugins, themes,
software and branding services. Persian (RTL) is the primary language and English (LTR) the
second; light mode is the default and dark mode is fully supported.

### Packages

| Package | What it is |
|---|---|
| `wp-content/themes/hamista` | Classic theme that owns the design system (tokens + UI primitives) every plugin reuses |
| `wp-content/plugins/hamista-core` | Foundation: the unified settings panel, modules, components, Jalali dates, security, performance |
| `wp-content/plugins/hamista-license-manager` | License keys, activation/update REST API, signed downloads of releases |
| `wp-content/plugins/hamista-customer-dashboard` | Customer panel on WooCommerce My Account: tickets, invoices, notifications |
| `wp-content/plugins/hamista-service-orders` | Service ordering with questionnaires, payment and a tracked workflow |

### Repository layout

```
wp-content/themes/hamista/     theme (text domain hamista); CSS sources in assets/css/src/
wp-content/plugins/hamista-*/  the four plugins (hamista-core is required by the others)
demo/elementor-templates/      exported Elementor templates for manual import
docs/                          fa/ user guides, en/ developer docs, reports; superpowers/ = spec + plan
tools/                         build-assets.mjs, build-zips.sh, i18n/compile.php, tests/, icons/
dist/                          release ZIPs
```

### Requirements

- PHP 8.1+ (tested on 8.4), WordPress 6.5+ (tested up to 7.1), WooCommerce 8.0+ (tested up to 11.3), Elementor 3.20+ optional.
- Building assets: Node.js 22 and npm. Compiling translations: PHP CLI and a WordPress checkout.

### Build commands

```bash
npm install                                  # esbuild, lightningcss and the fonts
npm run build                                # fonts, theme CSS bundles (+ core's ui.css), .min for every package, size report
npm run fonts                                # copy the web fonts only
npm run zip                                  # versioned ZIPs into dist/
php tools/i18n/compile.php --all             # .po → .mo + .l10n.php (HAMISTA_WP_PATH = WordPress to load POMO from)
```

The design tokens and UI primitives have one source, `wp-content/themes/hamista/assets/css/src/`:
`npm run build` writes the theme's `main.css` and core's fallback `hamista-core/assets/css/ui.css`
from it. Never edit generated `.css` / `.min.*` files directly.

### Documentation

- Design spec and implementation plan: `docs/superpowers/`
- Persian installation and user guides: `docs/fa/`
- English developer docs, License API and SDK: `docs/en/`
- Reports: `docs/security.md`, `docs/performance-report.md`
