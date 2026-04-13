Pages.Alarms = (() => {

  async function render() {
    try {
      const json = await apiFetch('api/v1/allarmi.php');
      if (json.status === 'ok') State.setAlarms(json.data || []);
    } catch (e) { console.warn('Errore caricamento allarmi:', e); }

    const all    = State.getAlarms().map(_normalizeAlarm);
    const unread = all.filter(a => !a.read);
    const read   = all.filter(a =>  a.read);

    document.getElementById('alarmSubTitle').textContent = `${unread.length} non letti · da Eventi_Allarme`;

    document.getElementById('alarmsUnread').innerHTML = unread.length
      ? unread.map(a => UI.alarmRowHtml(a)).join('')
      : '<div style="color:var(--text-muted);font-size:12px;text-align:center;padding:16px">Nessun allarme non letto 🎉</div>';

    document.getElementById('alarmsRead').innerHTML = read.length
      ? read.map(a => UI.alarmRowHtml(a)).join('')
      : '<div style="color:var(--text-muted);font-size:12px;padding:10px">Nessuno.</div>';

    try {
      const sJson = await apiFetch('api/v1/allarmi.php?stats=1');
      const stats = sJson.status === 'ok' ? sJson.data : [];
      const types = ['Troppo_Secco', 'Troppo_Umido', 'Troppo_Caldo', 'Troppo_Freddo', 'Poca_Luce'];
      document.getElementById('alarmStats').innerHTML = types.map(tp => {
        const found = stats.find(s => s.Tipo_Allarme === tp);
        const cnt   = found ? parseInt(found.totale) : 0;
        return `<div style="display:flex;align-items:center;justify-content:space-between;padding:8px 0;border-bottom:1px solid var(--border);font-size:12px">
          <span style="color:var(--text-dim)">${tp.replace(/_/g, ' ')}</span>
          <span style="font-family:var(--font-m);color:${cnt > 0 ? 'var(--amber)' : 'var(--text-muted)'}">${cnt}</span>
        </div>`;
      }).join('');
    } catch (e) {}

    document.getElementById('threshCard').innerHTML = `
      <table class="tbl" style="font-size:11px">
        <tr><th>Pianta</th><th>Temp °C</th><th>Umid %</th></tr>
        ${State.getPlants().map(p => `<tr>
          <td>${p.Soprannome || p.nick}</td>
          <td>${p.Temp_Ideale_Min ?? '—'}–${p.Temp_Ideale_Max ?? '—'}</td>
          <td>${p.Umidita_Suolo_Min ?? '—'}–${p.Umidita_Suolo_Max ?? '—'}</td>
        </tr>`).join('')}
      </table>`;

    UI.updateAlarmBadge();
  }

  async function markAllRead() {
    const json = await apiFetch('api/v1/allarmi.php?all=1', { method: 'PUT' });
    if (json.status === 'ok') {
      State.getAlarms().forEach(a => { a.Letto_Da_Utente = 1; a.read = true; });
      render();
      UI.toast('✓', 'Tutti gli allarmi segnati come letti');
      UI.updateAlarmBadge();
    }
  }

  function confirmClearAll() {
    UI.openConfirm('🗑', 'Cancella tutti gli allarmi?',
      'Tutti i record di Eventi_Allarme verranno eliminati.', async () => {
        const json = await apiFetch('api/v1/allarmi.php?all=1', { method: 'DELETE' });
        if (json.status === 'ok') {
          State.setAlarms([]);
          render();
          UI.updateAlarmBadge();
          UI.toast('🗑', 'Tutti gli allarmi eliminati');
        }
      });
  }

  return { render, markAllRead, confirmClearAll };
})();
