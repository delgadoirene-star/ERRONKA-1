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

    // Sortzea
    public function sortu($conn) {
        $sql = "INSERT INTO produktua (izena, deskripzioa, kategoria, prezioa, stock) 
                VALUES (?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            return false;
        }
        
        $stmt->bind_param("sssdi", $this->izena, $this->deskripzioa, $this->kategoria, 
                          $this->prezioa, $this->stock);
        
        $emaitza = $stmt->execute();
        
        if ($emaitza) {
            $this->id = $conn->insert_id;
        }
        
        $stmt->close();
        return $emaitza;
    }

    // Guztiak lortzea
    public static function lortuGuztiak($conn) {
        if (!$conn) return [];
        $sql = "SELECT * FROM produktua WHERE aktibo = TRUE ORDER BY izena ASC";
        $result = $conn->query($sql);
        $produktuak = [];
        
        if ($result && $result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $produktuak[] = $row;
            }
        }
        
        return $produktuak;
    }

    // ID bidez bilatzea
    public static function lortuIdAgatik($conn, $id) {
        if (!$conn) return null;
        $stmt = $conn->prepare("SELECT * FROM produktua WHERE id=? AND aktibo=TRUE");
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
        if (!$conn) return false;
        $sql = "UPDATE produktua SET izena=?, deskripzioa=?, kategoria=?, prezioa=?, stock=? WHERE id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssdii", $this->izena, $this->deskripzioa, $this->kategoria, 
                          $this->prezioa, $this->stock, $this->id);
        $emaitza = $stmt->execute();
        $stmt->close();
        return $emaitza;
    }   

    public static function eguneratuStocka($conn, $produktu_id, $kantitatea) {
        if (!$conn) return false;
        $sql = "UPDATE produktua SET stock = stock - ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $kantitatea, $produktu_id);
        $stmt->execute();
        $stmt->close();
        return true;
    }

    // Stocka murriztea salmenta baten ondoren
    public static function murriztuStocka($conn, $produktu_id, $kantitatea) {
        if (!$conn) return false;
        $stmt = $conn->prepare("UPDATE produktua SET stock = stock - ? WHERE id=? AND stock >= ?");
        $stmt->bind_param("iii", $kantitatea, $produktu_id, $kantitatea);
        $emaitza = $stmt->execute();
        $stmt->close();
        return $emaitza;
    }

    // Desaktibatzea
    public static function desaktibatu($conn, $id) {
        if (!$conn) return false;
        $stmt = $conn->prepare("UPDATE produktua SET aktibo=FALSE WHERE id=?");
        $stmt->bind_param("i", $id);
        $emaitza = $stmt->execute();
        $stmt->close();
        return $emaitza;
    }
}
?>