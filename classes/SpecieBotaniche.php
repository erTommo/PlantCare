<?php
require_once __DIR__ . '/../config/database.php';

class SpecieBotaniche {
    private mysqli $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }


    public function getAll(): array {
        $result = $this->db->query(
            'SELECT ID_Specie, Nome_Comune, Nome_Scientifico,
                    Temp_Ideale_Min, Temp_Ideale_Max,
                    Umidita_Suolo_Min, Umidita_Suolo_Max,
                    Luce_Ideale_Min, Luce_Ideale_Max,
                    Tossica_Per_Animali, Foto_Default_URL
             FROM Specie_Botaniche
             ORDER BY Nome_Comune ASC'
        );
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function getById(int $id): ?array {
        $stmt = $this->db->prepare(
            'SELECT * FROM Specie_Botaniche WHERE ID_Specie = ? LIMIT 1'
        );
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $row ?: null;
    }

    public function search(string $query): array {
        $like = '%' . $query . '%';
        $stmt = $this->db->prepare(
            'SELECT ID_Specie, Nome_Comune, Nome_Scientifico,
                    Temp_Ideale_Min, Temp_Ideale_Max,
                    Umidita_Suolo_Min, Umidita_Suolo_Max,
                    Luce_Ideale_Min, Luce_Ideale_Max,
                    Tossica_Per_Animali, Foto_Default_URL
             FROM Specie_Botaniche
             WHERE Nome_Comune LIKE ? OR Nome_Scientifico LIKE ?
             ORDER BY Nome_Comune ASC'
        );
        $stmt->bind_param('ss', $like, $like);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $rows;
    }


    public function create(array $data): int {
        $this->_validate($data);

        $nomeComune      = $data['nome_comune'];
        $nomeSci         = $data['nome_scientifico']  ?? null;
        $tempMin         = $data['temp_ideale_min']   ?? null;
        $tempMax         = $data['temp_ideale_max']   ?? null;
        $umidMin         = $data['umidita_suolo_min'] ?? null;
        $umidMax         = $data['umidita_suolo_max'] ?? null;
        $luceMin         = $data['luce_ideale_min']   ?? null;
        $luceMax         = $data['luce_ideale_max']   ?? null;
        $tossica         = isset($data['tossica_per_animali']) ? (int) $data['tossica_per_animali'] : 0;
        $foto            = $data['foto_default_url']  ?? null;

        $stmt = $this->db->prepare(
            'INSERT INTO Specie_Botaniche
             (Nome_Comune, Nome_Scientifico, Temp_Ideale_Min, Temp_Ideale_Max,
              Umidita_Suolo_Min, Umidita_Suolo_Max,
              Luce_Ideale_Min, Luce_Ideale_Max,
              Tossica_Per_Animali, Foto_Default_URL)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->bind_param('ssddddddis',
            $nomeComune, $nomeSci,
            $tempMin, $tempMax,
            $umidMin, $umidMax,
            $luceMin, $luceMax,
            $tossica, $foto
        );
        $stmt->execute();
        $newId = (int) $this->db->insert_id;
        $stmt->close();
        return $newId;
    }

    public function update(int $id, array $data): bool {
        $this->_validate($data);

        $nomeComune = $data['nome_comune'];
        $nomeSci    = $data['nome_scientifico']  ?? null;
        $tempMin    = $data['temp_ideale_min']   ?? null;
        $tempMax    = $data['temp_ideale_max']   ?? null;
        $umidMin    = $data['umidita_suolo_min'] ?? null;
        $umidMax    = $data['umidita_suolo_max'] ?? null;
        $luceMin    = $data['luce_ideale_min']   ?? null;
        $luceMax    = $data['luce_ideale_max']   ?? null;
        $tossica    = isset($data['tossica_per_animali']) ? (int) $data['tossica_per_animali'] : 0;
        $foto       = $data['foto_default_url']  ?? null;

        $stmt = $this->db->prepare(
            'UPDATE Specie_Botaniche SET
                Nome_Comune         = ?,
                Nome_Scientifico    = ?,
                Temp_Ideale_Min     = ?,
                Temp_Ideale_Max     = ?,
                Umidita_Suolo_Min   = ?,
                Umidita_Suolo_Max   = ?,
                Luce_Ideale_Min     = ?,
                Luce_Ideale_Max     = ?,
                Tossica_Per_Animali = ?,
                Foto_Default_URL    = ?
             WHERE ID_Specie = ?'
        );
        $stmt->bind_param('ssddddddisi',
            $nomeComune, $nomeSci,
            $tempMin, $tempMax,
            $umidMin, $umidMax,
            $luceMin, $luceMax,
            $tossica, $foto,
            $id
        );
        $stmt->execute();
        $affected = $stmt->affected_rows;
        $stmt->close();
        return $affected > 0;
    }


    public function delete(int $id): bool {
        $stmt = $this->db->prepare('DELETE FROM Specie_Botaniche WHERE ID_Specie = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $affected = $stmt->affected_rows;
        $stmt->close();
        return $affected > 0;
    }

    private function _validate(array $data): void {
        if (empty($data['nome_comune'])) {
            throw new InvalidArgumentException('Il campo nome_comune è obbligatorio.');
        }
        if (isset($data['temp_ideale_min'], $data['temp_ideale_max'])
            && $data['temp_ideale_min'] >= $data['temp_ideale_max']) {
            throw new InvalidArgumentException('Temp_Ideale_Min deve essere < Temp_Ideale_Max.');
        }
        if (isset($data['umidita_suolo_min'], $data['umidita_suolo_max'])
            && $data['umidita_suolo_min'] >= $data['umidita_suolo_max']) {
            throw new InvalidArgumentException('Umidita_Suolo_Min deve essere < Umidita_Suolo_Max.');
        }
    }
}
