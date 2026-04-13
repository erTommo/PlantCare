<div class="page" id="page-grafici">
  <div class="topbar">
    <div>
      <div class="page-title">Grafici</div>
      <div class="page-sub">Storico da Rilevazioni_Sensori</div>
    </div>
    <div class="topbar-actions">
      <div class="tabs">
        <button class="tab active" onclick="setTimeRange('24h',this)">24h</button>
        <button class="tab"        onclick="setTimeRange('7d',this)">7 giorni</button>
        <button class="tab"        onclick="setTimeRange('30d',this)">30 giorni</button>
      </div>
      <button class="btn btn-ghost btn-sm" onclick="exportPlantsCSV()">📥 Esporta CSV</button>
    </div>
  </div>

  <div class="content">
    <div class="plant-pills" id="chartPills"></div>

    <div class="chart-card">
      <div class="chart-head">
        <div>
          <div class="chart-title">Umidità Suolo</div>
          <div style="font-size:10px;color:var(--text-muted)">Umidita_Suolo · %</div>
        </div>
        <div class="legend">
          <div class="leg-item"><div class="leg-dot" style="background:var(--green)"></div>Valore</div>
          <div class="leg-item"><div class="leg-dot" style="background:var(--amber);opacity:.6"></div>Min soglia</div>
          <div class="leg-item"><div class="leg-dot" style="background:var(--blue);opacity:.6"></div>Max soglia</div>
        </div>
      </div>
      <div class="chart-wrap" style="height:170px">
        <svg id="chart1" viewBox="0 0 900 170" preserveAspectRatio="none"></svg>
      </div>
    </div>

    <div class="g2">
      <div class="chart-card">
        <div class="chart-head"><div class="chart-title">Temperatura Aria</div></div>
        <div class="chart-wrap"><svg id="chart2" viewBox="0 0 440 160" preserveAspectRatio="none"></svg></div>
      </div>
      <div class="chart-card">
        <div class="chart-head"><div class="chart-title">Luminosità</div></div>
        <div class="chart-wrap"><svg id="chart3" viewBox="0 0 440 160" preserveAspectRatio="none"></svg></div>
      </div>
    </div>

    <div class="chart-card">
      <div class="chart-head">
        <div class="chart-title">Tabella rilevazioni</div>
        <button class="btn btn-ghost btn-sm" onclick="exportPlantsCSV()">📥 CSV</button>
      </div>
      <table class="tbl" id="rilevTable"></table>
    </div>
  </div>
</div>
