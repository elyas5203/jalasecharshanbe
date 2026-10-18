<div class="sidebar-overlay" onclick="toggleSidebar()"></div>
<div class="sidebar">
  <h2 style="text-align:center; color:var(--primary); margin-top:10px;">مدیریت کلاس</h2>
  <hr style="width:100%; opacity:0.1; margin:15px 0;">
  <div class="menu-item active" onclick="nav('dash')"><i class="fas fa-chart-line"></i> داشبورد</div>
  <div class="menu-item" onclick="nav('tools')"><i class="fas fa-toolbox"></i> جعبه ابزار کلاس</div>
  <div class="menu-item" onclick="nav('profiles')"><i class="fas fa-user-graduate"></i> پروفایل‌ها</div>
  <div class="menu-item" onclick="nav('plans')"><i class="fas fa-book-reader"></i> طرح درس</div>
  <div class="menu-item" onclick="nav('archive')"><i class="fas fa-history"></i> آرشیو فعالیت‌ها</div>
  <div class="menu-item" onclick="nav('calendar')"><i class="fas fa-history"></i> تقویم جلسات</div>
  <div class="menu-item" onclick="nav('search')"><i class="fas fa-search"></i> جستجوی سراسری</div>
  <div class="menu-item" onclick="nav('att')"><i class="fas fa-check-double"></i> حضور و غیاب</div>
  <div class="menu-item" onclick="nav('score')"><i class="fas fa-star-half-alt"></i> امتیازدهی</div>
  <div class="menu-item" onclick="nav('notes')"><i class="fas fa-pen-nib"></i> یادداشت‌ها</div>
  <div class="menu-item" onclick="nav('settings')"><i class="fas fa-user-cog"></i> تنظیمات</div>
  <div class="menu-item" onclick="toggleDark()"><i class="fas fa-moon"></i> تغییر حالت شب/روز</div>
  <?php if (!empty($__readOnly)): ?>
  <a class="menu-item" href="admin.php" style="text-decoration:none; color:inherit; margin-top:auto;"><i class="fas fa-arrow-right"></i> بازگشت به پنل مدیریت</a>
  <?php else: ?>
  <a class="menu-item" href="logout.php" style="text-decoration:none; color:inherit; margin-top:auto;"><i class="fas fa-arrow-right"></i> خروج از حساب</a>
  <?php endif; ?>
</div>
