function normalizePlant(p) {
  if (p._n) return p;
  return Object.assign({}, p, {
    _n:      true,
    id:      parseInt(p.ID_Esemplare ?? p.id),
    nick:    p.Soprannome         ?? p.nick    ?? '—',
    species: p.Nome_Scientifico   ?? p.species ?? '—',
    status:  p.stato              ?? p.status  ?? 'ok',
    live:    p.live               ?? false,
    s: {
      umidita: p.ultime_rilevazioni?.Umidita_Suolo?.valore    ?? p.s?.umidita ?? null,
      temp:    p.ultime_rilevazioni?.Temperatura_Aria?.valore ?? p.s?.temp    ?? null,
      lux:     p.ultime_rilevazioni?.Luminosita?.valore       ?? p.s?.lux     ?? null,
    },
    t: {
      umidita: [p.Umidita_Suolo_Min ?? 30, p.Umidita_Suolo_Max ?? 70],
      temp:    [p.Temp_Ideale_Min   ?? 15, p.Temp_Ideale_Max   ?? 30],
      lux:     [p.Luce_Ideale_Min   ?? 500, p.Luce_Ideale_Max  ?? 5000],
    },
  });
}

function normalizeAlarm(a) {
  var types = {
    Troppo_Secco:  { ico: '🌡️', cls: 'ai-dry'  },
    Troppo_Umido:  { ico: '💧',  cls: 'ai-wet'  },
    Troppo_Caldo:  { ico: '🔥',  cls: 'ai-hot'  },
    Troppo_Freddo: { ico: '❄️',  cls: 'ai-cold' },
    Poca_Luce:     { ico: '🌑',  cls: 'ai-dark' },
  };
  var tipo = a.Tipo_Allarme ?? a.type ?? '';
  var meta = types[tipo] ?? { ico: '⚠️', cls: '' };
  return Object.assign({}, a, {
    id:    parseInt(a.ID_Allarme   ?? a.id),
    plant: a.Soprannome ?? a.plant ?? '—',
    type:  tipo,
    time:  a.Data_Ora
      ? new Date(a.Data_Ora).toLocaleString('it-IT', { day: '2-digit', month: '2-digit', hour: '2-digit', minute: '2-digit' })
      : (a.time ?? ''),
    val:   a.Valore_Rilevato !== undefined ? String(a.Valore_Rilevato) : (a.val ?? ''),
    read:  a.Letto_Da_Utente ? !!a.Letto_Da_Utente : !!a.read,
    ico:   meta.ico,
    cls:   meta.cls,
  });
}

function fmtLux(v) { return v > 999 ? (v / 1000).toFixed(1) + 'k' : String(v); }
function rand(a, b) { return Math.random() * (b - a) + a; }
function dateStr() { return new Date().toISOString().split('T')[0].replace(/-/g, ''); }

function toast(icon, msg, type) {
  var t = document.getElementById('toast');
  document.getElementById('toastIcon').textContent = icon;
  document.getElementById('toastMsg').textContent  = msg;
  t.className = 'toast show' + (type === 'err' ? ' err' : '');
  clearTimeout(t._t);
  t._t = setTimeout(function() { t.classList.remove('show'); }, 3200);
}

function openConfirm(icon, title, desc, cb, btnLabel, btnClass) {
  confirmCb = cb;
  document.getElementById('confirmIcon').textContent  = icon;
  document.getElementById('confirmTitle').textContent = title;
  document.getElementById('confirmDesc').textContent  = desc;
  var btn = document.getElementById('confirmOkBtn');
  btn.textContent = btnLabel || 'Elimina';
  btn.className   = 'btn ' + (btnClass || 'btn-danger');
  document.getElementById('confirmDialog').classList.add('open');
}

function closeConfirm() {
  document.getElementById('confirmDialog').classList.remove('open');
  confirmCb = null;
}

function executeConfirm() {
  document.getElementById('confirmDialog').classList.remove('open');
  if (confirmCb) { confirmCb(); confirmCb = null; }
}

function closeModal(e) {
  if (e.target === document.getElementById('plantModal')) closeModalDirect();
}

function closeModalDirect() {
  document.getElementById('plantModal').classList.remove('open');
}

