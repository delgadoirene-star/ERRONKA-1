<?php
require_once 'seguritatea.php';

// Langilearen modeloa
class Langilea {
    private $id;
    private $usuario_id;
    private $departamendua;
    private $pozisio;
    private $data_kontratazio;
    private $soldata;
    private $telefonoa;
    private $foto;

    public function __construct($usuario_id, $departamendua = '', $pozisio = '', 
                            $data_kontratazio = null, $soldata = 0, $telefonoa = '', $foto = '') {
        $this->usuario_id = $usuario_id;
        $this->departamendua = $departamendua;
        $this->pozisio = $pozisio;
        $this->data_kontratazio = $data_kontratazio;
        $this->soldata = $soldata;
        $this->telefonoa = $telefonoa;
        $this->foto = $foto;
    }

    // Getters
    public function getId() { return $this->id; }
    public function getUsuarioId() { return $this->usuario_id; }
    public function getDepartamendua() { return $this->departamendua; }
    public function getPozisio() { return $this->pozisio; }
    public function getDataKontratazio() { return $this->data_kontratazio; }
    public function getSoldata() { return $this->soldata; }

    // Setters
    public function setId($id) { $this->id = $id; }
    public function setDepartamendua($departamendua) { $this->departamendua = $departamendua; }
    public function setPozisio($pozisio) { $this->pozisio = $pozisio; }
    public function setSoldata($soldata) { $this->soldata = $soldata; }

    // Langilea sortzea
    public function sortu($conn) {
        $sql = "INSERT INTO langilea (usuario_id, departamendua, pozisio, data_kontratazio, soldata, telefonoa, foto) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            return false;
        }
        
        $stmt->bind_param("isssiss", $this->usuario_id, $this->departamendua, $this->pozisio, 
                          $this->data_kontratazio, $this->soldata, $this->telefonoa, $this->foto);
        
        $emaitza = $stmt->execute();
        
        if ($emaitza) {
            $this->id = $conn->insert_id;
        }
        
        $stmt->close();
        return $emaitza;
    }

    // Guztiak lortu
    public static function lortuGuztiak($conn) {
        if (!$conn) return [];
        $sql = "SELECT l.*, u.izena, u.abizena, u.email, u.nan 
                FROM langilea l 
                JOIN usuario u ON l.usuario_id = u.id 
                WHERE l.aktibo = TRUE 
                ORDER BY u.izena ASC";
        
        $result = $conn->query($sql);
        $langileak = [];
        
        if ($result && $result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $langileak[] = $row;
            }
        }
        
        return $langileak;
    }

    // ID bidez lortu
    public static function lortuIdAgatik($conn, $id) {
        if (!$conn) return null;
        $stmt = $conn->prepare("SELECT l.*, u.izena, u.abizena, u.email, u.nan 
                               FROM langilea l 
                               JOIN usuario u ON l.usuario_id = u.id 
                               WHERE l.id=? AND l.aktibo=TRUE");
        if (!$stmt) return null;
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->num_rows > 0 ? $result->fetch_assoc() : null;
        $stmt->close();
        return $row;
    }

    // Eguneratzea
    public function eguneratu($conn) {
        $sql = "UPDATE langilea SET departamendua=?, pozisio=?, soldata=?, telefonoa=? WHERE id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssdsi", $this->departamendua, $this->pozisio, $this->soldata, $this->telefonoa, $this->id);
        $emaitza = $stmt->execute();
        $stmt->close();
        return $emaitza;
    }

    // Desaktibatzea
    public static function desaktibatu($conn, $id) {
        $stmt = $conn->prepare("UPDATE langilea SET aktibo=FALSE WHERE id=?");
        $stmt->bind_param("i", $id);
        $emaitza = $stmt->execute();
        $stmt->close();
        return $emaitza;
    }

    // Ezabatzea
    public static function ezabatu($conn, $id) {
        $stmt = $conn->prepare("DELETE FROM langilea WHERE id = ?");
        $stmt->bind_param("i", $id);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }
}
?>