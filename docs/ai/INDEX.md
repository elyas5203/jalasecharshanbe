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
| `attendance_records` | `bootstrap.php` | [`#L60`](file:///c:/xampp/htdocs/CharShanbeJalase/bootstrap.php#L60) | وضعیت حضور تک‌تک متربیان در هر جلسه همراه با دقایق تاخیر و علت غیبت (`absence_reason`) |
| `settings` | `bootstrap.php` | [`#L61`](file:///c:/xampp/htdocs/CharShanbeJalase/bootstrap.php#L61) | تنظیمات کلید-مقداری سراسری و ربات تلگرام |
| `notes` | `bootstrap.php` | [`#L62`](file:///c:/xampp/htdocs/CharShanbeJalase/bootstrap.php#L62) | یادداشت‌ها و صورتجلسات ادواری مربیان |
| `plans` | `bootstrap.php` | [`#L63`](file:///c:/xampp/htdocs/CharShanbeJalase/bootstrap.php#L63) | طرح درس‌های جامع، سین برنامه و ماژول‌ها |
| `essentials` | `bootstrap.php` | [`#L64`](file:///c:/xampp/htdocs/CharShanbeJalase/bootstrap.php#L64) | وسایل و ملزومات کلاسی (تفکیک به موجود یا خرید) |
| `mi_config` / `intelligence_domains` | `bootstrap.php` | [`#L65`](file:///c:/xampp/htdocs/CharShanbeJalase/bootstrap.php#L65) | تنظیمات پایه‌ای و حوزه‌های هشت‌گانه هوش‌های گاردنر |
| `mi_logs` / `intelligence_observations` | `bootstrap.php` | [`#L66`](file:///c:/xampp/htdocs/CharShanbeJalase/bootstrap.php#L66) | سوابق لاگ‌های ارزیابی سه‌منبعه هوش (والدین، مربی، فعالیت عملی) |
| `practical_activities` | `bootstrap.php` | [`#L185`](file:///c:/xampp/htdocs/CharShanbeJalase/bootstrap.php#L185) | کاتالوگ فعالیت‌ها و چالش‌های عملی استاندارد دوره‌ای |
| `archive_logs` | `bootstrap.php` | [`#L67`](file:///c:/xampp/htdocs/CharShanbeJalase/bootstrap.php#L67) | آرشیو فعالیت‌های انجام‌شده آزاد و گروه‌بندی‌ها |
| `plan_files` | `bootstrap.php` | [`#L68`](file:///c:/xampp/htdocs/CharShanbeJalase/bootstrap.php#L68) | فایل‌ها و پیوست‌های چندرسانه‌ای طرح درس‌ها |
| `mi_parent_interviews` | `bootstrap.php` | [`#L310`](file:///c:/xampp/htdocs/CharShanbeJalase/bootstrap.php#L310) | پاسخ‌های مصاحبه تکمیلی و سوالات کیفی والدین (صفحات ۸ و ۹ فرم چاپی) |
| `telegram_queue` | `bootstrap.php` | [`#L69`](file:///c:/xampp/htdocs/CharShanbeJalase/bootstrap.php#L69) | صف ارسال پیام‌ها و اعلانات آفلاین تلگرام |
| `users` | `bootstrap.php` | [`#L85`](file:///c:/xampp/htdocs/CharShanbeJalase/bootstrap.php#L85) | کاربران سیستم و مدیران سامانه چندمستأجری |

---

## ۲. شاخص توابع کلیدی بک‌اند (Backend Functions & Controllers)

| نام تابع / نماد | فایل مرجع | محدوده خطوط | ماژول / مسئولیت |
|---|---|---|---|
| `db()` | `bootstrap.php` | [`#L9-L39`](file:///c:/xampp/htdocs/CharShanbeJalase/bootstrap.php#L9-L39) | ایجاد و بازگرداندن شیء اتصال امن PDO |
| `initSchema()` | `bootstrap.php` | [`#L57-L95`](file:///c:/xampp/htdocs/CharShanbeJalase/bootstrap.php#L57-L95) | مقداردهی اولیه پایگاه‌داده و جداول |
| `migrateMISystem()` | `bootstrap.php` | [`#L120-L245`](file:///c:/xampp/htdocs/CharShanbeJalase/bootstrap.php#L120-L245) | مایگریشن سیستم هوش چندگانه به نسخه ۱۱ |
| `lateScore()` | `bootstrap.php` | [`#L422-L429`](file:///c:/xampp/htdocs/CharShanbeJalase/bootstrap.php#L422-L429) | محاسبه پلکانی امتیاز تاخیر بر اساس دقایق |
| `updateCalculations()` | `bootstrap.php` | [`#L430-L460`](file:///c:/xampp/htdocs/CharShanbeJalase/bootstrap.php#L430-L460) | محاسبه ریاضی دقیق حضور، غیبت، تاخیر پلکانی و امتیازات |
| `telegramApi()` | `bootstrap.php` | [`#L465-L510`](file:///c:/xampp/htdocs/CharShanbeJalase/bootstrap.php#L465-L510) | کلاینت ارتباط با متدهای مختلف ربات تلگرام |
| `authBootSession()` | `auth.php` | [`#L15-L44`](file:///c:/xampp/htdocs/CharShanbeJalase/auth.php#L15-L44) | راه‌اندازی سشن امن و چرخش ۳۰ دقیقه‌ای شناسه |
| `csrfToken()` / `csrfCheck()` | `auth.php` | [`#L49-L65`](file:///c:/xampp/htdocs/CharShanbeJalase/auth.php#L49-L65) | تولید و اعتبارسنجی توکن‌های امنیتی CSRF |
| `getAppData()` | `api.php` | [`#L114-L125`](file:///c:/xampp/htdocs/CharShanbeJalase/api.php#L114-L125) | تجمیع تمام اطلاعات مورد نیاز صفحه اول داشبورد |
| `getTrendData()` | `api.php` | [`#L175-L231`](file:///c:/xampp/htdocs/CharShanbeJalase/api.php#L175-L231) | روند جلسات و محاسبه درصدهای وزنی تاخیر پلکانی |
| `submitAttendance()` | `api.php` | [`#L349-L373`](file:///c:/xampp/htdocs/CharShanbeJalase/api.php#L349-L373) | ذخیره اتمیک حضور و غیاب جلسه با دقایق تاخیر و علت غیبت |
| `getStudentDetails()` | `api.php` | [`#L593-L615`](file:///c:/xampp/htdocs/CharShanbeJalase/api.php#L593-L615) | واکشی مشخصات، آمار تفکیکی و امتیاز سیستمی و رشد |
| `savePlan()` | `api.php` | [`#L510-L560`](file:///c:/xampp/htdocs/CharShanbeJalase/api.php#L510-L560) | درج/به‌روزرسانی طرح درس و همگام‌سازی ملزومات |
| `submitMIObservation()` | `api.php` | [`#L873-L948`](file:///c:/xampp/htdocs/CharShanbeJalase/api.php#L873-L948) | ثبت یا به‌روزرسانی رخداد مشاهده هوش بدون ایجاد رکوردهای تکراری |
| `saveParentDomainBatch()` | `api.php` | [`#L950-L1010`](file:///c:/xampp/htdocs/CharShanbeJalase/api.php#L950-L1010) | ذخیره یکپارچه پاسخ‌های ۸ گویه والدین و مثال عینی هر حوزه در یک تراکنش |
| `generateMIAIPrompt()` | `api.php` | [`#L1150-L1250`](file:///c:/xampp/htdocs/CharShanbeJalase/api.php#L1150-L1250) | ساخت پرونده جامع Markdown برای ارسال به ChatGPT/Claude |
| `uploadStudentImage()` | `api.php` | [`#L1080-L1110`](file:///c:/xampp/htdocs/CharShanbeJalase/api.php#L1080-L1110) | دریافت و ذخیره عکس محلی متربی در دیسک |

---

## ۳. شاخص کامپوننت‌ها و توابع فرانت‌اند (Frontend Symbols)

| نام تابع / متغیر | فایل مرجع | محدوده خطوط | نقش در رابط کاربری |
| `escapeHtml()` | `parts/app.js` | [`#L2-L13`](file:///c:/xampp/htdocs/CharShanbeJalase/parts/app.js#L2-L13) | تابع سراسری ایمن‌سازی رشته‌ها و کاراکترهای خطرناک XSS |
| `fetchData()` | `index.php` / `parts/app.js` | [`#L45-L80`](file:///c:/xampp/htdocs/CharShanbeJalase/parts/app.js#L45-L80) | واکشی اولیه تمام داده‌های اپلیکیشن |
| `renderDash()` | `parts/app.js` | [`#L70-L110`](file:///c:/xampp/htdocs/CharShanbeJalase/parts/app.js#L70-L110) | رندر کارت‌های متربی برتر، آمار و لیست داشبورد |
| `renderAtt()` | `parts/app.js` | [`#L130-L175`](file:///c:/xampp/htdocs/CharShanbeJalase/parts/app.js#L130-L175) | رندر لیست حضور و غیاب با سوئیچ‌های وضعیت |
| `renderSummaryStats()` | `parts/app.js` | [`#L893-L930`](file:///c:/xampp/htdocs/CharShanbeJalase/parts/app.js#L893-L930) | رندر ۴ کارت آماری حضور، تاخیر (تعداد جلسه و دقیقه) و غیبت |
| `renderPlans()` | `parts/app.js` | [`#L200-L260`](file:///c:/xampp/htdocs/CharShanbeJalase/parts/app.js#L200-L260) | رندر کارت‌های طرح درس با تم رنگی ماژول‌ها |
| `selectParentScore()` | `parts/mi-new.js` | [`#L1218-L1275`](file:///c:/xampp/htdocs/CharShanbeJalase/parts/mi-new.js#L1218-L1275) | انتخاب تک‌لمسی مقیاس ۶ گزینه‌ای والدین و ذخیره خودکار پس‌زمینه |
| `saveParentSurveyDomain()` | `parts/mi-new.js` | [`#L1277-L1320`](file:///c:/xampp/htdocs/CharShanbeJalase/parts/mi-new.js#L1277-L1320) | ثبت قطعی پاسخ‌ها و مثال عینی این حوزه در دیتابیس |
| `selectGameScore()` | `parts/mi-new.js` | [`#L1322-L1335`](file:///c:/xampp/htdocs/CharShanbeJalase/parts/mi-new.js#L1322-L1335) | انتخاب کارت مدرن امتیاز چالش عملی پروژه اسپکتروم |
| `renderAttendanceChart()` | `parts/chart.js` | [`#L45-L120`](file:///c:/xampp/htdocs/CharShanbeJalase/parts/chart.js#L45-L120) | رسم نمودار تعاملی روند جلسات با نقطه لمس |
| `renderMIChart()` | `parts/app.js` | [`#L871-L900`](file:///c:/xampp/htdocs/CharShanbeJalase/parts/app.js#L871-L900) | رسم نمودار ستونی آنالیز هوش‌های چندگانه در تَب آمار پروفایل متربی |
| `renderMIAnalysisBarChart()` | `parts/mi-new.js` | [`#L878-L951`](file:///c:/xampp/htdocs/CharShanbeJalase/parts/mi-new.js#L878-L951) | نمودار ستونی آنالیز تطبیقی و رتبه‌بندی ۸ هوش در تَب هوش نمای والد |
| `renderMIHorizontalChart()` | `parts/chart.js` | [`#L81-L85`](file:///c:/xampp/htdocs/CharShanbeJalase/parts/chart.js#L81-L85) | متد _barHorizontal رندرر بومی با پالت رنگی آرایه‌ای برای ستون‌ها |
| `liquidGlass()` | `assets/liquid-glass.js` | [`#L176-L230`](file:///c:/xampp/htdocs/CharShanbeJalase/assets/liquid-glass.js#L176-L230) | موتور انکسار فیزیکی شیشه مایع آیفون بر پایه نقشه جابجایی SVG |
| `.tile-icon-badge` | `assets/app.css` | [`#L533-L547`](file:///c:/xampp/htdocs/CharShanbeJalase/assets/app.css#L533-L547) | نشان‌های کریستالی اسکویرکل با درخشش پس‌زمینه برای ناوبری سریع داشبورد |
| `.glass-date-pill` | `views/layout/header.php` | [`#L20-L28`](file:///c:/xampp/htdocs/CharShanbeJalase/views/layout/header.php#L20-L28) | کپسول شیشه‌ای شناور تاریخ زنده جلالی در هدر سیستم |
| `.bottom-nav` | `assets/app.css` | [`#L318-L345`](file:///c:/xampp/htdocs/CharShanbeJalase/assets/app.css#L318-L345) | جزیره معلق ناوبری visionOS با کپسول‌های فعال و لبه‌های بلورین |

---

## ۴. اسناد مستر معماری (Master Documents Reference)

| شماره سند | نام سند | فایل مرجع | موضوع و هدف |
|---|---|---|---|
| `MASTER-01` | System Baseline | [`MASTER_01_SYSTEM_BASELINE.md`](file:///c:/xampp/htdocs/CharShanbeJalase/docs/ai/masters/MASTER_01_SYSTEM_BASELINE.md) | نقشه پایه سیستم و معماری اولیه |
| `MASTER-02` | UI/UX Redesign | [`MASTER_02_UI_UX_REDESIGN.md`](file:///c:/xampp/htdocs/CharShanbeJalase/docs/ai/masters/MASTER_02_UI_UX_REDESIGN.md) | بازطراحی رابط و تجربه کاربری |
| `MASTER-03` | iOS Liquid Glass System | [`MASTER_03_IOS_LIQUID_GLASS.md`](file:///c:/xampp/htdocs/CharShanbeJalase/docs/ai/masters/MASTER_03_IOS_LIQUID_GLASS.md) | زبان طراحی شیشه مایع اصیل آیفون، آئورا مش و ارگونومی موبایل |
| `MASTER-04` | Modular Architecture | [`MASTER_04_MODULAR_PAGES_ARCHITECTURE.md`](file:///c:/xampp/htdocs/CharShanbeJalase/docs/ai/masters/MASTER_04_MODULAR_PAGES_ARCHITECTURE.md) | تفکیک صفحات و ماژول‌ها |
| `MASTER-05` | Tardiness Scoring | [`MASTER_05_TARDINESS_SCORING_FORMULA.md`](file:///c:/xampp/htdocs/CharShanbeJalase/docs/ai/masters/MASTER_05_TARDINESS_SCORING_FORMULA.md) | فرمول پلکانی امتیاز دقایق تاخیر |
| `MASTER-06` | Absence Reason | [`MASTER_06_ABSENCE_REASON_RECORDING.md`](file:///c:/xampp/htdocs/CharShanbeJalase/docs/ai/masters/MASTER_06_ABSENCE_REASON_RECORDING.md) | ثبت و گزارش علل غیبت |
| `MASTER-07` | Multi-Source MI System | [`MASTER_07_MULTIPLE_INTELLIGENCES_MULTI_SOURCE_ASSESSMENT.md`](file:///c:/xampp/htdocs/CharShanbeJalase/docs/ai/masters/MASTER_07_MULTIPLE_INTELLIGENCES_MULTI_SOURCE_ASSESSMENT.md) | ارزیابی سه‌منبعه هوش‌های چندگانه و خروجی هوش مصنوعی |
| `MASTER-08` | Stats Presentation Standard | [`MASTER_08_ATTENDANCE_STATS_PRESENTATION.md`](file:///c:/xampp/htdocs/CharShanbeJalase/docs/ai/masters/MASTER_08_ATTENDANCE_STATS_PRESENTATION.md) | استاندارد تفکیک جلسات و دقایق تاخیر و کارت‌های آماری |
| `MASTER-09` | Engineered Visual Ergonomics & Studio Frosted Glass | [`MASTER_09_ENGINEERED_VISUAL_DESIGN_AND_EYE_COMFORT.md`](file:///c:/xampp/htdocs/CharShanbeJalase/docs/ai/masters/MASTER_09_ENGINEERED_VISUAL_DESIGN_AND_EYE_COMFORT.md) | مهندسی ارگونومی بصری، راحتی چشم و شیشه مات استاندارد استودیویی |
| `MASTER-10` | Parent-Child MI Hierarchy & Smart Report Card | [`MASTER_10_PARENT_CHILD_MI_HIERARCHY_AND_REPORT_CARD.md`](file:///c:/xampp/htdocs/CharShanbeJalase/docs/ai/masters/MASTER_10_PARENT_CHILD_MI_HIERARCHY_AND_REPORT_CARD.md) | معماری سلسله‌مراتبی والد-فرزند هوش‌ها و صدور کارنامه رسمی پرینت |
| `MASTER-11` | Total System Redesign — Ultra Liquid Glass 2026 | [`MASTER_11_TOTAL_SYSTEM_REDESIGN_ULTRA_LIQUID_GLASS.md`](file:///c:/xampp/htdocs/CharShanbeJalase/docs/ai/masters/MASTER_11_TOTAL_SYSTEM_REDESIGN_ULTRA_LIQUID_GLASS.md) | ریدیزاین جامع و شکیل سیستم به سبک لیکویید گلس فوق پیشرفته ۲۰۲۶ و پوشش ۵۰ نقش سوارم |

