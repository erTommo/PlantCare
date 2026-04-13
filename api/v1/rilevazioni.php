<?php
require_once __DIR__ . '/../../config/db.php';
setHeaders();
$userId = richiediAuth();
$db     = getDB();
$method = $_SERVER['REQUEST_METHOD'];

function verificaAllarme($db, $esemplareId, $tipo, $valore) {
    $stmt = $db->prepare('SELECT s.Temp_Ideale_Min, s.Temp_Ideale_Max, s.Umidita_Suolo_Min, s.Umidita_Suolo_Max, s.Luce_Ideale_Min, s.Luce_Ideale_Max FROM Esemplari_Piante e JOIN Specie_Botaniche s ON e.ID_Specie = s.ID_Specie WHERE e.ID_Esemplare = ? LIMIT 1');
    $stmt->bind_param('i', $esemplareId);
    $stmt->execute();
    $soglie = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$soglie) return;

    $tipoAllarme = null;
    if ($tipo === 'Umidita_Suolo') {
        if ($soglie['Umidita_Suolo_Min'] !== null && $valore < $soglie['Umidita_Suolo_Min']) $tipoAllarme = 'Troppo_Secco';
        elseif ($soglie['Umidita_Suolo_Max'] !== null && $valore > $soglie['Umidita_Suolo_Max']) $tipoAllarme = 'Troppo_Umido';
    } elseif ($tipo === 'Temperatura_Aria') {
        if ($soglie['Temp_Ideale_Min'] !== null && $valore < $soglie['Temp_Ideale_Min']) $tipoAllarme = 'Troppo_Freddo';
        elseif ($soglie['Temp_Ideale_Max'] !== null && $valore > $soglie['Temp_Ideale_Max']) $tipoAllarme = 'Troppo_Caldo';
    } elseif ($tipo === 'Luminosita') {
        if ($soglie['Luce_Ideale_Min'] !== null && $valore < $soglie['Luce_Ideale_Min']) $tipoAllarme = 'Poca_Luce';
    }

    if ($tipoAllarme) {
        $dup = $db->prepare('SELECT ID_Allarme FROM Eventi_Allarme WHERE ID_Esemplare = ? AND Tipo_Allarme = ? AND Data_Ora >= NOW() - INTERVAL 30 MINUTE LIMIT 1');
        $dup->bind_param('is', $esemplareId, $tipoAllarme);
        $dup->execute();
        $dup->store_result();
        if ($dup->num_rows === 0) {
            $dup->close();
            $ins = $db->prepare('INSERT INTO Eventi_Allarme (ID_Esemplare, Tipo_Allarme, Valore_Rilevato) VALUES (?, ?, ?)');
            $ins->bind_param('isd', $esemplareId, $tipoAllarme, $valore);
            $ins->execute();
            $ins->close();
        } else {
            $dup->close();
        }
    }
}

function inserisciRilevazione($db, $esemplareId, $tipo, $valore) {
    $tipiValidi = ['Temperatura_Aria', 'Umidita_Aria', 'Umidita_Suolo', 'Luminosita', 'pH'];
    if (!in_array($tipo, $tipiValidi)) errore('Tipo_Misurazione non valido.', 422);
    $stmt = $db->prepare('INSERT INTO Rilevazioni_Sensori (ID_Esemplare, Tipo_Misurazione, Valore) VALUES (?, ?, ?)');
    $stmt->bind_param('isd', $esemplareId, $tipo, $valore);
    $stmt->execute();
    $newId = (int) $db->insert_id;
    $stmt->close();
    verificaAllarme($db, $esemplareId, $tipo, $valore);
    return $newId;
}

