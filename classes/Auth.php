<?php
require_once __DIR__ . '/../config/database.php';

class Auth {
    private mysqli $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    public function login(string $email, string $password): array {
        if (empty($email) || empty($password)) {
            throw new InvalidArgumentException('Email e password obbligatorie.');
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Formato email non valido.');
        }

        $stmt = $this->db->prepare(
            'SELECT ID_Utente, Nome, Email, Password_Hash, Data_Iscrizione
             FROM Utenti WHERE Email = ? LIMIT 1'
        );
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user   = $result->fetch_assoc();
        $stmt->close();

        if (!$user || !password_verify($password, $user['Password_Hash'])) {
            throw new RuntimeException('Email o password errate.');
        }

        $this->_startSession();
        $_SESSION['user_id'] = $user['ID_Utente'];
        $_SESSION['email']   = $user['Email'];

        return $this->_sanitizeUser($user);
    }

    public function register(string $nome, string $email, string $password): array {
        if (empty($nome) || empty($email) || empty($password)) {
            throw new InvalidArgumentException('Nome, email e password obbligatori.');
        }
        if (strlen($nome) > 100) {
            throw new InvalidArgumentException('Il nome non può superare 100 caratteri.');
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Formato email non valido.');
        }
        if (strlen($password) < 8) {
            throw new InvalidArgumentException('La password deve avere almeno 8 caratteri.');
        }

        $stmt = $this->db->prepare('SELECT ID_Utente FROM Utenti WHERE Email = ? LIMIT 1');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $stmt->close();
            throw new RuntimeException('Email già registrata.');
        }
        $stmt->close();

        $hash = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $this->db->prepare(
            'INSERT INTO Utenti (Nome, Email, Password_Hash) VALUES (?, ?, ?)'
        );
        $stmt->bind_param('sss', $nome, $email, $hash);
        $stmt->execute();
        $newId = (int) $this->db->insert_id;
        $stmt->close();

        $stmt = $this->db->prepare(
            'SELECT ID_Utente, Nome, Email, Data_Iscrizione FROM Utenti WHERE ID_Utente = ?'
        );
        $stmt->bind_param('i', $newId);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        $this->_startSession();
        $_SESSION['user_id'] = $user['ID_Utente'];
        $_SESSION['email']   = $user['Email'];

        return $this->_sanitizeUser($user);
    }

    public function logout(): void {
        $this->_startSession();
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params['path'], $params['domain'],
                $params['secure'], $params['httponly']
            );
        }
        session_destroy();
    }

    public function me(): ?array {
        $this->_startSession();
        if (empty($_SESSION['user_id'])) {
            return null;
        }
        $userId = (int) $_SESSION['user_id'];
        $stmt = $this->db->prepare(
            'SELECT ID_Utente, Nome, Email, Data_Iscrizione FROM Utenti WHERE ID_Utente = ?'
        );
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $user ? $this->_sanitizeUser($user) : null;
    }

    public function updateProfile(int $userId, string $nome, string $email, ?string $password = null): array {
        if (empty($nome) || empty($email)) {
            throw new InvalidArgumentException('Nome ed email obbligatori.');
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Formato email non valido.');
        }

        $stmt = $this->db->prepare(
            'SELECT ID_Utente FROM Utenti WHERE Email = ? AND ID_Utente != ? LIMIT 1'
        );
        $stmt->bind_param('si', $email, $userId);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $stmt->close();
            throw new RuntimeException('Email già in uso da un altro account.');
        }
        $stmt->close();

        if ($password !== null) {
            if (strlen($password) < 8) {
                throw new InvalidArgumentException('La password deve avere almeno 8 caratteri.');
            }
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $this->db->prepare(
                'UPDATE Utenti SET Nome = ?, Email = ?, Password_Hash = ? WHERE ID_Utente = ?'
            );
            $stmt->bind_param('sssi', $nome, $email, $hash, $userId);
        } else {
            $stmt = $this->db->prepare(
                'UPDATE Utenti SET Nome = ?, Email = ? WHERE ID_Utente = ?'
            );
            $stmt->bind_param('ssi', $nome, $email, $userId);
        }
        $stmt->execute();
        $stmt->close();

        $_SESSION['email'] = $email;

        $stmt = $this->db->prepare(
            'SELECT ID_Utente, Nome, Email, Data_Iscrizione FROM Utenti WHERE ID_Utente = ?'
        );
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $this->_sanitizeUser($user);
    }
    public function deleteAccount(int $userId): void {
        $stmt = $this->db->prepare('DELETE FROM Utenti WHERE ID_Utente = ?');
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $stmt->close();
        $this->logout();
    }

    public static function requireAuth(): int {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (empty($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['status' => 'ERR', 'message' => 'Non autenticato.', 'data' => null]);
            exit;
        }
        return (int) $_SESSION['user_id'];
    }

    private function _startSession(): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    private function _sanitizeUser(array $user): array {
        unset($user['Password_Hash']);
        return $user;
    }
}
