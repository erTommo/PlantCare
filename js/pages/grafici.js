Pages.Charts_ = (() => {
  let currentRange = '24h';

  function render() {
    const plants = State.getPlants();
    if (!plants.length) return;
    if (!State.getChartPlant()) State.setChartPlant(plants[0].ID_Esemplare ?? plants[0].id);

    document.getElementById('chartPills').innerHTML = plants.map(p => {
      const pid = p.ID_Esemplare ?? p.id;
      return `<div class="plant-pill ${pid === State.getChartPlant() ? 'active' : ''}"
                   onclick="Pages.Charts_.selectPlant(${pid})">
        ${p.Foto_Default_URL ? '' : '🌿'} ${p.Soprannome || p.nick}
      </div>`;
    }).join('');

    _drawCharts();
    _loadAndRenderTable();
  }

  function selectPlant(id) {
    State.setChartPlant(parseInt(id));
    render();
  }

  function setTimeRange(range, btn) {
    currentRange = range;
    document.querySelectorAll('#page-grafici .tab').forEach(t => t.classList.remove('active'));
    btn.classList.add('active');
    _drawCharts();
    _loadAndRenderTable();
  }

  async function _drawCharts() {
    const id = State.getChartPlant();
    if (!id) return;
    const plant = State.getPlants().find(p => parseInt(p.ID_Esemplare ?? p.id) === id);

    const [uRes, tRes, lRes] = await Promise.allSettled([
      apiFetch(`api/v1/rilevazioni.php?id_esemplare=${id}&tipo=Umidita_Suolo&range=${currentRange}`),
      apiFetch(`api/v1/rilevazioni.php?id_esemplare=${id}&tipo=Temperatura_Aria&range=${currentRange}`),
      apiFetch(`api/v1/rilevazioni.php?id_esemplare=${id}&tipo=Luminosita&range=${currentRange}`),
    ]);

    if (uRes.status === 'fulfilled' && uRes.value.status === 'ok') {
      const vals = uRes.value.data.map(r => parseFloat(r.valore));
      const tMin = plant?.Umidita_Suolo_Min ?? 30;
      const tMax = plant?.Umidita_Suolo_Max ?? 70;
      Charts.render('chart1', vals, { w: 900, h: 170 }, '#82d44e', tMin, tMax, 0, 100);
    }
    if (tRes.status === 'fulfilled' && tRes.value.status === 'ok') {
      const vals = tRes.value.data.map(r => parseFloat(r.valore));
      Charts.renderSimple('chart2', vals, { w: 440, h: 160 }, '#e9a825');
    }
    if (lRes.status === 'fulfilled' && lRes.value.status === 'ok') {
      const vals = lRes.value.data.map(r => parseFloat(r.valore));
      Charts.renderSimple('chart3', vals, { w: 440, h: 160 }, '#5ab8d8');
    }
  }

  async function _loadAndRenderTable() {
    const id = State.getChartPlant();
    if (!id) return;
    try {
      const json = await apiFetch(`api/v1/rilevazioni.php?id_esemplare=${id}&raw=1&limit=50`);
      if (json.status !== 'ok') return;
      document.getElementById('rilevTable').innerHTML = `
        <thead><tr><th>#</th><th>Data_Ora</th><th>Tipo_Misurazione</th><th>Valore</th></tr></thead>
        <tbody>${json.data.map(r => `<tr>
          <td style="color:var(--text-muted);font-family:var(--font-m)">${r.ID_Rilevazione}</td>
          <td>${new Date(r.Data_Ora_Rilevazione).toLocaleString('it-IT')}</td>
          <td><span style="font-family:var(--font-m);font-size:10px;color:var(--blue)">${r.Tipo_Misurazione}</span></td>
          <td style="font-family:var(--font-m);color:var(--green2)">${r.Valore}</td>
        </tr>`).join('')}</tbody>`;
    } catch (e) { console.warn('Errore tabella rilevazioni:', e); }
  }

  return { render, selectPlant, setTimeRange };
})();
