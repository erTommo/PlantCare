<div class="page" id="page-aggiungi">
  <div class="topbar">
    <div>
      <div class="page-title">Aggiungi pianta</div>
      <div class="page-sub">Nuovo esemplare · Esemplari_Piante</div>
    </div>
  </div>

  <div class="content">
    <div class="steps">
      <div class="step active" id="step1"><div class="step-num">1</div><span>Specie</span></div>
      <div class="step-line"   id="line1"></div>
      <div class="step"        id="step2"><div class="step-num">2</div><span>Dettagli</span></div>
      <div class="step-line"   id="line2"></div>
      <div class="step"        id="step3"><div class="step-num">3</div><span>Soglie</span></div>
      <div class="step-line"   id="line3"></div>
      <div class="step"        id="step4"><div class="step-num">4</div><span>Conferma</span></div>
    </div>

    <div id="addStep1">
      <div class="sec">Scegli la specie botanica · Specie_Botaniche</div>
      <div class="card">
        <div class="form-group">
          <label class="form-label">Cerca specie</label>
          <input class="form-input" id="speciesSearch" type="text"
                 placeholder="es. Ficus, Monstera…"
                 oninput="filterSpecies(this.value)">
        </div>
        <div class="species-grid" id="speciesGrid"></div>
        <div style="display:flex;justify-content:flex-end;margin-top:14px">
          <button class="btn btn-primary" onclick="nextStep(2)">Continua →</button>
        </div>
      </div>
    </div>

    <div id="addStep2" style="display:none">
      <div class="sec">Dettagli esemplare</div>
      <div class="card">
        <div style="display:flex;gap:18px;align-items:flex-start;margin-bottom:16px">
          <div class="avatar-upload" onclick="pickEmoji()">
            <div id="photoEmoji" style="font-size:28px">📷</div>
            <span>Tocca</span>
          </div>
          <div style="flex:1">
            <div class="form-group">
              <label class="form-label">Soprannome *</label>
              <input class="form-input" id="nickInput" type="text" placeholder="es. Luigi…">
            </div>
            <div class="form-group" style="margin-bottom:0">
              <label class="form-label">Data aggiunta</label>
              <input class="form-input" id="dateInput" type="date">
            </div>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Note</label>
          <textarea class="form-input" rows="2" placeholder="Posizione, condizioni particolari…"></textarea>
        </div>
        <div style="display:flex;justify-content:space-between">
          <button class="btn btn-ghost"   onclick="nextStep(1)">← Indietro</button>
          <button class="btn btn-primary" onclick="nextStep(3)">Continua →</button>
        </div>
      </div>
    </div>

    <div id="addStep3" style="display:none">
      <div class="sec">Soglie di allarme · Specie_Botaniche</div>
      <div class="card">
        <div style="background:var(--bg3);border-radius:8px;padding:10px 14px;margin-bottom:16px;font-size:12px;color:var(--text-dim)">
          ℹ Pre-compilate da Specie_Botaniche. Puoi personalizzarle.
        </div>
        <div class="form-section">
          <div class="form-section-title">🌡️ Temperatura Aria (°C)</div>
          <div class="range-wrap">
            <div><label class="form-label">Min</label><input class="range-input" id="tMin" type="number" value="15" step="0.5"></div>
            <div><label class="form-label">Max</label><input class="range-input" id="tMax" type="number" value="30" step="0.5"></div>
          </div>
        </div>
        <div class="form-section">
          <div class="form-section-title">💧 Umidità Suolo (%)</div>
          <div class="range-wrap">
            <div><label class="form-label">Min</label><input class="range-input" id="uMin" type="number" value="40"></div>
            <div><label class="form-label">Max</label><input class="range-input" id="uMax" type="number" value="80"></div>
          </div>
        </div>
        <div class="form-section">
          <div class="form-section-title">☀️ Luminosità (lux)</div>
          <div class="range-wrap">
            <div><label class="form-label">Min</label><input class="range-input" id="lMin" type="number" value="1000" step="100"></div>
            <div><label class="form-label">Max</label><input class="range-input" id="lMax" type="number" value="5000" step="100"></div>
          </div>
        </div>
        <div style="display:flex;justify-content:space-between;margin-top:16px">
          <button class="btn btn-ghost"   onclick="nextStep(2)">← Indietro</button>
          <button class="btn btn-primary" onclick="nextStep(4)">Continua →</button>
        </div>
      </div>
    </div>

    <div id="addStep4" style="display:none">
      <div class="sec">Conferma</div>
      <div class="card">
        <div style="text-align:center;padding:20px 0">
          <div style="font-size:56px;margin-bottom:10px" id="confirmEmoji">🌱</div>
          <div style="font-size:20px;font-weight:600" id="confirmName">—</div>
          <div style="font-size:11px;color:var(--text-dim);font-style:italic;margin-top:4px" id="confirmSpecies">—</div>
          <span class="badge-status bs-ok" style="margin-top:10px;display:inline-flex">Pronto per il monitoraggio</span>
        </div>
        <div style="background:var(--bg3);border-radius:10px;padding:14px;font-family:var(--font-m);font-size:10.5px;color:var(--text-dim);line-height:1.8">
          <div style="color:var(--text-muted);margin-bottom:4px">// INSERT INTO Esemplari_Piante</div>
          <div id="previewSql"></div>
        </div>
        <div style="display:flex;justify-content:space-between;margin-top:16px">
          <button class="btn btn-ghost"   onclick="nextStep(3)">← Indietro</button>
          <button class="btn btn-primary" onclick="savePlant()">✓ Salva pianta</button>
        </div>
      </div>
    </div>
  </div>
</div>
