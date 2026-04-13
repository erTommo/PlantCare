function renderDashboard() {
  var ok  = plants.filter(function(p) { return (p.stato || p.status) === 'ok'; }).length;
  var bad = plants.length - ok;
  var unr = unreadCount();
  document.getElementById('dashStats').innerHTML =
    statHtml('🌿', plants.length, 'Piante totali') +
    statHtml('✅', ok,            'Stato ottimale') +
    statHtml('⚠️', bad,           'Richiedono cura') +
    statHtml('🔔', unr,           'Allarmi non letti');

  var lp       = getLivePlant();
  var liveWrap = document.getElementById('liveSectionWrap');
  if (liveWrap) liveWrap.style.display = lp ? '' : 'none';

  if (lp) {
    document.getElementById('liveName').textContent    = lp.Soprannome      || '—';
    document.getElementById('liveSpecies').textContent = lp.Nome_Scientifico || '—';
    renderLiveSensors();
    loadDashChart();
  }

  var dashAl = document.getElementById('dashAlarms');
  if (dashAl) {
    var shown = alarms.slice(0, 4);
    dashAl.innerHTML = shown.length
      ? shown.map(alarmRowHtml).join('')
      : '<div style="color:var(--text-muted);font-size:12px;padding:10px">Nessun allarme 🎉</div>';
  }

  var dashPl = document.getElementById('dashPlants');
  if (dashPl) dashPl.innerHTML = plants.map(plantCardHtml).join('');
}

function statHtml(icon, val, lbl) {
  return '<div class="stat"><div><div class="stat-val">' + val + '</div><div class="stat-lbl">' + lbl + '</div></div><div class="stat-icon">' + icon + '</div></div>';
}

function renderLiveSensors() {
  var p  = getLivePlant();
  var el = document.getElementById('liveSensors');
  if (!p || !el) return;
  var r = p.ultime_rilevazioni || {};
  var defs = [
    { lbl: 'Umidità Suolo', unit: '%',  val: r.Umidita_Suolo?.valore    ?? null, min: p.Umidita_Suolo_Min ?? 30,  max: p.Umidita_Suolo_Max ?? 70   },
    { lbl: 'Temperatura',   unit: '°C', val: r.Temperatura_Aria?.valore  ?? null, min: p.Temp_Ideale_Min   ?? 15,  max: p.Temp_Ideale_Max   ?? 30   },
    { lbl: 'Umid. Aria',    unit: '%',  val: r.Umidita_Aria?.valore      ?? null, min: 30, max: 70 },
    { lbl: 'Luminosità',    unit: 'lx', val: r.Luminosita?.valore        ?? null, min: p.Luce_Ideale_Min   ?? 500, max: p.Luce_Ideale_Max   ?? 5000 },
  ];
  el.innerHTML = defs.map(function(d) {
    if (d.val === null) return '<div class="sensor-mini"><div class="sensor-mini-lbl">' + d.lbl + '</div><div style="font-size:11px;color:var(--text-muted)">Nessun dato</div></div>';
    var pct = Math.min(100, (d.val / (d.max * 1.3)) * 100);
    var cls = d.val < d.min ? 'alert' : d.val > d.max ? 'warn' : '';
    return '<div class="sensor-mini">'
      + '<div class="sensor-mini-val">' + d.val + '<span class="sensor-mini-unit">' + d.unit + '</span></div>'
      + '<div class="sensor-mini-lbl">' + d.lbl + '</div>'
      + '<div class="sbar-wrap"><div class="sbar ' + cls + '" style="width:' + pct + '%"></div></div>'
      + '<div class="sbar-range"><span>' + d.min + '</span><span>' + d.max + '</span></div>'
      + '</div>';
  }).join('');
}

