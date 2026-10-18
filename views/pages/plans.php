<!-- PLANS VIEW -->
<div id="view-plans" class="view <?= ($initialPage ?? 'dash') === 'plans' ? 'active' : '' ?>">
  <div class="card">
    <h3 class="section-title">
      📚 طرح درس 
      <button class="btn btn-blue" onclick="openPlanModal()" style="width:auto; padding:10px 16px; border-radius:16px;">➕ جدید</button>
    </h3>
  </div>
  <div id="plans-list"></div>
</div>
