# DOMAIN MASTER: STUDENTS, ATTENDANCE & SCORING (STUDENTS_AND_ATTENDANCE.md)

> **سند والد دامنه (Parent Domain Master)**  
> **حوزه دامنه:** پرونده متربیان، جلسات حضور و غیاب، تاخیر، غیبت، سیستم محاسبه امتیاز، شماره‌های داینامیک و نمودار رشد  
> **سند والد:** [`ROOT_MASTER.md`](file:///c:/xampp/htdocs/CharShanbeJalase/docs/ai/ROOT_MASTER.md)  
> **مسیر سند:** `docs/ai/domains/STUDENTS_AND_ATTENDANCE.md`  
> **وضعیت:** ACTIVE & VERIFIED  

---

## ۱. مأموریت و قوانین بیزینس دامنه (Domain Mission & Business Rules)

این دامنه شریان حیاتی اطلاعات متربیان و نظم کلاسی است:
1. **پروفایل متربی:** شامل نام، عکس محلی (`uploads/students/`)، بیوگرافی، تاریخ تولد، مدرسه، ملاحظات پزشکی و شماره تماس‌های داینامیک در قالب JSON (پدر، مادر، برادر، خود متربی و...).
2. **وضعیت‌های چهارگانه حضور و غیاب:**
   - **حاضر (Present):** ۱+ امتیاز به امتیاز سیستمی اضافه می‌شود.
   - **غیبت (Absent):** ۱- امتیاز و ۱ واحد به شمارنده غیبت‌ها اضافه می‌شود.
   - **تاخیر (Late):** محاسبه دقیق دقایق تاخیر (`late_minutes`) و ثبت در شمارنده تاخیرها. امتیاز حضور بر اساس مدل پلکانی محاسبه می‌شود:
     - ۱ تا ۵ دقیقه: `+0.95`
     - ۶ تا ۱۰ دقیقه: `+0.85`
     - ۱۱ تا ۱۵ دقیقه: `+0.70`
     - ۱۶ تا ۲۰ دقیقه: `+0.55`
     - ۲۱ تا ۳۰ دقیقه: `+0.40`
     - بیش از ۳۰ دقیقه: `+0.25`
   - **موجه (Excused):** نیم امتیاز مثبت (`+0.50`) به عنوان فعالیت موجه.
3. **امتیاز نهایی:** مجموع ریاضی `امتیاز سیستمی حضور (system_score)` + `امتیاز دستی تشویقی/انضباطی (manual_score)`.
4. **شاخص آتشین (On-Fire Streak):** اگر متربی در تمام جلسات گذشته هیچ غیبت و تاخیری نداشته باشد، نشانگر ویژه آتش 🔥 برای او فعال می‌شود.

---

## ۲. دیاگرام جریان محاسبه خودکار حضور و امتیاز (Scoring Engine Flow)

```mermaid
flowchart TD
    SubmitEvent["ارسال داده حضور و غیاب جلسه\n(file: api.php#L349)"] --> LockSession["ایجاد یا یافتن جلسه در جدول attendance_sessions\n(file: api.php#L354-L358)"]
    
    LockSession --> LoopStudents["پیمایش تک‌تک متربیان حاضر در لیست"]
    
    subgraph CalculationCore ["موتور محاسبه اتمیک در bootstrap.php#updateCalculations"]
        LoopStudents --> CheckStatus{"بررسی وضعیت ثبت‌شده"}
        CheckStatus -->|حاضر (Present)| AddPresent["absences += 0\nsystem_score += 1.00"]
        CheckStatus -->|غیبت (Absent)| AddAbsent["absences += 1\nsystem_score -= 1.00"]
        CheckStatus -->|موجه (Excused)| AddExcused["absences += 0\nsystem_score += 0.50"]
        CheckStatus -->|تاخیر (Late)| CheckMins{"بررسی late_minutes"}
        
        CheckMins -->|تاخیر 0-5 دقیقه| M1["system_score += 0.95"]
        CheckMins -->|تاخیر 6-10 دقیقه| M2["system_score += 0.85"]
        CheckMins -->|تاخیر 11-15 دقیقه| M3["system_score += 0.70"]
        CheckMins -->|تاخیر 16-20 دقیقه| M4["system_score += 0.55"]
        CheckMins -->|تاخیر 21-30 دقیقه| M5["system_score += 0.40"]
        CheckMins -->|تاخیر بیش از 30 دقیقه| M6["system_score += 0.25"]
        
        AddPresent --> SumScore["محاسبه امتیاز کل:\ntotal_score = system_score + manual_score"]
        AddAbsent --> SumScore
        AddExcused --> SumScore
        M1 --> SumScore
        M2 --> SumScore
        M3 --> SumScore
        M4 --> SumScore
        M5 --> SumScore
        M6 --> SumScore
        
        SumScore --> UpdateStudentRow["به‌روزرسانی سطر دانش‌آموز در جدول students\n(file: bootstrap.php)"]
    end
    
    UpdateStudentRow --> CheckStreak{"آیا absences == 0 و late == 0؟"}
    CheckStreak -->|بله| EnableFireBadge["فعال‌سازی نشانگر آتش (fire: true)"]
    CheckStreak -->|خیر| DisableFireBadge["غیرفعال‌سازی نشانگر آتش (fire: false)"]
    
    EnableFireBadge --> CommitTrans["کامیت تراکنش و تولید خروجی JSON"]
    DisableFireBadge --> CommitTrans
```

---

## ۳. ارجاعات کد میکروسکوپی (Deterministic Code References)

| موجودیت / تابع | فایل مرجع | محدوده خطوط | نام نماد | هدف و مسئولیت |
|---|---|---|---|---|
| **اسکیمای دیتابیس** | `bootstrap.php` | [`#L58-L60`](file:///c:/xampp/htdocs/CharShanbeJalase/bootstrap.php#L58-L60) | جدول‌های `students`, `attendance_sessions`, `attendance_records` | ساختار ذخیره‌سازی اطلاعات فردی، جلسات و سوابق حضور |
| **موتور بازشماری امتیاز** | `bootstrap.php` | [`#L450-L510`](file:///c:/xampp/htdocs/CharShanbeJalase/bootstrap.php#L450-L510) | `recalculateStudentAttendanceAndScore()` | محاسبه دقیق مجدد از روی کل جلسات و تصحیح مغایرت‌ها |
| **ثبت حضور و غیاب** | `api.php` | [`#L245-L280`](file:///c:/xampp/htdocs/CharShanbeJalase/api.php#L245-L280) | تابع `submitAttendance($pdo, $data)` | ذخیره رکوردهای حضور در یک تراکنش اتمیک دیتابیس |
| **واکشی کامل پروفایل**| `api.php` | [`#L180-L225`](file:///c:/xampp/htdocs/CharShanbeJalase/api.php#L180-L225) | تابع `getFullStudentProfile($pdo, $name)` | دریافت مشخصات، هوش‌ها، نمودار رشد و آمار حضور |
| **افزودن امتیاز دستی** | `api.php` | [`#L295-L310`](file:///c:/xampp/htdocs/CharShanbeJalase/api.php#L295-L310) | تابع `addManualScore($pdo, $name, $pts)` | ثبت امتیازات تشویقی/انضباطی مربی بدون دستکاری جلسات |
| **آپلود عکس متربی** | `api.php` | [`#L955-L978`](file:///c:/xampp/htdocs/CharShanbeJalase/api.php#L955-L978) | تابع `uploadStudentImage($pdo)` | ذخیره عکس در `uploads/students/` و حذف عکس قبلی جهت جلوگیری از پر شدن دیسک |
| **رسم نمودار حضور** | `parts/chart.js` | [`#L45-L160`](file:///c:/xampp/htdocs/CharShanbeJalase/parts/chart.js#L45-L160) | موتور رسم محلی Canvas | رسم نمودار تعاملی با خط راهنمای عمودی و جعبه اطلاعات جلسه |

---

## ۴. منشور نبایدهای دامنه حضور و متربیان (Domain Invariants)

> ⛔ **خطوط قرمز دامنه:**

1. **نباید لینک خارجی گوگل درایو در فرانت‌اند لود مستقیم شود:** عکس‌های دانش‌آموزان باید از مسیرهای محلی `uploads/students/` لود شوند و در صورت عدم وجود یا خطا، عکس پیش‌فرض محلی `assets/default-avatar.svg` نمایش یابد (`bootstrap.php#L410-L425`).
2. **نباید تاریخ جلسات تکراری ثبت شوند:** فیلد `session_date` در جدول `attendance_sessions` دارای قید یکتا (`UNIQUE`) است و ثبت مجدد باید منجر به به‌روزرسانی رکوردهای همان تاریخ شود، نه ایجاد جلسه مضاعف (`bootstrap.php#L59`).
3. **نباید حذف متربی منجر به یتیم شدن رکوردها گردد:** کلید خارجی در `attendance_records` دارای `ON DELETE CASCADE` است تا با حذف دانش‌آموز، لاگ‌های وی به صورت اتمیک پاک شوند (`bootstrap.php#L60`).
4. **نباید شماره‌های داینامیک ساختار نامعتبر داشته باشند:** ذخیره تلفن‌ها در فیلد JSON `phones` باید همیشه شامل جفت‌کلیدهای استاندارد `{label, number}` باشد.
