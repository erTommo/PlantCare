Pages.Dashboard = (() => {

  function render() {
    _renderStats();
    const lp = State.getLivePlant();

    const liveCard = document.querySelector('#page-dashboard .g2');
    if (liveCard) liveCard.style.display = lp ? '' : 'none';

    if (lp) {
      document.getElementById('liveName').textContent    = lp.Soprannome      || lp.nick    || '—';
      document.getElementById('liveSpecies').textContent = lp.Nome_Scientifico || lp.species || '—';
      _renderLiveSensors();
      _loadAndRenderDashChart();

      const waterBtn = document.getElementById('waterBtn');
      if (waterBtn) waterBtn.style.display = '';
    }

    _renderAlarms();
    _renderPlants();
  }

  function _renderStats() {
    const plants = State.getPlants();
    const alarms = State.getAlarms();
    const ok     = plants.filter(p => (p.stato || p.status) === 'ok').length;
    const bad    = plants.filter(p => (p.stato || p.status) !== 'ok').length;
    const unread = alarms.filter(a => !a.Letto_Da_Utente && !a.read).length;
    document.getElementById('dashStats').innerHTML = [
      _stat('🌿', plants.length, 'Piante totali'),
      _stat('✅', ok,            'Stato ottimale'),
      _stat('⚠️', bad,           'Richiedono cura'),
      _stat('🔔', unread,        'Allarmi non letti'),
    ].join('');
  }

  function _stat(icon, val, lbl) {
    return `<div class="stat">
      <div><div class="stat-val">${val}</div><div class="stat-lbl">${lbl}</div></div>
      <div class="stat-icon">${icon}</div>
    </div>`;
  }

  function _renderLiveSensors() {
    const p  = State.getLivePlant();
    const el = document.getElementById('liveSensors');
    if (!p || !el) return;

    const r = p.ultime_rilevazioni || {};
    const defs = [
      { key: 'Umidita_Suolo',    lbl: 'Umidità Suolo', unit: '%',  min: p.Umidita_Suolo_Min ?? p.t?.umidita_suolo?.[0] ?? 30,  max: p.Umidita_Suolo_Max ?? p.t?.umidita_suolo?.[1] ?? 70  },
      { key: 'Temperatura_Aria', lbl: 'Temperatura',    unit: '°C', min: p.Temp_Ideale_Min    ?? p.t?.temperatura?.[0]   ?? 15,  max: p.Temp_Ideale_Max    ?? p.t?.temperatura?.[1]   ?? 30  },
      { key: 'Umidita_Aria',     lbl: 'Umidità Aria',  unit: '%',  min: 30, max: 70 },
      { key: 'Luminosita',       lbl: 'Luminosità',     unit: 'lx', min: p.Luce_Ideale_Min    ?? p.t?.luminosita?.[0]    ?? 500, max: p.Luce_Ideale_Max    ?? p.t?.luminosita?.[1]    ?? 5000 },
    ];

    el.innerHTML = defs.map(d => {
      const v = r[d.key]?.valore ?? p.s?.[d.key.toLowerCase().replace('_aria', '')] ?? null;
      if (v === null) return `<div class="sensor-mini"><div class="sensor-mini-lbl">${d.lbl}</div><div style="color:var(--text-muted);font-size:11px">Nessun dato</div></div>`;
      const pct = Math.min(100, (v / (d.max * 1.3)) * 100);
      const cls = v < d.min ? 'alert' : v > d.max ? 'warn' : '';
      return `<div class="sensor-mini">
        <div class="sensor-mini-val">${v}<span class="sensor-mini-unit">${d.unit}</span></div>
        <div class="sensor-mini-lbl">${d.lbl}</div>
        <div class="sbar-wrap"><div class="sbar ${cls}" style="width:${pct}%"></div></div>
        <div class="sbar-range"><span>${d.min}</span><span>${d.max}</span></div>
      </div>`;
    }).join('');
  }

  async function _loadAndRenderDashChart() {
    const lp = State.getLivePlant();
    if (!lp) return;
    const id = lp.ID_Esemplare ?? lp.id;

    const tipoMap = {
      'umidita':          'Umidita_Suolo',
      'temp':             'Temperatura_Aria',
      'luce':             'Luminosita',
      'Umidita_Suolo':    'Umidita_Suolo',
      'Temperatura_Aria': 'Temperatura_Aria',
      'Luminosita':       'Luminosita',
    };
    const tipo = tipoMap[State.getDashChart()] ?? 'Umidita_Suolo';

    try {
      const json = await apiFetch(`api/v1/rilevazioni.php?id_esemplare=${id}&tipo=${tipo}&range=24h`);
      if (json.status === 'ok' && json.data.length) {
        const valori = json.data.map(r => parseFloat(r.valore));
        Charts.renderSimple('dashChart', valori, { w: 480, h: 160 }, Utils.chartColor(State.getDashChart()));
      }
    } catch (e) { console.warn('Chart load error:', e); }
  }

  function _renderAlarms() {
    const el = document.getElementById('dashAlarms');
    if (!el) return;
    const shown = State.getAlarms().slice(0, 4);
    el.innerHTML = shown.length
      ? shown.map(a => UI.alarmRowHtml(_normalizeAlarm(a))).join('')
      : '<div style="color:var(--text-muted);font-size:12px;padding:10px">Nessun allarme 🎉</div>';
  }

  function _renderPlants() {
    const el = document.getElementById('dashPlants');
    if (el) el.innerHTML = State.getPlants().map(p => UI.plantCardHtml(_normalizePlant(p))).join('');
  }

  async function setChartType(type, btn) {
    State.setDashChart(type);
    document.querySelectorAll('#page-dashboard .tab').forEach(t => t.classList.remove('active'));
    btn.classList.add('active');
    await _loadAndRenderDashChart();
  }

  async function waterMain() {
    const lp  = State.getLivePlant();
    const btn = document.getElementById('waterBtn');
    btn.disabled = true; btn.textContent = '💧 Annaffiatura in corso...';
    UI.toast('💧', 'Annaffiatura avviata per ' + (lp?.Soprannome || lp?.nick || 'pianta live') + '!');
    setTimeout(() => {
      btn.textContent = '✓ Completata'; btn.style.color = 'var(--green)';
      setTimeout(() => { btn.disabled = false; btn.innerHTML = '💧 Annaffia ora'; btn.style = ''; }, 2500);
    }, 2000);
  }

  async function waterPlant(name) {
    UI.toast('💧', `Annaffiatura avviata per ${name}!`);
    UI.closeModalDirect();
  }

  function setLivePlant(id) {
    const pid = parseInt(id);
    State.getPlants().forEach(p => p.live = (parseInt(p.ID_Esemplare ?? p.id) === pid));
    const lp = State.getLivePlant();
    document.getElementById('liveName').textContent    = lp?.Soprannome || lp?.nick || '—';
    document.getElementById('liveSpecies').textContent = lp?.Nome_Scientifico || lp?.species || '—';
    State.setChartPlant(id);
    UI.renderSidebar();
    _renderLiveSensors();
    _renderPlants();
    UI.closeModalDirect();
    UI.toast('📡', `"${lp?.Soprannome || lp?.nick}" impostata come pianta live!`);
  }

  async function tick() {
    const lp = State.getLivePlant();
    if (!lp) return;
    const id = lp.ID_Esemplare ?? lp.id;
    try {
      const json = await apiFetch(`api/v1/rilevazioni.php?id_esemplare=${id}&raw=1&limit=10`);
      if (json.status !== 'ok') return;
      json.data.forEach(r => {
        if (!lp.ultime_rilevazioni) lp.ultime_rilevazioni = {};
        if (!lp.ultime_rilevazioni[r.Tipo_Misurazione]) {
          lp.ultime_rilevazioni[r.Tipo_Misurazione] = {
            valore:    parseFloat(r.Valore),
            timestamp: r.Data_Ora_Rilevazione,
          };
        }
      });
      if (document.getElementById('liveSensors')) _renderLiveSensors();
    } catch (e) { }
  }

  return { render, setChartType, waterMain, waterPlant, setLivePlant, tick };
})();
