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
        $this->aktibo = 1;
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
        $hash = password_hash($this->password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO usuario (izena, abizena, nan, email, user, password, rol, aktibo) VALUES (?,?,?,?,?,?,?,1)");
        $stmt->bind_param("sssssss", $this->izena, $this->abizena, $this->nan, $this->email, $this->user, $hash, $this->rol);
        $ok = $stmt->execute();
        if ($ok) {
            $this->id = $stmt->insert_id;
        }
        $stmt->close();
        return $ok;
    }

    // Email edo NANa bidez bilatzea
    public static function lortuEmailEdoNANegatik($conn, $email, $nan): ?array {
        $stmt = $conn->prepare("SELECT * FROM usuario WHERE email = ? OR nan = ?");
        if (!$stmt) return null;
        $stmt->bind_param("ss", $email, $nan);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc() ?: null;
        $stmt->close(); // ensure closure
        return $row;
    }
    
    // ID bidez bilatzea
    public static function lortuIdAgatik($conn, $id) {
        $stmt = $conn->prepare("SELECT * FROM usuario WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res->fetch_assoc();
        $stmt->close();
        return $row ?: null;
    }

    public static function lortuEmailAgatik(mysqli $conn, string $email): ?array {
        $stmt = $conn->prepare("SELECT * FROM usuario WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res->fetch_assoc();
        $stmt->close();
        return $row ?: null;
    }

    // Pasahitza aldatzea
    public function aldatuPasahitza($conn, $pasahitz_berria) {
        $hash = password_hash($pasahitz_berria, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE usuario SET password=? WHERE id=?");
        $stmt->bind_param("si", $hash, $this->id);
        $emaitza = $stmt->execute();
        $stmt->close();
        return $emaitza;
    }
}