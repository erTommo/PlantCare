# 🌱 PlantCare — IoT Plant Monitoring System

PlantCare è un'applicazione web per il monitoraggio in tempo reale della salute delle piante tramite sensori IoT. Il sistema raccoglie dati ambientali (temperatura, umidità, luminosità) da un microcontrollore Arduino, li confronta con i parametri ottimali di ogni specie botanica e genera allarmi automatici in caso di anomalie.

---

## Indice

- [Funzionalità](#funzionalità)
- [Architettura](#architettura)
- [Stack tecnologico](#stack-tecnologico)
- [Struttura del progetto](#struttura-del-progetto)
- [Schema del database](#schema-del-database)
- [API REST](#api-rest)
- [Hardware IoT](#hardware-iot)
- [Installazione](#installazione)
- [Account demo](#account-demo)

---

## Funzionalità

- **Dashboard live** — sensori aggiornati ogni 4 secondi, grafico 24h, badge allarmi
- **Gestione piante** — aggiungi, filtra e rimuovi esemplari con wizard a 4 step
- **Catalogo specie** — 15 specie pre-caricate con soglie ottimali (temperatura, umidità suolo, luminosità)
- **Allarmi automatici** — generati dal backend al superamento delle soglie, con deduplicazione a 30 minuti
- **Grafici storici** — Umidità Suolo, Temperatura Aria, Luminosità per intervalli 1h / 24h / 7d / 30d
- **Irrigazione remota** — comando `WATER` inviato ad Arduino via porta seriale in risposta a `Troppo_Secco`
- **Export dati** — CSV piante, CSV allarmi, backup JSON completo
- **Autenticazione** — login/registrazione con sessioni PHP, password bcrypt, aggiornamento profilo

---

## Architettura

```
┌─────────────┐    seriale    ┌──────────────────┐    HTTP POST    ┌──────────────────┐
│   Arduino   │ ◄───────────► │ arduino_bridge.py │ ──────────────► │  save_data.php   │
│  (sensori)  │               │  (bridge Python)  │                 │  (PHP backend)   │
└─────────────┘               └──────────────────┘                 └────────┬─────────┘
                                                                             │
                                                                    ┌────────▼─────────┐
                                                                    │    PlantCareDB    │
                                                                    │     (MySQL)       │
                                                                    └────────┬─────────┘
                                                                             │
┌─────────────────────────────────────────────────────────────────┐         │
│                     Browser (SPA)                               │         │
│  JS modules: app · auth · state · ui · charts · export · pages  │ ◄── REST API v1 ──┘
│  CSS: variables · layout · components · auth                    │
└─────────────────────────────────────────────────────────────────┘
```

Il frontend è una **Single Page Application** PHP+JS con pattern a moduli rivelatori. Il backend espone una **REST API** con autenticazione via sessione. Il bridge Python gestisce la comunicazione bidirezionale col microcontrollore.

---

## Stack tecnologico

| Layer | Tecnologia |
|---|---|
| Frontend | HTML5, CSS3, JavaScript (ES6+ moduli IIFE) |
| Backend | PHP 8+ (MVC, PDO-style con MySQLi) |
| Database | MySQL / MariaDB |
| Hardware | Arduino Uno, DHT11, BH1750, sensore capacitivo suolo, relè |
| Bridge | Python 3 + `pyserial` + `requests` |
| Web server | Apache (XAMPP) con mod_rewrite |
| Fonts | Playfair Display, DM Mono, Cabinet Grotesk (Google Fonts) |

---

## Struttura del progetto

```
PlantCare/
├── index.php                  # Entry point — include tutte le pagine PHP
├── index.html                 # Redirect a index.php
├── save_data.php              # Endpoint legacy per il bridge Arduino
├── arduino_bridge.py          # Bridge seriale Arduino → PHP
│
├── arduino/
│   └── arduino.ino            # Firmware Arduino (DHT11 + BH1750 + relè pompa)
│
├── api/v1/                    # REST API
│   ├── auth.php               # Login, register, logout, me, update_profile
│   ├── esemplari.php          # CRUD esemplari piante
│   ├── rilevazioni.php        # GET grafici / raw / stats · POST singola / batch
│   ├── allarmi.php            # GET / PUT (letto) / DELETE allarmi
│   ├── specie.php             # CRUD specie botaniche
│   └── stats.php              # Contatori dashboard
│
├── classes/                   # Business logic PHP
│   ├── Auth.php               # Autenticazione sessioni + bcrypt
│   ├── EsemplariPiante.php    # Esemplari con ultime rilevazioni e stato calcolato
│   ├── EventiAllarme.php      # CRUD allarmi + stats
│   ├── RilevazioniSensori.php # Insert con validazione + trigger allarmi automatici
│   └── SpecieBotaniche.php    # CRUD specie con validazione soglie
│
├── config/
│   ├── database.php           # Singleton MySQLi
│   └── response.php           # Helper JSON response
│
├── css/
│   ├── variables.css          # Design tokens (colori, font, spaziature)
│   ├── layout.css             # Shell, sidebar, topbar, griglie
│   ├── components.css         # Bottoni, card, sensori, grafici, modal, toast
│   └── auth.css               # Login/registrazione + form condivisi
│
├── js/
│   ├── app.js                 # Bootstrap, router, live tick
│   ├── auth.js                # Login, register, logout, auto-login
│   ├── state.js               # Store centralizzato (pattern revealing module)
│   ├── ui.js                  # Componenti UI condivisi (toast, modal, confirm)
│   ├── charts.js              # Rendering SVG grafici
│   ├── utils.js               # Helper puri (rand, clamp, CSV, JSON download)
│   ├── export.js              # Export CSV / JSON
│   └── pages/
│       ├── helpers.js         # Pages namespace + apiFetch + normalize functions
│       ├── dashboard.js       # Controller Dashboard (live sensors, chart, tick)
│       ├── piante.js          # Controller Le mie piante
│       ├── allarmi.js         # Controller Allarmi
│       ├── grafici.js         # Controller Grafici (multi-range, tabella raw)
│       ├── aggiungi.js        # Controller Aggiungi pianta (wizard 4 step)
│       └── impostazioni.js    # Controller Impostazioni (profilo, stats DB, export)
│
├── pages/                     # Template PHP (inclusi da index.php)
│   ├── auth.php               # Schermata login / registrazione
│   ├── sidebar.php            # Navigazione laterale
│   ├── dashboard.php          # Pagina Dashboard
│   ├── piante.php             # Pagina Le mie piante
│   ├── allarmi.php            # Pagina Allarmi
│   ├── grafici.php            # Pagina Grafici
│   ├── aggiungi.php           # Pagina Aggiungi pianta
│   ├── impostazioni.php       # Pagina Impostazioni
│   └── overlays.php           # Modal, confirm dialog, toast
│
└── database/
    └── schema_and_seed.sql    # Schema completo + 15 specie + utente demo
```

---

## Schema del database

```
PlantCareDB
│
├── Utenti
│   ├── ID_Utente (PK)
│   ├── Nome
│   ├── Email (UNIQUE)
│   ├── Password_Hash (bcrypt)
│   └── Data_Iscrizione
│
├── Specie_Botaniche
│   ├── ID_Specie (PK)
│   ├── Nome_Comune / Nome_Scientifico
│   ├── Temp_Ideale_Min / Max
│   ├── Umidita_Suolo_Min / Max
│   ├── Luce_Ideale_Min / Max
│   ├── Tossica_Per_Animali
│   └── Foto_Default_URL
│
├── Esemplari_Piante
│   ├── ID_Esemplare (PK)
│   ├── ID_Utente (FK → CASCADE)
│   ├── ID_Specie (FK → RESTRICT)
│   ├── Soprannome
│   ├── Data_Aggiunta
│   └── Foto_Attuale_URL
│
├── Rilevazioni_Sensori
│   ├── ID_Rilevazione (PK, BIGINT)
│   ├── ID_Esemplare (FK → CASCADE)
│   ├── Tipo_Misurazione (ENUM: Temperatura_Aria | Umidita_Aria | Umidita_Suolo | Luminosita | pH)
│   ├── Valore
│   └── Data_Ora_Rilevazione
│
└── Eventi_Allarme
    ├── ID_Allarme (PK)
    ├── ID_Esemplare (FK → CASCADE)
    ├── Tipo_Allarme (ENUM: Troppo_Secco | Troppo_Umido | Troppo_Caldo | Troppo_Freddo | Poca_Luce)
    ├── Data_Ora
    ├── Letto_Da_Utente
    └── Valore_Rilevato
```

Gli allarmi duplicati entro 30 minuti vengono soppressi automaticamente da `RilevazioniSensori::_checkAndFireAlarm()`.

---

## API REST

Base URL: `/api/v1/`  
Autenticazione: **sessione PHP** (cookie `PHPSESSID`). Gli endpoint contrassegnati con 🔒 richiedono login.

### Auth — `auth.php`

| Metodo | Query | Descrizione |
|---|---|---|
| POST | `?action=login` | Login con email + password |
| POST | `?action=register` | Registrazione nuovo account |
| POST | `?action=logout` | Distrugge la sessione |
| GET | `?action=me` | Restituisce l'utente corrente |
| PUT 🔒 | `?action=update_profile` | Aggiorna nome, email, password |
| DELETE 🔒 | `?action=delete_account` | Elimina account e tutti i dati |

### Esemplari — `esemplari.php` 🔒

| Metodo | Query | Descrizione |
|---|---|---|
| GET | — | Lista piante dell'utente (con ultime rilevazioni e stato) |
| GET | `?id=X` | Singola pianta |
| POST | — | Crea nuovo esemplare |
| PUT | `?id=X` | Aggiorna soprannome / foto |
| DELETE | `?id=X` | Elimina (CASCADE su rilevazioni e allarmi) |
| DELETE | `?all=1` | Elimina tutte le piante dell'utente |

### Rilevazioni — `rilevazioni.php` 🔒

| Metodo | Query | Descrizione |
|---|---|---|
| GET | `?id_esemplare=X&tipo=Y&range=Z` | Dati aggregati per grafico (range: `1h` `6h` `24h` `7d` `30d`) |
| GET | `?id_esemplare=X&raw=1&limit=N` | Rilevazioni grezze (max 500) |
| GET | `?id_esemplare=X&stats=1` | Statistiche per tipo (min, max, media) |
| POST | — | Inserisce singola rilevazione |
| POST | `?batch=1` | Inserisce array di rilevazioni |

### Allarmi — `allarmi.php` 🔒

| Metodo | Query | Descrizione |
|---|---|---|
| GET | — | Tutti gli allarmi dell'utente |
| GET | `?badge=1` | Solo conteggio non letti |
| GET | `?stats=1` | Conteggio per tipo allarme |
| GET | `?id_esemplare=X` | Allarmi di una singola pianta |
| PUT | `?id=X` | Segna allarme come letto |
| PUT | `?all=1` | Segna tutti come letti |
| DELETE | `?id=X` | Elimina singolo allarme |
| DELETE | `?all=1` | Elimina tutti gli allarmi |

### Specie — `specie.php`

| Metodo | Query | Descrizione |
|---|---|---|
| GET | — | Tutte le specie |
| GET | `?q=testo` | Ricerca per nome comune / scientifico |
| GET | `?id=X` | Singola specie |
| POST 🔒 | — | Crea nuova specie |
| PUT 🔒 | `?id=X` | Aggiorna specie |
| DELETE 🔒 | `?id=X` | Elimina specie (RESTRICT se ha esemplari collegati) |

### Stats — `stats.php` 🔒

`GET /api/v1/stats.php` — restituisce contatori globali per l'utente:

```json
{
  "totale_piante": 3,
  "totale_allarmi": 12,
  "allarmi_non_letti": 2,
  "totale_rilevazioni": 4800
}
```

---

## Hardware IoT

### Componenti

| Componente | Funzione |
|---|---|
| Arduino Uno | Microcontrollore principale |
| DHT11 (pin 2) | Temperatura e umidità aria |
| BH1750 / GY-302 (I2C 0x23) | Luminosità in lux |
| Sensore capacitivo suolo (A0) | Umidità suolo (0–100%) |
| Modulo relè (pin 7) | Controllo pompa irrigazione |

### Protocollo seriale

**Arduino → Python** (ogni 5 secondi, pipe `|`):
```
TEMP|HUM_ARIA|PRESSIONE|HUM_SUOLO|LUX
es.: 23.5|58.2|0.0|34|820
```

**Python → Arduino** (comando):
```
WATER\n   →  attiva relè per 3 secondi
```

### Bridge Python

Il file `arduino_bridge.py` si occupa di:
1. Leggere le righe seriali da Arduino
2. Inviare ogni valore al backend via HTTP POST su `save_data.php`
3. Se il backend risponde `Troppo_Secco`, inviare il comando `WATER` ad Arduino
4. Gestire riconnessione automatica in caso di perdita porta seriale

Configurazione iniziale nel file:
```python
PORTA_SERIALE = 'COM3'       # adattare alla propria porta
PHP_URL       = 'http://localhost/PlantCare/save_data.php'
ID_PIANTA     = 1            # ID esemplare da monitorare
```

---

## Installazione

### Requisiti

- PHP 8.0+
- MySQL 8.0+ / MariaDB 10.6+
- Apache con `mod_rewrite` abilitato (XAMPP consigliato)
- Python 3.8+ con `pip install pyserial requests` (solo per bridge Arduino)

### Procedura

1. **Clona il repository** nella cartella `htdocs` di XAMPP:
   ```bash
   git clone <repo-url> htdocs/PlantCare
   ```

2. **Crea il database** importando lo schema:
   ```bash
   mysql -u root -p < database/schema_and_seed.sql
   ```

3. **Configura la connessione** in `config/database.php`:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_USER', 'root');
   define('DB_PASS', '');        // cambia in produzione
   define('DB_NAME', 'PlantCareDB');
   ```

4. **Avvia Apache e MySQL** tramite XAMPP Control Panel.

5. **Apri** `http://localhost/PlantCare` nel browser.

6. *(Opzionale)* **Avvia il bridge Arduino**:
   ```bash
   python arduino_bridge.py
   ```

---

## Account demo

Il seed SQL include un utente pre-configurato per testare l'applicazione senza registrarsi:

| Campo | Valore |
|---|---|
| Email | `demo@plantcare.it` |
| Password | `demo1234` |

---

## Licenza

Progetto scolastico — Istituto IIS Jean Monnet. Non destinato a uso commerciale.
