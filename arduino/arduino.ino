#include <Wire.h>
#include <Adafruit_Sensor.h>
#include <Adafruit_BME280.h>

Adafruit_BME280 bme; // rappresentante virtuale del sensore BME280
const int soilPin = A0;    // Sensore Umidità Suolo analogico
const int relayPin = 7;    // Modulo Relay per la Pompa
const int bh1750Addr = 0x23; // Indirizzo I2C per GY-302

void setup() {
  Serial.begin(9600);
  pinMode(relayPin, OUTPUT);
  digitalWrite(relayPin, HIGH); 

  if (!bme.begin(0x76)) {
    Serial.println("Errore BME280!");
  }
  
  Wire.begin();
}

void loop() {
  // Lettura BME280
  float temperatura = bme.readTemperature();
  float umidita = bme.readHumidity();
  float pressione = bme.readPressure() / 100.0F; //trasformazione da Pa -> hPa

  // Lettura Umidità Suolo (Capacitivo)
  int soilVal = analogRead(soilPin);

  // Lettura Luce (GY-302) semplice
  int lux = readBH1750();

  // Invio dati formattati per il ponte Python
  // Formato: temp|hum|pres|soil|lux
  Serial.print(temperatura); Serial.print("|");
  Serial.print(umidita);  Serial.print("|");
  Serial.print(pressione); Serial.print("|");
  Serial.print(soilVal); Serial.print("|");
  Serial.println(lux);

  delay(5000); 
}

int readBH1750() {
  int val = 0;
  Wire.beginTransmission(bh1750Addr);//"Bussa" alla porta del sensore (indirizzo 0x23)
  Wire.requestFrom(bh1750Addr, 2);//Chiede al sensore di inviargli 2 byte di dati (la misura della luce)
  while (Wire.available()) {
    val = (val << 8) | Wire.read();//Prende i pezzetti di dati e li riassembla in un numero unico
  }
  return val;
}