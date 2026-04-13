const Pages = {};

async function apiFetch(url, options = {}) {
  const defaults = {
    headers:     { 'Content-Type': 'application/json' },
    credentials: 'same-origin',
  };
  const res  = await fetch(url, { ...defaults, ...options });
  const json = await res.json();
  return json;
}

function _normalizePlant(p) {
  if (p._normalized) return p;
  return {
    ...p,
    _normalized: true,
    id:      parseInt(p.ID_Esemplare ?? p.id),
    nick:    p.Soprannome   ?? p.nick    ?? '—',
    species: p.Nome_Scientifico ?? p.species ?? '—',
    icon:    p.Foto_Default_URL ? '' : '🌿',
    status:  p.stato        ?? p.status  ?? 'ok',
    live:    p.live         ?? false,
    s: {
      umidita_suolo: p.ultime_rilevazioni?.Umidita_Suolo?.valore    ?? p.s?.umidita_suolo ?? null,
      temperatura:   p.ultime_rilevazioni?.Temperatura_Aria?.valore ?? p.s?.temperatura   ?? null,
      umidita_aria:  p.ultime_rilevazioni?.Umidita_Aria?.valore     ?? p.s?.umidita_aria  ?? null,
      luminosita:    p.ultime_rilevazioni?.Luminosita?.valore       ?? p.s?.luminosita    ?? null,
    },
    t: {
      umidita_suolo: [p.Umidita_Suolo_Min  ?? p.t?.umidita_suolo?.[0] ?? 30,  p.Umidita_Suolo_Max  ?? p.t?.umidita_suolo?.[1] ?? 70],
      temperatura:   [p.Temp_Ideale_Min    ?? p.t?.temperatura?.[0]   ?? 15,  p.Temp_Ideale_Max    ?? p.t?.temperatura?.[1]   ?? 30],
      umidita_aria:  [30, 70],
      luminosita:    [p.Luce_Ideale_Min    ?? p.t?.luminosita?.[0]    ?? 500, p.Luce_Ideale_Max    ?? p.t?.luminosita?.[1]    ?? 5000],
    },
  };
}

function _normalizeAlarm(a) {
  const typeMap = {
    Troppo_Secco:  { ico: '🌡️', cls: 'ai-dry',  lbl: 'Troppo Secco'  },
    Troppo_Umido:  { ico: '💧',  cls: 'ai-wet',  lbl: 'Troppo Umido'  },
    Troppo_Caldo:  { ico: '🔥',  cls: 'ai-hot',  lbl: 'Troppo Caldo'  },
    Troppo_Freddo: { ico: '❄️',  cls: 'ai-cold', lbl: 'Troppo Freddo' },
    Poca_Luce:     { ico: '🌑',  cls: 'ai-dark', lbl: 'Poca Luce'     },
  };
  const tipo = a.Tipo_Allarme ?? a.type ?? '';
  const meta = typeMap[tipo] ?? { ico: '⚠️', cls: '', lbl: tipo };
  return {
    ...a,
    id:      parseInt(a.ID_Allarme   ?? a.id),
    plantId: parseInt(a.ID_Esemplare ?? a.plantId),
    plant:   a.Soprannome     ?? a.plant  ?? '—',
    type:    tipo,
    time:    a.Data_Ora
      ? new Date(a.Data_Ora).toLocaleString('it-IT', { day:'2-digit', month:'2-digit', hour:'2-digit', minute:'2-digit' })
      : (a.time ?? ''),
    val:     a.Valore_Rilevato !== undefined ? String(a.Valore_Rilevato) : (a.val ?? ''),
    read:    a.Letto_Da_Utente ? !!a.Letto_Da_Utente : !!a.read,
    ico:     meta.ico,
    cls:     meta.cls,
  };
}

window._normalizePlant = _normalizePlant;
window._normalizeAlarm = _normalizeAlarm;
