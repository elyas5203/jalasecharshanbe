// Tiny local chart renderer used instead of Chart.js CDN. It supports the line/bar charts used by this app.
class Chart {
  static register() {}
  constructor(canvas, config) {
    this.canvas = canvas;
    this.config = config || {};
    this.ctx = canvas ? canvas.getContext('2d') : null;
    this.tooltip = {getActiveElements: () => []};
    this.chartArea = {top: 0, bottom: 0};
    this.activeIndex = null;
    this._resizeHandler = () => this.draw();
    this._moveHandler = (event) => this._handlePointer(event);
    this._leaveHandler = () => { this.activeIndex = null; this._hideTip(); this.draw(); };
    window.addEventListener('resize', this._resizeHandler);
    if (this.canvas) {
      this.canvas.addEventListener('pointermove', this._moveHandler);
      this.canvas.addEventListener('pointerdown', this._moveHandler);
      this.canvas.addEventListener('pointerleave', this._leaveHandler);
    }
    this.draw();
  }
  destroy() {
    window.removeEventListener('resize', this._resizeHandler);
    if (this.canvas) {
      this.canvas.removeEventListener('pointermove', this._moveHandler);
      this.canvas.removeEventListener('pointerdown', this._moveHandler);
      this.canvas.removeEventListener('pointerleave', this._leaveHandler);
    }
    this._hideTip();
    if (this.ctx) this.ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);
  }
  _resize() {
    const ratio = window.devicePixelRatio || 1;
    const rect = this.canvas.parentElement ? this.canvas.parentElement.getBoundingClientRect() : this.canvas.getBoundingClientRect();
    const w = Math.max(260, rect.width || 320);
    const h = Math.max(160, rect.height || 220);
    this.canvas.width = Math.floor(w * ratio);
    this.canvas.height = Math.floor(h * ratio);
    this.canvas.style.width = w + 'px';
    this.canvas.style.height = h + 'px';
    this.ctx.setTransform(ratio, 0, 0, ratio, 0, 0);
    return {w, h};
  }
  draw() {
    if (!this.canvas || !this.ctx) return;
    const {w, h} = this._resize();
    const cfg = this.config;
    const type = cfg.type || 'line';
    const labels = (cfg.data && cfg.data.labels) || [];
    const ds = ((cfg.data && cfg.data.datasets) || [])[0] || {data: []};
    const data = (ds.data || []).map(v => Number(v) || 0);
    const horizontal = cfg.options && cfg.options.indexAxis === 'y';
    this.ctx.clearRect(0, 0, w, h);
    this.ctx.direction = 'rtl';
    this.ctx.font = '500 12px Vazirmatn, sans-serif';
    if (!labels.length) { this._empty(w, h); return; }
    if (type === 'bar') horizontal ? this._barHorizontal(labels, data, ds, w, h) : this._barVertical(labels, data, ds, w, h);
    else this._line(labels, data, ds, w, h, cfg);
  }
  _fmt(v) { return (typeof window.toPersianNum === 'function') ? window.toPersianNum(v) : String(v).replace(/[0-9]/g, d => '۰۱۲۳۴۵۶۷۸۹'[d]); }
  _empty(w, h) { this.ctx.fillStyle = '#94a3b8'; this.ctx.textAlign = 'center'; this.ctx.fillText('داده‌ای برای نمایش نیست', w/2, h/2); }
  _grid(x, y, w, h) { const c=this.ctx; c.strokeStyle='#e2e8f0'; c.lineWidth=1; for(let i=0;i<=4;i++){ const yy=y+h-(h*i/4); c.beginPath(); c.moveTo(x,yy); c.lineTo(x+w,yy); c.stroke(); } }
  _line(labels, data, ds, w, h, cfg) {
    const c=this.ctx, pad={l:38,r:18,t:18,b:44}, x=pad.l, y=pad.t, cw=w-pad.l-pad.r, ch=h-pad.t-pad.b;
    const optMax = cfg.options && cfg.options.scales && cfg.options.scales.y && cfg.options.scales.y.max;
    const max = optMax || Math.max(5, ...data);
    this._grid(x,y,cw,ch);
    const pts = data.map((v,i)=>({x:x+(labels.length===1?cw/2:(cw*i/(labels.length-1))), y:y+ch-(Math.max(0,v)/max)*ch, v}));
    this._points = pts; this._plot = {x, y, w: cw, h: ch};
    c.strokeStyle = ds.borderColor || '#4f46e5'; c.lineWidth = 3; c.beginPath(); pts.forEach((p,i)=> i?c.lineTo(p.x,p.y):c.moveTo(p.x,p.y)); c.stroke();
    c.fillStyle = ds.borderColor || '#4f46e5'; pts.forEach(p=>{ c.beginPath(); c.arc(p.x,p.y,4,0,Math.PI*2); c.fill(); });
    c.fillStyle = '#64748b'; c.textAlign = 'center'; labels.forEach((lb,i)=>{ c.fillText(this._fmt(lb), pts[i].x, h-18); });
    c.textAlign='right'; c.fillText(this._fmt(max), x-6, y+4); c.fillText(this._fmt(0), x-6, y+ch+4);
    this._drawActive();
  }
  _barVertical(labels, data, ds, w, h) {
    const c=this.ctx, pad={l:32,r:16,t:20,b:58}, x=pad.l, y=pad.t, cw=w-pad.l-pad.r, ch=h-pad.t-pad.b;
    const max=Math.max(5,...data); this._grid(x,y,cw,ch); const gap=8, bw=Math.max(14,(cw/labels.length)-gap);
    labels.forEach((lb,i)=>{ const bh=(Math.max(0,data[i])/max)*ch, bx=x+i*(cw/labels.length)+(cw/labels.length-bw)/2, by=y+ch-bh; const barColor = Array.isArray(ds.backgroundColor) ? (ds.backgroundColor[i] || '#4f46e5') : (ds.backgroundColor || '#4f46e5'); c.fillStyle=barColor; this._roundRect(bx,by,bw,bh,6); c.fill(); c.fillStyle='#334155'; c.textAlign='center'; c.fillText(this._fmt(data[i]), bx+bw/2, by-5); c.save(); c.translate(bx+bw/2,h-18); c.rotate(-0.55); c.fillStyle='#64748b'; c.fillText(this._fmt(String(lb).replace(/ .*/,'')),0,0); c.restore(); });
  }
  _barHorizontal(labels, data, ds, w, h) {
    const c=this.ctx, pad={l:26,r:120,t:16,b:20}, x=pad.l, y=pad.t, cw=w-pad.l-pad.r, ch=h-pad.t-pad.b;
    const max=Math.max(5,...data); const row=ch/labels.length;
    labels.forEach((lb,i)=>{ const by=y+i*row+row*0.22, bh=Math.max(12,row*0.52), bw=(Math.max(0,data[i])/max)*cw; c.fillStyle='#eef2ff'; this._roundRect(x,by,cw,bh,7); c.fill(); const barColor = Array.isArray(ds.backgroundColor) ? (ds.backgroundColor[i] || '#4f46e5') : (ds.backgroundColor || '#4f46e5'); c.fillStyle=barColor; this._roundRect(x,by,bw,bh,7); c.fill(); c.fillStyle='#334155'; c.textAlign='right'; c.fillText(this._fmt(lb), w-10, by+bh*0.75); c.textAlign='left'; c.fillText(this._fmt(data[i]), x+bw+5, by+bh*0.75); });
  }
  _handlePointer(event) {
    if (!this._points || !this._points.length) return;
    const rect = this.canvas.getBoundingClientRect();
    const x = event.clientX - rect.left;
    let nearest = 0;
    let dist = Infinity;
    this._points.forEach((p, i) => { const d = Math.abs(p.x - x); if (d < dist) { dist = d; nearest = i; } });
    this.activeIndex = nearest;
    this.draw();
    this._showTip(nearest, event.clientX - rect.left, event.clientY - rect.top);
  }
  _activeLines(index) {
    const cfg = this.config || {}, labels = (cfg.data && cfg.data.labels) || [], ds = ((cfg.data && cfg.data.datasets) || [])[0] || {};
    const value = (ds.data || [])[index] ?? 0;
    const cb = cfg.options && cfg.options.plugins && cfg.options.plugins.tooltip && cfg.options.plugins.tooltip.callbacks && cfg.options.plugins.tooltip.callbacks.label;
    let lines = [];
    if (typeof cb === 'function') {
      const out = cb({raw: value, dataIndex: index, label: labels[index]});
      lines = Array.isArray(out) ? out : [out];
    } else {
      lines = [`${ds.label || 'مقدار'}: ${this._fmt(value)}`];
    }
    return [`جلسه: ${this._fmt(labels[index] || '')}`].concat(lines.map(x => this._fmt(x)));
  }
  _drawActive() {
    if (this.activeIndex === null || !this._points || !this._points[this.activeIndex] || !this._plot) return;
    const c = this.ctx, p = this._points[this.activeIndex], plot = this._plot;
    c.save();
    c.beginPath(); c.moveTo(p.x, plot.y); c.lineTo(p.x, plot.y + plot.h); c.lineWidth = 2; c.strokeStyle = 'rgba(20,33,61,.55)'; c.setLineDash([5,5]); c.stroke();
    c.setLineDash([]); c.fillStyle = '#fff8e8'; c.strokeStyle = '#c89b3c'; c.lineWidth = 3; c.beginPath(); c.arc(p.x, p.y, 6, 0, Math.PI*2); c.fill(); c.stroke();
    c.restore();
  }
  _showTip(index, x, y) {
    if (!this.canvas) return;
    if (!this.tip) { this.tip = document.createElement('div'); this.tip.className = 'chart-tip chart-tip-fixed'; document.body.appendChild(this.tip); }
    this.tip.innerHTML = this._activeLines(index).map((line, i) => i ? `<div class="${i === 1 ? 'tip-metric' : ''}">${line}</div>` : `<b>${line}</b>`).join('');
    const rect = this.canvas.getBoundingClientRect();
    const vw = window.innerWidth || document.documentElement.clientWidth || 360;
    const vh = window.innerHeight || document.documentElement.clientHeight || 640;
    const tipW = Math.min(320, Math.max(236, vw - 18));
    this.tip.style.width = tipW + 'px';
    this.tip.style.display = 'block';
    const tipH = this.tip.offsetHeight || 160;
    let left = rect.left + x + 16;
    if (left + tipW + 10 > vw) left = rect.left + x - tipW - 16;
    left = Math.max(9, Math.min(vw - tipW - 9, left));
    let top = rect.top + y - 18;
    if (top + tipH + 10 > vh) top = vh - tipH - 10;
    top = Math.max(9, top);
    this.tip.style.left = left + 'px';
    this.tip.style.top = top + 'px';
  }
  _hideTip() { if (this.tip) this.tip.style.display = 'none'; }
  _roundRect(x,y,w,h,r){ const c=this.ctx; if (w < 0) w = 0; r=Math.min(r,Math.abs(w)/2,Math.abs(h)/2); c.beginPath(); c.moveTo(x+r,y); c.arcTo(x+w,y,x+w,y+h,r); c.arcTo(x+w,y+h,x,y+h,r); c.arcTo(x,y+h,x,y,r); c.arcTo(x,y,x+w,y,r); c.closePath(); }
}
