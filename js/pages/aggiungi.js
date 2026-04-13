Pages.AddPlant = (() => {

  function render() {
    State.setAddStep(1);
    State.setSelSpecies(null);
    _showStep(1);
    document.getElementById('speciesSearch').value      = '';
    document.getElementById('nickInput').value          = '';
    document.getElementById('dateInput').value          = new Date().toISOString().split('T')[0];
    document.getElementById('photoEmoji').textContent   = '📷';
    _renderSpeciesGrid('');
  }

  function filterSpecies(val) {
    _renderSpeciesGrid(val);
  }

  function selectSpecies(id) {
    State.setSelSpecies(parseInt(id));
    _renderSpeciesGrid(document.getElementById('speciesSearch').value);
    const sp = State.getSpeciesDB().find(s => parseInt(s.ID_Specie ?? s.id) === parseInt(id));
    if (sp) {
      document.getElementById('tMin').value = sp.Temp_Ideale_Min    ?? sp.t?.tMin ?? '';
      document.getElementById('tMax').value = sp.Temp_Ideale_Max    ?? sp.t?.tMax ?? '';
      document.getElementById('uMin').value = sp.Umidita_Suolo_Min  ?? sp.t?.uMin ?? '';
      document.getElementById('uMax').value = sp.Umidita_Suolo_Max  ?? sp.t?.uMax ?? '';
      document.getElementById('lMin').value = sp.Luce_Ideale_Min    ?? sp.t?.lMin ?? '';
      document.getElementById('lMax').value = sp.Luce_Ideale_Max    ?? sp.t?.lMax ?? '';
    }
  }

  function nextStep(n) {
    if (n === 2 && !State.getSelSpecies())                             { UI.toast('⚠', 'Seleziona una specie!', 'err'); return; }
    if (n === 3 && !document.getElementById('nickInput').value.trim()) { UI.toast('⚠', 'Inserisci un soprannome!', 'err'); return; }
    if (n === 4) _buildConfirm();
    _showStep(n);
  }

  function pickEmoji() {
    const emojis = ['🌵', '🌿', '🌺', '🌸', '🌱', '🌳', '🪴', '🍃', '🌻', '💐', '🎋'];
    document.getElementById('photoEmoji').textContent = emojis[Math.floor(Math.random() * emojis.length)];
  }

  async function save() {
    const sel  = State.getSelSpecies();
    const sp   = State.getSpeciesDB().find(s => parseInt(s.ID_Specie ?? s.id) === sel);
    const nick = document.getElementById('nickInput').value.trim() || sp?.Nome_Comune || 'Nuova pianta';
    const body = {
      id_specie:     sp?.ID_Specie ?? sp?.id,
      soprannome:    nick,
      data_aggiunta: document.getElementById('dateInput').value,
    };

    try {
      const json = await apiFetch('api/v1/esemplari.php', { method: 'POST', body: JSON.stringify(body) });
      if (json.status !== 'ok') { UI.toast('⚠', json.message, 'err'); return; }

      const listJson = await apiFetch('api/v1/esemplari.php');
      if (listJson.status === 'ok') State.setPlants(listJson.data || []);

      UI.renderSidebar();
      UI.toast('🌱', `"${nick}" aggiunta con successo!`);
      setTimeout(() => App.goTo('piante'), 700);
    } catch (e) {
      UI.toast('⚠', 'Errore di rete durante il salvataggio.', 'err');
    }
  }

  function _renderSpeciesGrid(filter) {
    const f        = filter.toLowerCase();
    const filtered = State.getSpeciesDB().filter(s => {
      const nome = (s.Nome_Comune     || s.nome || '').toLowerCase();
      const sci  = (s.Nome_Scientifico || s.sci  || '').toLowerCase();
      return nome.includes(f) || sci.includes(f);
    });
    document.getElementById('speciesGrid').innerHTML = filtered.map(s => {
      const sid = parseInt(s.ID_Specie ?? s.id);  // normalizza a numero
      return `<div class="species-card ${State.getSelSpecies() === sid ? 'selected' : ''}"
                   onclick="Pages.AddPlant.selectSpecies(${sid})">
        <div class="se">${s.Foto_Default_URL ? `<img src="${s.Foto_Default_URL}" style="width:32px;height:32px;object-fit:cover;border-radius:4px">` : '🌱'}</div>
        <div class="sn">${s.Nome_Comune || s.nome}</div>
        <div class="ss">${s.Nome_Scientifico || s.sci || ''}</div>
      </div>`;
    }).join('');
  }

  function _buildConfirm() {
    const sel  = State.getSelSpecies();
    const sp   = State.getSpeciesDB().find(s => parseInt(s.ID_Specie ?? s.id) === sel);
    const nick = document.getElementById('nickInput').value || 'Senza nome';
    document.getElementById('confirmEmoji').textContent   = '🌱';
    document.getElementById('confirmName').textContent    = nick;
    document.getElementById('confirmSpecies').textContent = sp?.Nome_Scientifico || sp?.sci || '—';
    document.getElementById('previewSql').innerHTML = `
      <div>ID_Specie: <span style="color:var(--green2)">${sp?.ID_Specie ?? sp?.id}</span></div>
      <div>Soprannome: <span style="color:var(--green2)">'${nick}'</span></div>
      <div>Data_Aggiunta: <span style="color:var(--green2)">'${document.getElementById('dateInput').value}'</span></div>`;
  }

  function _showStep(n) {
    State.setAddStep(n);
    for (let i = 1; i <= 4; i++) {
      const el = document.getElementById('addStep' + i);
      if (el) el.style.display = i === n ? '' : 'none';
      const s = document.getElementById('step' + i);
      if (s) s.className = 'step' + (i < n ? ' done' : i === n ? ' active' : '');
      const l = document.getElementById('line' + i);
      if (l) l.className = 'step-line' + (i < n ? ' done' : '');
    }
  }

  return { render, filterSpecies, selectSpecies, nextStep, pickEmoji, save };
})();
