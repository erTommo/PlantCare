<div class="page" id="page-piante">
  <div class="topbar">
    <div>
      <div class="page-title">Le mie piante</div>
      <div class="page-sub" id="pianteSubTitle">—</div>
    </div>
    <div class="topbar-actions">
      <div class="tabs">
        <button class="tab active" onclick="setFilterPiante('all',this)">Tutte</button>
        <button class="tab"        onclick="setFilterPiante('ok',this)">✓ OK</button>
        <button class="tab"        onclick="setFilterPiante('warn',this)">⚠ Attenzione</button>
        <button class="tab"        onclick="setFilterPiante('alert',this)">! Allarme</button>
      </div>
      <button class="btn btn-primary btn-sm" onclick="goTo('aggiungi')">+ Aggiungi</button>
    </div>
  </div>

  <div class="content">
    <div class="ga" id="plantsPage"></div>
    <div id="emptyPlants" style="display:none;text-align:center;padding:48px;color:var(--text-muted)">
      <div style="font-size:40px;margin-bottom:12px">🌵</div>
      <div style="font-size:18px;margin-bottom:8px">Nessuna pianta trovata</div>
      <div style="font-size:12px">Nessuna pianta corrisponde al filtro selezionato.</div>
    </div>
  </div>
</div>
