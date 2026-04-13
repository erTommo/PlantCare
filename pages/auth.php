<div id="authScreen">
  <div class="auth-box">
    <div class="auth-logo">🌱 PlantCare</div>
    <div class="auth-sub">Monitoraggio piante IoT</div>
    <div class="auth-tabs">
      <div class="auth-tab active" id="loginTab"    onclick="switchTab('login')">Accedi</div>
      <div class="auth-tab"        id="registerTab" onclick="switchTab('register')">Registrati</div>
    </div>
    <div id="loginForm">
      <div class="auth-err" id="loginErr" style="display:none">Email o password errate.</div>
      <div class="form-group">
        <label class="form-label">Email</label>
        <input class="form-input" id="loginEmail" type="email" placeholder="tua@email.it" value="demo@plantcare.it">
      </div>
      <div class="form-group">
        <label class="form-label">Password</label>
        <input class="form-input" id="loginPass" type="password" placeholder="••••••••" value="password">
      </div>
      <button class="auth-submit" onclick="login()">Accedi →</button>
      <div class="auth-footer">Demo: demo@plantcare.it / password</div>
    </div>
    <div id="registerForm" style="display:none">
      <div class="auth-err" id="regErr"></div>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Nome</label>
          <input class="form-input" id="regNome" type="text" placeholder="Mario">
        </div>
        <div class="form-group">
          <label class="form-label">Cognome</label>
          <input class="form-input" id="regCognome" type="text" placeholder="Rossi">
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Email</label>
        <input class="form-input" id="regEmail" type="email" placeholder="tua@email.it">
      </div>
      <div class="form-group">
        <label class="form-label">Password</label>
        <input class="form-input" id="regPass" type="password" placeholder="Minimo 8 caratteri">
      </div>
      <div class="form-group">
        <label class="form-label">Conferma password</label>
        <input class="form-input" id="regPass2" type="password" placeholder="Ripeti password">
      </div>
      <button class="auth-submit" onclick="register()">Crea account →</button>
    </div>
  </div>
</div>
