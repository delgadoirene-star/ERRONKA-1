<?php
require_once 'seguritatea.php';

class Salmenta {
    private $id;
    private $langile_id;
    private $produktu_id;
    private $kantitatea;
    private $prezioa_unitarioa;
    private $data_salmenta;
    private $bezeroa_izena;
    private $bezeroa_nif;
    private $bezeroa_telefonoa;
    private $oharra;

    public function __construct($langile_id, $produktu_id, $kantitatea, $prezioa_unitarioa, 
                                $bezeroa_izena = '', $bezeroa_nif = '', $bezeroa_telefonoa = '', $oharra = '') {
        $this->langile_id = $langile_id;
        $this->produktu_id = $produktu_id;
        $this->kantitatea = $kantitatea;
        $this->prezioa_unitarioa = $prezioa_unitarioa;
        $this->data_salmenta = date('Y-m-d H:i:s');
        $this->bezeroa_izena = $bezeroa_izena;
        $this->bezeroa_nif = $bezeroa_nif;
        $this->bezeroa_telefonoa = $bezeroa_telefonoa;
        $this->oharra = $oharra;
    }

    // Getters
    public function getId() { return $this->id; }
    public function getLangileId() { return $this->langile_id; }
    public function getProduktuId() { return $this->produktu_id; }
    public function getKantitatea() { return $this->kantitatea; }
    public function getPrezioa() { return $this->kantitatea * $this->prezioa_unitarioa; }
    public function getPrezioa_unitarioa() { return $this->prezioa_unitarioa; }
    public function getDataSalmenta() { return $this->data_salmenta; }
    public function getBezeroa() { return $this->bezeroa_izena; }
    public function getBezeroaNif() { return $this->bezeroa_nif; }
    public function getBezeroaTelefonoa() { return $this->bezeroa_telefonoa; }
    public function getOharra() { return $this->oharra; }

    // Setters
    public function setId($id) { $this->id = $id; }
    public function setDataSalmenta($data) { $this->data_salmenta = $data; }

    // Sortzea
    public function sortu($conn) {
		if (!$conn) {
			error_log("Salmenta::sortu called without DB connection");
			return false;
		}
		$sql = "INSERT INTO salmenta (langile_id, produktu_id, kantitatea, prezioa_unitarioa, 
				data_salmenta, bezeroa_izena, bezeroa_nif, bezeroa_telefonoa, oharra) 
				VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
		
		$stmt = $conn->prepare($sql);
		if (!$stmt) {
			error_log("Salmenta prepare Error: " . $conn->error);
			return false;
		}
        
        // TIPOS CORRECTOS: 3 ints, 2 doubles, 5 strings = "iiiddsssss"
        $stmt->bind_param(
            "iiidsssss",
            $this->langile_id,
            $this->produktu_id,
            $this->kantitatea,
            $this->prezioa_unitarioa,
            $this->data_salmenta,
            $this->bezeroa_izena,
            $this->bezeroa_nif,
            $this->bezeroa_telefonoa,
            $this->oharra
        );
        
        $emaitza = $stmt->execute();
        
        if ($emaitza) {
            $this->id = $conn->insert_id;
        }
        