function renderSidebar() {
  document.getElementById('sidebarPlants').innerHTML = plants.length
    ? plants.map(function(raw) {
        var p = normalizePlant(raw);
        return '<div class="sp-item" onclick="openPlantModal(' + p.id + ')">'
          + '<div class="sp-dot dot-' + p.status + '"></div>'
          + '<div><div class="sp-name">' + p.nick + '</div>'
          + '<div class="sp-sci">' + p.species.split(' ')[0] + '</div></div>'
          + '</div>';
      }).join('')
    : '<div style="padding:8px 10px;font-size:11px;color:var(--text-muted)">Nessuna pianta</div>';
}

function updateAlarmBadge() {
  var cnt = unreadCount();
  var el  = document.getElementById('alarmBadge');
  el.textContent   = cnt;
  el.style.display = cnt > 0 ? '' : 'none';
}

function plantCardHtml(raw) {
  var p   = normalizePlant(raw);
  var lbl = { ok: '✓ OK', warn: '⚠ Attenzione', alert: '! Allarme' }[p.status] || '—';
  var u   = p.s.umidita !== null ? p.s.umidita : '—';
  var t   = p.s.temp    !== null ? p.s.temp    : '—';
  var l   = p.s.lux     !== null ? fmtLux(p.s.lux) : '—';
  return '<div class="plant-card" onclick="openPlantModal(' + p.id + ')">'
    + '<div class="plant-card-head">'
    + '<div class="plant-emoji">🌿</div>'
    + '<div><div class="plant-name">' + p.nick + '</div><div class="plant-sci">' + p.species + '</div></div>'
    + (p.live ? '<span style="margin-left:auto;font-size:9px;color:var(--green)">● LIVE</span>' : '')
    + '</div>'
    + '<div class="plant-mini-grid">'
    + '<div class="sensor-mini"><div class="sensor-mini-val">' + u + '<span class="sensor-mini-unit">%</span></div><div class="sensor-mini-lbl">Suolo</div></div>'
    + '<div class="sensor-mini"><div class="sensor-mini-val">' + t + '<span class="sensor-mini-unit">°</span></div><div class="sensor-mini-lbl">Temp</div></div>'
    + '<div class="sensor-mini"><div class="sensor-mini-val">' + l + '<span class="sensor-mini-unit">lx</span></div><div class="sensor-mini-lbl">Luce</div></div>'
    + '</div>'
    + '<div class="plant-foot">'
    + '<span class="badge-status bs-' + p.status + '">' + lbl + '</span>'
    + '<span style="font-size:10px;color:var(--text-muted)">Dettagli →</span>'
    + '</div></div>';
}

function alarmRowHtml(raw) {
  var a = normalizeAlarm(raw);
  return '<div class="alarm-row">'
    + '<div class="alarm-ico ' + a.cls + '">' + a.ico + '</div>'
    + '<div style="flex:1">'
    + '<div class="alarm-title">' + a.type.replace(/_/g, ' ') + ' — <strong>' + a.plant + '</strong></div>'
    + '<div class="alarm-meta">' + a.time + ' · Valore: ' + a.val + '</div>'
    + '</div>'
    + (!a.read ? '<div class="alarm-unread"></div>' : '')
    + '</div>';
}

