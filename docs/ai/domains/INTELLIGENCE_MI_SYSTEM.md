# DOMAIN MASTER: MULTIPLE INTELLIGENCES & TALENT DISCOVERY (INTELLIGENCE_MI_SYSTEM.md)

> **سند والد دامنه (Parent Domain Master)**  
> **حوزه دامنه:** هوش‌های چندگانه هوارد گاردنر (۸ حوزه)، ثبت مشاهدات رفتاری، شاخص‌های استعدادیابی و نمودارهای تحلیلی  
> **سند والد:** [`ROOT_MASTER.md`](file:///c:/xampp/htdocs/CharShanbeJalase/docs/ai/ROOT_MASTER.md)  
> **مسیر سند:** `docs/ai/domains/INTELLIGENCE_MI_SYSTEM.md`  
> **وضعیت:** ACTIVE & VERIFIED  

---

## ۱. مأموریت و مبانی علمی دامنه هوش (Domain Mission & Principles)

سامانه بر پایه نظریه علمی **هوش‌های چندگانه هوارد گاردنر (Howard Gardner)** توسعه یافته و ۸ هوش بنیادین را پایش می‌کند:
1. 🗣️ **زبانی - کلامی (Linguistic):** بیان شیوا، درک مطلب، دایره واژگان.
2. 🔢 **منطقی - ریاضی (Logical-Mathematical):** حل مسئله، استدلال، تفکر محاسباتی.
3. 🎨 **تصویری - فضایی (Visual-Spatial):** تجسم ذهنی، نقشه‌خوانی، هنر و طراحی.
4. ⚽ **بدنی - جنبشی (Bodily-Kinesthetic):** مهارت‌های حرکتی، تعادل، ورزش، زبان بدن.
5. 🎵 **موسیقایی - ریتمیک (Musical):** درک ضرب‌آهنگ، تمپو، حساسیت به صداها.
6. 🤝 **میان‌فردی (Interpersonal):** همدلی، رهبری گروه، تعامل موثر، روابط اجتماعی.
7. 🧘 **درون‌فردی (Intrapersonal):** خودآگاهی، مدیریت خشم و احساسات، استقلال رای.
8. 🌿 **طبیعت‌گرا (Naturalistic):** انس با طبیعت و محیط‌زیست، شناخت جانداران و پدیده‌ها.

### ساختار لاگ‌گذاری مشاهدات:
- هر مشاهده شامل: دانش‌آموز، نوع هوش، رفتار ثبت‌شده، وزن امتیاز (+۱ یا -۱)، بستر مشاهده (`context` مثل کلاس، حیاط، اردو) و منبع مشاهده (`source` مثل مربی، همیار، ولی).
- **نمایش نمودار:** نمودارها به صورت میله‌ای افقی (Horizontal Bar Chart) بهینه‌شده برای موبایل رسم می‌شوند تا والدین و مربیان با یک نگاه توزیع توانمندی‌های متربی را بسنجند.

---

## ۲. دیاگرام جریان ارزیابی و ثبت هوش (Intelligence Observation Flow)

```mermaid
flowchart TD
    CoachObs["مربی: مشاهده رفتار متربی در حین فعالیت\n(file: parts/mi-new.js)"] --> SelectStudent["انتخاب دانش‌آموز و هوش هدف"]
    
    SelectStudent --> PickBehavior{"نوع رفتار مشاهده‌شده"}
    PickBehavior -->|رفتار مثبت (+)| PositiveScore["امتیاز مثبت (+1 یا +2)"]
    PickBehavior -->|رفتار نیازمند تقویت (-)| NegativeScore["امتیاز اصلاحی (-1)"]
    
    PositiveScore --> AttachMeta["انتخاب زمینه (کلاس/حیاط) و منبع مشاهده"]
    NegativeScore --> AttachMeta
    
    AttachMeta --> SubmitObservation["ارسال به submitMIObservation در API\n(file: api.php#L610)"]
    
    subgraph DataPersistence ["تثبیت در پایگاه داده نسخه ۸"]
        SubmitObservation --> LogObservation["ثبت رخداد در جدول mi_observations_v2\n(file: api.php#L625)"]
        LogObservation --> LegacySync["ثبت پشتیبان در mi_logs_legacy جهت حفظ سازگاری"]
        LegacySync --> AggregateScores["محاسبه مجدد میانگین امتیاز در mi_domain_scores"]
    end
    
    AggregateScores --> RefreshUI["به‌روزرسانی لحظه‌ای نمودار هوش در پروفایل دانش‌آموز\n(file: parts/chart.js)"]
```

---

## ۳. ارجاعات کد میکروسکوپی (Deterministic Code References)

| قابلیت / مؤلفه | فایل مرجع | محدوده خطوط | نام نماد | توضیحات فنی |
|---|---|---|---|---|
| **تعریف ساختار دیتابیس**| `bootstrap.php` | [`#L65-L66`](file:///c:/xampp/htdocs/CharShanbeJalase/bootstrap.php#L65-L66) | جدول‌های `mi_config`, `mi_logs` | ساختار جدول‌های اولیه هوش و رفتارها |
| **مایگریشن پیشرفته نسخه ۸**| `bootstrap.php`| [`#L120-L245`](file:///c:/xampp/htdocs/CharShanbeJalase/bootstrap.php#L120-L245)| تابع `migrateMISystem($pdo)` | ایجاد جداول تخصصی دامنه‌ها، زمینه‌ها، منابع و امتیازات |
| **واکشی دامنه‌های هوش** | `api.php` | [`#L550-L580`](file:///c:/xampp/htdocs/CharShanbeJalase/api.php#L550-L580) | تابع `getIntelligenceDomains($pdo)` | دریافت لیست ۸ هوش همراه با رفتارهای مثبت و منفی |
| **ثبت هوشمند مشاهده** | `api.php` | [`#L605-L660`](file:///c:/xampp/htdocs/CharShanbeJalase/api.php#L605-L660) | تابع `submitMIObservation($pdo, ...)`| ذخیره هم‌زمان در جدول جدید و لگسی برای سازگاری کامل |
| **پروفایل هوش متربی** | `api.php` | [`#L670-L720`](file:///c:/xampp/htdocs/CharShanbeJalase/api.php#L670-L720) | تابع `getStudentMIProfileNew($pdo, $name)`| تولید خروجی داده برای نمودار میله‌ای و ریز لاگ‌ها |
| **ماژول رابط کاربری فرانت**| `parts/mi-new.js`| [`#L1-L220`](file:///c:/xampp/htdocs/CharShanbeJalase/parts/mi-new.js#L1-L220) | کامپوننت UI ثبت و بازبینی هوش | رابط واکنش‌گرا و سریع برای انتخاب رفتار با تاچ آسان |
| **رسم میله‌ای نمودار** | `parts/chart.js`| [`#L180-L270`](file:///c:/xampp/htdocs/CharShanbeJalase/parts/chart.js#L180-L270)| موتور محلی رسم Bar Chart | نمایش میله‌های رنگی افقی درصد انطباق هوش در صفحه متربی |

---

## ۴. منشور نبایدهای دامنه هوش و استعدادیابی (Domain Invariants)

> ⛔ **خطوط قرمز دامنه هوش:**

1. **نباید هیچ داده تاریخی در مهاجرت‌ها مفقود شود:** ارتقا به نسخه جدید MI باید صددرصد لاگ‌های گذشته را در جدول `mi_logs_legacy` حفظ کند (`MI_MIGRATION_REPORT.md`).
2. **نباید نمودار هوش به کتابخانه‌های آنلاین متکی باشد:** رسم نمودارهای هوش باید کاملاً با جاوااسکریپت و بدون وابستگی به CDN خارجی انجام شود (`parts/chart.js`).
3. **نباید عناوین هوش‌ها به طور دلخواه دستکاری شوند:** دسته‌بندی هشت‌گانه گاردنر استاندارد علمی سیستم است و هرگونه تغییر در اسامی پایه باید با هماهنگی سند والد انجام شود.
4. **نباید امتیازهای هوش با امتیازات انضباطی کلاسی ترکیب شوند:** امتیازات هوش صرفاً سنجه استعدادیابی کیفی هستند و با امتیازات حضور و غیاب در جدول `students.score` جمع ریاضی نمی‌شوند.
