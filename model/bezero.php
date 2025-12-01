<?php
/**
 * Bezero (Customer) model for e-commerce functionality
 */
require_once __DIR__ . '/seguritatea.php';

class Bezero {
    private $id;
    private $izena;
    private $abizena;
    private $email;
    private $password;
    private $telefonoa;
    private $helbidea;
    private $hiria;
    private $posta_kodea;
    private $probintzia;
    private $aktibo;

    public function __construct($izena, $abizena, $email, $password, $telefonoa = '', 
                                $helbidea = '', $hiria = '', $posta_kodea = '', $probintzia = '') {
        $this->izena = $izena;
        $this->abizena = $abizena;
        $this->email = $email;
        $this->password = $password;
        $this->telefonoa = $telefonoa;
        $this->helbidea = $helbidea;
        $this->hiria = $hiria;
        $this->posta_kodea = $posta_kodea;
        $this->probintzia = $probintzia;
        $this->aktibo = 1;
    }

    public function getId() { return $this->id; }
    public function getIzena() { return $this->izena; }
    public function getAbizena() { return $this->abizena; }
    public function getEmail() { return $this->email; }
    public function getTelefonoa() { return $this->telefonoa; }
    public function getHelbidea() { return $this->helbidea; }
    public function getHiria() { return $this->hiria; }
    public function getPostakodea() { return $this->posta_kodea; }
    public function getProbintzia() { return $this->probintzia; }
    public function getNombreCompleto() { return $this->izena . " " . $this->abizena; }

    public function setId($id) { $this->id = $id; }

    /**
     * Create new customer
     */
    public function sortu($conn): bool {
        if (!$conn) {
            error_log("Bezero::sortu called without DB connection");
            return false;
        }
        $hash = password_hash($this->password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO bezero (izena, abizena, email, password, telefonoa, helbidea, hiria, posta_kodea, probintzia, aktibo) VALUES (?,?,?,?,?,?,?,?,?,1)");
        if (!$stmt) {
            error_log("Bezero prepare error: " . $conn->error);
            return false;
        }
        $stmt->bind_param("sssssssss", $this->izena, $this->abizena, $this->email, $hash, 
                          $this->telefonoa, $this->helbidea, $this->hiria, $this->posta_kodea, $this->probintzia);
        $ok = $stmt->execute();
        if ($ok) {
            $this->id = $stmt->insert_id;
        }
        $stmt->close();
        return $ok;
    }

    /**
     * Find customer by email
     */
    public static function lortuEmailAgatik($conn, string $email): ?array {
        if (!$conn) return null;
        $stmt = $conn->prepare("SELECT * FROM bezero WHERE email = ?");
        if (!$stmt) return null;
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res->fetch_assoc();
        $stmt->close();
        return $row ?: null;
    }

    /**
     * Find customer by ID
     */
    public static function lortuIdAgatik($conn, int $id): ?array {
        if (!$conn) return null;
        $stmt = $conn->prepare("SELECT * FROM bezero WHERE id = ?");
        if (!$stmt) return null;
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res->fetch_assoc();
        $stmt->close();
        return $row ?: null;
    }

    /**
     * Authenticate customer
     */
    public static function egiaztautentifikazioa($conn, string $email, string $password): ?array {
        if (!$conn) return null;
        $stmt = $conn->prepare("SELECT * FROM bezero WHERE email = ? AND aktibo = 1");
        if (!$stmt) return null;
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows === 1) {
            $bezero = $result->fetch_assoc();
            if (password_verify($password, $bezero['password'])) {
                $stmt->close();
                return $bezero;
            }
        }
        $stmt->close();
        return null;
    }

    /**
     * Update customer profile
     */
    public static function eguneratu($conn, int $id, array $data): bool {
        if (!$conn) return false;
        $stmt = $conn->prepare("UPDATE bezero SET izena=?, abizena=?, telefonoa=?, helbidea=?, hiria=?, posta_kodea=?, probintzia=? WHERE id=?");
        if (!$stmt) return false;
        $stmt->bind_param("sssssssi", 
            $data['izena'], $data['abizena'], $data['telefonoa'], 
            $data['helbidea'], $data['hiria'], $data['posta_kodea'], $data['probintzia'], $id);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    /**
     * Change password
     */
    public static function aldatuPasahitza($conn, int $id, string $password_berria): bool {
        if (!$conn) return false;
        $hash = password_hash($password_berria, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE bezero SET password=? WHERE id=?");
        if (!$stmt) return false;
        $stmt->bind_param("si", $hash, $id);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }
}