async function loadDashChart() {
  var lp = getLivePlant();
  if (!lp) return;
  var id = lp.ID_Esemplare ?? lp.id;
  var tipoMap  = { umidita: 'Umidita_Suolo', temp: 'Temperatura_Aria', luce: 'Luminosita' };
  var colorMap = { umidita: '#3a8c2f', temp: '#c07a10', luce: '#2980b9' };
  var tipo = tipoMap[dashChart]  || 'Umidita_Suolo';
  var col  = colorMap[dashChart] || '#3a8c2f';
  try {
    var json = await apiFetch('api/v1/rilevazioni.php?id_esemplare=' + id + '&tipo=' + tipo + '&range=24h');
    if (json.status === 'ok' && json.data.length) {
      renderChart('dashChart', json.data.map(function(r) { return parseFloat(r.valore); }), { w: 480, h: 160 }, col);
    }
  } catch(e) {}
}

async function setChartType(type, btn) {
  dashChart = type;
  document.querySelectorAll('#page-dashboard .tab').forEach(function(t) { t.classList.remove('active'); });
  btn.classList.add('active');
  await loadDashChart();
}

async function waterMain() {
  var lp  = getLivePlant();
  var btn = document.getElementById('waterBtn');
  btn.disabled = true; btn.textContent = '💧 Annaffiatura in corso...';
  toast('💧', 'Annaffiatura avviata per ' + (lp?.Soprannome || 'pianta live') + '!');
  setTimeout(function() {
    btn.textContent = '✓ Completata'; btn.style.color = 'var(--green)';
    setTimeout(function() { btn.disabled = false; btn.innerHTML = '💧 Annaffia ora'; btn.style.color = ''; }, 2500);
  }, 2000);
}

function waterPlant(name) {
  toast('💧', 'Annaffiatura avviata per ' + name + '!');
  closeModalDirect();
}

function setLivePlant(id) {
  plants.forEach(function(p) { p.live = (parseInt(p.ID_Esemplare ?? p.id) === parseInt(id)); });
  var lp = getLivePlant();
  document.getElementById('liveName').textContent    = lp?.Soprannome      || '—';
  document.getElementById('liveSpecies').textContent = lp?.Nome_Scientifico || '—';
  chartPlant = id;
  renderSidebar();
  renderLiveSensors();
  closeModalDirect();
  toast('📡', '"' + (lp?.Soprannome || '') + '" impostata come pianta live!');
}

async function tickLive() {
  var lp = getLivePlant();
  if (!lp) return;
  var id = lp.ID_Esemplare ?? lp.id;
  try {
    var json = await apiFetch('api/v1/rilevazioni.php?id_esemplare=' + id + '&raw=1&limit=10');
    if (json.status !== 'ok') return;
    if (!lp.ultime_rilevazioni) lp.ultime_rilevazioni = {};
    var seen = {};
    json.data.forEach(function(r) {
      if (!seen[r.Tipo_Misurazione]) {
        seen[r.Tipo_Misurazione] = true;
        lp.ultime_rilevazioni[r.Tipo_Misurazione] = { valore: parseFloat(r.Valore), timestamp: r.Data_Ora_Rilevazione };
      }
    });
    if (document.getElementById('liveSensors')) renderLiveSensors();
  } catch(e) {}
}

function renderPiante() {
  var filtered = filterStatus === 'all'
    ? plants
    : plants.filter(function(p) { return (p.stato || p.status) === filterStatus; });
  document.getElementById('pianteSubTitle').textContent = plants.length + ' esemplari registrati';
  var grid  = document.getElementById('plantsPage');
  var empty = document.getElementById('emptyPlants');
  if (!filtered.length) {
    grid.innerHTML = '';
    empty.style.display = 'block';
  } else {
    empty.style.display = 'none';
    grid.innerHTML = filtered.map(plantCardHtml).join('');
  }
}

function setFilterPiante(status, btn) {
  filterStatus = status;
  document.querySelectorAll('#page-piante .tab').forEach(function(t) { t.classList.remove('active'); });
  btn.classList.add('active');
  renderPiante();
}

