<?php
class Produktua {
    private $id;
    private $izena;
    private $deskripzioa;
    private $kategoria;
    private $prezioa;
    private $stock;
    private $stock_minimo;
    private $irudia;

    public function __construct($izena, $deskripzioa = '', $kategoria = '', $prezioa = 0, $stock = 0, $irudia = '') {
        $this->izena = $izena;
        $this->deskripzioa = $deskripzioa;
        $this->kategoria = $kategoria;
        $this->prezioa = $prezioa;
        $this->stock = $stock;
        $this->stock_minimo = 10;
        $this->irudia = $irudia;
    }

    public function getId() { return $this->id; }
    public function getIzena() { return $this->izena; }
    public function getDeskripzioa() { return $this->deskripzioa; }
    public function getKategoria() { return $this->kategoria; }
    public function getPrezioa() { return $this->prezioa; }
    public function getStock() { return $this->stock; }
    public function getStockMinimo() { return $this->stock_minimo; }
    public function getIrudia() { return $this->irudia; }

    public function setId($id) { $this->id = $id; }
    public function setIzena($izena) { $this->izena = $izena; }
    public function setPrezioa($prezioa) { $this->prezioa = $prezioa; }
    public function setStock($stock) { $this->stock = $stock; }
    public function setIrudia($irudia) { $this->irudia = $irudia; }

    public static function all(mysqli $conn): array {
        $rows = [];
        $res = $conn->query("SELECT id, izena, deskripzioa, kategoria, prezioa, stock, stock_minimo, irudia FROM produktua ORDER BY id DESC");
        while ($r = $res->fetch_assoc()) { $rows[] = $r; }
        return $rows;
    }
    
    public static function find(mysqli $conn, int $id): ?array {
        $st = $conn->prepare("SELECT id, izena, deskripzioa, kategoria, prezioa, stock, stock_minimo, irudia FROM produktua WHERE id=?");
        $st->bind_param("i",$id); $st->execute();
        $r = $st->get_result()->fetch_assoc(); $st->close();
        return $r ?: null;
    }
    
    public static function create(mysqli $conn, array $d): bool {
        $st = $conn->prepare("INSERT INTO produktua (izena, deskripzioa, kategoria, prezioa, stock, stock_minimo, irudia) VALUES (?,?,?,?,?,?,?)");
        $irudia = $d['irudia'] ?? '';
        $st->bind_param("sssdiis",$d['izena'],$d['deskripzioa'],$d['kategoria'],$d['prezioa'],$d['stock'],$d['stock_minimo'],$irudia);
        $ok = $st->execute(); $st->close(); return $ok;
    }
    
    public static function update(mysqli $conn, int $id, array $d): bool {
        $st = $conn->prepare("UPDATE produktua SET izena=?, deskripzioa=?, kategoria=?, prezioa=?, stock=?, stock_minimo=?, irudia=? WHERE id=?");
        $irudia = $d['irudia'] ?? '';
        $st->bind_param("sssdiisi",$d['izena'],$d['deskripzioa'],$d['kategoria'],$d['prezioa'],$d['stock'],$d['stock_minimo'],$irudia,$id);
        $ok = $st->execute(); $st->close(); return $ok;
    }
    
    public static function delete(mysqli $conn, int $id): bool {
        $st = $conn->prepare("DELETE FROM produktua WHERE id=?");
        $st->bind_param("i",$id); $ok = $st->execute(); $st->close(); return $ok;
    }
    
    /**
     * Get products by category for store display
     */
    public static function lortuKategoriagatik(mysqli $conn, string $kategoria): array {
        $rows = [];
        $st = $conn->prepare("SELECT id, izena, deskripzioa, kategoria, prezioa, stock, irudia FROM produktua WHERE kategoria = ? AND stock > 0 ORDER BY izena ASC");
        $st->bind_param("s", $kategoria);
        $st->execute();
        $res = $st->get_result();
        while ($r = $res->fetch_assoc()) { $rows[] = $r; }
        $st->close();
        return $rows;
    }
    
    /**
     * Get all categories
     */
    public static function lortuKategoriak(mysqli $conn): array {
        $rows = [];
        $res = $conn->query("SELECT DISTINCT kategoria FROM produktua WHERE kategoria != '' ORDER BY kategoria ASC");
        while ($r = $res->fetch_assoc()) { $rows[] = $r['kategoria']; }
        return $rows;
    }
    
    /**
     * Get available products for store (with stock)
     */
    public static function lortuDendarako(mysqli $conn, ?string $kategoria = null, ?string $bilaketa = null): array {
        $rows = [];
        $sql = "SELECT id, izena, deskripzioa, kategoria, prezioa, stock, irudia FROM produktua WHERE stock > 0";
        $params = [];
        $types = "";
        
        if ($kategoria) {
            $sql .= " AND kategoria = ?";
            $params[] = $kategoria;
            $types .= "s";
        }
        
        if ($bilaketa) {
            $sql .= " AND (izena LIKE ? OR deskripzioa LIKE ?)";
            $bilaketa_param = "%" . $bilaketa . "%";
            $params[] = $bilaketa_param;
            $params[] = $bilaketa_param;
            $types .= "ss";
        }
        
        $sql .= " ORDER BY izena ASC";
        
        if (empty($params)) {
            $res = $conn->query($sql);
        } else {
            $st = $conn->prepare($sql);
            $st->bind_param($types, ...$params);
            $st->execute();
            $res = $st->get_result();
        }
        
        while ($r = $res->fetch_assoc()) { $rows[] = $r; }
        if (isset($st)) $st->close();
        return $rows;
    }
    
    /**
     * Get featured/popular products
     */
    public static function lortuNabarmenduak(mysqli $conn, int $limit = 8): array {
        $rows = [];
        $st = $conn->prepare("SELECT id, izena, deskripzioa, kategoria, prezioa, stock, irudia FROM produktua WHERE stock > 0 ORDER BY created_at DESC LIMIT ?");
        $st->bind_param("i", $limit);
        $st->execute();
        $res = $st->get_result();
        while ($r = $res->fetch_assoc()) { $rows[] = $r; }
        $st->close();
        return $rows;
    }
}
?>