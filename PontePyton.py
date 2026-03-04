import serial
import requests
import time

ser = serial.Serial('COM3', 9600, timeout=1) 
php_url = "http://localhost/save_data.php"
ID_PIANTA = 1 # L'ID della pianta

while True:
    if ser.in_waiting > 0:
        line = ser.readline().decode('utf-8').strip()
        if "|" in line:
            dati = line.split("|")
            
            # Creiamo una lista di misurazioni da inviare
            misurazioni = [
                {'tipo': 'Temperatura_Aria', 'valore': dati[0]},
                {'tipo': 'Umidita_Aria', 'valore': dati[1]},
                {'tipo': 'Umidita_Suolo', 'valore': dati[3]},
                {'tipo': 'Luminosita', 'valore': dati[4]}
            ]
            
            for m in misurazioni:
                payload = {
                    'id_esemplare': ID_PIANTA,
                    'tipo': m['tipo'],
                    'valore': m['valore']
                }
                try:
                    r = requests.post(php_url, data=payload)
                    print(f"Inviato {m['tipo']}: {r.text}")
                except:
                    print("Errore connessione PHP")
        time.sleep(1)