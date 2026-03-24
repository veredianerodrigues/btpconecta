document.addEventListener('DOMContentLoaded', () => {
  const cfg   = window.RD_ADMIN || {};
  const table = document.querySelector('#rd-tabela');
  const tbody = table ? table.querySelector('tbody') : null;

  const fData      = document.getElementById('rd-filter-data');
  const fTipo      = document.getElementById('rd-filter-tipo');
  const fCategoria = document.getElementById('rd-filter-categoria');
  const fLocal     = document.getElementById('rd-filter-local');
  const fMatricula = document.getElementById('rd-filter-matricula');

  const stripDomain = (m) => String(m || '').replace(/@.*$/, '');
  if (fMatricula) {
    fMatricula.addEventListener('input', () => {
      fMatricula.value = fMatricula.value.replace(/[^0-9A-Za-z._@-]/g,'');
    });
  }

  const btnFiltrar = document.getElementById('rd-btn-filtrar');
  const btnEmail   = document.getElementById('rd-btn-email');
  const msgEmail   = document.getElementById('rd-email-msg');


  const btnPrev   = document.getElementById('rd-prev');
  const btnNext   = document.getElementById('rd-next');
  const selLimit  = document.getElementById('rd-limit');
  const metaTotal = document.getElementById('rd-total');
  const metaPage  = document.getElementById('rd-page');
  const metaPages = document.getElementById('rd-pages');

  let PAGE  = 1;
  let PAGES = 1;
  let LIMIT = selLimit ? (parseInt(selLimit.value, 10) || 20) : 20;
  const STATUS = cfg.status || 'solicitado';

  const LOCAL_LABELS = (cfg && cfg.localOptions) ? cfg.localOptions : null;

  function hasLocalHeader() {
    const ths = Array.from(document.querySelectorAll('#rd-tabela thead th')).map(th => th.textContent.trim().toLowerCase());
    return ths.includes('local') || ths.includes('local de retirada') || ths.includes('retirada');
  }
  function headerColsCount() {
    const ths = document.querySelectorAll('#rd-tabela thead th');
    return ths && ths.length ? ths.length : 7;
  }
  function localLabel(key) {
    if (!key) return '';
    if (LOCAL_LABELS && Object.prototype.hasOwnProperty.call(LOCAL_LABELS, key)) return LOCAL_LABELS[key];
    return key;
  }

  const translateStatus = (status) => {
    const map = {
      'ativo': 'Solicitado',
      'confirmado': 'Retirado',
      'cancelado': 'Cancelado'
    };
    const key = (status || '').toLowerCase().trim();
    return map[key] || (status ? status.charAt(0).toUpperCase() + status.slice(1) : '');
  };
  const isoToBR = (iso) => {
    const m = /^(\d{4})-(\d{2})-(\d{2})$/.exec(String(iso || ''));
    return m ? `${m[3]}/${m[2]}/${m[1]}` : (iso || '');
  };
  const toISO = (v) => {
    const s = String(v || '').trim();
    const m = /^(\d{2})\/(\d{2})\/(\d{4})$/.exec(s);
    if (m) return `${m[3]}-${m[2]}-${m[1]}`;
    if (/^\d{4}-\d{2}-\d{2}$/.test(s)) return s;
    return '';
  };
  const maskDateInput = (el) => {
    el.addEventListener('input', () => {
      let v = el.value.replace(/\D/g, '').slice(0, 8);
      if (v.length >= 5) v = `${v.slice(0,2)}/${v.slice(2,4)}/${v.slice(4)}`;
      else if (v.length >= 3) v = `${v.slice(0,2)}/${v.slice(2)}`;
      el.value = v;
    });
  };
  const yesno = (n) => (Number(n) === 1 ? 'Sim' : 'Não');
  const buildUrl = (base, params) => {
    const u = new URL(base, window.location.origin);
    Object.entries(params || {}).forEach(([k, v]) => {
      if (v === undefined || v === null || v === '') return;
      u.searchParams.set(k, v);
    });
    return u.toString();
  };

  function updatePager(meta) {
    if (!metaTotal || !metaPage || !metaPages) return;
    const total = Number(meta?.total || 0);
    const page  = Number(meta?.page  || 1);
    const pages = Number(meta?.pages || 1);
    metaTotal.textContent = String(total);
    metaPage.textContent  = String(page);
    metaPages.textContent = String(pages);
    PAGES = pages; PAGE = page;
    if (btnPrev) btnPrev.disabled = page <= 1;
    if (btnNext) btnNext.disabled = page >= pages;
  }

  function applyDataLabels(t = table) {
    if (!t) return;
    const ths = Array.from(t.querySelectorAll('thead th'));
    if (!ths.length) return;
    const labels = ths.map(th => th.textContent.trim());

    t.querySelectorAll('tbody tr').forEach(tr => {
      Array.from(tr.children).forEach((td, i) => {
        if (!td.hasAttribute('data-label') && labels[i]) {
          td.setAttribute('data-label', labels[i]);
        }
      });
    });
  }

  window.RD_ADMIN = Object.assign({}, cfg, { applyDataLabels });

  if (fData) {
    if (!fData.value && cfg.today) {
      fData.value = isoToBR(cfg.today);
    }
  }
  async function marcarRetirado(id) {
    const base = (cfg.restPatch || '/wp-json/rd/v1/refeicoes/').replace(/\/?$/, '/');
    const url  = base + String(id);
    const r = await fetch(url, {
      method: 'PATCH',
      headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': cfg.nonce },
      body: JSON.stringify({ action: 'retirado' }),
      credentials: 'same-origin'
    });
    const j = await r.json().catch(() => ({}));
    if (!r.ok) throw new Error(j?.message || 'Falha ao marcar retirado');
  }

  function executarFiltro() {
    PAGE = 1;
    carregar();
  }

  async function carregar() {
    if (!tbody) return;
    table?.setAttribute('aria-busy','true');
    tbody.innerHTML = `<tr><td colspan="${headerColsCount()}">Carregando…</td></tr>`;
    const iso       = toISO(fData?.value);
    const tipo      = (fTipo?.value || '').trim();
    const categoria = (fCategoria?.value || '').trim();
    const local     = (fLocal?.value || '').trim();
    const matricula = (fMatricula?.value || '').trim();

    const url = buildUrl(cfg.restList || '/wp-json/rd/v1/refeicoes', {
      status: STATUS,
      data: iso || undefined,
      tipo: tipo || undefined,
      categoria: categoria || undefined,
      local_retirada: local || undefined,
      matricula: matricula || undefined,
      page: (btnPrev || btnNext || selLimit) ? PAGE  : undefined,
      limit: (btnPrev || btnNext || selLimit) ? LIMIT : undefined,
    });

    try {
      const res = await fetch(url, { headers: { 'X-WP-Nonce': cfg.nonce } });
      const payload = await res.json().catch(() => ({}));
      if (!res.ok) throw new Error(payload?.message || 'Falha ao carregar');

      const rows = Array.isArray(payload) ? payload : (payload?.data || []);
      const includeLocal = hasLocalHeader() || (rows && rows.some(r => r && r.local_retirada));

      const meta = Array.isArray(payload) ? { total: rows.length, page: 1, pages: 1 } : (payload?.meta || {});

      if (!rows || rows.length === 0) {
        tbody.innerHTML = `<tr><td colspan="${headerColsCount()}">Nenhum registro encontrado.</td></tr>`;
        updatePager(meta);
        if (typeof updateCsvHref === 'function') updateCsvHref();
        table?.removeAttribute('aria-busy');
        return;
      }

      tbody.innerHTML = '';
      rows.forEach(row => tbody.appendChild(renderRow(row, includeLocal)));
      applyDataLabels();

      updatePager(meta);
      if (typeof updateCsvHref === 'function') updateCsvHref();
    } catch (e) {
      tbody.innerHTML = `<tr><td colspan="${headerColsCount()}">${e.message || 'Erro inesperado'}</td></tr>`;
    } finally {
      table?.removeAttribute('aria-busy');
    }
  }

  function categoryLabel(code) {
    if (!code) return '';
    const labels = cfg.categoryLabels || {};
    return labels[code] || code;
  }

  function renderRow(item, includeLocal) {
    const tr = document.createElement('tr');
    const td = (t) => { const e = document.createElement('td'); e.textContent = t; return e; };

    tr.appendChild(td(stripDomain(item.matricula || '')));
    tr.appendChild(td(item.nome_completo || ''));
    tr.appendChild(td(isoToBR(item.data_refeicao || '')));
    tr.appendChild(td(categoryLabel(item.categoria || '')));
    tr.appendChild(td(item.refeicao || ''));
    tr.appendChild(td(yesno(item.retirado)));
    if (includeLocal) {
      const tdLocal = document.createElement('td');
      tdLocal.textContent = localLabel(item.local_retirada || '');
      tr.appendChild(tdLocal);
    }

    const tdAcoes = document.createElement('td');
    if (!Number(item.retirado) && (item.can_edit || item.status === 'confirmado')) {
      const btn = document.createElement('button');
      btn.className = 'button';
      btn.textContent = 'Marcar retirado';
      btn.addEventListener('click', async () => {
        btn.disabled = true;
        try {
          await marcarRetirado(item.id);
          await carregar();
        } catch (err) {
          alert(err.message || 'Falha ao marcar retirado');
          btn.disabled = false;
        }
      });
      tdAcoes.appendChild(btn);
    } else if (item.status === 'cancelado') {
      tdAcoes.textContent = translateStatus('cancelado');
    } else if (Number(item.retirado)) {
      tdAcoes.textContent = translateStatus('confirmado');
    } else {
      tdAcoes.innerHTML = '&nbsp;';
    }
    tr.appendChild(tdAcoes);

    return tr;
  }

  btnFiltrar?.addEventListener('click', (e) => { e.preventDefault(); executarFiltro(); });

  let filterTimeout = null;
  const debounceFilter = (fn, delay = 300) => {
    return function(...args) {
      clearTimeout(filterTimeout);
      filterTimeout = setTimeout(() => fn.apply(this, args), delay);
    };
  };

  const handleEnterFilter = (el, opts = {}) => {
    if (!el) return;
    el.addEventListener('keydown', (e) => {
      if (e.key !== 'Enter') return;

      if (opts.skipWhenDatepickerVisible) {
        const $ = window.jQuery;
        if ($ && typeof $.fn.datepicker === 'function') {
          const widget = $(el).datepicker('widget');
          if (widget && widget.length && widget.is(':visible')) {
            return;
          }
        }
      }

      const run = () => executarFiltro();
      if (!opts.allowDefault) e.preventDefault();
      if (opts.defer) setTimeout(run, 0); else run();
    });
  };

  handleEnterFilter(fData, { skipWhenDatepickerVisible: true });
  handleEnterFilter(fTipo, { defer: true, allowDefault: true });
  handleEnterFilter(fLocal, { defer: true, allowDefault: true });
  handleEnterFilter(fMatricula);

  fData?.addEventListener('change', debounceFilter(() => {
    if (window.RD_IGNORE_DATE_CHANGE) {
      return;
    }
    PAGE = 1;
    executarFiltro();
    if (typeof updateCsvHref === 'function') updateCsvHref();
  }, 100));

  fTipo?.addEventListener('change', debounceFilter(() => {
    PAGE = 1;
    executarFiltro();
    if (typeof updateCsvHref === 'function') updateCsvHref();
  }, 100));

  fLocal?.addEventListener('change', debounceFilter(() => {
    PAGE = 1;
    executarFiltro();
    if (typeof updateCsvHref === 'function') updateCsvHref();
  }, 100));

  fCategoria?.addEventListener('change', debounceFilter(() => {
    PAGE = 1;
    executarFiltro();
    if (typeof updateCsvHref === 'function') updateCsvHref();
  }, 100));

  btnPrev?.addEventListener('click', (e) => { e.preventDefault(); if (PAGE > 1) { PAGE--; carregar(); } });
  btnNext?.addEventListener('click', (e) => { e.preventDefault(); if (PAGE < PAGES) { PAGE++; carregar(); } });
  selLimit?.addEventListener('change', () => { LIMIT = parseInt(selLimit.value, 10) || 20; PAGE = 1; carregar(); });

  carregar();

  btnEmail?.addEventListener('click', async () => {
    if (!cfg.restEmail) return;
    msgEmail.textContent = 'Enviando...';
    msgEmail.style.opacity = '1';
    try {
      const iso       = toISO(fData?.value);
      const tipo      = (fTipo?.value || '').trim();
      const categoria = (fCategoria?.value || '').trim();
      const local     = (fLocal?.value || '').trim();
      const matricula = (fMatricula?.value || '').trim();

      const u = buildUrl(cfg.restEmail, {
        data: iso || undefined,
        tipo: tipo || undefined,
        categoria: categoria || undefined,
        local_retirada: local || undefined,
        matricula: matricula || undefined,
        status: STATUS
      });

      const r = await fetch(u, { method: 'POST', headers: { 'X-WP-Nonce': cfg.nonce }, credentials: 'same-origin' });
      const j = await r.json().catch(() => ({}));
      if (!r.ok) throw new Error(j?.message || 'Falha ao enviar');
      msgEmail.textContent = 'Enviado';
    } catch (e) {
      msgEmail.textContent = e.message || 'Erro ao enviar';
    } finally {
      setTimeout(() => { msgEmail.style.opacity = '0'; }, 3000);
    }
  });
});
