(function () {
  var CFG = (window.__UMAMI_CONNECT__) || {};
  var D = document;

  if (CFG.autotrackForms) {
    Array.from(document.querySelectorAll('form')).forEach(function(form) {
      if (!form.hasAttribute('data-umami-event')) {
        var id = form.getAttribute('id');
        var name = form.getAttribute('name');
        if (id) {
          form.setAttribute('data-umami-event', 'form:' + id);
        } else if (name) {
          form.setAttribute('data-umami-event', 'form:' + name);
        } else {
          form.setAttribute('data-umami-event', 'form_submit');
        }
      }
    });
  }

  function log() {
    if (CFG.debug && typeof console !== 'undefined') {
      console.log.apply(console, ['[UmamiConnect]'].concat([].slice.call(arguments)));
    }
  }

  function getByPath(root, path) {
    try {
      if (!path) return undefined;
      var parts = String(path).split('.');
      var cur = root;
      for (var i=0;i<parts.length;i++){
        if (cur == null) return undefined;
        cur = cur[parts[i]];
      }
      return cur;
    } catch(e){ return undefined; }
  }

  function consentGranted() {
    if (!CFG.consentRequired) return true;
    return !!getByPath(window, CFG.consentFlag || 'umamiConsentGranted');
  }

  function canTrack() {
    return typeof window.umami === 'object' &&
           typeof window.umami.track === 'function' &&
           consentGranted() &&
           localStorage.getItem('umami.disabled') !== 'true';
  }

  function parseDataAttr(node) {
    var raw = node.getAttribute('data-umami-data');
    if (!raw) return null;
    try { return JSON.parse(raw); } catch { return null; }
  }

  function defaultLinkEventName(a) {
    try {
      var href = a.getAttribute('href');
      if (href && href !== '#') {
        return href;
      }
      var text = (a.textContent || '').trim();
      return text ? 'link:' + text : 'click';
    } catch { return 'click'; }
  }

  function defaultButtonEventName(btn) {
    var label = (btn.getAttribute('aria-label') || btn.textContent || '').trim();
    return label ? ('button:' + label) : 'button_click';
  }

  function defaultFormEventName(form) {
    var id = form.getAttribute('id') || '';
    var name = form.getAttribute('name') || '';
    if (id) return 'form:' + id;
    if (name) return 'form:' + name;
    return 'form_submit';
  }

  function track(eventName, data) {
    if (!canTrack()) { log('skip track (not ready or no consent)', eventName, data); return; }
    try {
      if (data && typeof data === 'object') {
        window.umami.track(eventName, data);
      } else {
        window.umami.track(eventName);
      }
      log('track', eventName, data || null);
    } catch (e) { log('track error', e); }
  }

  // --- LINK CLICKS ---
  if (CFG.autotrackLinks) {
    D.addEventListener('click', function (ev) {
      var el = ev.target;
      while (el && el !== D && el.nodeType === 1) {
        if (el.tagName === 'A') break;
        el = el.parentElement;
      }
      if (!el || el.tagName !== 'A') return;
      if (el.hasAttribute('data-umami-skip')) return;
      if (el.hasAttribute('data-umami-event')) return;

      var name = defaultLinkEventName(el);
      var data = parseDataAttr(el);
      track(name, data);
      ev.stopPropagation();
    }, { capture: true });
  }

  // --- BUTTON CLICKS ---
  if (CFG.autotrackButtons) {
    D.addEventListener('click', function (ev) {
      var el = ev.target;
      while (el && el !== D && el.nodeType === 1) {
        if (el.tagName === 'BUTTON') break;
        el = el.parentElement;
      }
      if (!el || el.tagName !== 'BUTTON') return;
      if (el.hasAttribute('data-umami-skip')) return;
      if (el.hasAttribute('data-umami-event')) return;

      var name = defaultButtonEventName(el);
      var data = parseDataAttr(el);
      track(name, data);
    }, { capture: true });
  }

  // --- FORM SUBMIT ---
  if (CFG.autotrackForms) {
    D.addEventListener('submit', function (ev) {
      var form = ev.target;
      if (!form || form.tagName !== 'FORM') return;
      if (form.hasAttribute('data-umami-skip')) return;

      var name = form.getAttribute('data-umami-event') || defaultFormEventName(form);
      var data = parseDataAttr(form);

      if (!data) {
        data = {};
        var id = form.getAttribute('id') || '';
        var nameAttr = form.getAttribute('name') || '';
        if (id) data.formId = id;
        if (nameAttr) data.formName = nameAttr;
        var action = form.getAttribute('action') || '';
        if (action) data.action = action;
      }
      track(name, data);
    }, { capture: true });
  }

  // --- SUBMIT BUTTON CLICKS (AJAX-forms) ---
  D.addEventListener('click', function(ev) {
    var el = ev.target;
    // <button type=submit> / <input type=submit>
    if (el && el.nodeType === 1 && (
      (el.tagName === 'BUTTON' && (el.type === 'submit' || !el.type)) ||
      (el.tagName === 'INPUT' && el.type === 'submit')
    )) {
      var form = el.form || el.closest('form');
      if (!form) return;
      if (form.hasAttribute('data-umami-skip')) return;
      var name = form.getAttribute('data-umami-event') || defaultFormEventName(form);
      var data = parseDataAttr(form);
      if (!data) {
        data = {};
        var id = form.getAttribute('id') || '';
        var nameAttr = form.getAttribute('name') || '';
        if (id) data.formId = id;
        if (nameAttr) data.formName = nameAttr;
        var action = form.getAttribute('action') || '';
        if (action) data.action = action;
      }
      track(name, data);
    }
  }, { capture: true });

  D.addEventListener('click', function(ev) {
    var el = ev.target;
    if (el && el.nodeType === 1 && el.tagName === 'BUTTON' && el.type === 'button') {
      var form = el.form || el.closest('form');
      if (!form) return;
      if (form.hasAttribute('data-umami-skip')) return;
      var name = form.getAttribute('data-umami-event') || defaultFormEventName(form);
      var data = parseDataAttr(form);
      if (!data) {
        data = {};
        var id = form.getAttribute('id') || '';
        var nameAttr = form.getAttribute('name') || '';
        if (id) data.formId = id;
        if (nameAttr) data.formName = nameAttr;
        var action = form.getAttribute('action') || '';
        if (action) data.action = action;
      }
      track(name, data);
    }
  }, { capture: true });

  var readyCheckCount = 0;
  var readyIv = setInterval(function(){
    readyCheckCount++;
    if (canTrack()) {
      clearInterval(readyIv);
      log('umami ready');
    }
    if (readyCheckCount > 60) clearInterval(readyIv);
  }, 500);
})();
