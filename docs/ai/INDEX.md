# CODEBASE RADAR & SYMBOL INDEX (INDEX.md)
# شاخص و رادار جستجوی سریع نمادها، توابع و فایل‌ها

> **سند شاخص و رادار کد (Microscopic Codebase Index)**  
> **پروتکل مرجع:** پروتکل جامع معماری مستر و حافظه شناختی مشترک (UMAC-P)  
> **مسیر سند:** `docs/ai/INDEX.md`  
> **هدف:** نگاشت فوری هر واژه، کلیدواژه، موجودیت و تابع به مسیر فایل و خط دقیق برای هدایت هوش‌های بعدی.

---

## ۱. شاخص موجودیت‌های پایگاه داده (Database Tables & Models)

| نام جدول / کلیدواژه | فایل تعریف اسکیما | خط دقیق | شرح موجودیت |
|---|---|---|---|
| `students` | `bootstrap.php` | [`#L58`](file:///c:/xampp/htdocs/CharShanbeJalase/bootstrap.php#L58) | پرونده متربیان، نمرات، عکس و اطلاعات تماس |
| `attendance_sessions` | `bootstrap.php` | [`#L59`](file:///c:/xampp/htdocs/CharShanbeJalase/bootstrap.php#L59) | سربرگ جلسات حضور و غیاب بر پایه تاریخ یکتا |
| `attendance_records` | `bootstrap.php` | [`#L60`](file:///c:/xampp/htdocs/CharShanbeJalase/bootstrap.php#L60) | وضعیت حضور تک‌تک متربیان در هر جلسه همراه با دقایق تاخیر |
| `settings` | `bootstrap.php` | [`#L61`](file:///c:/xampp/htdocs/CharShanbeJalase/bootstrap.php#L61) | تنظیمات کلید-مقداری سراسری و ربات تلگرام |
| `notes` | `bootstrap.php` | [`#L62`](file:///c:/xampp/htdocs/CharShanbeJalase/bootstrap.php#L62) | یادداشت‌ها و صورتجلسات ادواری مربیان |
| `plans` | `bootstrap.php` | [`#L63`](file:///c:/xampp/htdocs/CharShanbeJalase/bootstrap.php#L63) | طرح درس‌های جامع، سین برنامه و ماژول‌ها |
| `essentials` | `bootstrap.php` | [`#L64`](file:///c:/xampp/htdocs/CharShanbeJalase/bootstrap.php#L64) | وسایل و ملزومات کلاسی (تفکیک به موجود یا خرید) |
| `mi_config` | `bootstrap.php` | [`#L65`](file:///c:/xampp/htdocs/CharShanbeJalase/bootstrap.php#L65) | تنظیمات پایه‌ای هوش‌های هشت‌گانه گاردنر |
| `mi_logs` | `bootstrap.php` | [`#L66`](file:///c:/xampp/htdocs/CharShanbeJalase/bootstrap.php#L66) | سوابق و لاگ‌های مشاهدات رفتاری هوش |
| `archive_logs` | `bootstrap.php` | [`#L67`](file:///c:/xampp/htdocs/CharShanbeJalase/bootstrap.php#L67) | آرشیو فعالیت‌های انجام‌شده آزاد و گروه‌بندی‌ها |
| `plan_files` | `bootstrap.php` | [`#L68`](file:///c:/xampp/htdocs/CharShanbeJalase/bootstrap.php#L68) | فایل‌ها و پیوست‌های چندرسانه‌ای طرح درس‌ها |
| `telegram_queue` | `bootstrap.php` | [`#L69`](file:///c:/xampp/htdocs/CharShanbeJalase/bootstrap.php#L69) | صف ارسال پیام‌ها و اعلانات آفلاین تلگرام |
| `users` | `bootstrap.php` | [`#L85`](file:///c:/xampp/htdocs/CharShanbeJalase/bootstrap.php#L85) | کاربران سیستم و مدیران سامانه چندمستأجری |

---

## ۲. شاخص توابع کلیدی بک‌اند (Backend Functions & Controllers)

| نام تابع / نماد | فایل مرجع | محدوده خطوط | ماژول / مسئولیت |
|---|---|---|---|
| `db()` | `bootstrap.php` | [`#L9-L39`](file:///c:/xampp/htdocs/CharShanbeJalase/bootstrap.php#L9-L39) | ایجاد و بازگرداندن شیء اتصال امن PDO |
| `initSchema()` | `bootstrap.php` | [`#L57-L95`](file:///c:/xampp/htdocs/CharShanbeJalase/bootstrap.php#L57-L95) | مقداردهی اولیه پایگاه‌داده و جداول |
| `migrateMISystem()` | `bootstrap.php` | [`#L120-L245`](file:///c:/xampp/htdocs/CharShanbeJalase/bootstrap.php#L120-L245) | مایگریشن سیستم هوش چندگانه به نسخه ۸ |
| `lateScore()` | `bootstrap.php` | [`#L422-L429`](file:///c:/xampp/htdocs/CharShanbeJalase/bootstrap.php#L422-L429) | محاسبه پلکانی امتیاز تاخیر بر اساس دقایق |
| `updateCalculations()` | `bootstrap.php` | [`#L430-L460`](file:///c:/xampp/htdocs/CharShanbeJalase/bootstrap.php#L430-L460) | محاسبه ریاضی دقیق حضور، غیبت، تاخیر پلکانی و امتیازات |
| `telegramApi()` | `bootstrap.php` | [`#L465-L510`](file:///c:/xampp/htdocs/CharShanbeJalase/bootstrap.php#L465-L510) | کلاینت ارتباط با متدهای مختلف ربات تلگرام |
| `authBootSession()` | `auth.php` | [`#L15-L44`](file:///c:/xampp/htdocs/CharShanbeJalase/auth.php#L15-L44) | راه‌اندازی سشن امن و چرخش ۳۰ دقیقه‌ای شناسه |
| `csrfToken()` / `csrfCheck()` | `auth.php` | [`#L49-L65`](file:///c:/xampp/htdocs/CharShanbeJalase/auth.php#L49-L65) | تولید و اعتبارسنجی توکن‌های امنیتی CSRF |
| `getAppData()` | `api.php` | [`#L114-L125`](file:///c:/xampp/htdocs/CharShanbeJalase/api.php#L114-L125) | تجمیع تمام اطلاعات مورد نیاز صفحه اول داشبورد |
| `getTrendData()` | `api.php` | [`#L175-L231`](file:///c:/xampp/htdocs/CharShanbeJalase/api.php#L175-L231) | روند جلسات و محاسبه درصدهای وزنی تاخیر پلکانی |
| `submitAttendance()` | `api.php` | [`#L349-L373`](file:///c:/xampp/htdocs/CharShanbeJalase/api.php#L349-L373) | ذخیره اتمیک حضور و غیاب جلسه با دقایق تاخیر |
| `getStudentDetails()` | `api.php` | [`#L593-L615`](file:///c:/xampp/htdocs/CharShanbeJalase/api.php#L593-L615) | واکشی مشخصات، آمار تفکیکی و امتیاز سیستمی و رشد |
| `savePlan()` | `api.php` | [`#L510-L560`](file:///c:/xampp/htdocs/CharShanbeJalase/api.php#L510-L560) | درج/به‌روزرسانی طرح درس و همگام‌سازی ملزومات |
| `submitMIObservation()` | `api.php` | [`#L890-L945`](file:///c:/xampp/htdocs/CharShanbeJalase/api.php#L890-L945) | ثبت رخداد هوش در جداول نسخه جدید و لگسی |
| `uploadStudentImage()` | `api.php` | [`#L980-L1010`](file:///c:/xampp/htdocs/CharShanbeJalase/api.php#L980-L1010) | دریافت و ذخیره عکس محلی متربی در دیسک |

---

## ۳. شاخص کامپوننت‌ها و توابع فرانت‌اند (Frontend Symbols)

| نام تابع / متغیر | فایل مرجع | محدوده خطوط | نقش در رابط کاربری |
|---|---|---|---|
| `fetchData()` | `index.php` / `parts/app.js` | [`#L30-L65`](file:///c:/xampp/htdocs/CharShanbeJalase/parts/app.js#L30-L65) | واکشی اولیه تمام داده‌های اپلیکیشن |
| `renderDash()` | `parts/app.js` | [`#L70-L110`](file:///c:/xampp/htdocs/CharShanbeJalase/parts/app.js#L70-L110) | رندر کارت‌های متربی برتر، آمار و لیست داشبورد |
| `renderAtt()` | `parts/app.js` | [`#L130-L175`](file:///c:/xampp/htdocs/CharShanbeJalase/parts/app.js#L130-L175) | رندر لیست حضور و غیاب با سوئیچ‌های وضعیت |
| `renderPlans()` | `parts/app.js` | [`#L200-L260`](file:///c:/xampp/htdocs/CharShanbeJalase/parts/app.js#L200-L260) | رندر کارت‌های طرح درس با تم رنگی ماژول‌ها |
| `renderAttendanceChart()` | `parts/chart.js` | [`#L45-L120`](file:///c:/xampp/htdocs/CharShanbeJalase/parts/chart.js#L45-L120) | رسم نمودار تعاملی روند جلسات با نقطه لمس |
| `renderMIHorizontalChart()` | `parts/chart.js` | [`#L180-L240`](file:///c:/xampp/htdocs/CharShanbeJalase/parts/chart.js#L180-L240) | رسم میله‌های هوش چندگانه برای صفحه متربی |
| `openDatePickerFor()` | `index.php` | [`#L150-L190`](file:///c:/xampp/htdocs/CharShanbeJalase/index.php#L150-L190) | باز کردن اسپینر انتخاب تاریخ شمسی |
