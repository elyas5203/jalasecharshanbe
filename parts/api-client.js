window.app = { run: new Proxy({}, {
  get(_target, prop) {
    if (prop === 'withSuccessHandler') return function(cb) { const runner = makeRunner(); runner._success = cb; return runner.proxy; };
    if (prop === 'withFailureHandler') return function(cb) { const runner = makeRunner(); runner._failure = cb; return runner.proxy; };
    return (...args) => callApi(prop, args, null, null);
  }
}) };
function makeRunner() {
  const state = { _success: null, _failure: null, proxy: null };
  state.proxy = new Proxy(state, { get(obj, prop) {
    if (prop === 'withSuccessHandler') return cb => { obj._success = cb; return obj.proxy; };
    if (prop === 'withFailureHandler') return cb => { obj._failure = cb; return obj.proxy; };
    return (...args) => callApi(prop, args, obj._success, obj._failure);
  }});
  return state;
}
function callApi(action, args, success, failure) {
  const headers = {'Content-Type': 'application/json'};
  if (window.CHSH && window.CHSH.csrf) headers['X-CSRF-Token'] = window.CHSH.csrf;
  return fetch('api.php?action=' + encodeURIComponent(action), {
    method: 'POST', headers: headers, body: JSON.stringify({args: args || []})
  }).then(async res => {
    if (res.status === 401) { window.location.href = 'login.php'; throw new Error('نشست منقضی شده است'); }
    const text = await res.text();
    let data; try { data = JSON.parse(text); } catch (e) { throw new Error(text || 'پاسخ نامعتبر از سرور'); }
    if (!res.ok || data.message) throw new Error(data.message || data.msg || 'خطای سرور');
    if (success) success(data);
    return data;
  }).catch(err => { if (failure) failure(err); else { console.error(err); if (window.showAlert) showAlert(err.message, 'error'); } });
}
