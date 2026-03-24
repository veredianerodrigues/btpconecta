(function () {
  'use strict';
  if (!window.RD_LIST) return;

  const cfg = window.RD_LIST;

  var LOCAL_LABELS = null;
  var mealTypesCache = null;
  async function fetchLocalLabels() {
    try {
      var res = await fetch(cfg.apiBase + '/refeicao/form-data', { headers: { 'X-WP-Nonce': cfg.nonce } });
      var j = await res.json().catch(function(){ return {}; });
      if (!res.ok) return null;
      return j && j.local_retirada_options || null;
    } catch (e) { return null; }
  }
  function localLabel(key) {
    if (!key) return '';
    return (LOCAL_LABELS && LOCAL_LABELS[key]) ? LOCAL_LABELS[key] : key;
  }

  const setMsg = (root, t) => { const el = root.querySelector('#rd-list-msg'); if (el) el.textContent = t || ''; };
  function isoToBR(iso) {
    if (!iso || typeof iso !== 'string') return '';
    var p = iso.split('-');
    if (p.length !== 3) return iso;
    return p[2] + '/' + p[1] + '/' + p[0];
  }
  function brToISO(br) {
    if (!br || typeof br !== 'string') return null;
    var m = br.match(/^(\d{2})\/(\d{2})\/(\d{4})$/);
    if (!m) return null;
    return m[3] + '-' + m[2] + '-' + m[1];
  }
  function dateFromRow(row) {
    var iso = row && (row.data || row.data_refeicao) || '';
    var parts = (iso || '').split('-');
    var y = parseInt(parts[0], 10), m = parseInt(parts[1], 10), d = parseInt(parts[2], 10);
    if (!y || !m || !d) return null;
    return new Date(y, m - 1, d);
  }
  function todayMidnight() {
    var n = new Date();
    return new Date(n.getFullYear(), n.getMonth(), n.getDate());
  }
  function cmpByClosenessToToday(a, b) {
    var t = todayMidnight().getTime();
    var daObj = dateFromRow(a), dbObj = dateFromRow(b);
    var da = daObj ? daObj.getTime() : t;
    var db = dbObj ? dbObj.getTime() : t;
    var A = da - t, B = db - t;
    var aF = A >= 0, bF = B >= 0;
    if (aF && bF) return A - B;
    if (aF && !bF) return -1;
    if (!aF && bF) return 1;
    return Math.abs(A) - Math.abs(B);
  }
  function inNext30Days(row) {
    var dt = dateFromRow(row);
    if (!dt) return false;
    var today = todayMidnight();
    var limit = new Date(today);
    limit.setDate(today.getDate() + 30);
    return dt >= today && dt <= limit;
  }
  function ts(row) {
    var d = dateFromRow(row);
    return d ? d.getTime() : Number.POSITIVE_INFINITY;
  }
  function cmpAscByDate(a, b) { return ts(a) - ts(b); }
  function isRetirado(r) { return r && (r.retirado === 1 || r.retirado === '1' || r.retirado === true); }

  function parseISODate(iso) {
    if (!iso || typeof iso !== 'string') return null;
    var m = iso.match(/^(\d{4})-(\d{2})-(\d{2})$/);
    if (!m) return null;
    return new Date(+m[1], +m[2] - 1, +m[3]);
  }

  async function loadMealTypes() {
    if (mealTypesCache !== null) return mealTypesCache;
    try {
      var res = await fetch(cfg.apiBase + '/meal-types', { headers: { 'X-WP-Nonce': cfg.nonce } });
      var j = await res.json().catch(function() { return {}; });
      if (res.ok && Array.isArray(j?.items)) {
        mealTypesCache = j.items;
        return mealTypesCache;
      }
    } catch (_) {}
    mealTypesCache = [];
    return mealTypesCache;
  }

  function filterMealTypesByDate(isoDate) {
    if (!mealTypesCache || !isoDate) return mealTypesCache || [];
    var date = parseISODate(isoDate);
    if (!date) return mealTypesCache || [];
    var dayOfWeek = date.getDay();
    return (mealTypesCache || []).filter(function(item) {
      if (item && typeof item === 'object' && Array.isArray(item.days)) {
        return item.days.includes(dayOfWeek);
      }
      return true;
    });
  }

  async function apiGet(path, params) {
    params = params || {};
    const usp = new URLSearchParams(params);
    const res = await fetch(cfg.apiBase + path + '?' + usp.toString(), {
      headers: { 'X-WP-Nonce': cfg.nonce }
    });
    const j = await res.json().catch(function(){ return {}; });
    if (!res.ok) throw new Error((j && j.message) || 'Erro na consulta.');
    return j;
  }
  async function apiPatch(path, body) {
    const res = await fetch(cfg.apiBase + path, {
      method: 'PATCH',
      headers: {
        'Content-Type': 'application/json',
        'X-WP-Nonce': cfg.nonce
      },
      body: JSON.stringify(body || {})
    });
    const j = await res.json().catch(function(){ return {}; });
    if (!res.ok) throw new Error((j && j.message) || 'Erro ao atualizar.');
    return j;
  }

  const iconTrash = () =>
    '<svg width="18" height="18" viewBox="0 0 24 24" aria-hidden="true" focusable="false">' +
      '<path fill="currentColor" d="M6 19a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V7H6v12Zm3-8h2v8H9v-8Zm4 0h2v8h-2v-8ZM15.5 4l-1-1h-5l-1 1H5v2h14V4h-3.5Z"/>' +
    '</svg>';
  const iconEdit  = () =>
    '<svg width="18" height="18" viewBox="0 0 24 24" aria-hidden="true" focusable="false">' +
      '<path fill="currentColor" d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04a1 1 0 0 0 0-1.41l-2.34-2.34a1 1 0 0 0-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/>' +
    '</svg>';
  const iconCheck = () =>
    '<svg width="18" height="18" viewBox="0 0 24 24" aria-hidden="true" focusable="false">' +
      '<path fill="currentColor" d="M9 16.2 4.8 12l-1.8 1.8L9 19 21 7l-1.8-1.8z"/>' +
    '</svg>';
  function getLabelForCode(code) {
    if (!code || !mealTypesCache) return code;
    var found = mealTypesCache.find(function(item) {
      return (item?.code || item) === code;
    });
    return found?.label || code;
  }

  function createTipoControl(valorAtual, isoDate) {
    var tipos = filterMealTypesByDate(isoDate);
    if (!tipos.length && mealTypesCache && mealTypesCache.length) tipos = mealTypesCache;

    if (tipos && tipos.length) {
      const sel = document.createElement('select');
      sel.className = 'rd-input rd-readonly';
      sel.setAttribute('data-field', 'tipo');
      sel.disabled = true;

      const opt0 = document.createElement('option');
      opt0.value = ''; opt0.textContent = 'Selecione';
      sel.appendChild(opt0);

      tipos.forEach(function(item) {
        const code = item?.code || item;
        const label = item?.label || item;
        const o = document.createElement('option');
        o.value = code; o.textContent = label;
        sel.appendChild(o);
      });
      sel.value = valorAtual || '';
      return sel;
    }
    const inp = document.createElement('input');
    inp.type = 'text';
    inp.className = 'rd-input rd-readonly';
    inp.setAttribute('data-field', 'tipo');
    inp.setAttribute('readonly', 'readonly');
    inp.value = getLabelForCode(valorAtual) || valorAtual || '';
    return inp;
  }

  function updateTipoOptions(sel, isoDate, currentValue) {
    if (!sel || sel.tagName !== 'SELECT') return;
    var tipos = filterMealTypesByDate(isoDate);
    if (!tipos.length && mealTypesCache) tipos = mealTypesCache;

    sel.innerHTML = '';
    var opt0 = document.createElement('option');
    opt0.value = ''; opt0.textContent = 'Selecione';
    sel.appendChild(opt0);

    tipos.forEach(function(item) {
      var code = item?.code || item;
      var label = item?.label || item;
      var o = document.createElement('option');
      o.value = code; o.textContent = label;
      sel.appendChild(o);
    });

    if (currentValue && tipos.some(function(item) { return (item?.code || item) === currentValue; })) {
      sel.value = currentValue;
    }
  }
  const getTipoValue = (el) => el.tagName === 'SELECT' ? el.value : (el.value || '').trim();
  const setTipoValue = (el, v) => { if (el.tagName === 'SELECT') el.value = v || ''; else el.value = v || ''; };

  function setEditable(rowEl, on) {
    const dataInp = rowEl.querySelector('input[data-field="data"]');
    const tipoCtl = rowEl.querySelector('[data-field="tipo"]');
    if (!dataInp || !tipoCtl) return;

    if (on) {
      dataInp.removeAttribute('readonly'); dataInp.classList.remove('rd-readonly');
      if (tipoCtl.tagName === 'SELECT') { tipoCtl.disabled = false; } else { tipoCtl.removeAttribute('readonly'); }
      tipoCtl.classList.remove('rd-readonly');
      rowEl.classList.add('rd-editing'); dataInp.focus();
    } else {
      dataInp.setAttribute('readonly','readonly'); dataInp.classList.add('rd-readonly');
      if (tipoCtl.tagName === 'SELECT') { tipoCtl.disabled = true; } else { tipoCtl.setAttribute('readonly','readonly'); }
      tipoCtl.classList.add('rd-readonly');
      rowEl.classList.remove('rd-editing');
    }
  }
function createRow(root, row) {
  const canEdit = (row.can_edit !== false);
  if (!canEdit) return null;

  const id      = row.id;
  const isoDate = row.data || row.data_refeicao || '';
  const tipoVal = row.tipo || row.refeicao || '';

  const wrapper = document.createElement('div');
  wrapper.className = 'rd-row rd-item';
  wrapper.dataset.origIso  = isoDate;
  wrapper.dataset.origTipo = tipoVal;

  const dataInp = document.createElement('input');
  dataInp.type = 'text';
  dataInp.className = 'rd-input rd-readonly';
  dataInp.value = isoToBR(isoDate);
  dataInp.setAttribute('readonly', 'readonly');
  dataInp.setAttribute('data-field', 'data');
  const tipoCtl = createTipoControl(tipoVal, isoDate);

  dataInp.addEventListener('change', function() {
    var newIso = brToISO(dataInp.value);
    if (newIso && tipoCtl.tagName === 'SELECT') {
      updateTipoOptions(tipoCtl, newIso, getTipoValue(tipoCtl));
    }
  });
  const btnDel = document.createElement('button');
  btnDel.type = 'button';
  btnDel.className = 'rd-icon rd-icon--danger';
  btnDel.title = 'Cancelar';
  btnDel.setAttribute('aria-label', 'Cancelar');
  btnDel.innerHTML = iconTrash();
  const btnEdt = document.createElement('button');
  btnEdt.type = 'button';
  btnEdt.className = 'rd-icon rd-icon--primary';
  btnEdt.title = 'Editar';
  btnEdt.setAttribute('aria-label', 'Editar');
  btnEdt.innerHTML = iconEdit();

  btnDel.addEventListener('click', async () => {
    if (!confirm('Cancelar esta solicitação?')) return;
    try {
      btnDel.disabled = true; btnEdt.disabled = true;
      await apiPatch('/refeicoes/' + id, { action: 'cancelar' });
      wrapper.remove();
      setMsg(root, 'Solicitação cancelada.');
    } catch (e) {
      setMsg(root, e.message || 'Erro ao cancelar.');
      btnDel.disabled = false; btnEdt.disabled = false;
    }
  });

  async function saveEdition() {
    const iso = brToISO(dataInp.value);
    if (!iso) { setMsg(root, 'Data inválida. Use DD/MM/AAAA.'); return; }
    const tipoNew = getTipoValue(tipoCtl);
    if (!tipoNew) { setMsg(root, 'Selecione a refeição.'); return; }

    try {
      btnDel.disabled = true; btnEdt.disabled = true;
      await apiPatch('/refeicoes/' + id, {
        action: 'editar',
        data: iso,
        tipo: tipoNew,
        data_refeicao: iso,
        refeicao: tipoNew
      });
      wrapper.dataset.origIso  = iso;
      wrapper.dataset.origTipo = tipoNew;
      dataInp.value = isoToBR(iso);
      setTipoValue(tipoCtl, tipoNew);

      setEditable(wrapper, false);
      btnEdt.innerHTML = iconEdit(); btnEdt.title = 'Editar';
      btnEdt.classList.remove('rd-icon--success'); btnEdt.classList.add('rd-icon--primary');
      setMsg(root, 'Solicitação atualizada.');
    } catch (e) {
      setMsg(root, e.message || 'Erro ao atualizar.');
    } finally {
      btnDel.disabled = false; btnEdt.disabled = false;
    }
  }

  btnEdt.addEventListener('click', () => {
    const editing = wrapper.classList.contains('rd-editing');
    if (!editing) {
      setEditable(wrapper, true);
      btnEdt.innerHTML = iconCheck(); btnEdt.title = 'Salvar';
      btnEdt.classList.remove('rd-icon--primary'); btnEdt.classList.add('rd-icon--success');
    } else {
      saveEdition();
    }
  });

  function onKey(ev) {
    const editing = wrapper.classList.contains('rd-editing');
    if (!editing) return;
    if (ev.key === 'Enter') {
      ev.preventDefault(); saveEdition();
    } else if (ev.key === 'Escape') {
      ev.preventDefault();
      dataInp.value = isoToBR(wrapper.dataset.origIso || '');
      setTipoValue(tipoCtl, wrapper.dataset.origTipo || '');
      setEditable(wrapper, false);
      btnEdt.innerHTML = iconEdit(); btnEdt.title = 'Editar';
      btnEdt.classList.remove('rd-icon--success'); btnEdt.classList.add('rd-icon--primary');
      setMsg(root, '');
    }
  }
  dataInp.addEventListener('keydown', onKey);
  tipoCtl.addEventListener('keydown', onKey);

  const c1 = document.createElement('div'); c1.className = 'rd-col'; c1.appendChild(dataInp);

  const cCat = document.createElement('div'); cCat.className = 'rd-col rd-col-cat';
  const catCode = row.categoria || '';
  if (catCode) { cCat.textContent = catCode.charAt(0).toUpperCase() + catCode.slice(1); }

  const c3 = document.createElement('div'); c3.className = 'rd-actions'; c3.append(btnDel, btnEdt);

  const c2 = document.createElement('div'); c2.className = 'rd-col'; c2.appendChild(tipoCtl);

  const cLocal = document.createElement('div'); cLocal.className = 'rd-col rd-col-local';
  const locKey = row.local_retirada || '';
  if (locKey) { cLocal.textContent = localLabel ? localLabel(locKey) : locKey; }

  wrapper.append(c1, cCat, c2, cLocal, c3);
  return wrapper;
}
  async function mountEdit(root) {
    const container = root.querySelector('#rd-list-container');
    if (!container) return;

    setMsg(root, ''); container.innerHTML = '';
    if (LOCAL_LABELS === null) { LOCAL_LABELS = await fetchLocalLabels(); }
    if (mealTypesCache === null) { await loadMealTypes(); }

    const data = await apiGet('/refeicoes', { status: 'solicitado' });
    const items = (data.items || data || []).slice()

      .sort(cmpAscByDate);

    if (!items.length) { setMsg(root, 'Você não possui solicitações editáveis.'); return; }

    items.forEach((row) => {
      const el = createRow(root, row);
      if (el) container.appendChild(el);
    });
  }

  async function mountCards(root) {
    const cardsEl = root.querySelector('#rd-cards');
    if (!cardsEl) return;
    setMsg(root, ''); cardsEl.innerHTML = '';

    const data = await apiGet('/refeicoes', { status: 'confirmado' });
    const items = (data.items || data || []).slice()
      .filter(isRetirado)
      .filter(inNext30Days)
      .sort(cmpAscByDate);

    if (!items.length) return;

    items.forEach((row, i) => {
      const card = document.createElement('div');
      card.className = 'rd-card rd-card-' + (((i % 3) + 1));
      const dt = isoToBR(row.data || row.data_refeicao);
      const tp = row.tipo || row.refeicao || '';
      card.innerHTML = '<strong>' + dt + '</strong>: ' + tp;
      cardsEl.appendChild(card);
    });
  }

  function bindRefresh(root) {
    const btn = root.querySelector('.rd-refresh');
    if (!btn) return;
    btn.addEventListener('click', () => {
      btn.disabled = true;
      (async () => { await mountEdit(root); btn.disabled = false; })();
    });
  }

  document.querySelectorAll('.rd.rd-list').forEach((root) => {
    const view = (root.getAttribute('data-view') || 'edit').toLowerCase();
    if (view === 'edit' && root.querySelector('#rd-list-container')) {
      bindRefresh(root);
      mountEdit(root).catch((e) => setMsg(root, (e && e.message) || 'Erro ao carregar.'));
    } else if (view === 'cards' && root.querySelector('#rd-cards')) {
      mountCards(root).catch((e) => setMsg(root, (e && e.message) || 'Erro ao carregar.'));
    }
  });
})();
