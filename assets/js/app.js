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

  /* ---- Delegación de clicks ---- */
  document.addEventListener('click', async (ev) => {
    const like = ev.target.closest('[data-like]');
    const rep = ev.target.closest('[data-repost]');
    const del = ev.target.closest('[data-del]');
    const foll = ev.target.closest('[data-follow]');
    const openC = ev.target.closest('[data-open-compose]');
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
    if (rep) {
      ev.preventDefault();
      if (!RS.me) return needLogin();
      const id = rep.dataset.repost, span = $('span', rep), on = rep.classList.contains('on');
      rep.classList.toggle('on');
      try { const j = await api('repost.php', { id }); span.textContent = fmt(j.count); toast(on ? 'Repost quitado' : 'Reposteado'); }
      catch (e) { rep.classList.toggle('on'); toast(e.message, 1); }
      return;
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
      return;
    }
    if (openC) { ev.preventDefault(); openCompose(); return; }
    if (post && !ev.target.closest('a,button')) { location.href = post.dataset.href; }
  });

  function setSvg(btn, name) {
    const fill = name === 'liked';
    const svg = $('svg', btn); if (!svg) return;
    svg.innerHTML = '<path d="M12 21s-7.5-4.6-10-9.3C.3 8.3 1.9 4.5 5.5 4.5c2 0 3.3 1.1 4.1 2.3L12 9l2.4-2.2c.8-1.2 2.1-2.3 4.1-2.3 3.6 0 5.2 3.8 3.5 7.2C19.5 16.4 12 21 12 21Z"' + (fill ? ' fill="currentColor"' : '') + '/>';
  }

  /* ---- Composer (modal + en línea) ---- */
  function wireComposer(form) {
    if (!form || form._wired) return; form._wired = true;
    const ta = $('textarea', form), count = $('[data-count]', form), submit = $('[data-submit], button[type=submit]', form);
    const file = $('input[type=file]', form), prev = $('.composer-preview', form);
    const refresh = () => {
      const len = ta.value.length, left = 280 - len;
      if (count) { count.textContent = left; count.className = 'count' + (left < 0 ? ' over' : left <= 20 ? ' warn' : ''); }
      if (submit) submit.disabled = (len === 0 && !(file && file.files.length)) || left < 0;
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
  const closeCompose = () => { const m = $('#composeModal'); if (m) m.hidden = true; };
  document.addEventListener('click', e => { if (e.target.closest('[data-close-compose]') || e.target.id === 'composeModal') closeCompose(); });
  document.addEventListener('keydown', e => { if (e.key === 'Escape') closeCompose(); });

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

  document.addEventListener('DOMContentLoaded', () => { $$('[data-composer]').forEach(wireComposer); });
})();