function removePlant(id) {
  var pid = parseInt(id);
  var p   = plants.find(function(x) { return parseInt(x.ID_Esemplare ?? x.id) === pid; });
  if (!p) return;
  var nome = p.Soprannome || 'questa pianta';
  openConfirm('🌿', 'Rimuovere "' + nome + '"?', "L'esemplare e le sue rilevazioni verranno eliminati.", async function() {
    var json = await apiFetch('api/v1/esemplari.php?id=' + id, { method: 'DELETE' });
    if (json.status !== 'ok') { toast('⚠', json.message, 'err'); return; }
    plants = plants.filter(function(x) { return parseInt(x.ID_Esemplare ?? x.id) !== pid; });
    alarms = alarms.filter(function(a) { return parseInt(a.ID_Esemplare ?? a.plantId) !== pid; });
    renderSidebar();
    updateAlarmBadge();
    closeModalDirect();
    toast('🗑', '"' + nome + '" rimossa');
    var cp = document.querySelector('.page.active')?.id?.replace('page-', '');
    if (cp) goTo(cp);
  });
}

async function renderAllarmi() {
  try {
    var json = await apiFetch('api/v1/allarmi.php');
    if (json.status === 'ok') alarms = json.data || [];
  } catch(e) {}

  var all    = alarms.map(normalizeAlarm);
  var unread = all.filter(function(a) { return !a.read; });
  var read   = all.filter(function(a) { return  a.read; });

  document.getElementById('alarmSubTitle').textContent = unread.length + ' non letti · da Eventi_Allarme';

  document.getElementById('alarmsUnread').innerHTML = unread.length
    ? unread.map(alarmRowHtml).join('')
    : '<div style="color:var(--text-muted);font-size:12px;text-align:center;padding:16px">Nessun allarme non letto 🎉</div>';

  document.getElementById('alarmsRead').innerHTML = read.length
    ? read.map(alarmRowHtml).join('')
    : '<div style="color:var(--text-muted);font-size:12px;padding:10px">Nessuno.</div>';

  try {
    var sj    = await apiFetch('api/v1/allarmi.php?stats=1');
    var stats = sj.status === 'ok' ? sj.data : [];
    var types = ['Troppo_Secco', 'Troppo_Umido', 'Troppo_Caldo', 'Troppo_Freddo', 'Poca_Luce'];
    document.getElementById('alarmStats').innerHTML = types.map(function(tp) {
      var found = stats.find(function(s) { return s.Tipo_Allarme === tp; });
      var cnt   = found ? parseInt(found.totale) : 0;
      return '<div style="display:flex;align-items:center;justify-content:space-between;padding:8px 0;border-bottom:1px solid var(--border);font-size:12px">'
        + '<span style="color:var(--text-dim)">' + tp.replace(/_/g, ' ') + '</span>'
        + '<span style="color:' + (cnt > 0 ? 'var(--amber)' : 'var(--text-muted)') + '">' + cnt + '</span>'
        + '</div>';
    }).join('');
  } catch(e) {}

  document.getElementById('threshCard').innerHTML =
    '<table class="tbl" style="font-size:11px"><thead><tr><th>Pianta</th><th>Temp °C</th><th>Umid %</th></tr></thead><tbody>'
    + plants.map(function(p) {
        return '<tr><td>' + (p.Soprannome || '—') + '</td>'
          + '<td>' + (p.Temp_Ideale_Min ?? '—') + '–' + (p.Temp_Ideale_Max ?? '—') + '</td>'
          + '<td>' + (p.Umidita_Suolo_Min ?? '—') + '–' + (p.Umidita_Suolo_Max ?? '—') + '</td></tr>';
      }).join('')
    + '</tbody></table>';

  updateAlarmBadge();
}

async function markAllRead() {
  var json = await apiFetch('api/v1/allarmi.php?all=1', { method: 'PUT' });
  if (json.status === 'ok') {
    alarms.forEach(function(a) { a.Letto_Da_Utente = 1; a.read = true; });
    renderAllarmi();
    toast('✓', 'Tutti gli allarmi segnati come letti');
    updateAlarmBadge();
  }
}

function confirmClearAll() {
  openConfirm('🗑', 'Cancella tutti gli allarmi?', 'Tutti i record di Eventi_Allarme verranno eliminati.', async function() {
    var json = await apiFetch('api/v1/allarmi.php?all=1', { method: 'DELETE' });
    if (json.status === 'ok') {
      alarms = [];
      renderAllarmi();
      updateAlarmBadge();
      toast('🗑', 'Tutti gli allarmi eliminati');
    }
  });
}

