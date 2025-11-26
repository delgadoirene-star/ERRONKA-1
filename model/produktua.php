<?php
class Produktua {
    private $id;
    private $izena;
    private $deskripzioa;
    private $kategoria;
    private $prezioa;
    private $stock;
    private $stock_minimo;

    public function __construct($izena, $deskripzioa = '', $kategoria = '', $prezioa = 0, $stock = 0) {
        $this->izena = $izena;
        $this->deskripzioa = $deskripzioa;
        $this->kategoria = $kategoria;
        $this->prezioa = $prezioa;
        $this->stock = $stock;
        $this->stock_minimo = 10;
    }

    // Getters
    public function getId() { return $this->id; }
    public function getIzena() { return $this->izena; }
    public function getDeskripzioa() { return $this->deskripzioa; }
    public function getKategoria() { return $this->kategoria; }
    public function getPrezioa() { return $this->prezioa; }
    public function getStock() { return $this->stock; }
    public function getStockMinimo() { return $this->stock_minimo; }

    // Setters
    public function setId($id) { $this->id = $id; }
    public function setIzena($izena) { $this->izena = $izena; }
    public function setPrezioa($prezioa) { $this->prezioa = $prezioa; }
    public function setStock($stock) { $this->stock = $stock; }

    // ====== CRUD static helpers ======
    public static function all(mysqli $conn): array {
        $rows = [];
        $res = $conn->query("SELECT id, izena, deskripzioa, kategoria, prezioa, stock, stock_minimo FROM produktua ORDER BY id DESC");
        while ($r = $res->fetch_assoc()) { $rows[] = $r; }
        return $rows;
    }
    public static function find(mysqli $conn, int $id): ?array {
        $st = $conn->prepare("SELECT id, izena, deskripzioa, kategoria, prezioa, stock, stock_minimo FROM produktua WHERE id=?");
        $st->bind_param("i",$id); $st->execute();
        $r = $st->get_result()->fetch_assoc(); $st->close();
        return $r ?: null;
    }
    public static function create(mysqli $conn, array $d): bool {
        $st = $conn->prepare("INSERT INTO produktua (izena, deskripzioa, kategoria, prezioa, stock, stock_minimo) VALUES (?,?,?,?,?,?)");
        $st->bind_param("sssdii",$d['izena'],$d['deskripzioa'],$d['kategoria'],$d['prezioa'],$d['stock'],$d['stock_minimo']);
        $ok = $st->execute(); $st->close(); return $ok;
    }
    public static function update(mysqli $conn, int $id, array $d): bool {
        $st = $conn->prepare("UPDATE produktua SET izena=?, deskripzioa=?, kategoria=?, prezioa=?, stock=?, stock_minimo=? WHERE id=?");
        $st->bind_param("sssdiii",$d['izena'],$d['deskripzioa'],$d['kategoria'],$d['prezioa'],$d['stock'],$d['stock_minimo'],$id);
        $ok = $st->execute(); $st->close(); return $ok;
    }
    public static function delete(mysqli $conn, int $id): bool {
        $st = $conn->prepare("DELETE FROM produktua WHERE id=?");
        $st->bind_param("i",$id); $ok = $st->execute(); $st->close(); return $ok;
    }
}
?>