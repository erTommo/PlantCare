<?php


require_once __DIR__ . '/../config/database.php';

class EsemplariPiante {
    private mysqli $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    public function getAllByUser(int $userId): array {
        $stmt = $this->db->prepare(
            'SELECT
                e.ID_Esemplare, e.ID_Utente, e.ID_Specie,
                e.Soprannome, e.Data_Aggiunta, e.Foto_Attuale_URL,
                s.Nome_Comune, s.Nome_Scientifico, s.Foto_Default_URL,
                s.Temp_Ideale_Min, s.Temp_Ideale_Max,
                s.Umidita_Suolo_Min, s.Umidita_Suolo_Max,
                s.Luce_Ideale_Min,  s.Luce_Ideale_Max,
                s.Tossica_Per_Animali
             FROM Esemplari_Piante e
             JOIN Specie_Botaniche s ON e.ID_Specie = s.ID_Specie
             WHERE e.ID_Utente = ?
             ORDER BY e.Data_Aggiunta DESC'
        );
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $plants = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        foreach ($plants as &$plant) {
            $plant['ultime_rilevazioni'] = $this->_getLatestReadings((int) $plant['ID_Esemplare']);
            $plant['stato']              = $this->_computeStatus($plant);
        }
        unset($plant);

        return $plants;
    }

    public function getById(int $esemplareId, int $userId): ?array {
        $stmt = $this->db->prepare(
            'SELECT
                e.*, s.Nome_Comune, s.Nome_Scientifico,
                s.Temp_Ideale_Min, s.Temp_Ideale_Max,
                s.Umidita_Suolo_Min, s.Umidita_Suolo_Max,
                s.Luce_Ideale_Min,  s.Luce_Ideale_Max,
                s.Tossica_Per_Animali, s.Foto_Default_URL
             FROM Esemplari_Piante e
             JOIN Specie_Botaniche s ON e.ID_Specie = s.ID_Specie
             WHERE e.ID_Esemplare = ? AND e.ID_Utente = ?
             LIMIT 1'
        );
        $stmt->bind_param('ii', $esemplareId, $userId);
        $stmt->execute();
        $plant = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$plant) return null;

        $plant['ultime_rilevazioni'] = $this->_getLatestReadings($esemplareId);
        $plant['stato']              = $this->_computeStatus($plant);
        return $plant;
    }


    public function create(int $userId, array $data): int {
        if (empty($data['id_specie'])) {
            throw new InvalidArgumentException('id_specie è obbligatorio.');
        }

        $idSpecie = (int) $data['id_specie'];

        // Verifica che la specie esista
        $check = $this->db->prepare('SELECT ID_Specie FROM Specie_Botaniche WHERE ID_Specie = ?');
        $check->bind_param('i', $idSpecie);
        $check->execute();
        $check->store_result();
        if ($check->num_rows === 0) {
            $check->close();
            throw new InvalidArgumentException('Specie non trovata.');
        }
        $check->close();

        $soprannome    = $data['soprannome']       ?? null;
        $dataAggiunta  = $data['data_aggiunta']    ?? date('Y-m-d');
        $fotoUrl       = $data['foto_attuale_url'] ?? null;

        $stmt = $this->db->prepare(
            'INSERT INTO Esemplari_Piante (ID_Utente, ID_Specie, Soprannome, Data_Aggiunta, Foto_Attuale_URL)
             VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->bind_param('iisss', $userId, $idSpecie, $soprannome, $dataAggiunta, $fotoUrl);
        $stmt->execute();
        $newId = (int) $this->db->insert_id;
        $stmt->close();
        return $newId;
    }


    public function update(int $esemplareId, int $userId, array $data): bool {
        $soprannome = $data['soprannome']       ?? null;
        $fotoUrl    = $data['foto_attuale_url'] ?? null;

        $stmt = $this->db->prepare(
            'UPDATE Esemplari_Piante
             SET Soprannome = ?, Foto_Attuale_URL = ?
             WHERE ID_Esemplare = ? AND ID_Utente = ?'
        );
        $stmt->bind_param('ssii', $soprannome, $fotoUrl, $esemplareId, $userId);
        $stmt->execute();
        $affected = $stmt->affected_rows;
        $stmt->close();
        return $affected > 0;
    }


    public function delete(int $esemplareId, int $userId): bool {
        $stmt = $this->db->prepare(
            'DELETE FROM Esemplari_Piante WHERE ID_Esemplare = ? AND ID_Utente = ?'
        );
        $stmt->bind_param('ii', $esemplareId, $userId);
        $stmt->execute();
        $affected = $stmt->affected_rows;
        $stmt->close();
        return $affected > 0;
    }


    public function countByUser(int $userId): int {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) FROM Esemplari_Piante WHERE ID_Utente = ?'
        );
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $stmt->bind_result($count);
        $stmt->fetch();
        $stmt->close();
        return (int) $count;
    }
    public function deleteAllByUser(int $userId): int {
        $stmt = $this->db->prepare('DELETE FROM Esemplari_Piante WHERE ID_Utente = ?');
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $affected = $stmt->affected_rows;
        $stmt->close();
        return $affected;
    }


    private function _getLatestReadings(int $esemplareId): array {
        $types  = ['Temperatura_Aria', 'Umidita_Aria', 'Umidita_Suolo', 'Luminosita', 'pH'];
        $result = [];
        foreach ($types as $type) {
            $stmt = $this->db->prepare(
                'SELECT Valore, Data_Ora_Rilevazione
                 FROM Rilevazioni_Sensori
                 WHERE ID_Esemplare = ? AND Tipo_Misurazione = ?
                 ORDER BY Data_Ora_Rilevazione DESC
                 LIMIT 1'
            );
            $stmt->bind_param('is', $esemplareId, $type);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if ($row) {
                $result[$type] = [
                    'valore'    => (float) $row['Valore'],
                    'timestamp' => $row['Data_Ora_Rilevazione'],
                ];
            }
        }
        return $result;
    }

    private function _computeStatus(array $plant): string {
        $readings = $plant['ultime_rilevazioni'];
        $status   = 'ok';

        $checks = [
            ['key' => 'Temperatura_Aria', 'min' => (float)($plant['Temp_Ideale_Min'] ?? -INF), 'max' => (float)($plant['Temp_Ideale_Max'] ?? INF)],
            ['key' => 'Umidita_Suolo',   'min' => (float)($plant['Umidita_Suolo_Min'] ?? -INF), 'max' => (float)($plant['Umidita_Suolo_Max'] ?? INF)],
        ];

        foreach ($checks as $c) {
            if (!isset($readings[$c['key']])) continue;
            $v   = $readings[$c['key']]['valore'];
            $gap = min(abs($v - $c['min']), abs($v - $c['max']));
            if ($v < $c['min'] || $v > $c['max']) {
                $range  = $c['max'] - $c['min'];
                $status = ($range > 0 && $gap / $range > 0.2) ? 'alert' : 'warn';
                break;
            }
        }
        return $status;
    }
}
