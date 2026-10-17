# ACTIVE PROJECT STATE & MULTI-AI CONTINUOUS MEMORY (PROJECT_STATE.md)

## Current state — 2026-10-17 — استقرار کامل اسناد مستر UMAC-P و آماده‌سازی ریدیزاین UI/UX (MASTER-01 & MASTER-02) [Agent: Antigravity]
- **دستور کارفرما (Mandate):** «فایلای مستر رو بسازی... از این لحظه، تو موظف هستی ۱۰۰٪ بر اساس پروتکل جامع معماری مستر و حافظه شناختی مشترک (UMAC-P) عمل کنی و هیچ پاسخ یا کدی خارج از این چارچوب پذیرفته نیست... میخوام ری دیزاین بکنم سیستم رو کلا از لحاظ ui ux»
- **تغییرات کلیدی معماری:**
  - استقرار ۱۰۰٪ ساختار درختی `docs/ai/` شامل سند والد ریشه (`ROOT_MASTER.md`)، پنج سند والد دامنه در `domains/`، دفتر تصمیمات (`DECISIONS.md`) با شناسه‌های DEC-0001 تا DEC-0005، شاخص رادار کد (`INDEX.md`)، نیازمندی‌ها و نقشه راه (`BACKLOG.md`) و گاه‌شمار تاریخی (`CHANGELOG.md`).
  - تدوین اسناد اجرایی فرزند `MASTER_01_SYSTEM_BASELINE.md` (تثبیت و مستندسازی سیستم پایه) و `MASTER_02_UI_UX_REDESIGN.md` (معماری و نقشه ریدیزاین مدرن موبایل‌فرست).
  - تثبیت و پشتیبان‌گیری کامل دیتابیس و کدهای سالم در مخزن گیت‌هاب (`elyas5203/jalasecharshanbe`) روی کامیت `acea9af` با قابلیت رول‌بک قطعی.
- **فایل‌های تغییر یافته:**
  - `docs/ai/ROOT_MASTER.md` (سند والد کلان سیستم)
  - `docs/ai/PROJECT_STATE.md` (حافظه زنده و فعال هوش‌ها)
  - `docs/ai/CHANGELOG.md` (گاه‌شمار رخدادهای مهندسی)
  - `docs/ai/DECISIONS.md` (دفتر تصمیمات ثبت‌شده DEC-0001 تا DEC-0005)
  - `docs/ai/BACKLOG.md` (فهرست نیازمندی‌های فازبندی‌شده ریدیزاین)
  - `docs/ai/INDEX.md` (رادار جستجوی سریع خطوط و موجودیت‌ها)
  - `docs/ai/domains/AUTH_AND_SECURITY.md` (دامنه امنیت، نشست و CSRF)
  - `docs/ai/domains/STUDENTS_AND_ATTENDANCE.md` (دامنه متربیان، حضور و امتیاز)
  - `docs/ai/domains/CURRICULUM_AND_ACTIVITIES.md` (دامنه طرح درس، سین، ملزومات و ابزارها)
  - `docs/ai/domains/INTELLIGENCE_MI_SYSTEM.md` (دامنه هوش هشت‌گانه و استعدادیابی)
  - `docs/ai/domains/INFRASTRUCTURE_AUTOMATION.md` (دامنه تلگرام، بکاپ و آفلاین PWA)
  - `docs/ai/masters/MASTER_01_SYSTEM_BASELINE.md` (سند فرزند اجرایی تسک ۱)
  - `docs/ai/masters/MASTER_02_UI_UX_REDESIGN.md` (سند فرزند اجرایی تسک ۲)
- **اعتبارسنجی:**
  - انطباق ۱۰۰٪ با پروتکل جامع معماری مستر (UMAC-P).
  - بررسی خط‌به‌خط ساختار فایل‌ها، سینتکس دیاگرام‌های Mermaid و ارجاعات میکروسکوپی.
  - تست اتصال و وضعیت سینک مخزن گیت.
- **تسک بعدی پیشنهادی:** دریافت تایید و جزئیات سلیقه بصری کارفرما برای شروع فاز ۱ پیاده‌سازی ریدیزاین UI/UX بر اساس سند `MASTER_02_UI_UX_REDESIGN.md`.

---

## وضعیت فعلی سیستم (Current Baseline Summary)
- **پلتفرم:** PHP 8.x + MySQL + Vanilla JS در XAMPP لوکال.
- **دیتابیس:** دیتابیس `charshanbe_jalase` با اسکیمای نسخه ۸ فعال و بارگذاری داده‌های واقعی CSV (۱۳ متربی، ۲۲ جلسه، ۲۰۰ رکورد حضور، ۲۱۴ لاگ هوش).
- **مخزن گیت:** متصل به `https://github.com/elyas5203/jalasecharshanbe.git` روی برنچ `main`.
- **خطوط قرمز:** حفظ ۱۰۰٪ کارکردهای فعلی، استقلال کامل از CDN، حفظ داده‌های دیتابیس و عدم حذف کدهای پایدار.
