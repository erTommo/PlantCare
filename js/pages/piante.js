Pages.Plants = (() => {

  function render() {
    const filter   = State.getFilter();
    const all      = State.getPlants();
    const filtered = filter === 'all' ? all : all.filter(p => (p.stato || p.status) === filter);

    document.getElementById('pianteSubTitle').textContent = `${all.length} esemplari registrati`;

    const grid  = document.getElementById('plantsPage');
    const empty = document.getElementById('emptyPlants');

    if (!filtered.length) {
      grid.innerHTML = '';
      empty.style.display = 'block';
    } else {
      empty.style.display = 'none';
      grid.innerHTML = filtered.map(p => UI.plantCardHtml(_normalizePlant(p))).join('');
    }
  }

  function setFilter(status, btn) {
    State.setFilter(status);
    document.querySelectorAll('#page-piante .tab').forEach(t => t.classList.remove('active'));
    btn.classList.add('active');
    render();
  }

  function remove(id) {
    const pid  = parseInt(id);
    const p    = State.getPlants().find(x => parseInt(x.ID_Esemplare ?? x.id) === pid);
    if (!p) return;
    const nome = p.Soprannome || p.nick || 'questa pianta';

    UI.openConfirm('🌿', `Rimuovere "${nome}"?`,
      `L'esemplare e le sue rilevazioni verranno eliminati (ON DELETE CASCADE).`,
      async () => {
        const json = await apiFetch(`api/v1/esemplari.php?id=${id}`, { method: 'DELETE' });
        if (json.status !== 'ok') { UI.toast('⚠', json.message, 'err'); return; }
        State.removePlant(id);
        UI.renderSidebar();
        UI.updateAlarmBadge();
        UI.closeModalDirect();
        UI.toast('🗑', `"${nome}" rimossa`);
        const cp = document.querySelector('.page.active')?.id?.replace('page-', '');
        if (cp) App.refreshPage(cp);
      }
    );
  }

  return { render, setFilter, remove };
})();
