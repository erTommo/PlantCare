<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/EventiAllarme.php';

class RilevazioniSensori {
    private mysqli $db;

    private const VALID_TYPES = [
        'Temperatura_Aria',
        'Umidita_Aria',
        'Umidita_Suolo',
        'Luminosita',
        'pH',
    ];

    public function __construct() {
        $this->db = Database::getConnection();
    }


    public function getForChart(int $esemplareId, string $tipo, string $range = '24h'): array {
        $this->_validateType($tipo);

        $interval = match ($range) {
            '1h'  => 'INTERVAL 1 HOUR',
            '6h'  => 'INTERVAL 6 HOUR',
            '24h' => 'INTERVAL 24 HOUR',
            '7d'  => 'INTERVAL 7 DAY',
            '30d' => 'INTERVAL 30 DAY',
            default => 'INTERVAL 24 HOUR',
        };

        $groupBy = match ($range) {
            '30d'  => "DATE_FORMAT(Data_Ora_Rilevazione, '%Y-%m-%d')",
            '7d'   => "DATE_FORMAT(Data_Ora_Rilevazione, '%Y-%m-%d %H:00')",
            default => "DATE_FORMAT(Data_Ora_Rilevazione, '%Y-%m-%d %H:%i')",
        };


        $sql = "SELECT
                    {$groupBy} AS label,
                    ROUND(AVG(Valore), 2) AS valore
                FROM Rilevazioni_Sensori
                WHERE ID_Esemplare = ?
                  AND Tipo_Misurazione = ?
                  AND Data_Ora_Rilevazione >= NOW() - {$interval}
                GROUP BY {$groupBy}
                ORDER BY label ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('is', $esemplareId, $tipo);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $rows;
    }

    public function getRaw(int $esemplareId, int $limit = 50): array {
        $limit = min($limit, 500);
        $stmt = $this->db->prepare(
            'SELECT ID_Rilevazione, Tipo_Misurazione, Valore, Data_Ora_Rilevazione
             FROM Rilevazioni_Sensori
             WHERE ID_Esemplare = ?
             ORDER BY Data_Ora_Rilevazione DESC
             LIMIT ?'
        );
        $stmt->bind_param('ii', $esemplareId, $limit);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $rows;
    }

    public function insert(int $esemplareId, string $tipo, float $valore): int {
        $this->_validateType($tipo);
        $this->_validateValue($tipo, $valore);

        $stmt = $this->db->prepare(
            'INSERT INTO Rilevazioni_Sensori (ID_Esemplare, Tipo_Misurazione, Valore)
             VALUES (?, ?, ?)'
        );
        $stmt->bind_param('isd', $esemplareId, $tipo, $valore);
        $stmt->execute();
        $newId = (int) $this->db->insert_id;
        $stmt->close();

        $this->_checkAndFireAlarm($esemplareId, $tipo, $valore);

        return $newId;
    }

    public function insertBatch(int $esemplareId, array $readings): int {
        $count = 0;
        $this->db->begin_transaction();
        try {
            foreach ($readings as $r) {
                if (empty($r['tipo']) || !isset($r['valore'])) continue;
                $this->insert($esemplareId, $r['tipo'], (float) $r['valore']);
                $count++;
            }
            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollback();
            throw $e;
        }
        return $count;
    }

    public function getStats(int $esemplareId): array {
        $stmt = $this->db->prepare(
            'SELECT
                Tipo_Misurazione,
                COUNT(*)                AS totale,
                ROUND(MIN(Valore), 2)   AS minimo,
                ROUND(MAX(Valore), 2)   AS massimo,
                ROUND(AVG(Valore), 2)   AS media,
                MIN(Data_Ora_Rilevazione) AS prima,
                MAX(Data_Ora_Rilevazione) AS ultima
             FROM Rilevazioni_Sensori
             WHERE ID_Esemplare = ?
             GROUP BY Tipo_Misurazione'
        );
        $stmt->bind_param('i', $esemplareId);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $rows;
    }

