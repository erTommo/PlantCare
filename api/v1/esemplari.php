<?php
require_once __DIR__ . '/../../config/db.php';
setHeaders();
$userId = richiediAuth();
$db     = getDB();
$method = $_SERVER['REQUEST_METHOD'];
$id     = isset($_GET['id']) ? (int) $_GET['id'] : null;

function getUltimeRilevazioni($db, $esemplareId) {
    $tipi   = ['Temperatura_Aria', 'Umidita_Aria', 'Umidita_Suolo', 'Luminosita', 'pH'];
    $result = [];
    foreach ($tipi as $tipo) {
        $stmt = $db->prepare('SELECT Valore, Data_Ora_Rilevazione FROM Rilevazioni_Sensori WHERE ID_Esemplare = ? AND Tipo_Misurazione = ? ORDER BY Data_Ora_Rilevazione DESC LIMIT 1');
        $stmt->bind_param('is', $esemplareId, $tipo);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if ($row) $result[$tipo] = ['valore' => (float) $row['Valore'], 'timestamp' => $row['Data_Ora_Rilevazione']];
    }
    return $result;
}

function calcolaStato($plant) {
    $r      = $plant['ultime_rilevazioni'];
    $status = 'ok';
    $checks = [
        ['key' => 'Temperatura_Aria', 'min' => (float) ($plant['Temp_Ideale_Min'] ?? -INF), 'max' => (float) ($plant['Temp_Ideale_Max'] ?? INF)],
        ['key' => 'Umidita_Suolo',    'min' => (float) ($plant['Umidita_Suolo_Min'] ?? -INF), 'max' => (float) ($plant['Umidita_Suolo_Max'] ?? INF)],
    ];
    foreach ($checks as $c) {
        if (!isset($r[$c['key']])) continue;
        $v = $r[$c['key']]['valore'];
        if ($v < $c['min'] || $v > $c['max']) {
            $range  = $c['max'] - $c['min'];
            $gap    = min(abs($v - $c['min']), abs($v - $c['max']));
            $status = ($range > 0 && $gap / $range > 0.2) ? 'alert' : 'warn';
            break;
        }
    }
    return $status;
}

if ($method === 'GET') {
    if ($id) {
        $stmt = $db->prepare('SELECT e.*, s.Nome_Comune, s.Nome_Scientifico, s.Temp_Ideale_Min, s.Temp_Ideale_Max, s.Umidita_Suolo_Min, s.Umidita_Suolo_Max, s.Luce_Ideale_Min, s.Luce_Ideale_Max, s.Tossica_Per_Animali, s.Foto_Default_URL FROM Esemplari_Piante e JOIN Specie_Botaniche s ON e.ID_Specie = s.ID_Specie WHERE e.ID_Esemplare = ? AND e.ID_Utente = ? LIMIT 1');
        $stmt->bind_param('ii', $id, $userId);
        $stmt->execute();
        $plant = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$plant) errore('Pianta non trovata.', 404);
        $plant['ultime_rilevazioni'] = getUltimeRilevazioni($db, $id);
        $plant['stato']              = calcolaStato($plant);
        risposta($plant);
    }
    $stmt = $db->prepare('SELECT e.ID_Esemplare, e.ID_Utente, e.ID_Specie, e.Soprannome, e.Data_Aggiunta, e.Foto_Attuale_URL, s.Nome_Comune, s.Nome_Scientifico, s.Foto_Default_URL, s.Temp_Ideale_Min, s.Temp_Ideale_Max, s.Umidita_Suolo_Min, s.Umidita_Suolo_Max, s.Luce_Ideale_Min, s.Luce_Ideale_Max, s.Tossica_Per_Animali FROM Esemplari_Piante e JOIN Specie_Botaniche s ON e.ID_Specie = s.ID_Specie WHERE e.ID_Utente = ? ORDER BY e.Data_Aggiunta DESC');
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $plants = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    foreach ($plants as &$p) {
        $p['ultime_rilevazioni'] = getUltimeRilevazioni($db, (int) $p['ID_Esemplare']);
        $p['stato']              = calcolaStato($p);
    }
    risposta($plants);
}

