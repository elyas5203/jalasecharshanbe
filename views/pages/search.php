<!-- SEARCH VIEW -->
<div id="view-search" class="view <?= ($initialPage ?? 'dash') === 'search' ? 'active' : '' ?>">
  <div class="card page-hero-lite search-shell">
    <h3 class="section-title">🔎 جستجوی سراسری</h3>
    <p class="page-hint">نام متربی، طرح درس، یادداشت یا آرشیو را سریع پیدا کن</p>
    <input type="text" id="global-search-input" placeholder="جستجو در متربی‌ها، طرح درس، یادداشت و آرشیو..." onkeyup="runGlobalSearch(this.value)">
    <div id="global-search-results"></div>
  </div>
</div>
