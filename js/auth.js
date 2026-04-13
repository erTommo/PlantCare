const Auth = (() => {

  const API = 'api/v1/auth.php';

  function switchTab(tab) {
    document.getElementById('loginForm').style.display    = tab === 'login'    ? '' : 'none';
    document.getElementById('registerForm').style.display = tab === 'register' ? '' : 'none';
    document.getElementById('loginTab').classList.toggle('active',    tab === 'login');
    document.getElementById('registerTab').classList.toggle('active', tab === 'register');
  }

  async function login() {
    const email = document.getElementById('loginEmail').value.trim();
    const pass  = document.getElementById('loginPass').value;
    const errEl = document.getElementById('loginErr');
    errEl.style.display = 'none';

    try {
      const res  = await fetch(`${API}?action=login`, {
        method:  'POST',
        headers: { 'Content-Type': 'application/json' },
        body:    JSON.stringify({ email, password: pass }),
      });
      const json = await res.json();

      if (json.status !== 'ok') {
        errEl.style.display = 'block';
        errEl.textContent   = json.message || 'Errore di accesso.';
        return;
      }

      State.setUser(json.data);
      await _loadUserData();
      _enterApp();

    } catch (e) {
      errEl.style.display = 'block';
      errEl.textContent   = 'Errore di rete. Riprova.';
    }
  }

  async function register() {
    const nome  = document.getElementById('regNome').value.trim();
    const cogn  = document.getElementById('regCognome').value.trim();
    const email = document.getElementById('regEmail').value.trim();
    const pass  = document.getElementById('regPass').value;
    const pass2 = document.getElementById('regPass2').value;

    if (!nome || !cogn)                { _regErr('Inserisci nome e cognome.'); return; }
    if (!email || !email.includes('@')) { _regErr('Inserisci un\'email valida.'); return; }
    if (pass.length < 8)               { _regErr('La password deve avere almeno 8 caratteri.'); return; }
    if (pass !== pass2)                { _regErr('Le password non coincidono.'); return; }

    try {
      const res  = await fetch(`${API}?action=register`, {
        method:  'POST',
        headers: { 'Content-Type': 'application/json' },
        body:    JSON.stringify({ nome: `${nome} ${cogn}`, email, password: pass }),
      });
      const json = await res.json();

      if (json.status !== 'ok') {
        _regErr(json.message || 'Errore durante la registrazione.');
        return;
      }

      State.setUser(json.data);
      await _loadUserData();
      _enterApp();

    } catch (e) {
      _regErr('Errore di rete. Riprova.');
    }
  }

  function logout() {
    UI.openConfirm('⏻', 'Esci dall\'account?', 'Verrai reindirizzato alla schermata di accesso.', async () => {
      await fetch(`${API}?action=logout`, { method: 'POST' });
      State.setUser(null);
      State.setPlants([]);
      State.setAlarms([]);
      document.getElementById('appScreen').style.display  = 'none';
      document.getElementById('authScreen').style.display = 'flex';
      document.getElementById('loginPass').value = '';
      document.getElementById('loginErr').style.display = 'none';
      UI.toast('✓', 'Logout effettuato');
    }, 'Esci', 'btn-danger');
  }

  async function tryAutoLogin() {
    try {
      const res  = await fetch(`${API}?action=me`);
      const json = await res.json();
      if (json.status === 'ok' && json.data) {
        State.setUser(json.data);
        await _loadUserData();
        _enterApp();
        return true;
      }
    } catch (e) {}
    return false;
  }

  async function _loadUserData() {
    try {
      const pRes  = await fetch('api/v1/esemplari.php');
      const pJson = await pRes.json();
      if (pJson.status === 'ok') State.setPlants(pJson.data || []);

      const aRes  = await fetch('api/v1/allarmi.php');
      const aJson = await aRes.json();
      if (aJson.status === 'ok') State.setAlarms(aJson.data || []);

      const sRes  = await fetch('api/v1/specie.php');
      const sJson = await sRes.json();
      if (sJson.status === 'ok') State.setSpeciesDB(sJson.data || []);

    } catch (e) {
      console.error('Errore caricamento dati:', e);
    }
  }

  function _regErr(msg) {
    const el = document.getElementById('regErr');
    el.textContent = msg; el.style.display = 'block';
  }

  function _enterApp() {
    const user = State.getUser();
    document.getElementById('authScreen').style.display = 'none';
    document.getElementById('appScreen').style.display  = 'flex';

    const nome  = user.Nome  || user.nome  || '—';
    const email = user.Email || user.email || '—';

    document.getElementById('userLabel').textContent     = email;
    document.getElementById('suName').textContent        = nome;
    document.getElementById('suAvatar').textContent      = '🧑‍🌾';
    document.getElementById('settingsName').textContent  = nome;
    document.getElementById('settingsEmail').textContent = email;
    document.getElementById('settingsAvatar').textContent= '🧑‍🌾';
    document.getElementById('editNome').value            = nome;
    document.getElementById('editEmail').value           = email;

    App.start();
  }

  return { switchTab, login, register, logout, tryAutoLogin };
})();
