<!-- MODAL: TIMER -->
<div class="modal" id="timer-modal">
  <div class="modal-box" style="text-align:center;">
    <h3 style="margin-top:0;">⏱️ تایمر کلاس</h3>
    <div class="modern-timer" id="timer-display">00:00</div>

    <div class="timer-input-group">
      <input type="text" inputmode="numeric" id="t-min" placeholder="دقیقه">
      <input type="text" inputmode="numeric" id="t-sec" placeholder="ثانیه">
      <button class="btn btn-purple" style="width:auto;" onclick="setCustomTimer()">تنظیم دستی</button>
    </div>

    <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:10px; margin-bottom:15px;">
      <button class="btn btn-green" onclick="startTimer()">شروع ▶</button>
      <button class="btn btn-red" onclick="stopTimer()">توقف ⏸</button>
      <button class="btn btn-blue" onclick="resetTimer()">بازنشانی 🔄</button>
    </div>
    <button class="btn btn-red" onclick="document.getElementById('timer-modal').style.display='none'" style="margin-top:20px;">بستن</button>
  </div>
</div>
