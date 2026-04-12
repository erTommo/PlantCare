#include <Wire.h>
#include "DHT.h"

const int dhtPin = 2;
const int soilPin = A0; // Sensore Umidità Suolo analogico
const int relayPin = 7; // Modulo Relay per la Pompa
const int bh1750Addr = 0x23; // Indirizzo I2C per GY-302

DHT dht(dhtPin, DHT11);

unsigned long ultimaLettura = 0;
const long intervalloLettura = 5000; 

unsigned long inizioPompa = 0;
const long durataPompa = 3000; 
bool pompaAttiva = false;

String comandoRicevuto = "";

void setup() {
  Serial.begin(9600);
  
  
  pinMode(relayPin, OUTPUT); //iniziializzazione reley
  digitalWrite(relayPin, HIGH); 

  
  dht.begin(); //Inizializza sensori
  Wire.begin();

  Wire.beginTransmission(bh1750Addr); // qua svegliamo il sensore della luce perche di default e' in modalita standby
  Wire.write(0x10); 
  Wire.endTransmission();
}

void loop() {
  unsigned long ora = millis();

  
  if (pompaAttiva && (ora - inizioPompa >= durataPompa)) { //spegnimento pompa
    pompaAttiva = false;
    digitalWrite(relayPin, HIGH); 
  }

  
  while (Serial.available() > 0) { //acolto comandi del file python
    char c = (char)Serial.read();
    if (c == '\n') {
      comandoRicevuto.trim();
      
      if (comandoRicevuto == "WATER") {
        pompaAttiva = true;
        inizioPompa = millis();
        digitalWrite(relayPin, LOW); // Accende il relè
      }
      comandoRicevuto = ""; 
    } else if (c != '\r') {
      comandoRicevuto += c;
    }
  }

  
  if (ora - ultimaLettura >= intervalloLettura) { // invio dati delle misurazioini
    ultimaLettura = ora;
    
    float temperatura = dht.readTemperature();
    float umidita = dht.readHumidity();

    if (isnan(temperatura)) temperatura = 0.0;
    if (isnan(umidita)) umidita = 0.0;

    float pressione = 0.0; // Spazio vuoto

    int rawSoil = analogRead(soilPin);
    int soilPct = map(rawSoil, 520, 260, 0, 100);
    soilPct = constrain(soilPct, 0, 100);

    int lux = readBH1750();

    Serial.print(temperatura); Serial.print("|");
    Serial.print(umidita);     Serial.print("|");
    Serial.print(pressione);   Serial.print("|");
    Serial.print(soilPct);     Serial.print("|");
    Serial.println(lux);
  }
}

int readBH1750() {
  int val = 0;
  Wire.beginTransmission(bh1750Addr);
  Wire.requestFrom(bh1750Addr, 2);
  while (Wire.available()) {
    val = (val << 8) | Wire.read();
  }
  return val;
}