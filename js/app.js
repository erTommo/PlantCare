var currentUser  = null;
var plants       = [];
var alarms       = [];
var speciesDB    = [];
var filterStatus = 'all';
var dashChart    = 'umidita';
var selSpecies   = null;
var chartPlant   = null;
var confirmCb    = null;
var addStep      = 1;
var chartsRange  = '24h';
var _intervals   = [];

async function apiFetch(url, opts) {
  opts = opts || {};
  var res = await fetch(url, Object.assign({ headers: { 'Content-Type': 'application/json' }, credentials: 'same-origin' }, opts));
  return res.json();
}
function getLivePlant() { return plants.find(function(p) { return p.live; }) || plants[0] || null; }
function unreadCount()  { return alarms.filter(function(a) { return !a.Letto_Da_Utente && !a.read; }).length; }
function switchTab(tab) {
  document.getElementById('loginForm').style.display    = tab === 'login' ? '' : 'none';
  document.getElementById('registerForm').style.display = tab === 'register' ? '' : 'none';
  document.getElementById('loginTab').classList.toggle('active', tab === 'login');
  document.getElementById('registerTab').classList.toggle('active', tab === 'register');
}
async function login() {
  var email = document.getElementById('loginEmail').value.trim();
  var pass  = document.getElementById('loginPass').value;
  var err   = document.getElementById('loginErr');
  err.style.display = 'none';
  try {
    var json = await apiFetch('api/v1/auth.php?action=login', { method: 'POST', body: JSON.stringify({ email: email, password: pass }) });
    if (json.status !== 'ok') { err.textContent = json.message || 'Errore di accesso.'; err.style.display = 'block'; return; }
    currentUser = json.data; await loadUserData(); enterApp();
  } catch(e) { err.textContent = 'Errore di rete. Riprova.'; err.style.display = 'block'; }
}
async function register() {
  var nome  = document.getElementById('regNome').value.trim();
  var cogn  = document.getElementById('regCognome').value.trim();
  var email = document.getElementById('regEmail').value.trim();
  var pass  = document.getElementById('regPass').value;
  var pass2 = document.getElementById('regPass2').value;
  var err   = document.getElementById('regErr');
  err.style.display = 'none';
  if (!nome || !cogn)       { err.textContent = 'Inserisci nome e cognome.'; err.style.display = 'block'; return; }
  if (!email.includes('@')) { err.textContent = 'Email non valida.'; err.style.display = 'block'; return; }
  if (pass.length < 8)      { err.textContent = 'Password minimo 8 caratteri.'; err.style.display = 'block'; return; }
  if (pass !== pass2)       { err.textContent = 'Le password non coincidono.'; err.style.display = 'block'; return; }
  try {
    var json = await apiFetch('api/v1/auth.php?action=register', { method: 'POST', body: JSON.stringify({ nome: nome + ' ' + cogn, email: email, password: pass }) });
    if (json.status !== 'ok') { err.textContent = json.message; err.style.display = 'block'; return; }
    currentUser = json.data; await loadUserData(); enterApp();
  } catch(e) { err.textContent = 'Errore di rete. Riprova.'; err.style.display = 'block'; }
}
function logout() {
  openConfirm('⏻', "Uscire dall'account?", 'Verrai reindirizzato alla schermata di accesso.', async function() {
    await fetch('api/v1/auth.php?action=logout', { method: 'POST' });
    currentUser = null; plants = []; alarms = [];
    _intervals.forEach(clearInterval); _intervals = [];
    document.getElementById('appScreen').style.display  = 'none';
    document.getElementById('authScreen').style.display = 'flex';
    document.getElementById('loginPass').value = '';
    document.getElementById('loginErr').style.display = 'none';
    toast('✓', 'Logout effettuato');
  }, 'Esci', 'btn-danger');
}
async function tryAutoLogin() {
  try {
    var json = await apiFetch('api/v1/auth.php?action=me');
    if (json.status === 'ok' && json.data) { currentUser = json.data; await loadUserData(); enterApp(); return; }
  } catch(e) {}
  document.getElementById('authScreen').style.display = 'flex';
}
async function loadUserData() {
  var pj = await apiFetch('api/v1/esemplari.php');
  if (pj.status === 'ok') { plants = pj.data || []; if (!chartPlant && plants.length) chartPlant = plants[0].ID_Esemplare ?? plants[0].id; }
  var aj = await apiFetch('api/v1/allarmi.php');
  if (aj.status === 'ok') alarms = aj.data || [];
  var sj = await apiFetch('api/v1/specie.php');
  if (sj.status === 'ok') speciesDB = sj.data || [];
}
function enterApp() {
  var nome = currentUser.Nome || '—', email = currentUser.Email || '—';
  document.getElementById('authScreen').style.display = 'none';
  document.getElementById('appScreen').style.display  = 'flex';
  document.getElementById('userLabel').textContent     = email;
  document.getElementById('suName').textContent        = nome;
  document.getElementById('settingsName').textContent  = nome;
  document.getElementById('settingsEmail').textContent = email;
  document.getElementById('editNome').value  = nome;
  document.getElementById('editEmail').value = email;
  startApp();
}
function goTo(page) {
  document.querySelectorAll('.page').forEach(function(p) { p.classList.remove('active'); });
  document.querySelectorAll('.nav-item').forEach(function(n) { n.classList.remove('active'); });
  document.getElementById('page-' + page).classList.add('active');
  var nav = document.getElementById('nav-' + page);
  if (nav) nav.classList.add('active');
  if (page === 'dashboard')    renderDashboard();
  if (page === 'piante')       renderPiante();
  if (page === 'allarmi')      renderAllarmi();
  if (page === 'grafici')      renderGrafici();
  if (page === 'aggiungi')     renderAggiungi();
  if (page === 'impostazioni') renderImpostazioni();
}
function startApp() {
  _intervals.forEach(clearInterval); _intervals = [];
  aggiornaOrologio();
  _intervals.push(setInterval(aggiornaOrologio, 30000));
  renderSidebar(); goTo('dashboard'); updateAlarmBadge();
  _intervals.push(setInterval(tickLive, 4000));
}
function aggiornaOrologio() {
  var now = new Date();
  var dl = document.getElementById('dateLabel');
  if (dl) dl.textContent = now.toLocaleDateString('it-IT', { weekday: 'long', day: '2-digit', month: 'long', year: 'numeric' });
  var cf = document.getElementById('clockFoot');
  if (cf) cf.textContent = now.toLocaleTimeString('it-IT', { hour: '2-digit', minute: '2-digit' });
}
document.addEventListener('DOMContentLoaded', tryAutoLogin);
