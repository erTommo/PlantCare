<?php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "PlantCareDB";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) { die("Errore: " . $conn->connect_error); }

// RECUPERO DATI DA PontePyton.py
$id_esemplare = $_POST['id_esemplare']; 
$tipo         = $_POST['tipo'];
$valore       = (float)$_POST['valore'];

// SALVATAGGIO DELLA RILEVAZIONE  NELLA TABELLA DEL DB
$stmt = $conn->prepare("INSERT INTO Rilevazioni_Sensori (ID_Esemplare, Tipo_Misurazione, Valore) VALUES (?, ?, ?)");
$stmt->bind_param("isd", $id_esemplare, $tipo, $valore);
$stmt->execute();
$stmt->close();

// Recuperiamo le soglie ideali per questa specifica pianta per poi confrontali con quelli ricevuti dal sensore ed identificare gli stati di allarme
$sql_soglie = "SELECT s.Temp_Ideale_Min, s.Temp_Ideale_Max, s.Umidita_Suolo_Min, s.Umidita_Suolo_Max, s.Luce_Ideale_Min 
               FROM Esemplari_Piante e 
               JOIN Specie_Botaniche s ON e.ID_Specie = s.ID_Specie 
               WHERE e.ID_Esemplare = ?";

$stmt_s = $conn->prepare($sql_soglie);
$stmt_s->bind_param("i", $id_esemplare);
$stmt_s->execute();
$result = $stmt_s->get_result();
$soglie = $result->fetch_assoc();

$tipo_allarme = null;

// Confronto basato sul tipo di sensore ricevuto
if ($tipo == 'Temperatura_Aria') {
    if ($valore < $soglie['Temp_Ideale_Min']) $tipo_allarme = 'Troppo_Freddo';
    elseif ($valore > $soglie['Temp_Ideale_Max']) $tipo_allarme = 'Troppo_Caldo';
} 
elseif ($tipo == 'Umidita_Suolo') {
    if ($valore > $soglie['Umidita_Suolo_Min']) $tipo_allarme = 'Troppo_Secco'; 
}
elseif ($tipo == 'Luminosita') {
    if ($valore < $soglie['Luce_Ideale_Min']) $tipo_allarme = 'Poca_Luce';
}

// SCRITTURA DELL'ALLARME (Se necessario)
if ($tipo_allarme) {
    // Controllo che si puo inserire se l'ultimo allarme per questa pianta è uguale ed è recente (< 1 ora)
    $stmt_a = $conn->prepare("INSERT INTO Eventi_Allarme (ID_Esemplare, Tipo_Allarme, Valore_Rilevato) VALUES (?, ?, ?)");
    $stmt_a->bind_param("isd", $id_esemplare, $tipo_allarme, $valore);
    $stmt_a->execute();
    $stmt_a->close();
    echo "Allarme generato: $tipo_allarme!";
}

echo " Dati salvati correttamente.";
$conn->close();
?>