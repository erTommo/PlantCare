<?php
require_once __DIR__ . '/../../config/db.php';
setHeaders();
$userId = richiediAuth();
$db     = getDB();
$method = $_SERVER['REQUEST_METHOD'];
$id     = isset($_GET['id']) ? (int) $_GET['id'] : null;

if ($method === 'GET') {
    if (isset($_GET['badge'])) {
        $stmt = $db->prepare('SELECT COUNT(*) FROM Eventi_Allarme a JOIN Esemplari_Piante e ON a.ID_Esemplare = e.ID_Esemplare WHERE e.ID_Utente = ? AND a.Letto_Da_Utente = 0');
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $stmt->bind_result($count);
        $stmt->fetch();
        $stmt->close();
        risposta(['non_letti' => (int) $count]);
    }

    if (isset($_GET['stats'])) {
        $stmt = $db->prepare('SELECT a.Tipo_Allarme, COUNT(*) AS totale FROM Eventi_Allarme a JOIN Esemplari_Piante e ON a.ID_Esemplare = e.ID_Esemplare WHERE e.ID_Utente = ? GROUP BY a.Tipo_Allarme ORDER BY totale DESC');
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        risposta($stmt->get_result()->fetch_all(MYSQLI_ASSOC));
    }

    if (isset($_GET['id_esemplare'])) {
        $esemplareId = (int) $_GET['id_esemplare'];
        $stmt = $db->prepare('SELECT a.* FROM Eventi_Allarme a JOIN Esemplari_Piante e ON a.ID_Esemplare = e.ID_Esemplare WHERE a.ID_Esemplare = ? AND e.ID_Utente = ? ORDER BY a.Data_Ora DESC');
        $stmt->bind_param('ii', $esemplareId, $userId);
        $stmt->execute();
        risposta($stmt->get_result()->fetch_all(MYSQLI_ASSOC));
    }

    $stmt = $db->prepare('SELECT a.ID_Allarme, a.ID_Esemplare, a.Tipo_Allarme, a.Data_Ora, a.Letto_Da_Utente, a.Valore_Rilevato, e.Soprannome, s.Nome_Comune, s.Nome_Scientifico FROM Eventi_Allarme a JOIN Esemplari_Piante e ON a.ID_Esemplare = e.ID_Esemplare JOIN Specie_Botaniche s ON e.ID_Specie = s.ID_Specie WHERE e.ID_Utente = ? ORDER BY a.Letto_Da_Utente ASC, a.Data_Ora DESC');
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    risposta($stmt->get_result()->fetch_all(MYSQLI_ASSOC));
}

if ($method === 'PUT') {
    if (isset($_GET['all'])) {
        $stmt = $db->prepare('UPDATE Eventi_Allarme a JOIN Esemplari_Piante e ON a.ID_Esemplare = e.ID_Esemplare SET a.Letto_Da_Utente = 1 WHERE e.ID_Utente = ? AND a.Letto_Da_Utente = 0');
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $count = $stmt->affected_rows;
        $stmt->close();
        risposta(['aggiornati' => $count], "{$count} allarmi segnati come letti.");
    }
    if (!$id) errore('ID allarme mancante.', 400);
    $stmt = $db->prepare('UPDATE Eventi_Allarme a JOIN Esemplari_Piante e ON a.ID_Esemplare = e.ID_Esemplare SET a.Letto_Da_Utente = 1 WHERE a.ID_Allarme = ? AND e.ID_Utente = ?');
    $stmt->bind_param('ii', $id, $userId);
    $stmt->execute();
    if ($stmt->affected_rows === 0) { $stmt->close(); errore('Allarme non trovato.', 404); }
    $stmt->close();
    risposta(null, 'Allarme segnato come letto.');
}

if ($method === 'DELETE') {
    if (isset($_GET['all'])) {
        $stmt = $db->prepare('DELETE a FROM Eventi_Allarme a JOIN Esemplari_Piante e ON a.ID_Esemplare = e.ID_Esemplare WHERE e.ID_Utente = ?');
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $count = $stmt->affected_rows;
        $stmt->close();
        risposta(['eliminati' => $count], "{$count} allarmi eliminati.");
    }
    if (!$id) errore('ID allarme mancante.', 400);
    $stmt = $db->prepare('DELETE a FROM Eventi_Allarme a JOIN Esemplari_Piante e ON a.ID_Esemplare = e.ID_Esemplare WHERE a.ID_Allarme = ? AND e.ID_Utente = ?');
    $stmt->bind_param('ii', $id, $userId);
    $stmt->execute();
    if ($stmt->affected_rows === 0) { $stmt->close(); errore('Allarme non trovato.', 404); }
    $stmt->close();
    risposta(null, 'Allarme eliminato.');
}

errore('Metodo non consentito.', 405);