function openPlantModal(rawId) {
  var id  = parseInt(rawId);
  var raw = plants.find(function(x) { return parseInt(x.ID_Esemplare ?? x.id) === id; });
  if (!raw) return;
  var p   = normalizePlant(raw);
  var lbl = { ok: '● Ottimale', warn: '⚠ Attenzione', alert: '! Allarme' }[p.status] || '—';
  var u   = p.s.umidita !== null ? p.s.umidita : '—';
  var t   = p.s.temp    !== null ? p.s.temp    : '—';
  var pa  = alarms.filter(function(a) { return parseInt(a.ID_Esemplare ?? a.plantId) === id; }).map(normalizeAlarm);

  document.getElementById('modalBody').innerHTML =
    '<div style="display:flex;align-items:center;gap:14px;margin-bottom:20px">'
    + '<div style="font-size:40px">🌿</div>'
    + '<div><div style="font-size:18px;font-weight:600">' + p.nick + '</div>'
    + '<div style="font-size:11px;color:var(--text-dim);font-style:italic">' + p.species + '</div></div>'
    + '<span class="badge-status bs-' + p.status + '" style="margin-left:auto">' + lbl + '</span>'
    + '</div>'
    + '<div class="g2" style="margin-bottom:16px">'
    + '<div class="sensor-mini"><div class="sensor-mini-val">' + u + '<span class="sensor-mini-unit">%</span></div>'
    + '<div class="sensor-mini-lbl">Umidità Suolo</div>'
    + '<div class="sbar-wrap"><div class="sbar" style="width:' + (u !== '—' ? Math.min(100, u) : 0) + '%"></div></div>'
    + '<div class="sbar-range"><span>' + p.t.umidita[0] + '%</span><span>' + p.t.umidita[1] + '%</span></div></div>'
    + '<div class="sensor-mini"><div class="sensor-mini-val">' + t + '<span class="sensor-mini-unit">°C</span></div>'
    + '<div class="sensor-mini-lbl">Temperatura</div>'
    + '<div class="sbar-wrap"><div class="sbar" style="width:' + (t !== '—' ? Math.min(100, (t / 40) * 100) : 0) + '%"></div></div>'
    + '<div class="sbar-range"><span>' + p.t.temp[0] + '°</span><span>' + p.t.temp[1] + '°</span></div></div>'
    + '</div>'
    + (pa.length
      ? '<div style="font-size:9px;color:var(--text-muted);letter-spacing:2px;margin-bottom:8px">ALLARMI (' + pa.length + ')</div>'
        + '<div style="margin-bottom:14px">'
        + pa.map(function(a) {
            return '<div style="display:flex;gap:8px;padding:6px 0;border-bottom:1px solid var(--border)">'
              + '<span>' + a.ico + '</span>'
              + '<span style="font-size:11px;color:var(--text-dim)">' + a.type.replace(/_/g, ' ') + ' — ' + a.val + '</span>'
              + (!a.read ? '<span style="margin-left:auto;width:6px;height:6px;border-radius:50%;background:var(--amber);display:inline-block"></span>' : '')
              + '</div>';
          }).join('')
        + '</div>'
      : '')
    + '<button class="water-btn" onclick="waterPlant(\'' + p.nick + '\')">💧 Annaffia ' + p.nick + '</button>'
    + '<div style="display:flex;gap:8px;margin-top:10px">'
    + (!p.live
      ? '<button class="btn btn-ghost btn-sm" onclick="setLivePlant(' + p.id + ')" style="flex:1">📡 Imposta Live</button>'
      : '<div style="flex:1;display:flex;align-items:center;gap:6px;font-size:10px;color:var(--green)"><div class="live-dot"></div>Pianta Live</div>')
    + '<button class="btn btn-ghost btn-sm" onclick="exportSinglePlantCSV(' + p.id + ')" style="flex:1">📥 CSV</button>'
    + '<button class="btn btn-danger btn-sm" onclick="removePlant(' + p.id + ')" style="flex:1">🗑 Rimuovi</button>'
    + '</div>';

  document.getElementById('plantModal').classList.add('open');
}

function renderChart(id, data, dims, color, tMin, tMax) {
  var el = document.getElementById(id);
  if (!el || !data || !data.length) return;
  if (data.length === 1) data = [data[0], data[0]];
  var W = dims.w || 900, H = dims.h || 160, pad = 14;
  var mn = Math.min.apply(null, data) * 0.9;
  var mx = Math.max.apply(null, data) * 1.05 || 1;
  if (tMin !== undefined && tMin !== null) mn = Math.min(mn, tMin * 0.9);
  if (tMax !== undefined && tMax !== null) mx = Math.max(mx, tMax * 1.1);
  var range = mx - mn || 1;
  var pts = data.map(function(v, i) {
    return [
      pad + (i / (data.length - 1)) * (W - pad * 2),
      H - pad - ((v - mn) / range) * (H - pad * 2)
    ];
  });
  var path = pts.map(function(p, i) { return (i === 0 ? 'M' : 'L') + p[0] + ',' + p[1]; }).join(' ');
  var area = path + ' L' + pts[pts.length - 1][0] + ',' + H + ' L' + pts[0][0] + ',' + H + ' Z';
  var thresh = '';
  if (tMin !== undefined && tMin !== null && tMax !== undefined && tMax !== null) {
    var yMn = Math.max(0, Math.min(H, H - pad - ((tMin - mn) / range) * (H - pad * 2)));
    var yMx = Math.max(0, Math.min(H, H - pad - ((tMax - mn) / range) * (H - pad * 2)));
    thresh = '<line x1="' + pad + '" y1="' + yMn + '" x2="' + (W - pad) + '" y2="' + yMn + '" stroke="#c07a10" stroke-width="1" stroke-dasharray="5,4" opacity=".4"/>'
           + '<line x1="' + pad + '" y1="' + yMx + '" x2="' + (W - pad) + '" y2="' + yMx + '" stroke="#2980b9" stroke-width="1" stroke-dasharray="5,4" opacity=".4"/>';
  }
  el.innerHTML = '<defs><linearGradient id="g' + id + '" x1="0" y1="0" x2="0" y2="1">'
    + '<stop offset="0%" stop-color="' + color + '" stop-opacity=".18"/>'
    + '<stop offset="100%" stop-color="' + color + '" stop-opacity="0"/>'
    + '</linearGradient></defs>'
    + thresh
    + '<path d="' + area + '" fill="url(#g' + id + ')"/>'
    + '<path d="' + path + '" fill="none" stroke="' + color + '" stroke-width="1.8" stroke-linejoin="round" stroke-linecap="round"/>';
}