function renderGrafici() {
  if (!plants.length) return;
  if (!chartPlant) chartPlant = plants[0].ID_Esemplare ?? plants[0].id;

  document.getElementById('chartPills').innerHTML = plants.map(function(p) {
    var pid = p.ID_Esemplare ?? p.id;
    return '<div class="plant-pill ' + (pid === chartPlant ? 'active' : '') + '" onclick="selectChartPlant(' + pid + ')">'
      + '🌿 ' + (p.Soprannome || '—') + '</div>';
  }).join('');

  drawCharts();
  loadRilevTable();
}

function selectChartPlant(id) {
  chartPlant = parseInt(id);
  renderGrafici();
}

function setTimeRange(range, btn) {
  chartsRange = range;
  document.querySelectorAll('#page-grafici .tab').forEach(function(t) { t.classList.remove('active'); });
  btn.classList.add('active');
  drawCharts();
  loadRilevTable();
}

async function drawCharts() {
  if (!chartPlant) return;
  var plant   = plants.find(function(p) { return parseInt(p.ID_Esemplare ?? p.id) === chartPlant; });
  var results = await Promise.allSettled([
    apiFetch('api/v1/rilevazioni.php?id_esemplare=' + chartPlant + '&tipo=Umidita_Suolo&range=' + chartsRange),
    apiFetch('api/v1/rilevazioni.php?id_esemplare=' + chartPlant + '&tipo=Temperatura_Aria&range=' + chartsRange),
    apiFetch('api/v1/rilevazioni.php?id_esemplare=' + chartPlant + '&tipo=Luminosita&range=' + chartsRange),
  ]);
  if (results[0].status === 'fulfilled' && results[0].value.status === 'ok') {
    renderChart('chart1', results[0].value.data.map(function(r) { return parseFloat(r.valore); }), { w: 900, h: 170 }, '#3a8c2f',
      plant?.Umidita_Suolo_Min ?? 30, plant?.Umidita_Suolo_Max ?? 70);
  }
  if (results[1].status === 'fulfilled' && results[1].value.status === 'ok') {
    renderChart('chart2', results[1].value.data.map(function(r) { return parseFloat(r.valore); }), { w: 440, h: 160 }, '#c07a10');
  }
  if (results[2].status === 'fulfilled' && results[2].value.status === 'ok') {
    renderChart('chart3', results[2].value.data.map(function(r) { return parseFloat(r.valore); }), { w: 440, h: 160 }, '#2980b9');
  }
}

async function loadRilevTable() {
  if (!chartPlant) return;
  try {
    var json = await apiFetch('api/v1/rilevazioni.php?id_esemplare=' + chartPlant + '&raw=1&limit=50');
    if (json.status !== 'ok') return;
    document.getElementById('rilevTable').innerHTML =
      '<thead><tr><th>#</th><th>Data_Ora</th><th>Tipo</th><th>Valore</th></tr></thead><tbody>'
      + json.data.map(function(r) {
          return '<tr>'
            + '<td style="color:var(--text-muted)">' + r.ID_Rilevazione + '</td>'
            + '<td>' + new Date(r.Data_Ora_Rilevazione).toLocaleString('it-IT') + '</td>'
            + '<td style="font-size:10px;color:var(--blue)">' + r.Tipo_Misurazione + '</td>'
            + '<td style="color:var(--green)">' + r.Valore + '</td>'
            + '</tr>';
        }).join('')
      + '</tbody>';
  } catch(e) {}
}

function renderAggiungi() {
  selSpecies = null;
  addStep    = 1;
  showStep(1);
  document.getElementById('speciesSearch').value    = '';
  document.getElementById('nickInput').value        = '';
  document.getElementById('dateInput').value        = new Date().toISOString().split('T')[0];
  document.getElementById('photoEmoji').textContent = '📷';
  renderSpeciesGrid('');
}

function filterSpecies(val) {
  renderSpeciesGrid(val);
}

