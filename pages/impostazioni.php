<div class="page" id="page-impostazioni">
  <div class="topbar">
    <div>
      <div class="page-title">Impostazioni</div>
      <div class="page-sub">Account · Notifiche · Sistema</div>
    </div>
    <div class="topbar-actions">
      <button class="btn btn-primary btn-sm" onclick="saveSettings()">✓ Salva modifiche</button>
    </div>
  </div>

  <div class="content">
    <div class="g2">
      <div>
        <div class="settings-block">
          <div class="settings-block-title">
            👤 Profilo utente
            <span style="font-size:9px;color:var(--text-muted)">— Utenti</span>
          </div>
          <div class="profile-header">
            <div class="profile-avatar-big" id="settingsAvatar">🧑</div>
            <div>
              <div style="font-size:16px;font-weight:600" id="settingsName">—</div>
              <div style="font-size:11px;color:var(--text-dim);margin-top:3px" id="settingsEmail">—</div>
              <span class="badge-status bs-ok" style="margin-top:6px;display:inline-flex">Account attivo</span>
            </div>
          </div>
          <div class="form-group">
            <label class="form-label">Nome completo</label>
            <input class="form-input" id="editNome" type="text">
          </div>
          <div class="form-group">
            <label class="form-label">Email</label>
            <input class="form-input" id="editEmail" type="email">
          </div>
          <div class="form-group" style="margin-bottom:0">
            <label class="form-label">Nuova password</label>
            <input class="form-input" id="editPass" type="password" placeholder="••••••••">
          </div>
        </div>

        <div class="settings-block" style="border-color:rgba(192,57,43,.2)">
          <div class="settings-block-title" style="color:var(--red)">⚠️ Zona pericolosa</div>
          <div style="font-size:12px;color:var(--text-dim);margin-bottom:14px;line-height:1.5">
            Queste azioni sono irreversibili e cancellano tutti i dati da PlantCareDB.
          </div>
          <div style="display:flex;gap:10px">
            <button class="btn btn-danger btn-sm" onclick="confirmDeleteAccount()">🗑 Elimina account</button>
            <button class="btn btn-danger btn-sm" onclick="confirmDeleteAllPlants()">🌿 Elimina tutte le piante</button>
          </div>
        </div>
      </div>

      <div>
        <div class="settings-block">
          <div class="settings-block-title">🔔 Notifiche allarmi</div>
          <div class="toggle">
            <div><div class="toggle-name">Troppo_Secco</div><div class="toggle-desc">Umidità suolo sotto soglia</div></div>
            <div class="switch on" onclick="this.classList.toggle('on')"></div>
          </div>
          <div class="toggle">
            <div><div class="toggle-name">Troppo_Umido</div><div class="toggle-desc">Eccesso di acqua nel suolo</div></div>
            <div class="switch on" onclick="this.classList.toggle('on')"></div>
          </div>
          <div class="toggle">
            <div><div class="toggle-name">Troppo_Caldo / Freddo</div><div class="toggle-desc">Temperatura fuori soglia</div></div>
            <div class="switch on" onclick="this.classList.toggle('on')"></div>
          </div>
          <div class="toggle">
            <div><div class="toggle-name">Poca_Luce</div><div class="toggle-desc">Luminosità insufficiente</div></div>
            <div class="switch" onclick="this.classList.toggle('on')"></div>
          </div>
        </div>

        <div class="settings-block">
          <div class="settings-block-title">⚙ Sistema IoT</div>
          <div class="form-group">
            <label class="form-label">Frequenza rilevazioni</label>
            <select class="form-input">
              <option>Ogni 5 minuti</option>
              <option selected>Ogni 15 minuti</option>
              <option>Ogni 30 minuti</option>
              <option>Ogni ora</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Unità temperatura</label>
            <select class="form-input">
              <option selected>Celsius (°C)</option>
              <option>Fahrenheit (°F)</option>
            </select>
          </div>
          <div class="toggle">
            <div><div class="toggle-name">Risparmio energetico</div><div class="toggle-desc">Riduce frequenza 00:00–06:00</div></div>
            <div class="switch on" onclick="this.classList.toggle('on')"></div>
          </div>
          <div class="toggle">
            <div><div class="toggle-name">Irrigazione automatica</div></div>
            <div class="switch" onclick="this.classList.toggle('on')"></div>
          </div>
        </div>

        <div class="settings-block">
          <div class="settings-block-title">🗄️ Database &amp; Export</div>
          <div style="font-size:11px;color:var(--text-dim);line-height:2;margin-bottom:14px">
            <div>DB: <span style="color:var(--green)">PlantCareDB</span></div>
            <div>Piante: <span style="color:var(--green)" id="dbPlantCount">—</span></div>
            <div>Rilevazioni: <span style="color:var(--green)" id="dbRilevCount">—</span></div>
            <div>Allarmi: <span style="color:var(--green)" id="dbAlarmCount">—</span></div>
          </div>
          <div style="display:flex;gap:8px;flex-wrap:wrap">
            <button class="btn btn-ghost btn-sm" onclick="exportPlantsCSV()">📥 CSV piante</button>
            <button class="btn btn-ghost btn-sm" onclick="exportAlarmsCSV()">📥 CSV allarmi</button>
            <button class="btn btn-ghost btn-sm" onclick="exportBackupJSON()">📥 Backup JSON</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
