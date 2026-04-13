<?php
require_once __DIR__ . '/config/db.php';
header('Content-Type: text/plain; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo 'ERRORE: Metodo non consentito.';
    exit;
}

$idEsemplare = (int) ($_POST['id_esemplare'] ?? 0);
$tipo        = trim($_POST['tipo'] ?? '');
$valore      = $_POST['valore'] ?? null;

if ($idEsemplare <= 0 || !$tipo || $valore === null) {
    http_response_code(400);
    echo 'ERRORE: Parametri mancanti.';
    exit;
}

$tipiValidi = ['Temperatura_Aria', 'Umidita_Aria', 'Umidita_Suolo', 'Luminosita', 'pH'];
if (!in_array($tipo, $tipiValidi)) {
    http_response_code(422);
    echo 'ERRORE: Tipo non valido.';
    exit;
}

$db  = getDB();
$val = (float) $valore;

$chk = $db->prepare('SELECT ID_Esemplare FROM Esemplari_Piante WHERE ID_Esemplare = ? LIMIT 1');
$chk->bind_param('i', $idEsemplare);
$chk->execute();
$chk->store_result();
if ($chk->num_rows === 0) {
    $chk->close();
    http_response_code(404);
    echo 'ERRORE: Esemplare non trovato.';
    exit;
}
$chk->close();

$stmt = $db->prepare('INSERT INTO Rilevazioni_Sensori (ID_Esemplare, Tipo_Misurazione, Valore) VALUES (?, ?, ?)');
$stmt->bind_param('isd', $idEsemplare, $tipo, $val);
$stmt->execute();
$stmt->close();

$stmt2 = $db->prepare('SELECT s.Temp_Ideale_Min, s.Temp_Ideale_Max, s.Umidita_Suolo_Min, s.Umidita_Suolo_Max, s.Luce_Ideale_Min, s.Luce_Ideale_Max FROM Esemplari_Piante e JOIN Specie_Botaniche s ON e.ID_Specie = s.ID_Specie WHERE e.ID_Esemplare = ? LIMIT 1');
$stmt2->bind_param('i', $idEsemplare);
$stmt2->execute();
$soglie = $stmt2->get_result()->fetch_assoc();
$stmt2->close();

if ($soglie) {
    $tipoAllarme = null;
    if ($tipo === 'Umidita_Suolo') {
        if ($soglie['Umidita_Suolo_Min'] !== null && $val < $soglie['Umidita_Suolo_Min']) $tipoAllarme = 'Troppo_Secco';
        elseif ($soglie['Umidita_Suolo_Max'] !== null && $val > $soglie['Umidita_Suolo_Max']) $tipoAllarme = 'Troppo_Umido';
    } elseif ($tipo === 'Temperatura_Aria') {
        if ($soglie['Temp_Ideale_Min'] !== null && $val < $soglie['Temp_Ideale_Min']) $tipoAllarme = 'Troppo_Freddo';
        elseif ($soglie['Temp_Ideale_Max'] !== null && $val > $soglie['Temp_Ideale_Max']) $tipoAllarme = 'Troppo_Caldo';
    } elseif ($tipo === 'Luminosita') {
        if ($soglie['Luce_Ideale_Min'] !== null && $val < $soglie['Luce_Ideale_Min']) $tipoAllarme = 'Poca_Luce';
    }

    if ($tipoAllarme) {
        $dup = $db->prepare('SELECT ID_Allarme FROM Eventi_Allarme WHERE ID_Esemplare = ? AND Tipo_Allarme = ? AND Data_Ora >= NOW() - INTERVAL 30 MINUTE LIMIT 1');
        $dup->bind_param('is', $idEsemplare, $tipoAllarme);
        $dup->execute();
        $dup->store_result();
        if ($dup->num_rows === 0) {
            $dup->close();
            $ins = $db->prepare('INSERT INTO Eventi_Allarme (ID_Esemplare, Tipo_Allarme, Valore_Rilevato) VALUES (?, ?, ?)');
            $ins->bind_param('isd', $idEsemplare, $tipoAllarme, $val);
            $ins->execute();
            $ins->close();
        } else {
            $dup->close();
        }
    }
}

if ($tipo === 'Umidita_Suolo') {
    $check = $db->prepare('SELECT ID_Allarme FROM Eventi_Allarme WHERE ID_Esemplare = ? AND Tipo_Allarme = "Troppo_Secco" AND Data_Ora >= NOW() - INTERVAL 5 MINUTE LIMIT 1');
    $check->bind_param('i', $idEsemplare);
    $check->execute();
    $check->store_result();
    if ($check->num_rows > 0) {
        $check->close();
        echo 'Troppo_Secco';
        exit;
    }
    $check->close();
}

echo 'OK';