function exportPlantsCSV() {
  var headers = ['ID_Esemplare', 'Soprannome', 'Specie', 'Stato', 'Umidita_Suolo', 'Temperatura', 'Luminosita'];
  var rows = plants.map(function(raw) {
    var p = normalizePlant(raw);
    return [p.id, p.nick, p.species, p.status, p.s.umidita ?? '—', p.s.temp ?? '—', p.s.lux ?? '—'];
  });
  downloadCSV('piante_' + dateStr() + '.csv', headers, rows);
  toast('📥', 'CSV piante esportato!');
}

function exportAlarmsCSV() {
  var headers = ['ID_Allarme', 'Pianta', 'Tipo_Allarme', 'Data_Ora', 'Valore', 'Letto'];
  var rows = alarms.map(function(raw) {
    var a = normalizeAlarm(raw);
    return [a.id, a.plant, a.type, a.time, a.val, a.read ? 'Sì' : 'No'];
  });
  downloadCSV('allarmi_' + dateStr() + '.csv', headers, rows);
  toast('📥', 'CSV allarmi esportato!');
}

function exportBackupJSON() {
  downloadJSON('plantcare_backup_' + dateStr() + '.json', {
    data:    new Date().toISOString(),
    utente:  { nome: currentUser?.Nome, email: currentUser?.Email },
    piante:  plants,
    allarmi: alarms,
  });
  toast('📥', 'Backup JSON scaricato!');
}

function exportSinglePlantCSV(id) {
  var raw = plants.find(function(x) { return parseInt(x.ID_Esemplare ?? x.id) === id; });
  if (!raw) return;
  var p = normalizePlant(raw);
  var headers = ['Ora', 'Umidita_Suolo', 'Temperatura', 'Luminosita'];
  var rows = Array.from({ length: 10 }, function(_, i) {
    var d = new Date(Date.now() - i * 3600000);
    return [
      d.toLocaleTimeString('it-IT'),
      p.s.umidita !== null ? Math.round(p.s.umidita + rand(-5, 5)) : '—',
      p.s.temp    !== null ? Math.round((p.s.temp + rand(-1, 1)) * 10) / 10 : '—',
      p.s.lux     !== null ? Math.round(p.s.lux + rand(-100, 100)) : '—',
    ];
  });
  downloadCSV(p.nick + '_' + dateStr() + '.csv', headers, rows);
  toast('📥', 'CSV ' + p.nick + ' esportato!');
  closeModalDirect();
}

function downloadCSV(filename, headers, rows) {
  var csv = [headers.join(',')]
    .concat(rows.map(function(r) { return r.map(function(v) { return '"' + v + '"'; }).join(','); }))
    .join('\n');
  var a = document.createElement('a');
  a.href = URL.createObjectURL(new Blob([csv], { type: 'text/csv;charset=utf-8;' }));
  a.download = filename;
  a.click();
}

function downloadJSON(filename, data) {
  var a = document.createElement('a');
  a.href = URL.createObjectURL(new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' }));
  a.download = filename;
  a.click();
}
