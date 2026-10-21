(function () {
  'use strict';

  var $ = function (id) { return document.getElementById(id); };

  function copyText(text) {
    if (navigator.clipboard && window.isSecureContext) {
      return navigator.clipboard.writeText(text);
    }
    return new Promise(function (resolve, reject) {
      var ta = document.createElement('textarea');
      ta.value = text;
      ta.style.position = 'fixed';
      ta.style.opacity = '0';
      document.body.appendChild(ta);
      ta.select();
      try { document.execCommand('copy') ? resolve() : reject(); } catch (e) { reject(e); } finally { document.body.removeChild(ta); }
    });
  }

  function flash(button, label) {
    var original = button.dataset.label || button.textContent;
    button.dataset.label = original;
    button.textContent = label;
    clearTimeout(button._t);
    button._t = setTimeout(function () { button.textContent = original; }, 1200);
  }

  function utf8ToBase64(str) {
    var bytes = new TextEncoder().encode(str), bin = '';
    for (var i = 0; i < bytes.length; i++) bin += String.fromCharCode(bytes[i]);
    return btoa(bin);
  }

  document.addEventListener('click', function (e) {
    var el = e.target.closest('[data-copy]');
    if (!el) return;
    copyText(el.dataset.copy).then(function () { flash(el, 'Copied!'); });
  });

  var root = $('generator');
  if (!root) return;

  var d = root.dataset;
  var nameBased = d.nameBased === '1';
  var supportsCount = d.supportsCount === '1';
  var max = parseInt(d.max, 10);
  var raw = JSON.parse(d.raw);
  var preset = JSON.parse(d.preset || '{}');
  var out = $('output'), status = $('status');
  var busy = false;

  var SEPARATORS = { newline: '\n', comma: ',', 'comma-space': ', ', space: ' ' };
  var QUOTES = { none: '', double: '"', single: "'" };

  var ctl = {
    upper: $('opt-upper'), hyphens: $('opt-hyphens'), braces: $('opt-braces'), urn: $('opt-urn'),
    quotes: $('opt-quotes'), separator: $('opt-separator'), count: $('count')
  };

  try {
    var saved = JSON.parse(localStorage.getItem(d.storage) || '{}');
    Object.keys(saved).forEach(function (k) {
      if (!ctl[k] || k === 'count') return;
      if (ctl[k].type === 'checkbox') ctl[k].checked = !!saved[k]; else ctl[k].value = saved[k];
    });
  } catch (e) {}

  function options() {
    return {
      upper: ctl.upper.checked, hyphens: ctl.hyphens.checked, braces: ctl.braces.checked, urn: ctl.urn.checked,
      quotes: ctl.quotes.value, separator: ctl.separator.value
    };
  }

  function formatOne(uuid, o) {
    var v = o.hyphens ? uuid : uuid.replace(/-/g, '');
    v = o.upper ? v.toUpperCase() : v.toLowerCase();
    if (o.urn) v = 'urn:uuid:' + v;
    if (o.braces) v = '{' + v + '}';
    var q = QUOTES[o.quotes] || '';
    return q + v + q;
  }

  function formatted() {
    var o = options();
    return raw.map(function (u) { return formatOne(u, o); });
  }

  function render() {
    var o = options();
    out.value = formatted().join(SEPARATORS[o.separator] || '\n');
    out.rows = Math.min(Math.max(o.separator === 'newline' ? raw.length : 2, 1), 12);
    var link = $('decode-link');
    if (link && raw.length) link.href = '/decode?uuid=' + encodeURIComponent(raw[0]);
    updateApi();
  }

  function apiQuery(extra) {
    var o = options(), q = new URLSearchParams(extra || {});
    if (o.upper) q.set('upper', '1');
    if (!o.hyphens) q.set('hyphens', '0');
    if (o.braces) q.set('braces', '1');
    if (o.urn) q.set('urn', '1');
    if (o.quotes !== 'none') q.set('quotes', o.quotes);
    if (o.separator !== 'newline') q.set('separator', o.separator);
    return q;
  }

  function nameParams() {
    var ns = $('namespace').value === 'custom' ? $('custom-namespace').value.trim() : $('namespace').value;
    return { namespace: ns, name: 'base64:' + utf8ToBase64($('name').value) };
  }

  function apiUrl(plain) {
    var extra = {};
    if (nameBased) extra = nameParams();
    else if (supportsCount && ctl.count && parseInt(ctl.count.value, 10) > 1) extra.count = ctl.count.value;
    if (plain) extra.format = 'text';
    var q = apiQuery(extra).toString().replace(/%3A/g, ':');
    return d.api + (q ? '?' + q : '');
  }

  function updateApi() {
    var el = $('api-url');
    if (el) el.textContent = new URL(apiUrl(false), location.origin).href;
  }

  function setStatus(msg, isError) {
    status.textContent = msg || '';
    status.classList.toggle('error', !!isError);
  }

  function request() {
    if (busy) return;
    busy = true;
    var btns = root.querySelectorAll('button[type=submit], #generate');
    btns.forEach(function (b) { b.disabled = true; });
    setStatus('');

    var url = d.api;
    var params = new URLSearchParams();
    if (nameBased) {
      var p = nameParams();
      params.set('namespace', p.namespace);
      params.set('name', p.name);
    } else if (supportsCount) {
      var n = Math.max(1, Math.min(max, parseInt(ctl.count.value, 10) || 1));
      ctl.count.value = n;
      params.set('count', n);
    }

    fetch(url + (params.toString() ? '?' + params : ''), { headers: { Accept: 'application/json' } })
      .then(function (r) { return r.json().then(function (body) { return { ok: r.ok, status: r.status, body: body }; }); })
      .then(function (res) {
        if (res.status === 429) throw new Error('Too many requests. Please wait a minute and try again.');
        if (!res.ok) throw new Error((res.body && res.body.message) || 'Something went wrong.');
        raw = res.body.map(function (u) { return u.toLowerCase(); });
        render();
      })
      .catch(function (err) { setStatus(err.message || 'Network error.', true); })
      .then(function () { busy = false; btns.forEach(function (b) { b.disabled = false; }); });
  }

  function persist() {
    try {
      var o = options();
      localStorage.setItem(d.storage, JSON.stringify(o));
    } catch (e) {}
  }

  Object.keys(ctl).forEach(function (k) {
    if (!ctl[k] || k === 'count') return;
    ctl[k].addEventListener('change', function () { render(); persist(); });
  });
  if (ctl.count) ctl.count.addEventListener('input', updateApi);

  if (nameBased) {
    var form = $('name-form');
    var nsSelect = $('namespace'), customLabel = $('custom-namespace-label');
    nsSelect.addEventListener('change', function () { customLabel.hidden = nsSelect.value !== 'custom'; updateApi(); });
    $('custom-namespace').addEventListener('input', updateApi);
    $('name').addEventListener('input', updateApi);
    form.addEventListener('submit', function (e) { e.preventDefault(); request(); });
  } else {
    $('generate').addEventListener('click', request);
    if (ctl.count) ctl.count.addEventListener('keydown', function (e) { if (e.key === 'Enter') request(); });
  }

  $('copy').addEventListener('click', function () {
    copyText(out.value).then(function () { flash($('copy'), 'Copied!'); setStatus(raw.length + (raw.length === 1 ? ' UUID' : ' UUIDs') + ' copied to clipboard.'); });
  });
  $('copy-json').addEventListener('click', function () {
    copyText(JSON.stringify(formatted(), null, 2)).then(function () { flash($('copy-json'), 'Copied!'); });
  });
  $('copy-api').addEventListener('click', function () {
    copyText(new URL(apiUrl(false), location.origin).href).then(function () { flash($('copy-api'), 'Copied!'); });
  });
  $('copy-curl').addEventListener('click', function () {
    copyText("curl '" + new URL(apiUrl(true), location.origin).href + "'").then(function () { flash($('copy-curl'), 'Copied!'); });
  });
  $('download').addEventListener('click', function () {
    var blob = new Blob([out.value + '\n'], { type: 'text/plain' });
    var a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = 'uuids-' + d.version + '.txt';
    document.body.appendChild(a);
    a.click();
    setTimeout(function () { URL.revokeObjectURL(a.href); a.remove(); }, 0);
  });

  out.addEventListener('focus', function () { out.select(); });
  render();
})();
