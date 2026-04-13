Pages.Settings = (() => {

  async function render() {
    const user = State.getUser();
    if (user) {
      document.getElementById('settingsAvatar').textContent = '🧑';
      document.getElementById('settingsName').textContent   = user.Nome  || '—';
      document.getElementById('settingsEmail').textContent  = user.Email || '—';
      document.getElementById('editNome').value  = user.Nome  || '';
      document.getElementById('editEmail').value = user.Email || '';
    }

    try {
      const json = await apiFetch('api/v1/stats.php');
      if (json.status === 'ok') {
        document.getElementById('dbPlantCount').textContent = json.data.totale_piante      ?? '—';
        document.getElementById('dbAlarmCount').textContent = json.data.totale_allarmi     ?? '—';
        document.getElementById('dbRilevCount').textContent = json.data.totale_rilevazioni ?? '—';
      }
    } catch (e) {
      document.getElementById('dbPlantCount').textContent = State.getPlants().length;
      document.getElementById('dbAlarmCount').textContent = State.getAlarms().length;
    }
  }

  async function save() {
    const newNome  = document.getElementById('editNome').value.trim();
    const newEmail = document.getElementById('editEmail').value.trim();
    const newPass  = document.getElementById('editPass').value;

    if (!newNome || !newEmail) { UI.toast('⚠', 'Nome ed email obbligatori.', 'err'); return; }

    const body = { nome: newNome, email: newEmail };
    if (newPass) {
      if (newPass.length < 8) { UI.toast('⚠', 'Password minimo 8 caratteri.', 'err'); return; }
      body.password = newPass;
    }

    const json = await apiFetch('api/v1/auth.php?action=update_profile', {
      method: 'PUT',
      body:   JSON.stringify(body),
    });
    if (json.status !== 'ok') { UI.toast('⚠', json.message, 'err'); return; }

    const user = json.data;
    State.setUser(user);
    document.getElementById('settingsName').textContent  = user.Nome;
    document.getElementById('settingsEmail').textContent = user.Email;
    document.getElementById('suName').textContent        = user.Nome;
    document.getElementById('userLabel').textContent     = user.Email;
    document.getElementById('editPass').value = '';
    UI.toast('✓', 'Impostazioni salvate!');
  }

  function confirmDeleteAccount() {
    UI.openConfirm('💀', "Eliminare l'account?",
      "L'account e TUTTI i dati (piante, allarmi, rilevazioni) verranno eliminati definitivamente.",
      async () => {
        const json = await apiFetch('api/v1/auth.php?action=delete_account', { method: 'DELETE' });
        if (json.status === 'ok') Auth.logout();
      }
    );
  }

  function confirmDeleteAllPlants() {
    UI.openConfirm('🌿', 'Eliminare tutte le piante?',
      `Tutti i ${State.getPlants().length} esemplari e relative rilevazioni verranno cancellati.`,
      async () => {
        const json = await apiFetch('api/v1/esemplari.php?all=1', { method: 'DELETE' });
        if (json.status === 'ok') {
          State.setPlants([]);
          State.setAlarms([]);
          State.setChartPlant(null);
          UI.renderSidebar();
          UI.updateAlarmBadge();
          render();
          UI.toast('🗑', 'Tutte le piante eliminate');
        }
      }
    );
  }

  return { render, save, confirmDeleteAccount, confirmDeleteAllPlants };
})();
