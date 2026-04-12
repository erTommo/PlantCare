import serial
import requests
import time

ser = serial.Serial('COM3', 9600, timeout=1) 
php_url = "http://localhost/PlantCare/save_data.php" 
ID_PIANTA = 1 

while True:
    if ser.in_waiting > 0:
        line = ser.readline().decode('utf-8').strip()
        if "|" in line:
            dati = line.split("|")
            
            
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
                    risposta = r.text
                    print(f"Inviato {m['tipo']}: {risposta}")
                    
                    #se il file php ci risponde dicendo che la terra e' troppo secca viene inviato un messaggio al codice arduino che accendera la pompa
                    if "Troppo_Secco" in risposta:
                        print(">>> ALLARME SICCITA'! Ordino ad Arduino di accendere la pompa...")
                        ser.write(b"WATER\n")
                        
                        time.sleep(5) 
                        
                except Exception as e:
                    print(f"Errore connessione PHP: {e}")
                    
        time.sleep(1)