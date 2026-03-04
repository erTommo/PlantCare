-- Creazione del database
CREATE DATABASE IF NOT EXISTS PlantCareDB;
USE PlantCareDB;

-- UTENTI
CREATE TABLE Utenti (
    ID_Utente INT AUTO_INCREMENT PRIMARY KEY,
    Nome VARCHAR(100) NOT NULL,
    Email VARCHAR(150) NOT NULL UNIQUE,
    Password_Hash VARCHAR(255) NOT NULL,
    Data_Iscrizione TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- SPECIE BOTANICHE (Con Soglie di Allarme per i Grafici)
-- Questi dati servono a disegnare le linee di "minimo" e "massimo" sui grafici
CREATE TABLE Specie_Botaniche (
    ID_Specie INT AUTO_INCREMENT PRIMARY KEY,
    Nome_Comune VARCHAR(100) NOT NULL,
    Nome_Scientifico VARCHAR(100),
    
    -- Parametri Ideali (Range di Benessere)
    Temp_Ideale_Min DECIMAL(4,1), -- Gradi Celsius (es. 18.0)
    Temp_Ideale_Max DECIMAL(4,1), -- Gradi Celsius (es. 28.0)
    
    Umidita_Suolo_Min INT, -- Percentuale % (es. 30%)
    Umidita_Suolo_Max INT, -- Percentuale % (es. 60%)
    
    Luce_Ideale_Min INT, -- Lux (es. 1000)
    Luce_Ideale_Max INT, -- Lux (es. 5000)
    
    Tossica_Per_Animali BOOLEAN DEFAULT FALSE,
    Foto_Default_URL VARCHAR(255)
);

-- ESEMPLARI PIANTE
CREATE TABLE Esemplari_Piante (
    ID_Esemplare INT AUTO_INCREMENT PRIMARY KEY,
    ID_Utente INT NOT NULL,
    ID_Specie INT NOT NULL,
    Soprannome VARCHAR(100), -- es. "Basilico Smart"
    Data_Aggiunta DATE,
    Foto_Attuale_URL VARCHAR(255),
    
    FOREIGN KEY (ID_Utente) REFERENCES Utenti(ID_Utente) ON DELETE CASCADE,
    FOREIGN KEY (ID_Specie) REFERENCES Specie_Botaniche(ID_Specie) ON DELETE RESTRICT
);

-- RILEVAZIONI SENSORI (Lo Storico per i Grafici)
-- Questa tabella crescerà molto. Qui finiscono i dati grezzi.
CREATE TABLE Rilevazioni_Sensori (
    ID_Rilevazione BIGINT AUTO_INCREMENT PRIMARY KEY,
    ID_Esemplare INT NOT NULL, -- Sostituito ID_Dispositivo con ID_Esemplare
    
    Tipo_Misurazione ENUM('Temperatura_Aria', 'Umidita_Aria', 'Umidita_Suolo', 'Luminosita', 'pH') NOT NULL,
    Valore DECIMAL(10,2) NOT NULL,
    Data_Ora_Rilevazione TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_esemplare_data (ID_Esemplare, Data_Ora_Rilevazione),
    
    -- Colleghiamo alla tabella Esemplari_Piante che hai già definito
    FOREIGN KEY (ID_Esemplare) REFERENCES Esemplari_Piante(ID_Esemplare) ON DELETE CASCADE
);

-- ALLARMI / NOTIFICHE GENERATE
-- Se un sensore rileva un valore fuori soglia, il sistema crea una riga qui
CREATE TABLE Eventi_Allarme (
    ID_Allarme INT AUTO_INCREMENT PRIMARY KEY,
    ID_Esemplare INT NOT NULL,
    Tipo_Allarme ENUM('Troppo_Secco', 'Troppo_Umido', 'Troppo_Caldo', 'Troppo_Freddo', 'Poca_Luce') NOT NULL,
    Data_Ora TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    Letto_Da_Utente BOOLEAN DEFAULT FALSE,
    Valore_Rilevato DECIMAL(10,2), -- Il valore che ha fatto scattare l'allarme
    
    FOREIGN KEY (ID_Esemplare) REFERENCES Esemplari_Piante(ID_Esemplare) ON DELETE CASCADE
);