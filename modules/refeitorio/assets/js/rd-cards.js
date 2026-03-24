(function () {
  'use strict';
  if (!window.RD_CARDS) return;

  const cfg = window.RD_CARDS;
  const variant = (cfg.variant || 'cards').toLowerCase();
  let LOCAL_LABELS = null;
  let CATEGORY_LABELS = null;
  async function fetchFormData() {
    try {
      const r = await fetch(`${cfg.apiBase}/refeicao/form-data`, { headers: { 'X-WP-Nonce': cfg.nonce } });
      const j = await r.json().catch(() => ({}));
      if (!r.ok) return null;
      LOCAL_LABELS = (j && j.local_retirada_options) || null;
      if (j && Array.isArray(j.meal_categories)) {
        CATEGORY_LABELS = {};
        j.meal_categories.forEach(c => { CATEGORY_LABELS[c.code] = c.label; });
      }
      return j;
    } catch (_) { return null; }
  }
  function localLabel(key) { return (LOCAL_LABELS && key) ? (LOCAL_LABELS[key] || key) : (key || ''); }
  function categoryLabel(code) { return (CATEGORY_LABELS && code) ? (CATEGORY_LABELS[code] || code) : (code || ''); }

  const setMsg = (root, t) => { const el = root.querySelector('#rd-list-msg'); if (el) el.textContent = t || ''; };
  const br = (iso) => {
    if (!iso || typeof iso !== 'string') return '';
    const [y, m, d] = iso.split('-'); if (!y || !m || !d) return iso;
    return `${d}/${m}/${y}`;
  };
  const translateStatus = (status) => {
    const map = {
      'ativo': 'Solicitado',
      'confirmado': 'Retirado',
      'cancelado': 'Cancelado'
    };
    const key = (status || '').toLowerCase().trim();
    return map[key] || status.charAt(0).toUpperCase() + status.slice(1);
  };
  const statusChipClass = (status) => {
    const key = (status || '').toLowerCase().trim();
    const classMap = {
      'ativo': 'rd-chip-solicitado',
      'confirmado': 'rd-chip-retirado',
      'cancelado': 'rd-chip-cancelado'
    };
    return classMap[key] || 'rd-chip-default';
  };
  const toDate = (iso) => {
    if (!iso) return null;
    const [y, m, d] = iso.split('-').map(Number);
    if (!y || !m || !d) return null;
    return new Date(y, m - 1, d);
  };
  const todayMidnight = () => { const n = new Date(); return new Date(n.getFullYear(), n.getMonth(), n.getDate()); };
  function cmpByCloseness(a, b) {
    const t = todayMidnight().getTime();
    const da = toDate(a.data || a.data_refeicao)?.getTime() ?? t;
    const db = toDate(b.data || b.data_refeicao)?.getTime() ?? t;
    const A = da - t, B = db - t, aF = A >= 0, bF = B >= 0;
    if (aF && bF) return A - B;
    if (aF && !bF) return -1;
    if (!aF && bF) return 1;
    return Math.abs(A) - Math.abs(B);
  }
  function weekKey(date) {
    const d = new Date(date); const day = (d.getDay() + 6) % 7;
    const monday = new Date(d); monday.setDate(d.getDate() - day);
    const sunday = new Date(monday); sunday.setDate(monday.getDate() + 6);
    const two = (n) => String(n).padStart(2, '0');
    const label = `${two(monday.getDate())}/${two(monday.getMonth()+1)}–${two(sunday.getDate())}/${two(sunday.getMonth()+1)}`;
    return { id: `${monday.getFullYear()}-${two(monday.getMonth()+1)}-${two(monday.getDate())}`, label };
  }
  function weekdayShort(date) {
    return ['Seg','Ter','Qua','Qui','Sex','Sáb','Dom'][ (date.getDay()+6)%7 ];
  }

  async function fetchMine(params = {}) {
    const usp = new URLSearchParams(params);
    const res = await fetch(`${cfg.apiBase}/refeicoes?${usp.toString()}`, { headers: { 'X-WP-Nonce': cfg.nonce } });
    const j = await res.json().catch(() => ({}));
    if (!res.ok) throw new Error(j?.message || 'Erro ao consultar solicitações.');
    return j.items || j || [];
  }

  async function load(root) {
    const host = root.querySelector('#rd-cards'); if (!host) return;
    setMsg(root, ''); host.innerHTML = '';

    const tokens = String(cfg.status ?? 'confirmado').split(/[|,]+/).map(s => s.trim().toLowerCase()).filter(Boolean);
    const statuses = tokens.length ? tokens : ['confirmado'];

    try {
      let items = [];
      if (LOCAL_LABELS === null) { await fetchFormData(); }
      for (const st of statuses) {
        const params = { mine: 1 };
        if (st === 'ativo') params.ativo = 1; else params.status = st;
        const part = await fetchMine(params);
        items = items.concat(part);
      }
      const map = new Map();
      for (const row of items) {
        const key = row.id ?? `${row.data || row.data_refeicao}-${row.tipo || row.refeicao}`;
        map.set(key, row);
      }
      items = Array.from(map.values()).sort(cmpByCloseness);
      if (!items.length) { setMsg(root, 'Nenhuma solicitação encontrada.'); return; }

      if (variant === 'agenda') {
        renderAgenda(host, items);
      } else {
        renderCards(host, items);
      }
    } catch (e) {
      setMsg(root, e.message || 'Erro ao carregar.');
    }
  }

  function renderCards(host, items) {
    items.forEach((row, i) => {
      const card = document.createElement('div');
      card.className = `rd-card rd-card-${(i % 3) + 1}`;
      const data = br(row.data || row.data_refeicao);
      const tipo = row.tipo || row.refeicao || '';
      const cat = categoryLabel(row.categoria || '');
      const loc = localLabel(row.local_retirada || '');
      card.innerHTML = `<strong>${data}</strong>${cat ? ' <em>(' + cat + ')</em>' : ''}: ${tipo}${loc ? ' — ' + loc : ''}`;
      host.appendChild(card);
    });
  }

  function inNext30Days(date) {
    if (!date) return false;
    const today = todayMidnight();
    const limit = new Date(today);
    limit.setDate(today.getDate() + 30);
    return date >= today && date <= limit;
  }

  function renderAgenda(host, items) {
  if (host) host.innerHTML = '';

  const groups = new Map();
  items.forEach((row) => {
    const d = toDate(row.data || row.data_refeicao);
    if (!d) return;

    if (!inNext30Days(d)) return;

    const wk = weekKey(d);
    if (!groups.has(wk.id)) groups.set(wk.id, { label: wk.label, rows: [] });
    groups.get(wk.id).rows.push(row);
  });

  const ordered = Array.from(groups.values()).sort((A, B) => {
    const a = toDate(A.rows[0].data || A.rows[0].data_refeicao);
    const b = toDate(B.rows[0].data || B.rows[0].data_refeicao);
    return a - b;
  });
  if (!ordered.length) {
    return;
  }

  ordered.forEach((g) => {
    const ul = document.createElement('ul');
    ul.className = 'rd-agenda';

    g.rows.sort((a, b) => (toDate(a.data || a.data_refeicao) - toDate(b.data || b.data_refeicao)));

    g.rows.forEach((row) => {
      const d = toDate(row.data || row.data_refeicao);
      const li = document.createElement('li');
      li.className = 'rd-agenda-item';
      const loc = localLabel(row.local_retirada || '');
      const cat = categoryLabel(row.categoria || '');
      const rowStatus = row.status || 'confirmado';
      li.innerHTML = `
        <span class="rd-dot"></span>
        <span class="rd-agenda-date"><strong>${weekdayShort(d)}</strong> ${br(row.data || row.data_refeicao)}</span>
        ${cat ? `<span class="rd-agenda-cat">${cat}</span>` : ''}
        <span class="rd-agenda-meal">${row.tipo || row.refeicao || ''}</span>
        ${loc ? `<span class=\"rd-agenda-local\">${loc}</span>` : ''}
        <span class="rd-chip ${statusChipClass(rowStatus)}">${translateStatus(rowStatus)}</span>
      `;
      ul.appendChild(li);
    });

    host.appendChild(ul);
  });
}

  document.querySelectorAll('.rd.rd-list').forEach(load);
})();
