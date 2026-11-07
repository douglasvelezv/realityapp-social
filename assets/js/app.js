/* Reality Social · interacciones (vanilla JS + Fetch) */
(function () {
  'use strict';
  const RS = window.RS || { base: '/social', csrf: '', me: 0 };
  const $ = (s, r = document) => r.querySelector(s);
  const $$ = (s, r = document) => [...r.querySelectorAll(s)];

  function toast(msg, err) {
    const box = $('#toast'); if (!box) return;
    const t = document.createElement('div');
    t.className = 'toast' + (err ? ' err' : ''); t.textContent = msg;
    box.appendChild(t); setTimeout(() => t.remove(), 3200);
  }
  async function api(path, data) {
    const opt = { method: 'POST', headers: { 'X-Requested-With': 'fetch' } };
    if (data instanceof FormData) { data.append('csrf', RS.csrf); opt.body = data; }
    else { opt.headers['Content-Type'] = 'application/json'; opt.body = JSON.stringify({ ...(data || {}), csrf: RS.csrf }); }
    const r = await fetch(RS.base + '/api/' + path, opt);
    let j = {}; try { j = await r.json(); } catch (e) {}
    if (!r.ok) throw new Error(j.error || 'Error de red');
    return j;
  }
  const fmt = n => n >= 1e6 ? (n / 1e6).toFixed(1).replace(/\.0$/, '') + 'M'
    : n >= 1e3 ? (n / 1e3).toFixed(1).replace(/\.0$/, '') + 'K' : (n || '');
  const needLogin = () => { toast('Inicia sesión para continuar'); setTimeout(() => location.href = RS.base + '/auth/login.php', 700); };

  const ICON = {
    like:  '<path d="M12 21s-7.5-4.6-10-9.3C.3 8.3 1.9 4.5 5.5 4.5c2 0 3.3 1.1 4.1 2.3L12 9l2.4-2.2c.8-1.2 2.1-2.3 4.1-2.3 3.6 0 5.2 3.8 3.5 7.2C19.5 16.4 12 21 12 21Z"/>',
    liked: '<path d="M12 21s-7.5-4.6-10-9.3C.3 8.3 1.9 4.5 5.5 4.5c2 0 3.3 1.1 4.1 2.3L12 9l2.4-2.2c.8-1.2 2.1-2.3 4.1-2.3 3.6 0 5.2 3.8 3.5 7.2C19.5 16.4 12 21 12 21Z" fill="currentColor"/>',
    bookmark:   '<path d="M6 3h12a1 1 0 0 1 1 1v17l-7-4.5L5 21V4a1 1 0 0 1 1-1Z"/>',
    bookmarked: '<path d="M6 3h12a1 1 0 0 1 1 1v17l-7-4.5L5 21V4a1 1 0 0 1 1-1Z" fill="currentColor"/>'
  };
  function setSvg(btn, name) { const svg = $('svg', btn); if (svg) svg.innerHTML = ICON[name] || ''; }

  function closeMenus(except) { $$('.menu:not([hidden])').forEach(m => { if (m !== except) m.hidden = true; }); $$('.pop').forEach(p => { if (p !== except) p.remove(); }); }

  async function doRepost(btn) {
    const id = btn.dataset.repost, span = $('span', btn), on = btn.classList.contains('on');
    btn.classList.toggle('on');
    try { const j = await api('repost.php', { id }); if (span) span.textContent = fmt(j.count); toast(on ? 'Repost quitado' : 'Reposteado'); }
    catch (e) { btn.classList.toggle('on'); toast(e.message, 1); }
  }
  function repostMenu(btn) {
    closeMenus();
    const id = btn.dataset.repost, on = btn.classList.contains('on');
    const pop = document.createElement('div'); pop.className = 'pop';
    pop.innerHTML = '<button data-rp>' + (on ? 'Deshacer repost' : 'Repostear') + '</button><button data-qt>Citar</button>';
    document.body.appendChild(pop);
    const r = btn.getBoundingClientRect();
    pop.style.top = (window.scrollY + r.bottom + 6) + 'px';
    pop.style.left = (window.scrollX + r.left) + 'px';
    pop.querySelector('[data-rp]').onclick = () => { pop.remove(); doRepost(btn); };
    pop.querySelector('[data-qt]').onclick = () => { pop.remove(); openQuote(id); };
  }

  /* ---- Delegación de clicks ---- */
  document.addEventListener('click', async (ev) => {
    const like = ev.target.closest('[data-like]');
    const rep = ev.target.closest('[data-repost]');
    const bm = ev.target.closest('[data-bookmark]');
    const copy = ev.target.closest('[data-copy]');
    const menuBtn = ev.target.closest('[data-menu]');
    const pin = ev.target.closest('[data-pin]');
    const del = ev.target.closest('[data-del]');
    const foll = ev.target.closest('[data-follow]');
    const openC = ev.target.closest('[data-open-compose]');
    const themeT = ev.target.closest('[data-theme-toggle]');
    const post = ev.target.closest('.post[data-href]');

    if (like) {
      ev.preventDefault();
      if (!RS.me) return needLogin();
      const id = like.dataset.like, span = $('span', like), on = like.classList.contains('on');
      like.classList.toggle('on'); setSvg(like, !on ? 'liked' : 'like');
      try { const j = await api('like.php', { id }); span.textContent = fmt(j.count); }
      catch (e) { like.classList.toggle('on'); setSvg(like, on ? 'liked' : 'like'); toast(e.message, 1); }
      return;
    }
    if (rep) { ev.preventDefault(); if (!RS.me) return needLogin(); repostMenu(rep); return; }
    if (bm) {
      ev.preventDefault();
      if (!RS.me) return needLogin();
      const id = bm.dataset.bookmark, on = bm.classList.contains('on');
      bm.classList.toggle('on'); setSvg(bm, !on ? 'bookmarked' : 'bookmark');
      try { const j = await api('bookmark.php', { id }); toast(j.bookmarked ? 'Guardado' : 'Quitado de guardados'); }
      catch (e) { bm.classList.toggle('on'); setSvg(bm, on ? 'bookmarked' : 'bookmark'); toast(e.message, 1); }
      return;
    }
    if (copy) {
      ev.preventDefault();
      const url = new URL(copy.dataset.copy, location.origin).href;
      try { await navigator.clipboard.writeText(url); toast('Enlace copiado'); }
      catch (e) { prompt('Copia el enlace:', url); }
      closeMenus(); return;
    }
    if (menuBtn) {
      ev.preventDefault(); ev.stopPropagation();
      const menu = menuBtn.parentElement.querySelector('.menu');
      const willOpen = menu && menu.hidden; closeMenus(menu);
      if (menu) menu.hidden = !willOpen;
      return;
    }
    if (pin) {
      ev.preventDefault();
      try { const j = await api('post.php', { action: 'pin', id: pin.dataset.pin }); toast(j.pinned ? 'Fijado en tu perfil' : 'Dejó de estar fijado'); setTimeout(() => location.reload(), 500); }
      catch (e) { toast(e.message, 1); }
      closeMenus(); return;
    }
    if (foll) {
      ev.preventDefault();
      if (!RS.me) return needLogin();
      const id = foll.dataset.follow, ing = foll.classList.contains('ing');
      try {
        const j = await api('follow.php', { id });
        foll.classList.toggle('ing', j.following);
        foll.textContent = j.following ? 'Siguiendo' : 'Seguir';
      } catch (e) { toast(e.message, 1); }
      return;
    }
    if (del) {
      ev.preventDefault();
      if (!confirm('¿Eliminar esta publicación?')) return;
      try { await api('post.php', { action: 'delete', id: del.dataset.del }); const el = del.closest('.post'); if (el) el.remove(); toast('Publicación eliminada'); }
      catch (e) { toast(e.message, 1); }
      closeMenus(); return;
    }
    if (themeT) { ev.preventDefault(); toggleTheme(); return; }
    if (openC) { ev.preventDefault(); openCompose(); return; }
    if (ev.target.closest('.menu') || ev.target.closest('.pop')) return;
    closeMenus();
    if (post && !ev.target.closest('a,button')) { location.href = post.dataset.href; }
  });

  /* ---- Composer (modal + en línea) ---- */
  function wireComposer(form) {
    if (!form || form._wired) return; form._wired = true;
    const ta = $('textarea', form), count = $('[data-count]', form), submit = $('[data-submit], button[type=submit]', form);
    const file = $('input[type=file]', form), prev = $('.composer-preview', form);
    const refresh = () => {
      const len = ta.value.length, left = 280 - len;
      const hasQuote = !!form.querySelector('[name=quote_of]');
      if (count) { count.textContent = left; count.className = 'count' + (left < 0 ? ' over' : left <= 20 ? ' warn' : ''); }
      if (submit) submit.disabled = (len === 0 && !(file && file.files.length) && !hasQuote) || left < 0;
    };
    ta && ta.addEventListener('input', refresh);
    if (file) file.addEventListener('change', () => {
      if (file.files[0]) { const img = $('img', prev); img.src = URL.createObjectURL(file.files[0]); prev.hidden = false; }
      refresh();
    });
    const rm = $('.rm-img', form); rm && rm.addEventListener('click', () => { file.value = ''; prev.hidden = true; refresh(); });
    form.addEventListener('submit', async (e) => {
      e.preventDefault();
      if (!RS.me) return needLogin();
      const fd = new FormData(form);
      if (!(fd.get('body') || '').trim() && !(file && file.files.length)) return;
      submit.disabled = true;
      try {
        const j = await api('post.php', fd);
        if (j.html) {
          const wrap = document.createElement('div'); wrap.innerHTML = j.html.trim();
          const node = wrap.firstElementChild;
          const feed = $('[data-stream]');
          if (form.closest('#composeModal')) closeCompose();
          if (feed) feed.insertAdjacentElement('afterbegin', node);
          else { toast('Publicado'); location.href = RS.base + '/post.php?id=' + j.id; return; }
          toast('Publicado');
        }
        ta.value = ''; if (file) file.value = ''; if (prev) prev.hidden = true; refresh();
      } catch (err) { toast(err.message, 1); }
      finally { submit.disabled = false; }
    });
    refresh();
  }
  const openCompose = () => { const m = $('#composeModal'); if (m) { m.hidden = false; const t = $('textarea', m); t && t.focus(); } };
  const closeCompose = () => {
    const m = $('#composeModal'); if (!m) return; m.hidden = true;
    const f = $('form[data-composer]', m); if (f) { const q = $('[name=quote_of]', f); if (q) q.remove(); const c = $('.quote-ctx', f); if (c) c.remove(); }
  };
  function openQuote(id) {
    if (!RS.me) return needLogin();
    const m = $('#composeModal'); if (!m) { location.href = RS.base + '/?quote=' + id; return; }
    const f = $('form[data-composer]', m); const main = $('.composer-main', f);
    let q = $('[name=quote_of]', f); if (!q) { q = document.createElement('input'); q.type = 'hidden'; q.name = 'quote_of'; f.appendChild(q); }
    q.value = id;
    if (!$('.quote-ctx', f)) { const note = document.createElement('div'); note.className = 'quote-ctx'; note.textContent = 'Citando una publicación'; main.appendChild(note); }
    openCompose(); const t = $('textarea', m); t && (t.placeholder = 'Añade un comentario', t.focus());
    const sub = $('[data-submit], button[type=submit]', f); if (sub) sub.disabled = false;   // la cita ya es contenido
  }
  document.addEventListener('click', e => { if (e.target.closest('[data-close-compose]') || e.target.id === 'composeModal') closeCompose(); });
  document.addEventListener('keydown', e => { if (e.key === 'Escape') { closeCompose(); closeMenus(); } });

  /* ---- Tema claro/oscuro ---- */
  function applyTheme(t) {
    document.documentElement.setAttribute('data-theme', t);
    try { localStorage.setItem('rs-theme', t); } catch (e) {}
    $$('[data-theme-toggle] .tt-label').forEach(el => el.textContent = t === 'light' ? 'Modo oscuro' : 'Modo claro');
  }
  function toggleTheme() { const cur = document.documentElement.getAttribute('data-theme') || 'dark'; applyTheme(cur === 'dark' ? 'light' : 'dark'); }

  /* ---- Visualizaciones reales (impresiones del feed) ---- */
  function wireViews() {
    if (!RS.me || !('IntersectionObserver' in window)) return;
    const seen = new Set(); let pending = [], timer = null;
    const flush = () => {
      if (!pending.length) return;
      const ids = pending.splice(0); timer = null;
      fetch(RS.base + '/api/view.php', { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'fetch' }, body: JSON.stringify({ ids }) }).catch(() => {});
    };
    const io = new IntersectionObserver((entries) => {
      for (const en of entries) {
        if (!en.isIntersecting) continue;
        const el = en.target, id = el.dataset.id;
        if (!id || seen.has(id)) { io.unobserve(el); continue; }
        seen.add(id); pending.push(+id); io.unobserve(el);
        if (!timer) timer = setTimeout(flush, 1200);
      }
    }, { threshold: 0.6 });
    const watch = () => $$('.post[data-id]').forEach(p => io.observe(p));
    watch();
    new MutationObserver(watch).observe(document.body, { childList: true, subtree: true });
    window.addEventListener('beforeunload', flush);
  }

  /* ---- Cargar más ---- */
  document.addEventListener('click', async (ev) => {
    const btn = ev.target.closest('[data-more]'); if (!btn) return;
    const url = btn.dataset.more;
    btn.textContent = 'Cargando…';
    try {
      const r = await fetch(url, { headers: { 'X-Requested-With': 'fetch' } });
      const j = await r.json();
      const feed = $('[data-stream]');
      if (j.html && feed) feed.insertAdjacentHTML('beforeend', j.html);
      if (j.next) btn.dataset.more = j.next, btn.textContent = 'Mostrar más';
      else btn.remove();
    } catch (e) { btn.textContent = 'Mostrar más'; toast('No se pudo cargar más', 1); }
  });

  document.addEventListener('DOMContentLoaded', () => {
    $$('[data-composer]').forEach(wireComposer); wireViews();
    const t = document.documentElement.getAttribute('data-theme') || 'dark';
    $$('[data-theme-toggle] .tt-label').forEach(el => el.textContent = t === 'light' ? 'Modo oscuro' : 'Modo claro');
  });
})();
