<div class="page active" id="page-dashboard">
  <div class="topbar">
    <div>
      <div class="page-title">Dashboard</div>
      <div class="page-sub" id="dateLabel">—</div>
    </div>
    <div class="topbar-actions">
      <button class="btn btn-ghost btn-sm" onclick="goTo('aggiungi')">+ Nuova pianta</button>
    </div>
  </div>

  <div class="content">
    <div class="g4" style="margin-bottom:18px" id="dashStats"></div>

    <div id="liveSectionWrap">
    <div class="sec"><div class="live-dot"></div> Pianta principale — live</div>
    <div class="g2" style="margin-bottom:22px">
      <div class="card">
        <div class="plant-card-head" style="margin-bottom:16px">
          <div class="plant-emoji">🌵</div>
          <div>
            <div class="plant-name"    id="liveName">—</div>
            <div class="plant-sci"     id="liveSpecies">—</div>
          </div>
          <span class="badge-status bs-ok" style="margin-left:auto">● Live</span>
        </div>
        <div id="liveSensors" class="g2"></div>
        <button class="water-btn" id="waterBtn" onclick="waterMain()">💧 Annaffia ora</button>
      </div>

      <div class="card">
        <div class="chart-head">
          <div class="chart-title">Andamento 24h</div>
          <div class="tabs">
            <button class="tab active" onclick="setChartType('umidita',this)">Umidità</button>
            <button class="tab"        onclick="setChartType('temp',this)">Temp</button>
            <button class="tab"        onclick="setChartType('luce',this)">Luce</button>
          </div>
        </div>
        <div class="chart-wrap">
          <svg id="dashChart" viewBox="0 0 480 160" preserveAspectRatio="none"></svg>
        </div>
        <div style="display:flex;justify-content:space-between;margin-top:5px">
          <span style="font-size:9px;color:var(--text-muted)">24h fa</span>
          <span style="font-size:9px;color:var(--text-muted)">Adesso</span>
        </div>
      </div>
    </div>
    </div>

    <div class="sec">Allarmi recenti</div>
    <div class="card" style="margin-bottom:20px" id="dashAlarms"></div>

    <div class="sec">Tutte le piante</div>
    <div class="ga" id="dashPlants"></div>
  </div>
</div>
