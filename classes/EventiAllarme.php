<?php


require_once __DIR__ . '/../config/database.php';

class EventiAllarme {
    private mysqli $db;

    private const VALID_TYPES = [
        'Troppo_Secco',
        'Troppo_Umido',
        'Troppo_Caldo',
        'Troppo_Freddo',
        'Poca_Luce',
    ];

    public function __construct() {
        $this->db = Database::getConnection();
    }

    public function getAllByUser(int $userId): array {
        $stmt = $this->db->prepare(
            'SELECT
                a.ID_Allarme, a.ID_Esemplare, a.Tipo_Allarme,
                a.Data_Ora, a.Letto_Da_Utente, a.Valore_Rilevato,
                e.Soprannome, s.Nome_Comune, s.Nome_Scientifico
             FROM Eventi_Allarme a
             JOIN Esemplari_Piante e ON a.ID_Esemplare = e.ID_Esemplare
             JOIN Specie_Botaniche s ON e.ID_Specie   = s.ID_Specie
             WHERE e.ID_Utente = ?
             ORDER BY a.Letto_Da_Utente ASC, a.Data_Ora DESC'
        );
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $rows;
    }

    public function getByEsemplare(int $esemplareId, int $userId): array {
        $stmt = $this->db->prepare(
            'SELECT a.*
             FROM Eventi_Allarme a
             JOIN Esemplari_Piante e ON a.ID_Esemplare = e.ID_Esemplare
             WHERE a.ID_Esemplare = ? AND e.ID_Utente = ?
             ORDER BY a.Data_Ora DESC'
        );
        $stmt->bind_param('ii', $esemplareId, $userId);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $rows;
    }

    public function countUnreadByUser(int $userId): int {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) FROM Eventi_Allarme a
             JOIN Esemplari_Piante e ON a.ID_Esemplare = e.ID_Esemplare
             WHERE e.ID_Utente = ? AND a.Letto_Da_Utente = 0'
        );
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $stmt->bind_result($count);
        $stmt->fetch();
        $stmt->close();
        return (int) $count;
    }

    public function create(int $esemplareId, string $tipoAllarme, float $valoreRilevato): int {
        if (!in_array($tipoAllarme, self::VALID_TYPES, true)) {
            throw new InvalidArgumentException('Tipo allarme non valido: ' . $tipoAllarme);
        }

        $stmt = $this->db->prepare(
            'INSERT INTO Eventi_Allarme (ID_Esemplare, Tipo_Allarme, Valore_Rilevato)
             VALUES (?, ?, ?)'
        );
        $stmt->bind_param('isd', $esemplareId, $tipoAllarme, $valoreRilevato);
        $stmt->execute();
        $newId = (int) $this->db->insert_id;
        $stmt->close();
        return $newId;
    }

    public function markAsRead(int $allarmeId, int $userId): bool {
        $stmt = $this->db->prepare(
            'UPDATE Eventi_Allarme a
             JOIN Esemplari_Piante e ON a.ID_Esemplare = e.ID_Esemplare
             SET a.Letto_Da_Utente = 1
             WHERE a.ID_Allarme = ? AND e.ID_Utente = ?'
        );
        $stmt->bind_param('ii', $allarmeId, $userId);
        $stmt->execute();
        $affected = $stmt->affected_rows;
        $stmt->close();
        return $affected > 0;
    }


    public function markAllRead(int $userId): int {
        $stmt = $this->db->prepare(
            'UPDATE Eventi_Allarme a
             JOIN Esemplari_Piante e ON a.ID_Esemplare = e.ID_Esemplare
             SET a.Letto_Da_Utente = 1
             WHERE e.ID_Utente = ? AND a.Letto_Da_Utente = 0'
        );
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $affected = $stmt->affected_rows;
        $stmt->close();
        return $affected;
    }

    public function delete(int $allarmeId, int $userId): bool {
        $stmt = $this->db->prepare(
            'DELETE a FROM Eventi_Allarme a
             JOIN Esemplari_Piante e ON a.ID_Esemplare = e.ID_Esemplare
             WHERE a.ID_Allarme = ? AND e.ID_Utente = ?'
        );
        $stmt->bind_param('ii', $allarmeId, $userId);
        $stmt->execute();
        $affected = $stmt->affected_rows;
        $stmt->close();
        return $affected > 0;
    }


    public function deleteAllByUser(int $userId): int {
        $stmt = $this->db->prepare(
            'DELETE a FROM Eventi_Allarme a
             JOIN Esemplari_Piante e ON a.ID_Esemplare = e.ID_Esemplare
             WHERE e.ID_Utente = ?'
        );
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $affected = $stmt->affected_rows;
        $stmt->close();
        return $affected;
    }

    public function getStatsByUser(int $userId): array {
        $stmt = $this->db->prepare(
            'SELECT a.Tipo_Allarme, COUNT(*) AS totale
             FROM Eventi_Allarme a
             JOIN Esemplari_Piante e ON a.ID_Esemplare = e.ID_Esemplare
             WHERE e.ID_Utente = ?
             GROUP BY a.Tipo_Allarme
             ORDER BY totale DESC'
        );
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $rows;
    }

    public function countByUser(int $userId): int {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) FROM Eventi_Allarme a
             JOIN Esemplari_Piante e ON a.ID_Esemplare = e.ID_Esemplare
             WHERE e.ID_Utente = ?'
        );
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $stmt->bind_result($count);
        $stmt->fetch();
        $stmt->close();
        return (int) $count;
    }
}
