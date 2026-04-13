<?php
require_once __DIR__ . '/../../config/db.php';
setHeaders();
if (session_status() === PHP_SESSION_NONE) session_start();

$db     = getDB();
$action = $_GET['action'] ?? '';

if ($action === 'login') {
    $body  = getBody();
    $email = trim($body['email'] ?? '');
    $pass  = $body['password'] ?? '';
    if (!$email || !$pass) errore('Email e password obbligatorie.', 422);
    $stmt = $db->prepare('SELECT ID_Utente, Nome, Email, Password_Hash FROM Utenti WHERE Email = ? LIMIT 1');
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$user || !password_verify($pass, $user['Password_Hash'])) errore('Email o password errate.', 401);
    $_SESSION['user_id'] = $user['ID_Utente'];
    unset($user['Password_Hash']);
    risposta($user, 'Login effettuato.');
}

if ($action === 'register') {
    $body  = getBody();
    $nome  = trim($body['nome'] ?? '');
    $email = trim($body['email'] ?? '');
    $pass  = $body['password'] ?? '';
    if (!$nome || !$email || !$pass) errore('Nome, email e password obbligatori.', 422);
    if (strlen($pass) < 8) errore('Password di almeno 8 caratteri.', 422);
    $stmt = $db->prepare('SELECT ID_Utente FROM Utenti WHERE Email = ? LIMIT 1');
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) { $stmt->close(); errore('Email già registrata.', 409); }
    $stmt->close();
    $hash = password_hash($pass, PASSWORD_BCRYPT);
    $stmt = $db->prepare('INSERT INTO Utenti (Nome, Email, Password_Hash) VALUES (?, ?, ?)');
    $stmt->bind_param('sss', $nome, $email, $hash);
    $stmt->execute();
    $newId = (int) $db->insert_id;
    $stmt->close();
    $stmt = $db->prepare('SELECT ID_Utente, Nome, Email, Data_Iscrizione FROM Utenti WHERE ID_Utente = ?');
    $stmt->bind_param('i', $newId);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $_SESSION['user_id'] = $user['ID_Utente'];
    risposta($user, 'Registrazione completata.', 201);
}

if ($action === 'logout') {
    $_SESSION = [];
    session_destroy();
    risposta(null, 'Logout effettuato.');
}

if ($action === 'me') {
    if (empty($_SESSION['user_id'])) errore('Sessione non attiva.', 401);
    $id = (int) $_SESSION['user_id'];
    $stmt = $db->prepare('SELECT ID_Utente, Nome, Email, Data_Iscrizione FROM Utenti WHERE ID_Utente = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$user) errore('Utente non trovato.', 404);
    risposta($user, 'Sessione attiva.');
}

if ($action === 'update_profile') {
    $userId = richiediAuth();
    $body   = getBody();
    $nome   = trim($body['nome'] ?? '');
    $email  = trim($body['email'] ?? '');
    $pass   = isset($body['password']) && $body['password'] !== '' ? $body['password'] : null;
    if (!$nome || !$email) errore('Nome ed email obbligatori.', 422);
    $chkEmail = $db->prepare('SELECT ID_Utente FROM Utenti WHERE Email = ? AND ID_Utente != ? LIMIT 1');
    $chkEmail->bind_param('si', $email, $userId);
    $chkEmail->execute();
    $chkEmail->store_result();
    if ($chkEmail->num_rows > 0) { $chkEmail->close(); errore('Email già in uso da un altro account.', 409); }
    $chkEmail->close();
    if ($pass !== null) {
        if (strlen($pass) < 8) errore('Password di almeno 8 caratteri.', 422);
        $hash = password_hash($pass, PASSWORD_BCRYPT);
        $stmt = $db->prepare('UPDATE Utenti SET Nome = ?, Email = ?, Password_Hash = ? WHERE ID_Utente = ?');
        $stmt->bind_param('sssi', $nome, $email, $hash, $userId);
    } else {
        $stmt = $db->prepare('UPDATE Utenti SET Nome = ?, Email = ? WHERE ID_Utente = ?');
        $stmt->bind_param('ssi', $nome, $email, $userId);
    }
    $stmt->execute();
    $stmt->close();
    $_SESSION['email'] = $email;
    $stmt = $db->prepare('SELECT ID_Utente, Nome, Email, Data_Iscrizione FROM Utenti WHERE ID_Utente = ?');
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    risposta($user, 'Profilo aggiornato.');
}

if ($action === 'delete_account') {
    $userId = richiediAuth();
    $stmt = $db->prepare('DELETE FROM Utenti WHERE ID_Utente = ?');
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $stmt->close();
    $_SESSION = [];
    session_destroy();
    risposta(null, 'Account eliminato.');
}

errore('Azione non riconosciuta.', 404);
