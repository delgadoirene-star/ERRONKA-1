<?php
require_once 'seguritatea.php';

class Usuario {
    private $id;
    private $izena;
    private $abizena;
    private $nan;
    private $email;
    private $user;
    private $password;
    private $rol;
    private $aktibo;
    private $created_at;

    public function __construct($izena, $abizena, $nan, $email, $user, $password, $rol = 'langilea') {
        $this->izena = $izena;
        $this->abizena = $abizena;
        $this->nan = $nan;
        $this->email = $email;
        $this->user = $user;
        $this->password = $password;
        $this->rol = $rol;
    }

    // Getters
    public function getId() { return $this->id; }
    public function getIzena() { return $this->izena; }
    public function getAbizena() { return $this->abizena; }
    public function getNan() { return $this->nan; }
    public function getEmail() { return $this->email; }
    public function getUser() { return $this->user; }
    public function getRol() { return $this->rol; }
    public function getNombreCompleto() { return $this->izena . " " . $this->abizena; }

    // Setters
    public function setId($id) { $this->id = $id; }
    public function setRol($rol) { $this->rol = $rol; }

    // Sortzea
    public function sortu($conn) {
        $sql = "INSERT INTO usuario (izena, abizena, nan, email, user, password, rol) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            return false;
        }
        
        $stmt->bind_param("sssssss", $this->izena, $this->abizena, $this->nan, 
                          $this->email, $this->user, $this->password, $this->rol);
        
        $emaitza = $stmt->execute();
        
        if ($emaitza) {
            $this->id = $conn->insert_id;
        }
        
        $stmt->close();
        return $emaitza;
    }

    // Email edo NANa bidez bilatzea
    public static function lortuEmailEdoNANegatik($conn, $email, $nan) {
        $stmt = $conn->prepare("SELECT * FROM usuario WHERE email = ? OR nan = ?");
        $stmt->bind_param("ss", $email, $nan);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }
    
    // ID bidez bilatzea
    public static function lortuIdAgatik($conn, $id) {
        $stmt = $conn->prepare("SELECT * FROM usuario WHERE id=? AND aktibo=TRUE");
        if (!$stmt) {
            return null;
        }
        
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            return $result->fetch_assoc();
        }
        
        $stmt->close();
        return null;
    }
    
    // Pasahitza aldatzea
    public function aldatuPasahitza($conn, $pasahitz_berria) {
        $hash = password_hash($pasahitz_berria, PASSWORD_ARGON2ID);
        $stmt = $conn->prepare("UPDATE usuario SET password=? WHERE id=?");
        $stmt->bind_param("si", $hash, $this->id);
        $emaitza = $stmt->execute();
        $stmt->close();
        return $emaitza;
    }
}

// Siempre usar prepared statements, incluso en fallback
$stmt = $conn->prepare("DELETE FROM langilea WHERE id = ?");
$stmt->bind_param("i", $id);
$ok = $stmt->execute();
$stmt->close();
?>