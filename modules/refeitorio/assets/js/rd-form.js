(function () {
  'use strict';
  if (!window.RD_FORM) return;

  const cfg = window.RD_FORM;
  const $ = (root, sel) => root.querySelector(sel);
  const text = (el, t) => { if (el) el.textContent = t ?? ''; };
  const two = (n) => String(n).padStart(2, '0');

  let mealTypesCache = null;

  function parseISODate(iso) {
    if (!iso || typeof iso !== 'string') return null;
    const m = iso.match(/^(\d{4})-(\d{2})-(\d{2})$/);
    if (!m) return null;
    const y = +m[1], mo = +m[2], d = +m[3]; 
    return new Date(y, mo - 1, d); 
  }
  function toISO(d) { return `${d.getFullYear()}-${two(d.getMonth() + 1)}-${two(d.getDate())}`; }
  function todayLocal() {
    const n = new Date();
    return new Date(n.getFullYear(), n.getMonth(), n.getDate());
  }
  function nowHHMM() {
    const n = new Date();
    return `${two(n.getHours())}:${two(n.getMinutes())}`;
  }
  function cmpHHMM(a, b) {
    const [ah, am] = a.split(':').map(Number);
    const [bh, bm] = b.split(':').map(Number);
    return (ah * 60 + am) - (bh * 60 + bm);
  }
  function sanitizeMatricula(v) {
    let s = (v || '').toString().trim();
    if (s.includes('@')) s = s.split('@')[0];
    return s.replace(/\s+/g, '');
  }

  async function apiGet(path, params = {}) {
    const usp = new URLSearchParams(params);
    const res = await fetch(`${cfg.apiBase}${path}?${usp.toString()}`, {
      headers: { 'X-WP-Nonce': cfg.nonce }
    });
    const j = await res.json().catch(() => ({}));
    if (!res.ok) throw new Error(j?.message || 'Erro na consulta.');
    return j;
  }

  async function apiPost(path, body) {
    const res = await fetch(`${cfg.apiBase}${path}`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-WP-Nonce': cfg.nonce
      },
      body: JSON.stringify(body || {})
    });
    const j = await res.json().catch(() => ({}));
    if (!res.ok) throw new Error(j?.message || 'Erro ao enviar solicitação.');
    return j;
  }

  function isCutoffPassed(targetDate, cutoffHHMM, sameDay = false) {
    if (!cutoffHHMM) return false;
    const today = todayLocal();
    const nowCut = nowHHMM();
    const limitDate = new Date(targetDate);
    if (!sameDay) {
      limitDate.setDate(limitDate.getDate() - 1);
    }
    if (today.getTime() > limitDate.getTime()) return true;
    if (today.getTime() === limitDate.getTime() && cmpHHMM(nowCut, cutoffHHMM) > 0) return true;
    return false;
  }

  function buildAllowedFromWindow(baseISO, windowDays, cutoffHHMM, dateStart, dateEnd, sameDay = false) {
    const base = parseISODate(baseISO) || todayLocal();
    const today = todayLocal();
    const list = [];

    if (dateStart && dateEnd) {
      const start = parseISODate(dateStart);
      const end = parseISODate(dateEnd);

      if (start && end) {
        const effectiveStart = start < today ? today : start;

        let current = new Date(effectiveStart);
        while (current <= end) {
          if (cutoffHHMM && isCutoffPassed(current, cutoffHHMM, sameDay)) {
            current.setDate(current.getDate() + 1);
            continue;
          }
          if (current >= today) {
            list.push(toISO(current));
          }
          current.setDate(current.getDate() + 1);
        }
        return list;
      }
    }
    for (let i = 0; i < Math.max(1, Number(windowDays || 0)); i++) {
      const d = new Date(base.getFullYear(), base.getMonth(), base.getDate() + i);
      if (cutoffHHMM && isCutoffPassed(d, cutoffHHMM, sameDay)) continue;
      list.push(toISO(d));
    }
    return list;
  }

  function buildAllowedForCategory(categoryCode, categories) {
    const cat = (categories || []).find(c => c.code === categoryCode);
    const cutoff = cat?.cutoff || cfg.cutoff || '';
    const sameDay = cat?.same_day || false;
    return buildAllowedFromWindow(cfg.today, cfg.windowDays, cutoff, cfg.dateStart, cfg.dateEnd, sameDay);
  }

  function populateCategories(root, prefill) {
    const sel = $(root, '#rd-categoria');
    if (!sel) return [];

    let list = Array.isArray(prefill?.meal_categories) ? prefill.meal_categories : [];
    if (!list.length && Array.isArray(cfg.mealCategories)) {
      list = cfg.mealCategories;
    }

    sel.innerHTML = '<option value="">Selecione</option>' +
      list.map(c => `<option value="${c.code}" data-cutoff="${c.cutoff || ''}" data-same-day="${c.same_day ? '1' : '0'}">${c.label}</option>`).join('');

    return list;
  }

  function normalizeAllowedDates(serverData) {
    const arrays = [
      serverData?.dias_liberados,
      serverData?.liberados,
      serverData?.availableDates
    ].filter(Array.isArray);

    if (arrays.length) {
      const flat = arrays.flat().filter(Boolean).map(String);
      const valid = flat.filter(s => /^\d{4}-\d{2}-\d{2}$/.test(s));
      return Array.from(new Set(valid)).sort();
    }
    return buildAllowedFromWindow(cfg.today, cfg.windowDays, cfg.cutoff, cfg.dateStart, cfg.dateEnd);
  }

  function populateYearMonthDay(root, allowedISOList) {
    const selDia = $(root, '#rd-dia');
    const selMes = $(root, '#rd-mes');
    const selAno = $(root, '#rd-ano');
    if (!selDia || !selMes || !selAno) return { getSelectedISO: () => null };

    const dates = allowedISOList.slice().sort();
    if (!dates.length) {
      selAno.innerHTML = ''; selMes.innerHTML = ''; selDia.innerHTML = '';
      return { getSelectedISO: () => null };
    }

    const map = new Map();
    dates.forEach(iso => {
      const [y, m, d] = iso.split('-').map(Number);
      if (!map.has(y)) map.set(y, new Map());
      const mMap = map.get(y);
      if (!mMap.has(m)) mMap.set(m, []);
      mMap.get(m).push(d);
    });

    function fillYears(selectedYear) {
      const years = Array.from(map.keys()).sort((a,b)=>a-b);
      selAno.innerHTML = years.map(y => `<option value="${y}">${y}</option>`).join('');
      selAno.value = selectedYear && years.includes(+selectedYear) ? String(selectedYear) : String(years[0]);
    }
    function fillMonths(y, selectedMonth) {
      const monthsMap = map.get(+y) || new Map();
      const months = Array.from(monthsMap.keys()).sort((a,b)=>a-b);
      selMes.innerHTML = months.map(m => `<option value="${m}">${two(m)}</option>`).join('');
      selMes.value = selectedMonth && months.includes(+selectedMonth) ? String(selectedMonth) : String(months[0]);
    }
    function fillDays(y, m, selectedDay) {
      const days = (map.get(+y)?.get(+m) || []).slice().sort((a,b)=>a-b);
      selDia.innerHTML = days.map(d => `<option value="${d}">${two(d)}</option>`).join('');
      selDia.value = selectedDay && days.includes(+selectedDay) ? String(selectedDay) : String(days[0]);
    }

    const todayISO = toISO(todayLocal());
    const defaultISO = dates.find(d => d >= todayISO) || dates[0];
    const [defY, defM, defD] = defaultISO.split('-').map(Number);

    fillYears(defY);
    fillMonths(defY, defM);
    fillDays(defY, defM, defD);

    selAno.addEventListener('change', () => {
      fillMonths(selAno.value, null);
      fillDays(selAno.value, selMes.value, null);
    });
    selMes.addEventListener('change', () => { fillDays(selAno.value, selMes.value, null); });

    function getSelectedISO() {
      const y = Number(selAno.value);
      const m = Number(selMes.value);
      const d = Number(selDia.value);
      if (!y || !m || !d) return null;
      const iso = `${y}-${two(m)}-${two(d)}`;
      return allowedISOList.includes(iso) ? iso : null;
    }

    return { getSelectedISO };
  }

  async function loadMealTypes() {
    if (mealTypesCache !== null) return mealTypesCache;

    try {
      const r = await apiGet('/meal-types');
      if (Array.isArray(r?.items)) {
        mealTypesCache = r.items;
        return mealTypesCache;
      }
    } catch (_) {}

    mealTypesCache = [];
    return mealTypesCache;
  }

  function filterMealTypesByDate(isoDate) {
    if (!mealTypesCache || !isoDate) return [];

    const date = parseISODate(isoDate);
    if (!date) return mealTypesCache;

    const dayOfWeek = date.getDay();

    return mealTypesCache.filter(item => {
      if (item && typeof item === 'object' && Array.isArray(item.days)) {
        return item.days.includes(dayOfWeek);
      }
      return true;
    });
  }

  function updateMealTypesSelect(root, isoDate) {
    const sel = $(root, '#rd-tipo');
    if (!sel) return;

    const filtered = filterMealTypesByDate(isoDate);
    const currentValue = sel.value;

    sel.innerHTML = '<option value="">Selecione</option>' +
      filtered.map(item => {
        const code = item?.code || item;
        const label = item?.label || item;
        return `<option value="${code}">${label}</option>`;
      }).join('');

    if (currentValue && filtered.some(item => (item?.code || item) === currentValue)) {
      sel.value = currentValue;
    }
  }

  async function populateMeals(root, getSelectedISO) {
    await loadMealTypes();
    const iso = typeof getSelectedISO === 'function' ? getSelectedISO() : null;
    updateMealTypesSelect(root, iso);
  }

  function normalizeLocalOptions(src) {
    if (!src) return [];
    if (typeof src === 'object' && !Array.isArray(src)) {
      return Object.keys(src).map(k => ({ value: String(k), label: String(src[k]) }));
    }
    if (Array.isArray(src)) {
      return src.map(item => {
        if (item && typeof item === 'object') {
          const v = item.value ?? item.id ?? item.key ?? item.codigo ?? item.code ?? item.slug ?? item.sigla;
          const l = item.label ?? item.name ?? item.nome ?? item.descricao ?? item.desc ?? item.title ?? item.titulo ?? v;
          return { value: String(v ?? ''), label: String(l ?? '') };
        }
        return { value: String(item ?? ''), label: String(item ?? '') };
      }).filter(o => o.value);
    }
    return [];
  }

  function populateLocalFromPrefill(root, prefill) {
    const sel = root.querySelector('#rd-local-retirada');
    if (!sel) return;

    while (sel.firstChild) sel.removeChild(sel.firstChild);
    const opt0 = document.createElement('option');
    opt0.value = ''; opt0.textContent = 'Selecione';
    sel.appendChild(opt0);

    const options = normalizeLocalOptions(prefill?.local_retirada_options);
    options.forEach(({ value, label }) => {
      const o = document.createElement('option');
      o.value = value;
      o.textContent = String(label ?? value);
      sel.appendChild(o);
    });

    const def = String(prefill?.local_retirada ?? '');
    if (def) {
      sel.value = def;
      if (sel.value !== def) sel.value = '';
    }
  }
  async function mountForm(root) {
    const nomeEl = $(root, '#rd-form-nome');
    const nomeInput = $(root, '#rd-form-nome-input');
    const matEl  = $(root, '#rd-form-matricula');
    const msgEl  = $(root, '#rd-msg');
    const btn    = $(root, '#rd-enviar');
    const catEl  = $(root, '#rd-categoria');

    text(msgEl, '');

    let prefill = {};
    try { prefill = await apiGet('/refeicao/form-data'); } catch (_) {}

    const nome = (prefill?.nome_completo || '').toString().trim();
    const matricula = sanitizeMatricula(prefill?.matricula || '');
    if (nomeEl) text(nomeEl, nome || '—');
    if (nomeInput) {
      if (!nome) {
        nomeInput.style.display = '';
        nomeInput.placeholder = 'Informe seu nome completo';
        nomeInput.removeAttribute('disabled');
        try { nomeInput.focus(); } catch(_) {}
        if (nomeEl) nomeEl.style.display = 'none';
      } else {
        nomeInput.style.display = 'none';
        if (nomeEl) nomeEl.style.display = '';
      }
    }
    if (matEl)  text(matEl, matricula || '—');

    const categories = populateCategories(root, prefill);

    let currentAllowed = [];
    let getSelectedISO = () => null;

    function updateDatesForCategory(categoryCode) {
      if (categoryCode && categories.length) {
        currentAllowed = buildAllowedForCategory(categoryCode, categories);
      } else {
        currentAllowed = normalizeAllowedDates(prefill);
        if (!currentAllowed.length) {
          currentAllowed = buildAllowedFromWindow(cfg.today, cfg.windowDays, cfg.cutoff, cfg.dateStart, cfg.dateEnd);
        }
      }
      const result = populateYearMonthDay(root, currentAllowed);
      getSelectedISO = result.getSelectedISO;

      if (!currentAllowed.length && categoryCode) {
        text(msgEl, 'Horário limite para esta categoria já passou.');
      } else {
        text(msgEl, '');
      }

      updateMealTypesSelect(root, getSelectedISO());
    }

    if (catEl) {
      catEl.addEventListener('change', () => {
        updateDatesForCategory(catEl.value);
      });
    }

    const selDia = $(root, '#rd-dia');
    const selMes = $(root, '#rd-mes');
    const selAno = $(root, '#rd-ano');

    const onDateChange = () => updateMealTypesSelect(root, getSelectedISO());
    if (selDia) selDia.addEventListener('change', onDateChange);
    if (selMes) selMes.addEventListener('change', onDateChange);
    if (selAno) selAno.addEventListener('change', onDateChange);

    updateDatesForCategory(catEl?.value || '');

    await populateMeals(root, getSelectedISO);
    populateLocalFromPrefill(root, prefill);

    if (!currentAllowed.length && !catEl) {
      text(msgEl, 'Nenhuma data liberada no momento.');
      if (btn) btn.disabled = true;
      return;
    }

    const localEl = root.querySelector('#rd-local-retirada');
    if (localEl && localEl.required && (!localEl.options || localEl.options.length <= 1)) {
      text(msgEl, 'Nenhum local de retirada disponível. Tente novamente mais tarde.');
      if (btn) btn.disabled = true;
      return;
    }
    if (btn) {
      btn.addEventListener('click', async () => {
        text(msgEl, '');
        btn.disabled = true;

        try {
          const iso = getSelectedISO();
          const tipo = ($(root, '#rd-tipo')?.value || '').trim();
          const categoria = catEl ? (catEl.value || '').trim() : '';
          let nomeSend = '';
          if (nomeInput && nomeInput.style.display !== 'none') {
            nomeSend = (nomeInput.value || '').trim();
          } else {
            nomeSend = nome || '';
          }
          if (!nomeSend || nomeSend === '—' || nomeSend === '-') {
            nomeSend = '';
          }

          const matSend  = matricula || sanitizeMatricula($(root, '#rd-form-matricula')?.textContent || '');

          const localVal = localEl ? String(localEl.value || '') : '';
          const localIsRequired = !!(localEl && (localEl.required || localEl.hasAttribute('required')));

          if (!categoria && catEl) throw new Error('Selecione a refeição (Almoço, Jantar ou Ceia).');
          if (!iso) throw new Error('Selecione uma data válida.');
          if (!tipo) throw new Error('Selecione o tipo de cardápio.');
          if (!matSend) throw new Error('Matrícula não encontrada.');
          if (!nomeSend) throw new Error('Nome não encontrado.');
          if (localIsRequired && !localVal) throw new Error('Selecione o restaurante.');

          const payload = {
            nome_completo: nomeSend,
            matricula: matSend,
            data_refeicao: iso,
            refeicao: tipo,
            categoria: categoria,
            ...(localVal ? { local_retirada: localVal } : {})
          };

          await apiPost('/refeicoes', payload);

          text(msgEl, 'Solicitação enviada com sucesso.');
        } catch (err) {
          text(msgEl, err?.message || 'Erro ao enviar.');
        } finally {
          btn.disabled = false;
        }
      });
    }
  }
  document.querySelectorAll('.rd.rd-form').forEach((root) => {
    mountForm(root).catch((e) => {
      const msg = $('#rd-msg') || document.createElement('div');
      msg.id = 'rd-msg'; msg.className = 'rd-msg';
      msg.textContent = e.message || 'Erro ao inicializar o formulário.';
      root.appendChild(msg);
    });
  });
})();