if ($method === 'POST') {
    $body     = getBody();
    $idSpecie = (int) ($body['id_specie'] ?? 0);
    if (!$idSpecie) errore('id_specie è obbligatorio.', 422);
    $chk = $db->prepare('SELECT ID_Specie FROM Specie_Botaniche WHERE ID_Specie = ?');
    $chk->bind_param('i', $idSpecie);
    $chk->execute();
    $chk->store_result();
    if ($chk->num_rows === 0) { $chk->close(); errore('Specie non trovata.', 404); }
    $chk->close();
    $soprannome   = $body['soprannome']       ?? null;
    $dataAggiunta = $body['data_aggiunta']    ?? date('Y-m-d');
    $fotoUrl      = $body['foto_attuale_url'] ?? null;
    $stmt = $db->prepare('INSERT INTO Esemplari_Piante (ID_Utente, ID_Specie, Soprannome, Data_Aggiunta, Foto_Attuale_URL) VALUES (?, ?, ?, ?, ?)');
    $stmt->bind_param('iisss', $userId, $idSpecie, $soprannome, $dataAggiunta, $fotoUrl);
    $stmt->execute();
    $newId = (int) $db->insert_id;
    $stmt->close();
    $stmt = $db->prepare('SELECT e.*, s.Nome_Comune, s.Nome_Scientifico, s.Temp_Ideale_Min, s.Temp_Ideale_Max, s.Umidita_Suolo_Min, s.Umidita_Suolo_Max, s.Luce_Ideale_Min, s.Luce_Ideale_Max, s.Tossica_Per_Animali, s.Foto_Default_URL FROM Esemplari_Piante e JOIN Specie_Botaniche s ON e.ID_Specie = s.ID_Specie WHERE e.ID_Esemplare = ? AND e.ID_Utente = ? LIMIT 1');
    $stmt->bind_param('ii', $newId, $userId);
    $stmt->execute();
    $plant = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $plant['ultime_rilevazioni'] = [];
    $plant['stato']              = 'ok';
    risposta($plant, 'Pianta aggiunta con successo.', 201);
}

if ($method === 'PUT') {
    if (!$id) errore('ID esemplare mancante.', 400);
    $body       = getBody();
    $soprannome = $body['soprannome']       ?? null;
    $fotoUrl    = $body['foto_attuale_url'] ?? null;
    $stmt = $db->prepare('UPDATE Esemplari_Piante SET Soprannome = ?, Foto_Attuale_URL = ? WHERE ID_Esemplare = ? AND ID_Utente = ?');
    $stmt->bind_param('ssii', $soprannome, $fotoUrl, $id, $userId);
    $stmt->execute();
    $stmt->close();
    $stmt = $db->prepare('SELECT e.*, s.Nome_Comune, s.Nome_Scientifico, s.Temp_Ideale_Min, s.Temp_Ideale_Max, s.Umidita_Suolo_Min, s.Umidita_Suolo_Max, s.Luce_Ideale_Min, s.Luce_Ideale_Max, s.Tossica_Per_Animali, s.Foto_Default_URL FROM Esemplari_Piante e JOIN Specie_Botaniche s ON e.ID_Specie = s.ID_Specie WHERE e.ID_Esemplare = ? AND e.ID_Utente = ? LIMIT 1');
    $stmt->bind_param('ii', $id, $userId);
    $stmt->execute();
    $plant = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$plant) errore('Pianta non trovata o non autorizzato.', 404);
    $plant['ultime_rilevazioni'] = getUltimeRilevazioni($db, $id);
    $plant['stato']              = calcolaStato($plant);
    risposta($plant, 'Pianta aggiornata.');
}

if ($method === 'DELETE') {
    if (isset($_GET['all']) && $_GET['all'] === '1') {
        $stmt = $db->prepare('DELETE FROM Esemplari_Piante WHERE ID_Utente = ?');
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $count = $stmt->affected_rows;
        $stmt->close();
        risposta(['eliminate' => $count], "Eliminate {$count} piante.");
    }
    if (!$id) errore('ID esemplare mancante.', 400);
    $stmt = $db->prepare('DELETE FROM Esemplari_Piante WHERE ID_Esemplare = ? AND ID_Utente = ?');
    $stmt->bind_param('ii', $id, $userId);
    $stmt->execute();
    if ($stmt->affected_rows === 0) { $stmt->close(); errore('Pianta non trovata o non autorizzato.', 404); }
    $stmt->close();
    risposta(null, 'Pianta eliminata.');
}

errore('Metodo non consentito.', 405);