function selectSpecies(id) {
  selSpecies = parseInt(id);
  renderSpeciesGrid(document.getElementById('speciesSearch').value);
  var sp = speciesDB.find(function(s) { return parseInt(s.ID_Specie ?? s.id) === parseInt(id); });
  if (sp) {
    document.getElementById('tMin').value = sp.Temp_Ideale_Min   ?? '';
    document.getElementById('tMax').value = sp.Temp_Ideale_Max   ?? '';
    document.getElementById('uMin').value = sp.Umidita_Suolo_Min ?? '';
    document.getElementById('uMax').value = sp.Umidita_Suolo_Max ?? '';
    document.getElementById('lMin').value = sp.Luce_Ideale_Min   ?? '';
    document.getElementById('lMax').value = sp.Luce_Ideale_Max   ?? '';
  }
}

function nextStep(n) {
  if (n === 2 && !selSpecies)                                        { toast('⚠', 'Seleziona una specie!', 'err'); return; }
  if (n === 3 && !document.getElementById('nickInput').value.trim()) { toast('⚠', 'Inserisci un soprannome!', 'err'); return; }
  if (n === 4) buildConfirm();
  showStep(n);
}

function pickEmoji() {
  var emojis = ['🌵', '🌿', '🌺', '🌸', '🌱', '🌳', '🪴', '🍃', '🌻', '💐', '🎋'];
  document.getElementById('photoEmoji').textContent = emojis[Math.floor(Math.random() * emojis.length)];
}

async function savePlant() {
  var sp   = speciesDB.find(function(s) { return parseInt(s.ID_Specie ?? s.id) === selSpecies; });
  var nick = document.getElementById('nickInput').value.trim() || sp?.Nome_Comune || 'Nuova pianta';
  var body = {
    id_specie:     sp?.ID_Specie ?? sp?.id,
    soprannome:    nick,
    data_aggiunta: document.getElementById('dateInput').value,
  };
  try {
    var json = await apiFetch('api/v1/esemplari.php', { method: 'POST', body: JSON.stringify(body) });
    if (json.status !== 'ok') { toast('⚠', json.message, 'err'); return; }
    var listJson = await apiFetch('api/v1/esemplari.php');
    if (listJson.status === 'ok') {
      plants = listJson.data || [];
      if (!chartPlant && plants.length) chartPlant = plants[0].ID_Esemplare ?? plants[0].id;
    }
    renderSidebar();
    toast('🌱', '"' + nick + '" aggiunta con successo!');
    setTimeout(function() { goTo('piante'); }, 700);
  } catch(e) { toast('⚠', 'Errore di rete.', 'err'); }
}

function renderSpeciesGrid(filter) {
  var f        = filter.toLowerCase();
  var filtered = speciesDB.filter(function(s) {
    return (s.Nome_Comune || '').toLowerCase().includes(f) || (s.Nome_Scientifico || '').toLowerCase().includes(f);
  });
  document.getElementById('speciesGrid').innerHTML = filtered.map(function(s) {
    var sid = parseInt(s.ID_Specie ?? s.id);
    return '<div class="species-card ' + (selSpecies === sid ? 'selected' : '') + '" onclick="selectSpecies(' + sid + ')">'
      + '<div class="se">🌱</div>'
      + '<div class="sn">' + (s.Nome_Comune || '—') + '</div>'
      + '<div class="ss">' + (s.Nome_Scientifico || '') + '</div>'
      + '</div>';
  }).join('');
}

function buildConfirm() {
  var sp   = speciesDB.find(function(s) { return parseInt(s.ID_Specie ?? s.id) === selSpecies; });
  var nick = document.getElementById('nickInput').value || 'Senza nome';
  document.getElementById('confirmEmoji').textContent   = '🌱';
  document.getElementById('confirmName').textContent    = nick;
  document.getElementById('confirmSpecies').textContent = sp?.Nome_Scientifico || '—';
  document.getElementById('previewSql').innerHTML =
    '<div>ID_Specie: <span style="color:var(--green)">' + (sp?.ID_Specie ?? sp?.id) + '</span></div>'
    + '<div>Soprannome: <span style="color:var(--green)">\'' + nick + '\'</span></div>'
    + '<div>Data_Aggiunta: <span style="color:var(--green)">\'' + document.getElementById('dateInput').value + '\'</span></div>';
}

