(function () {
  'use strict';
  var root = document.getElementById('daylight-assistant');
  if (!root) return;

  var panel = root.querySelector('.assistant-panel');
  var log = root.querySelector('.assistant-log');
  var form = root.querySelector('.assistant-form');
  var input = root.querySelector('.assistant-input');
  var suggest = root.querySelector('.assistant-suggest');
  var launcher = root.querySelector('.assistant-launch');
  var greeting = root.getAttribute('data-greeting') || '';
  var MAX = 500;
  var history = [];
  var busy = false;

  // Everything below writes with textContent / setAttribute only, never innerHTML, so replies cannot inject markup.
  function scrollDown() { log.scrollTop = log.scrollHeight; }

  function addMessage(kind, text, links, isError) {
    var wrap = document.createElement('div');
    wrap.className = 'assistant-msg ' + (kind === 'user' ? 'from-user' : 'from-bot') + (isError ? ' is-error' : '');
    var body = document.createElement('div');
    body.className = 'assistant-msg-text';
    body.textContent = text;
    wrap.appendChild(body);
    if (links && links.length) {
      var box = document.createElement('div');
      box.className = 'assistant-links';
      links.forEach(function (l) {
        if (!l || typeof l.url !== 'string' || l.url.charAt(0) !== '/' || l.url.indexOf('//') === 0) return;
        var a = document.createElement('a');
        a.setAttribute('href', l.url);
        a.textContent = String(l.label || 'Open');
        box.appendChild(a);
      });
      if (box.childNodes.length) wrap.appendChild(box);
    }
    log.appendChild(wrap);
    scrollDown();
    return wrap;
  }

  function addTyping() {
    var wrap = document.createElement('div');
    wrap.className = 'assistant-msg from-bot';
    wrap.setAttribute('role', 'status');
    var dots = document.createElement('span');
    dots.className = 'assistant-typing';
    dots.setAttribute('aria-hidden', 'true');
    for (var i = 0; i < 3; i++) dots.appendChild(document.createElement('i'));
    var sr = document.createElement('span');
    sr.className = 'sr-only';
    sr.textContent = 'The assistant is typing';
    wrap.appendChild(dots);
    wrap.appendChild(sr);
    log.appendChild(wrap);
    scrollDown();
    return wrap;
  }

  function isOpen() { return !panel.hidden; }

  function openPanel() {
    panel.hidden = false;
    root.classList.add('is-open');
    launcher.setAttribute('aria-expanded', 'true');
    if (!log.childNodes.length && greeting) addMessage('bot', greeting);
    input.focus();
  }

  function closePanel() {
    panel.hidden = true;
    root.classList.remove('is-open');
    launcher.setAttribute('aria-expanded', 'false');
    launcher.focus();
  }

  function reset() {
    history = [];
    while (log.firstChild) log.removeChild(log.firstChild);
    if (greeting) addMessage('bot', greeting);
    suggest.hidden = false;
    input.value = '';
    input.style.height = '';
    input.focus();
  }

  function setBusy(v) {
    busy = v;
    form.querySelector('button[type="submit"]').disabled = v;
    log.setAttribute('aria-busy', v ? 'true' : 'false');
  }

  function send(raw) {
    var text = String(raw || '').replace(/\s+/g, ' ').trim();
    if (!text || busy) return;
    if (text.length > MAX) {
      addMessage('bot', 'Please keep your question under ' + MAX + ' characters.', null, true);
      return;
    }
    suggest.hidden = true;
    addMessage('user', text);
    var previous = history.slice(-6);
    history.push({ role: 'user', content: text });
    input.value = '';
    input.style.height = '';
    setBusy(true);
    var typing = addTyping();

    var body = new URLSearchParams();
    body.set('message', text);
    body.set('history', JSON.stringify(previous));
    body.set('_csrf', root.getAttribute('data-csrf') || '');

    var controller = typeof AbortController === 'function' ? new AbortController() : null;
    var timer = controller ? window.setTimeout(function () { controller.abort(); }, 35000) : null;

    fetch(root.getAttribute('data-endpoint'), {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'Accept': 'application/json' },
      body: body.toString(),
      credentials: 'same-origin',
      signal: controller ? controller.signal : undefined
    }).then(function (res) {
      return res.json().catch(function () { return {}; }).then(function (data) { return { ok: res.ok, data: data }; });
    }).then(function (r) {
      if (typing.parentNode) typing.parentNode.removeChild(typing);
      if (r.ok && typeof r.data.answer === 'string') {
        addMessage('bot', r.data.answer, r.data.links);
        history.push({ role: 'assistant', content: r.data.answer });
      } else {
        addMessage('bot', typeof r.data.error === 'string' ? r.data.error : "Sorry, I couldn't answer that just now.", null, true);
      }
    }).catch(function () {
      if (typing.parentNode) typing.parentNode.removeChild(typing);
      addMessage('bot', "I couldn't reach the assistant. Check your connection and try again.", null, true);
    }).then(function () {
      if (timer) window.clearTimeout(timer);
      setBusy(false);
      input.focus();
    });
  }

  root.addEventListener('click', function (e) {
    var t = e.target.closest('[data-assistant-toggle]');
    if (t) { isOpen() ? closePanel() : openPanel(); return; }
    if (e.target.closest('[data-assistant-reset]')) { reset(); return; }
    var s = e.target.closest('[data-assistant-suggest]');
    if (s) send(s.getAttribute('data-assistant-suggest'));
  });

  form.addEventListener('submit', function (e) { e.preventDefault(); send(input.value); });

  input.addEventListener('keydown', function (e) {
    if (e.key === 'Enter' && !e.shiftKey && !e.isComposing) { e.preventDefault(); send(input.value); }
  });
  input.addEventListener('input', function () {
    input.style.height = 'auto';
    input.style.height = Math.min(input.scrollHeight, 96) + 'px';
  });

  root.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && isOpen()) { e.stopPropagation(); closePanel(); }
  });
})();
