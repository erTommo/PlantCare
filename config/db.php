<?php
function getDB() {
    static $conn = null;
    if (!$conn) {
        $conn = new mysqli('localhost', 'root', '', 'PlantCareDB');
        if ($conn->connect_error) {
            http_response_code(503);
            echo json_encode(['status' => 'ERR', 'message' => 'Database non raggiungibile.', 'data' => null]);
            exit;
        }
        $conn->set_charset('utf8mb4');
    }
    return $conn;
}

function risposta($data, $msg = 'ok', $code = 200) {
    http_response_code($code);
    echo json_encode(['status' => 'ok', 'message' => $msg, 'data' => $data]);
    exit;
}

function errore($msg, $code = 400) {
    http_response_code($code);
    echo json_encode(['status' => 'ERR', 'message' => $msg, 'data' => null]);
    exit;
}

function richiediAuth() {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (empty($_SESSION['user_id'])) errore('Non autenticato.', 401);
    return (int) $_SESSION['user_id'];
}

function getBody() {
    return json_decode(file_get_contents('php://input'), true) ?? [];
}

function setHeaders() {
    header('Content-Type: application/json; charset=utf-8');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }
}