        $stmt->close();
        return $emaitza;
    }

    // Guztiak lortu
    public static function lortuGuztiak($conn, $langile_id = null) {
		if (!$conn) return [];
        if ($langile_id) {
            $sql = "SELECT s.*, l.usuario_id, u.izena, u.abizena, p.izena as produktu_izena, p.kategoria 
                    FROM salmenta s 
                    JOIN langilea l ON s.langile_id = l.id
                    JOIN usuario u ON l.usuario_id = u.id
                    JOIN produktua p ON s.produktu_id = p.id
                    WHERE s.langile_id = ? 
                    ORDER BY s.data_salmenta DESC";
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $langile_id);
        } else {
            $sql = "SELECT s.*, u.izena, u.abizena, p.izena as produktu_izena, p.kategoria 
                    FROM salmenta s 
                    JOIN langilea l ON s.langile_id = l.id
                    JOIN usuario u ON l.usuario_id = u.id
                    JOIN produktua p ON s.produktu_id = p.id
                    ORDER BY s.data_salmenta DESC";
            
            $stmt = $conn->prepare($sql);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        $salmentak = [];
        
        if ($result && $result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $salmentak[] = $row;
            }
        }
        
        $stmt->close();
        return $salmentak;
    }

    // Data tarte batean salmentak
    public static function lortuDataTarteAn($conn, $data_hasiera, $data_bukaera) {
		if (!$conn) return [];
        $sql = "SELECT s.*, u.izena, u.abizena, p.izena as produktu_izena 
                FROM salmenta s 
                JOIN langilea l ON s.langile_id = l.id
                JOIN usuario u ON l.usuario_id = u.id
                JOIN produktua p ON s.produktu_id = p.id
                WHERE s.data_salmenta BETWEEN ? AND ?
                ORDER BY s.data_salmenta DESC";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $data_hasiera, $data_bukaera);
        $stmt->execute();
        $result = $stmt->get_result();
        $salmentak = [];
        
        if ($result && $result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $salmentak[] = $row;
            }
        }
        
        $stmt->close();
        return $salmentak;
    }

    // Salmentaren guztira kalkulatzea
    public static function kalkulaSalmentaGuztira($conn, $langile_id = null) {
		if (!$conn) return 0;
        if ($langile_id) {
            $sql = "SELECT SUM(prezioa_unitarioa * kantitatea) as guztira FROM salmenta WHERE langile_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $langile_id);
        } else {
            $sql = "SELECT SUM(prezioa_unitarioa * kantitatea) as guztira FROM salmenta";
            $stmt = $conn->prepare($sql);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        
        return $row['guztira'] ?? 0;
    }

    // Eguneratzea
    public function eguneratu($conn) {
		if (!$conn) return false;
        $sql = "UPDATE salmenta SET kantitatea=?, prezioa_unitarioa=?, 
                bezeroa_izena=?, bezeroa_nif=?, bezeroa_telefonoa=?, oharra=? WHERE id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("idssssi", $this->kantitatea, $this->prezioa_unitarioa, 
                          $this->bezeroa_izena, $this->bezeroa_nif, 
                          $this->bezeroa_telefonoa, $this->oharra, $this->id);
        $emaitza = $stmt->execute();
        $stmt->close();
        return $emaitza;
    }

    // Ezabatzea
    public static function ezabatu($conn, $id) {
		if (!$conn) return false;
        // Correct deletion target (salmenta), not langilea
        $stmt = $conn->prepare("DELETE FROM salmenta WHERE id = ?");
        $stmt->bind_param("i", $id);
        $emaitza = $stmt->execute();
        $stmt->close();
        return $emaitza;
    }

    // Estatistikak
    public static function lortuStatistikak($conn) {
		if (!$conn) return null;
        $sql = "SELECT 
                COUNT(*) as salmenta_totala,
                SUM(prezioa_unitarioa * kantitatea) as diru_totala,
                AVG(prezioa_unitarioa * kantitatea) as batez_bestekoa,
                DATE(MAX(data_salmenta)) as azkena_salmenta
                FROM salmenta";
        
        $result = $conn->query($sql);
        
        if ($result && $result->num_rows > 0) {
            return $result->fetch_assoc();
        }
        return null;
    }

    public static function all(mysqli $conn): array {
        $rows=[]; $res=$conn->query("SELECT * FROM salmenta ORDER BY id DESC");
        while($r=$res->fetch_assoc()){ $rows[]=$r; } return $rows;
    }
    public static function find(mysqli $conn,int $id):?array {
        $st=$conn->prepare("SELECT * FROM salmenta WHERE id=?"); $st->bind_param("i",$id);
        $st->execute(); $r=$st->get_result()->fetch_assoc(); $st->close(); return $r?:null;
    }
    public static function create(mysqli $conn,array $d):bool {
        $st=$conn->prepare("INSERT INTO salmenta (langile_id, produktu_id, kantitatea, prezioa_unitarioa, data_salmenta, bezeroa_izena, bezeroa_nif, bezeroa_telefonoa, oharra) VALUES (?,?,?,?,?,?,?,?,?)");
        $st->bind_param("iiidsssss",
            $d['langile_id'],
            $d['produktu_id'],
            $d['kantitatea'],
            $d['prezioa_unitarioa'],
            $d['data_salmenta'],
            $d['bezeroa_izena'],
            $d['bezeroa_nif'],
            $d['bezeroa_telefonoa'],
            $d['oharra']
        );
        $ok=$st->execute(); $st->close(); return $ok;
    }
    public static function update(mysqli $conn,int $id,array $d):bool {
        $st=$conn->prepare("UPDATE salmenta SET kantitatea=?, prezioa_unitarioa=?, bezeroa_izena=?, bezeroa_nif=?, bezeroa_telefonoa=?, oharra=? WHERE id=?");
        $st->bind_param("idssssi",
            $d['kantitatea'],
            $d['prezioa_unitarioa'],
            $d['bezeroa_izena'],
            $d['bezeroa_nif'],
            $d['bezeroa_telefonoa'],
            $d['oharra'],
            $id
        );
        $ok=$st->execute(); $st->close(); return $ok;
    }
    public static function delete(mysqli $conn,int $id):bool {
        $st=$conn->prepare("DELETE FROM salmenta WHERE id=?"); $st->bind_param("i",$id);
        $ok=$st->execute(); $st->close(); return $ok;
    }
}
?>