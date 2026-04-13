<?php
require_once __DIR__ . '/../../config/db.php';
setHeaders();
$userId = richiediAuth();
$db     = getDB();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') errore('Metodo non consentito.', 405);

$stmt = $db->prepare('SELECT COUNT(*) FROM Esemplari_Piante WHERE ID_Utente = ?');
$stmt->bind_param('i', $userId);
$stmt->execute();
$stmt->bind_result($piante);
$stmt->fetch();
$stmt->close();

$stmt = $db->prepare('SELECT COUNT(*) FROM Eventi_Allarme a JOIN Esemplari_Piante e ON a.ID_Esemplare = e.ID_Esemplare WHERE e.ID_Utente = ?');
$stmt->bind_param('i', $userId);
$stmt->execute();
$stmt->bind_result($allarmi);
$stmt->fetch();
$stmt->close();

$stmt = $db->prepare('SELECT COUNT(*) FROM Eventi_Allarme a JOIN Esemplari_Piante e ON a.ID_Esemplare = e.ID_Esemplare WHERE e.ID_Utente = ? AND a.Letto_Da_Utente = 0');
$stmt->bind_param('i', $userId);
$stmt->execute();
$stmt->bind_result($allarmiNonLetti);
$stmt->fetch();
$stmt->close();

$stmt = $db->prepare('SELECT COUNT(*) FROM Rilevazioni_Sensori rs JOIN Esemplari_Piante ep ON rs.ID_Esemplare = ep.ID_Esemplare WHERE ep.ID_Utente = ?');
$stmt->bind_param('i', $userId);
$stmt->execute();
$stmt->bind_result($rilevazioni);
$stmt->fetch();
$stmt->close();

risposta([
    'totale_piante'      => (int) $piante,
    'totale_allarmi'     => (int) $allarmi,
    'allarmi_non_letti'  => (int) $allarmiNonLetti,
    'totale_rilevazioni' => (int) $rilevazioni,
]);