function showStep(n) {
  addStep = n;
  for (var i = 1; i <= 4; i++) {
    var el = document.getElementById('addStep' + i);
    if (el) el.style.display = i === n ? '' : 'none';
    var s = document.getElementById('step' + i);
    if (s) s.className = 'step' + (i < n ? ' done' : i === n ? ' active' : '');
    var l = document.getElementById('line' + i);
    if (l) l.className = 'step-line' + (i < n ? ' done' : '');
  }
}

async function renderImpostazioni() {
  document.getElementById('settingsAvatar').textContent = '🧑';
  document.getElementById('settingsName').textContent   = currentUser?.Nome  || '—';
  document.getElementById('settingsEmail').textContent  = currentUser?.Email || '—';
  document.getElementById('editNome').value  = currentUser?.Nome  || '';
  document.getElementById('editEmail').value = currentUser?.Email || '';
  try {
    var json = await apiFetch('api/v1/stats.php');
    if (json.status === 'ok') {
      document.getElementById('dbPlantCount').textContent = json.data.totale_piante      ?? '—';
      document.getElementById('dbAlarmCount').textContent = json.data.totale_allarmi     ?? '—';
      document.getElementById('dbRilevCount').textContent = json.data.totale_rilevazioni ?? '—';
    }
  } catch(e) {
    document.getElementById('dbPlantCount').textContent = plants.length;
    document.getElementById('dbAlarmCount').textContent = alarms.length;
  }
}

async function saveSettings() {
  var nome  = document.getElementById('editNome').value.trim();
  var email = document.getElementById('editEmail').value.trim();
  var pass  = document.getElementById('editPass').value;
  if (!nome || !email) { toast('⚠', 'Nome ed email obbligatori.', 'err'); return; }
  var body = { nome: nome, email: email };
  if (pass) {
    if (pass.length < 8) { toast('⚠', 'Password minimo 8 caratteri.', 'err'); return; }
    body.password = pass;
  }
  var json = await apiFetch('api/v1/auth.php?action=update_profile', { method: 'PUT', body: JSON.stringify(body) });
  if (json.status !== 'ok') { toast('⚠', json.message, 'err'); return; }
  currentUser = json.data;
  document.getElementById('settingsName').textContent  = json.data.Nome;
  document.getElementById('settingsEmail').textContent = json.data.Email;
  document.getElementById('suName').textContent        = json.data.Nome;
  document.getElementById('userLabel').textContent     = json.data.Email;
  document.getElementById('editPass').value = '';
  toast('✓', 'Impostazioni salvate!');
}

function confirmDeleteAccount() {
  openConfirm('💀', "Eliminare l'account?", "L'account e TUTTI i dati verranno eliminati definitivamente.", async function() {
    var json = await apiFetch('api/v1/auth.php?action=delete_account', { method: 'DELETE' });
    if (json.status !== 'ok') { toast('⚠', json.message, 'err'); return; }
    await fetch('api/v1/auth.php?action=logout', { method: 'POST' });
    currentUser = null; plants = []; alarms = [];
    _intervals.forEach(clearInterval); _intervals = [];
    document.getElementById('appScreen').style.display  = 'none';
    document.getElementById('authScreen').style.display = 'flex';
    document.getElementById('loginPass').value = '';
    document.getElementById('loginErr').style.display = 'none';
    toast('✓', 'Account eliminato');
  });
}

function confirmDeleteAllPlants() {
  openConfirm('🌿', 'Eliminare tutte le piante?', 'Tutti i ' + plants.length + ' esemplari e relative rilevazioni verranno cancellati.', async function() {
    var json = await apiFetch('api/v1/esemplari.php?all=1', { method: 'DELETE' });
    if (json.status === 'ok') {
      plants = []; alarms = []; chartPlant = null;
      renderSidebar();
      updateAlarmBadge();
      renderImpostazioni();
      toast('🗑', 'Tutte le piante eliminate');
    }
  });
}