    public function countByUser(int $userId): int {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) FROM Rilevazioni_Sensori rs
             JOIN Esemplari_Piante ep ON rs.ID_Esemplare = ep.ID_Esemplare
             WHERE ep.ID_Utente = ?'
        );
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $stmt->bind_result($count);
        $stmt->fetch();
        $stmt->close();
        return (int) $count;
    }

    private function _checkAndFireAlarm(int $esemplareId, string $tipo, float $valore): void {
        $stmt = $this->db->prepare(
            'SELECT s.Temp_Ideale_Min, s.Temp_Ideale_Max,
                    s.Umidita_Suolo_Min, s.Umidita_Suolo_Max,
                    s.Luce_Ideale_Min, s.Luce_Ideale_Max
             FROM Esemplari_Piante e
             JOIN Specie_Botaniche s ON e.ID_Specie = s.ID_Specie
             WHERE e.ID_Esemplare = ? LIMIT 1'
        );
        $stmt->bind_param('i', $esemplareId);
        $stmt->execute();
        $soglie = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$soglie) return;

        $tipoAllarme = null;

        switch ($tipo) {
            case 'Umidita_Suolo':
                if ($soglie['Umidita_Suolo_Min'] !== null && $valore < $soglie['Umidita_Suolo_Min']) {
                    $tipoAllarme = 'Troppo_Secco';
                } elseif ($soglie['Umidita_Suolo_Max'] !== null && $valore > $soglie['Umidita_Suolo_Max']) {
                    $tipoAllarme = 'Troppo_Umido';
                }
                break;

            case 'Temperatura_Aria':
                if ($soglie['Temp_Ideale_Min'] !== null && $valore < $soglie['Temp_Ideale_Min']) {
                    $tipoAllarme = 'Troppo_Freddo';
                } elseif ($soglie['Temp_Ideale_Max'] !== null && $valore > $soglie['Temp_Ideale_Max']) {
                    $tipoAllarme = 'Troppo_Caldo';
                }
                break;

            case 'Luminosita':
                if ($soglie['Luce_Ideale_Min'] !== null && $valore < $soglie['Luce_Ideale_Min']) {
                    $tipoAllarme = 'Poca_Luce';
                }
                break;
        }

        if ($tipoAllarme !== null) {
            $dup = $this->db->prepare(
                'SELECT ID_Allarme FROM Eventi_Allarme
                 WHERE ID_Esemplare = ? AND Tipo_Allarme = ?
                   AND Data_Ora >= NOW() - INTERVAL 30 MINUTE
                 LIMIT 1'
            );
            $dup->bind_param('is', $esemplareId, $tipoAllarme);
            $dup->execute();
            $dup->store_result();
            $found = $dup->num_rows > 0;
            $dup->close();

            if (!$found) {
                $alarm = new EventiAllarme();
                $alarm->create($esemplareId, $tipoAllarme, $valore);
            }
        }
    }

    private function _validateType(string $tipo): void {
        if (!in_array($tipo, self::VALID_TYPES, true)) {
            throw new InvalidArgumentException(
                'Tipo_Misurazione non valido. Valori accettati: ' . implode(', ', self::VALID_TYPES)
            );
        }
    }

    private function _validateValue(string $tipo, float $valore): void {
        $ranges = [
            'Temperatura_Aria' => [-50, 80],
            'Umidita_Aria'     => [0, 100],
            'Umidita_Suolo'    => [0, 100],
            'Luminosita'       => [0, 200000],
            'pH'               => [0, 14],
        ];
        [$min, $max] = $ranges[$tipo];
        if ($valore < $min || $valore > $max) {
            throw new InvalidArgumentException(
                "Valore {$valore} fuori range per {$tipo} (atteso: {$min}–{$max})."
            );
        }
    }
}
