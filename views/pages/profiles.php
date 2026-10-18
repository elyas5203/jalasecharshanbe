<!-- PROFILES LIST VIEW -->
<div id="view-profiles" class="view <?= ($initialPage ?? 'dash') === 'profiles' ? 'active' : '' ?>">
  <div class="card">
    <h3>👥 مدیریت متربیان</h3>
    <div style="display:flex; gap:10px; margin-bottom:15px;">
      <input type="text" id="new-name" placeholder="نام متربی جدید..." style="margin:0;">
      <button class="btn btn-blue" style="width:60px; margin:0;" onclick="addStudent()">+</button>
    </div>
    <div id="profiles-list"></div>
  </div>
</div>
