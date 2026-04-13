<aside class="sidebar">
  <div class="logo">
    <div class="logo-mark">🌱 PlantCare</div>
    <div class="logo-ver" id="userLabel">—</div>
  </div>

  <div class="nav-group">
    <div class="nav-label">Menu</div>
    <div class="nav-item active" id="nav-dashboard"    onclick="goTo('dashboard')">
      <span class="ni">⊞</span> Dashboard
    </div>
    <div class="nav-item" id="nav-piante"       onclick="goTo('piante')">
      <span class="ni">🌿</span> Le mie piante
    </div>
    <div class="nav-item" id="nav-allarmi"      onclick="goTo('allarmi')">
      <span class="ni">🔔</span> Allarmi
      <span class="nav-badge" id="alarmBadge" style="display:none">0</span>
    </div>
    <div class="nav-item" id="nav-grafici"      onclick="goTo('grafici')">
      <span class="ni">📈</span> Grafici
    </div>
    <div class="nav-item" id="nav-aggiungi"     onclick="goTo('aggiungi')">
      <span class="ni">＋</span> Aggiungi pianta
    </div>
    <div class="nav-item" id="nav-impostazioni" onclick="goTo('impostazioni')">
      <span class="ni">⚙</span> Impostazioni
    </div>
  </div>

  <div class="nav-label">Le tue piante</div>
  <div class="sidebar-plants" id="sidebarPlants"></div>

  <div class="sidebar-foot">
    Ultimo aggiornamento
    <div class="live-row">
      <div class="live-dot"></div>
      <span id="clockFoot">—</span>
    </div>
  </div>

  <div class="sidebar-user" onclick="goTo('impostazioni')">
    <div class="su-avatar" id="suAvatar">🧑</div>
    <div class="su-name"   id="suName">—</div>
    <div class="su-logout" title="Logout" onclick="event.stopPropagation(); logout()">⏻</div>
  </div>
</aside>
