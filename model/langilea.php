<?php
require_once 'seguritatea.php';

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

    public function getId() { return $this->id; }
    public function getUsuarioId() { return $this->usuario_id; }
    public function getDepartamendua() { return $this->departamendua; }
    public function getPozisio() { return $this->pozisio; }
    public function getDataKontratazio() { return $this->data_kontratazio; }
    public function getSoldata() { return $this->soldata; }

    public function setId($id) { $this->id = $id; }
    public function setDepartamendua($departamendua) { $this->departamendua = $departamendua; }
    public function setPozisio($pozisio) { $this->pozisio = $pozisio; }
    public function setSoldata($soldata) { $this->soldata = $soldata; }

    public static function all(mysqli $conn): array {
        $rows=[]; $res=$conn->query("SELECT id, usuario_id, departamendua, pozisio, data_kontratazio, soldata, telefonoa, foto FROM langilea ORDER BY id DESC");
        while($r=$res->fetch_assoc()){ $rows[]=$r; } return $rows;
    }
    public static function find(mysqli $conn,int $id):?array {
        $st=$conn->prepare("SELECT id, usuario_id, departamendua, pozisio, data_kontratazio, soldata, telefonoa, foto FROM langilea WHERE id=?");
        $st->bind_param("i",$id); $st->execute(); $r=$st->get_result()->fetch_assoc(); $st->close(); return $r?:null;
    }
    public static function create(mysqli $conn,array $d):bool {
        $st=$conn->prepare("INSERT INTO langilea (usuario_id, departamendua, pozisio, data_kontratazio, soldata, telefonoa, foto) VALUES (?,?,?,?,?,?,?)");
        $st->bind_param("isssiss",$d['usuario_id'],$d['departamendua'],$d['pozisio'],$d['data_kontratazio'],$d['soldata'],$d['telefonoa'],$d['foto']);
        $ok=$st->execute(); $st->close(); return $ok;
    }
    public static function update(mysqli $conn,int $id,array $d):bool {
        $st=$conn->prepare("UPDATE langilea SET departamendua=?, pozisio=?, data_kontratazio=?, soldata=?, telefonoa=?, foto=? WHERE id=?");
        $st->bind_param("sssissi",
            $d['departamendua'],
            $d['pozisio'],
            $d['data_kontratazio'],
            $d['soldata'],
            $d['telefonoa'],
            $d['foto'],
            $id
        );
        $ok=$st->execute(); $st->close(); return $ok;
    }
    public static function delete(mysqli $conn,int $id):bool {
        $st=$conn->prepare("DELETE FROM langilea WHERE id=?"); $st->bind_param("i",$id);
        $ok=$st->execute(); $st->close(); return $ok;
    }

    public function sortu($conn) {
        $stmt = $conn->prepare("INSERT INTO langilea (usuario_id, departamendua, pozisio) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $this->usuario_id, $this->departamendua, $this->pozisio);
        
        if ($stmt->execute()) {
            $this->id = $conn->insert_id;
            $stmt->close();
            return true;
        }
        
        $stmt->close();
        return false;
    }
}
?>