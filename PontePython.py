import serial
import requests
import time
import sys


PORTA_SERIALE = 'COM3'      
BAUD_RATE     = 9600
TIMEOUT_SER   = 1           

PHP_URL       = 'http://localhost/PlantCare/save_data.php'
ID_PIANTA     = 1           

PAUSA_TRA_INVII = 1          
PAUSA_DOPO_POMPA = 5         

MAPPA_SENSORI = [
    (0, 'Temperatura_Aria'),
    (1, 'Umidita_Aria'),
    
    (3, 'Umidita_Suolo'),
    (4, 'Luminosita'),
]


def invia_rilevazione(tipo: str, valore: str) -> str:

    payload = {
        'id_esemplare': ID_PIANTA,
        'tipo':         tipo,
        'valore':       valore,
    }
    risposta = requests.post(PHP_URL, data=payload, timeout=5)
    risposta.raise_for_status()
    return risposta.text.strip()


def main():
    print(f"[PlantCare] Connessione a {PORTA_SERIALE} @ {BAUD_RATE} baud...")
    try:
        ser = serial.Serial(PORTA_SERIALE, BAUD_RATE, timeout=TIMEOUT_SER)
    except serial.SerialException as e:
        print(f"[ERRORE] Impossibile aprire la porta seriale: {e}")
        sys.exit(1)

    print(f"[PlantCare] Connesso. In attesa di dati (pianta ID={ID_PIANTA})...\n")

    while True:
        try:
            if ser.in_waiting > 0:
                linea = ser.readline().decode('utf-8', errors='replace').strip()

                if '|' not in linea:
                   
                    print(f"[Arduino] {linea}")
                    continue

                dati = linea.split('|')

                pompa_richiesta = False

                for indice, tipo in MAPPA_SENSORI:
                    if indice >= len(dati):
                        print(f"[WARN] Dato mancante per {tipo} (indice {indice})")
                        continue

                    valore_raw = dati[indice].strip()
                    if valore_raw == '' or valore_raw.lower() in ('nan', 'err', 'null'):
                        print(f"[WARN] Valore non valido per {tipo}: '{valore_raw}'")
                        continue

                    try:
                        risposta = invia_rilevazione(tipo, valore_raw)
                        print(f"[OK] {tipo}: {valore_raw} → {risposta}")

                        if risposta == 'Troppo_Secco':
                            pompa_richiesta = True

                    except requests.exceptions.ConnectionError:
                        print(f"[ERRORE] Server PHP non raggiungibile. Riprovo al prossimo ciclo.")
                        break
                    except requests.exceptions.Timeout:
                        print(f"[ERRORE] Timeout connessione PHP per {tipo}.")
                    except requests.exceptions.HTTPError as e:
                        print(f"[ERRORE] HTTP {e.response.status_code} per {tipo}: {e.response.text}")
                    except Exception as e:
                        print(f"[ERRORE] {tipo}: {e}")

                if pompa_richiesta:
                    print(">>> ALLARME SICCITA'! Ordino ad Arduino di accendere la pompa...")
                    ser.write(b'WATER\n')
                    time.sleep(PAUSA_DOPO_POMPA)

        except serial.SerialException as e:
            print(f"[ERRORE] Connessione seriale persa: {e}")
            print("[PlantCare] Tentativo di riconnessione in 5 secondi...")
            time.sleep(5)
            try:
                ser.close()
                ser = serial.Serial(PORTA_SERIALE, BAUD_RATE, timeout=TIMEOUT_SER)
                print("[PlantCare] Riconnesso.")
            except serial.SerialException:
                print("[ERRORE] Riconnessione fallita. Riprovo...")

        time.sleep(PAUSA_TRA_INVII)


if __name__ == '__main__':
    main()
