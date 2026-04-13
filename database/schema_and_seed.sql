CREATE DATABASE IF NOT EXISTS PlantCareDB
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE PlantCareDB;

CREATE TABLE IF NOT EXISTS Utenti (
    ID_Utente     INT AUTO_INCREMENT PRIMARY KEY,
    Nome          VARCHAR(100)  NOT NULL,
    Email         VARCHAR(150)  NOT NULL UNIQUE,
    Password_Hash VARCHAR(255)  NOT NULL,
    Data_Iscrizione TIMESTAMP   DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS Specie_Botaniche (
    ID_Specie          INT AUTO_INCREMENT PRIMARY KEY,
    Nome_Comune        VARCHAR(100)   NOT NULL,
    Nome_Scientifico   VARCHAR(100),
    Temp_Ideale_Min    DECIMAL(4,1),
    Temp_Ideale_Max    DECIMAL(4,1),
    Umidita_Suolo_Min  INT,
    Umidita_Suolo_Max  INT,
    Luce_Ideale_Min    INT,
    Luce_Ideale_Max    INT,
    Tossica_Per_Animali BOOLEAN       DEFAULT FALSE,
    Foto_Default_URL   VARCHAR(255)
);

CREATE TABLE IF NOT EXISTS Esemplari_Piante (
    ID_Esemplare   INT AUTO_INCREMENT PRIMARY KEY,
    ID_Utente      INT     NOT NULL,
    ID_Specie      INT     NOT NULL,
    Soprannome     VARCHAR(100),
    Data_Aggiunta  DATE,
    Foto_Attuale_URL VARCHAR(255),
    FOREIGN KEY (ID_Utente) REFERENCES Utenti(ID_Utente)          ON DELETE CASCADE,
    FOREIGN KEY (ID_Specie) REFERENCES Specie_Botaniche(ID_Specie) ON DELETE RESTRICT
);

CREATE TABLE IF NOT EXISTS Rilevazioni_Sensori (
    ID_Rilevazione       BIGINT AUTO_INCREMENT PRIMARY KEY,
    ID_Esemplare         INT    NOT NULL,
    Tipo_Misurazione     ENUM('Temperatura_Aria','Umidita_Aria','Umidita_Suolo','Luminosita','pH') NOT NULL,
    Valore               DECIMAL(10,2) NOT NULL,
    Data_Ora_Rilevazione TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_esemplare_data (ID_Esemplare, Data_Ora_Rilevazione),
    FOREIGN KEY (ID_Esemplare) REFERENCES Esemplari_Piante(ID_Esemplare) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS Eventi_Allarme (
    ID_Allarme       INT AUTO_INCREMENT PRIMARY KEY,
    ID_Esemplare     INT NOT NULL,
    Tipo_Allarme     ENUM('Troppo_Secco','Troppo_Umido','Troppo_Caldo','Troppo_Freddo','Poca_Luce') NOT NULL,
    Data_Ora         TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    Letto_Da_Utente  BOOLEAN   DEFAULT FALSE,
    Valore_Rilevato  DECIMAL(10,2),
    FOREIGN KEY (ID_Esemplare) REFERENCES Esemplari_Piante(ID_Esemplare) ON DELETE CASCADE
);

INSERT INTO Specie_Botaniche
    (Nome_Comune, Nome_Scientifico, Temp_Ideale_Min, Temp_Ideale_Max, Umidita_Suolo_Min, Umidita_Suolo_Max, Luce_Ideale_Min, Luce_Ideale_Max, Tossica_Per_Animali)
VALUES
    ('Cactus',       'Cactaceae',                    15.0, 35.0,  15,  40, 3000, 8000, FALSE),
    ('Monstera',     'Monstera deliciosa',            18.0, 28.0,  50,  80,  500, 2500, TRUE),
    ('Ficus',        'Ficus lyrata',                  15.0, 28.0,  40,  70,  500, 3000, TRUE),
    ('Orchidea',     'Phalaenopsis spp.',             18.0, 28.0,  30,  60,  800, 2000, FALSE),
    ('Pothos',       'Epipremnum aureum',             15.0, 30.0,  40,  70,  200, 2000, TRUE),
    ('Aloe Vera',    'Aloe vera',                     18.0, 35.0,  20,  50, 4000, 8000, FALSE),
    ('Basilico',     'Ocimum basilicum',              18.0, 32.0,  50,  80, 3000, 8000, FALSE),
    ('Margherita',   'Bellis perennis',               10.0, 25.0,  50,  90, 1500, 5000, FALSE),
    ('Lavanda',      'Lavandula angustifolia',        10.0, 30.0,  20,  50, 4000, 8000, FALSE),
    ('Rosmarino',    'Salvia rosmarinus',             10.0, 30.0,  30,  60, 3000, 7000, FALSE),
    ('Pothos Dorato','Epipremnum aureum aureum',      15.0, 30.0,  40,  70,  200, 2500, TRUE),
    ('Sansevieria',  'Dracaena trifasciata',          15.0, 35.0,  10,  40,  300, 3000, TRUE),
    ('Geranio',      'Pelargonium × hortorum',        10.0, 25.0,  40,  70, 2000, 6000, FALSE),
    ('Felce',        'Nephrolepis exaltata',          16.0, 24.0,  60,  90,  300, 1500, FALSE),
    ('Clivia',       'Clivia miniata',                10.0, 25.0,  30,  60,  500, 2000, FALSE);

INSERT INTO Utenti (Nome, Email, Password_Hash)
VALUES ('Demo PlantCare', 'demo@plantcare.it',
        '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');
