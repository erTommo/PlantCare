<div class="page" id="page-allarmi">
  <div class="topbar">
    <div>
      <div class="page-title">Allarmi</div>
      <div class="page-sub" id="alarmSubTitle">— · da Eventi_Allarme</div>
    </div>
    <div class="topbar-actions">
      <button class="btn btn-ghost btn-sm"  onclick="markAllRead()">✓ Segna tutti letti</button>
      <button class="btn btn-danger btn-sm" onclick="confirmClearAll()">🗑 Cancella tutti</button>
    </div>
  </div>

  <div class="content">
    <div class="g2">
      <div>
        <div class="sec">Non letti</div>
        <div class="card" id="alarmsUnread" style="margin-bottom:16px"></div>
        <div class="sec">Storico</div>
        <div class="card" id="alarmsRead"></div>
      </div>
      <div>
        <div class="sec">Riepilogo per tipo</div>
        <div class="card" style="margin-bottom:14px" id="alarmStats"></div>
        <div class="sec">Soglie per pianta</div>
        <div class="card" id="threshCard"></div>
      </div>
    </div>
  </div>
</div>
