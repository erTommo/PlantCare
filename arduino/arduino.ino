#include <Wire.h>
#include "DHT.h"

const int   dhtPin      = 2;
const int   soilPin     = A0;   
const int   relayPin    = 7;    
const byte  bh1750Addr  = 0x23; 

DHT dht(dhtPin, DHT11);

unsigned long ultimaLettura  = 0;
const long    intervalloLettura = 5000; 

unsigned long inizioPompa = 0;
const long    durataPompa = 3000; 
bool          pompaAttiva = false;

String comandoRicevuto = "";

void setup() {
  Serial.begin(9600);

  pinMode(relayPin, OUTPUT);
  digitalWrite(relayPin, HIGH);

  dht.begin();
  Wire.begin();

  Wire.beginTransmission(bh1750Addr);
  Wire.write(0x10);
  Wire.endTransmission();

  delay(180); 
}

void loop() {
  unsigned long ora = millis();

 
  if (pompaAttiva && (ora - inizioPompa >= durataPompa)) {
    pompaAttiva = false;
    digitalWrite(relayPin, HIGH);
    Serial.println("[INFO] Pompa spenta.");
  }

 
  while (Serial.available() > 0) {
    char c = (char)Serial.read();
    if (c == '\n') {
      comandoRicevuto.trim();
      if (comandoRicevuto == "WATER") {
        if (!pompaAttiva) {
          pompaAttiva = true;
          inizioPompa = millis();
          digitalWrite(relayPin, LOW);
          Serial.println("[INFO] Pompa accesa.");
        }
      }
      comandoRicevuto = "";
    } else if (c != '\r') {
      comandoRicevuto += c;
    }
  }

 
  if (ora - ultimaLettura >= intervalloLettura) {
    ultimaLettura = ora;

    float temperatura = dht.readTemperature();
    float umidita     = dht.readHumidity();


    if (isnan(temperatura)) temperatura = 0.0;
    if (isnan(umidita))     umidita     = 0.0;

    float pressione = 0.0; 

 
    int rawSoil  = analogRead(soilPin);
    int soilPct  = map(rawSoil, 520, 260, 0, 100);
    soilPct      = constrain(soilPct, 0, 100);

    int lux = readBH1750();

 
    Serial.print(temperatura); Serial.print("|");
    Serial.print(umidita);     Serial.print("|");
    Serial.print(pressione);   Serial.print("|");
    Serial.print(soilPct);     Serial.print("|");
    Serial.println(lux);
  }
}


int readBH1750() {
  uint16_t val = 0;
  if (Wire.requestFrom(bh1750Addr, (uint8_t)2) == 2) {
    val = (Wire.read() << 8) | Wire.read();

    val = (uint16_t)(val / 1.2);
  }
  return (int)val;
}