if ($method === 'GET') {
    $esemplareId = (int) ($_GET['id_esemplare'] ?? 0);
    if (!$esemplareId) errore('id_esemplare obbligatorio.', 400);

    $chk = $db->prepare('SELECT ID_Esemplare FROM Esemplari_Piante WHERE ID_Esemplare = ? AND ID_Utente = ? LIMIT 1');
    $chk->bind_param('ii', $esemplareId, $userId);
    $chk->execute();
    $chk->store_result();
    if ($chk->num_rows === 0) { $chk->close(); errore('Pianta non trovata o non autorizzato.', 403); }
    $chk->close();

    if (isset($_GET['stats'])) {
        $stmt = $db->prepare('SELECT Tipo_Misurazione, COUNT(*) AS totale, ROUND(MIN(Valore),2) AS minimo, ROUND(MAX(Valore),2) AS massimo, ROUND(AVG(Valore),2) AS media, MIN(Data_Ora_Rilevazione) AS prima, MAX(Data_Ora_Rilevazione) AS ultima FROM Rilevazioni_Sensori WHERE ID_Esemplare = ? GROUP BY Tipo_Misurazione');
        $stmt->bind_param('i', $esemplareId);
        $stmt->execute();
        risposta($stmt->get_result()->fetch_all(MYSQLI_ASSOC));
    }

    if (isset($_GET['raw'])) {
        $limit = min((int) ($_GET['limit'] ?? 50), 500);
        $stmt  = $db->prepare('SELECT ID_Rilevazione, Tipo_Misurazione, Valore, Data_Ora_Rilevazione FROM Rilevazioni_Sensori WHERE ID_Esemplare = ? ORDER BY Data_Ora_Rilevazione DESC LIMIT ?');
        $stmt->bind_param('ii', $esemplareId, $limit);
        $stmt->execute();
        risposta($stmt->get_result()->fetch_all(MYSQLI_ASSOC));
    }

    $tipo  = $_GET['tipo']  ?? 'Umidita_Suolo';
    $range = $_GET['range'] ?? '24h';
    $tipiValidi = ['Temperatura_Aria', 'Umidita_Aria', 'Umidita_Suolo', 'Luminosita', 'pH'];
    if (!in_array($tipo, $tipiValidi)) errore('Tipo non valido.', 422);

    $interval = match ($range) {
        '1h'  => 'INTERVAL 1 HOUR',
        '6h'  => 'INTERVAL 6 HOUR',
        '7d'  => 'INTERVAL 7 DAY',
        '30d' => 'INTERVAL 30 DAY',
        default => 'INTERVAL 24 HOUR',
    };
    $groupBy = match ($range) {
        '30d' => "DATE_FORMAT(Data_Ora_Rilevazione, '%Y-%m-%d')",
        '7d'  => "DATE_FORMAT(Data_Ora_Rilevazione, '%Y-%m-%d %H:00')",
        default => "DATE_FORMAT(Data_Ora_Rilevazione, '%Y-%m-%d %H:%i')",
    };

    $sql  = "SELECT {$groupBy} AS label, ROUND(AVG(Valore),2) AS valore FROM Rilevazioni_Sensori WHERE ID_Esemplare = ? AND Tipo_Misurazione = ? AND Data_Ora_Rilevazione >= NOW() - {$interval} GROUP BY {$groupBy} ORDER BY label ASC";
    $stmt = $db->prepare($sql);
    $stmt->bind_param('is', $esemplareId, $tipo);
    $stmt->execute();
    risposta($stmt->get_result()->fetch_all(MYSQLI_ASSOC));
}

if ($method === 'POST') {
    $body = getBody();

    if (isset($_GET['batch'])) {
        $esemplareId = (int) ($body['id_esemplare'] ?? 0);
        if (!$esemplareId || empty($body['readings'])) errore('id_esemplare e readings obbligatori.', 400);
        $chk = $db->prepare('SELECT ID_Esemplare FROM Esemplari_Piante WHERE ID_Esemplare = ? AND ID_Utente = ? LIMIT 1');
        $chk->bind_param('ii', $esemplareId, $userId);
        $chk->execute();
        $chk->store_result();
        if ($chk->num_rows === 0) { $chk->close(); errore('Pianta non trovata.', 403); }
        $chk->close();
        $count = 0;
        foreach ($body['readings'] as $r) {
            if (empty($r['tipo']) || !isset($r['valore'])) continue;
            inserisciRilevazione($db, $esemplareId, $r['tipo'], (float) $r['valore']);
            $count++;
        }
        risposta(['inserite' => $count], "{$count} rilevazioni salvate.", 201);
    }

    $esemplareId = (int) ($body['id_esemplare'] ?? 0);
    $tipo        = $body['tipo_misurazione'] ?? '';
    $valore      = isset($body['valore']) ? (float) $body['valore'] : null;
    if (!$esemplareId || !$tipo || $valore === null) errore('id_esemplare, tipo_misurazione e valore obbligatori.', 400);
    $chk = $db->prepare('SELECT ID_Esemplare FROM Esemplari_Piante WHERE ID_Esemplare = ? AND ID_Utente = ? LIMIT 1');
    $chk->bind_param('ii', $esemplareId, $userId);
    $chk->execute();
    $chk->store_result();
    if ($chk->num_rows === 0) { $chk->close(); errore('Pianta non trovata.', 403); }
    $chk->close();
    $newId = inserisciRilevazione($db, $esemplareId, $tipo, $valore);
    risposta(['id_rilevazione' => $newId], 'Rilevazione salvata.', 201);
}

errore('Metodo non consentito.', 405);
