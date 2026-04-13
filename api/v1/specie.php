<?php
require_once __DIR__ . '/../../config/db.php';
setHeaders();
$db     = getDB();
$method = $_SERVER['REQUEST_METHOD'];
$id     = isset($_GET['id']) ? (int) $_GET['id'] : null;

if ($method === 'GET') {
    if (isset($_GET['q'])) {
        $like = '%' . trim($_GET['q']) . '%';
        $stmt = $db->prepare('SELECT * FROM Specie_Botaniche WHERE Nome_Comune LIKE ? OR Nome_Scientifico LIKE ? ORDER BY Nome_Comune ASC');
        $stmt->bind_param('ss', $like, $like);
        $stmt->execute();
        risposta($stmt->get_result()->fetch_all(MYSQLI_ASSOC));
    }
    if ($id) {
        $stmt = $db->prepare('SELECT * FROM Specie_Botaniche WHERE ID_Specie = ? LIMIT 1');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$row) errore('Specie non trovata.', 404);
        risposta($row);
    }
    $result = $db->query('SELECT * FROM Specie_Botaniche ORDER BY Nome_Comune ASC');
    risposta($result->fetch_all(MYSQLI_ASSOC));
}

if ($method === 'POST') {
    richiediAuth();
    $body = getBody();
    if (empty($body['nome_comune'])) errore('Il campo nome_comune è obbligatorio.', 422);
    $nc   = $body['nome_comune'];
    $ns   = $body['nome_scientifico']  ?? null;
    $tmin = $body['temp_ideale_min']   ?? null;
    $tmax = $body['temp_ideale_max']   ?? null;
    $umin = $body['umidita_suolo_min'] ?? null;
    $umax = $body['umidita_suolo_max'] ?? null;
    $lmin = $body['luce_ideale_min']   ?? null;
    $lmax = $body['luce_ideale_max']   ?? null;
    $toss = isset($body['tossica_per_animali']) ? (int) $body['tossica_per_animali'] : 0;
    $foto = $body['foto_default_url']  ?? null;
    $stmt = $db->prepare('INSERT INTO Specie_Botaniche (Nome_Comune, Nome_Scientifico, Temp_Ideale_Min, Temp_Ideale_Max, Umidita_Suolo_Min, Umidita_Suolo_Max, Luce_Ideale_Min, Luce_Ideale_Max, Tossica_Per_Animali, Foto_Default_URL) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
    $stmt->bind_param('ssddddddis', $nc, $ns, $tmin, $tmax, $umin, $umax, $lmin, $lmax, $toss, $foto);
    $stmt->execute();
    $newId = (int) $db->insert_id;
    $stmt->close();
    $stmt = $db->prepare('SELECT * FROM Specie_Botaniche WHERE ID_Specie = ?');
    $stmt->bind_param('i', $newId);
    $stmt->execute();
    risposta($stmt->get_result()->fetch_assoc(), 'Specie aggiunta.', 201);
}

if ($method === 'PUT') {
    richiediAuth();
    if (!$id) errore('ID specie mancante.', 400);
    $body = getBody();
    if (empty($body['nome_comune'])) errore('Il campo nome_comune è obbligatorio.', 422);
    $nc   = $body['nome_comune'];
    $ns   = $body['nome_scientifico']  ?? null;
    $tmin = $body['temp_ideale_min']   ?? null;
    $tmax = $body['temp_ideale_max']   ?? null;
    $umin = $body['umidita_suolo_min'] ?? null;
    $umax = $body['umidita_suolo_max'] ?? null;
    $lmin = $body['luce_ideale_min']   ?? null;
    $lmax = $body['luce_ideale_max']   ?? null;
    $toss = isset($body['tossica_per_animali']) ? (int) $body['tossica_per_animali'] : 0;
    $foto = $body['foto_default_url']  ?? null;
    $stmt = $db->prepare('UPDATE Specie_Botaniche SET Nome_Comune=?, Nome_Scientifico=?, Temp_Ideale_Min=?, Temp_Ideale_Max=?, Umidita_Suolo_Min=?, Umidita_Suolo_Max=?, Luce_Ideale_Min=?, Luce_Ideale_Max=?, Tossica_Per_Animali=?, Foto_Default_URL=? WHERE ID_Specie=?');
    $stmt->bind_param('ssddddddisi', $nc, $ns, $tmin, $tmax, $umin, $umax, $lmin, $lmax, $toss, $foto, $id);
    $stmt->execute();
    if ($stmt->affected_rows === 0) { $stmt->close(); errore('Specie non trovata.', 404); }
    $stmt->close();
    $stmt = $db->prepare('SELECT * FROM Specie_Botaniche WHERE ID_Specie = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    risposta($stmt->get_result()->fetch_assoc(), 'Specie aggiornata.');
}

if ($method === 'DELETE') {
    richiediAuth();
    if (!$id) errore('ID specie mancante.', 400);
    $stmt = $db->prepare('DELETE FROM Specie_Botaniche WHERE ID_Specie = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    if ($stmt->affected_rows === 0) { $stmt->close(); errore('Specie non trovata.', 404); }
    $stmt->close();
    risposta(null, 'Specie eliminata.');
}

errore('Metodo non consentito.', 405);
