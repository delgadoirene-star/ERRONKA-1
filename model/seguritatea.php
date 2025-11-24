<?php
// Segurtasun-klasea (RA6 betetzeko)
class Seguritatea {
    
    // Sesioa hasieratzea
    public static function hasieratuSesioa() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
    
    // CSRF token sortzea
    public static function generateCSRFToken() {
        return bin2hex(random_bytes(32));
    }
    
    // CSRF token egiaztatzea
    public static function verifyCSRFToken($token) {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }

    // ===== RATE LIMITING - Botaren kontrako (RA6) =====
    public static function egiaztaLoginIntentoa($email) {
        self::hasieratuSesioa();
        
        $key = 'login_attempt_' . md5($email);
        $timeout_key = 'login_timeout_' . md5($email);
        
        // Timeout aktiboan badago
        if (isset($_SESSION[$timeout_key]) && time() < $_SESSION[$timeout_key]) {
            return false;
        }
        
        // Lehena bada
        if (!isset($_SESSION[$key])) {
            $_SESSION[$key] = 0;
        }
        
        $_SESSION[$key]++;
        
        // Max saioak gainditu
        if ($_SESSION[$key] > LOGIN_MAX_ATTEMPTS) {
            $_SESSION[$timeout_key] = time() + LOGIN_ATTEMPT_TIMEOUT;
            return false;
        }
        
        return true;
    }
    
    // Login saioak garbitzea
    public static function zuritu_login_intentoak($email) {
        self::hasieratuSesioa();
        $key = 'login_attempt_' . md5($email);
        $timeout_key = 'login_timeout_' . md5($email);
        
        unset($_SESSION[$key]);
        unset($_SESSION[$timeout_key]);
    }

    // ===== PASAHITZAREN BALIOZTAPENA (RA6) =====
    public static function balioztaPasahitza($password) {
        $errors = [];
        if (strlen($password) < PASSWORD_MIN_LENGTH) $errors[] = "Gutxienez " . PASSWORD_MIN_LENGTH . " karaktere.";
        if (!preg_match('/[A-Z]/', $password)) $errors[] = "Maiuskula bat gutxienez.";
        if (!preg_match('/[a-z]/', $password)) $errors[] = "Minuskula bat gutxienez.";
        if (!preg_match('/\d/', $password)) $errors[] = "Zenbaki bat gutxienez.";
        if (!preg_match('/[^A-Za-z\d]/', $password)) $errors[] = "Karaktere espeziala bat gutxienez.";
        return $errors;
    }

    // ===== AUTENTIFIKAZIOA (RA6) =====
    public static function egiaztautentifikazioa($conn, $email, $password) {
        $stmt = $conn->prepare("SELECT id, email, password, rol, aktibo FROM usuario WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password']) && $user['aktibo'] == 1) {
                return $user;
            }
        }
        return false;
    }

    // ===== EMAIL EGIAZTAPENA (RA6) =====
    public static function sortuEmailEgiaztamenduToken($email) {
        $token = bin2hex(random_bytes(32));
        $_SESSION['email_verify_' . $email] = [
            'token' => $token,
            'created' => time()
        ];
        return $token;
    }

    public static function egiaztaEmailToken($email, $token) {
        $key = 'email_verify_' . $email;
        
        if (!isset($_SESSION[$key])) {
            return false;
        }
        
        $data = $_SESSION[$key];
        $orain = time();
        
        // 24 ordutan baizik ez
        if ($orain - $data['created'] > 86400) {
            unset($_SESSION[$key]);
            return false;
        }
        
        if (hash_equals($data['token'], $token)) {
            unset($_SESSION[$key]);
            return true;
        }
        
        return false;
    }

    // ===== LOGGING - SEGURITATEA (RA8) =====
    public static function logSeguritatea($conn, $ekintza, $data, $usuario_id) {
        $stmt = $conn->prepare("INSERT INTO seguritatea_loga (usuario_id, ekintza, data, ip) VALUES (?, ?, NOW(), ?)");
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $stmt->bind_param("iss", $usuario_id, $ekintza, $ip);
        $stmt->execute();
    }
    
    // Sesioaren egiaztapena
    public static function egiaztaSesioa() {
        self::hasieratuSesioa();
        
        if (!isset($_SESSION['usuario_id'])) {
            return false;
        }
        
        // Session timeout
        $timeout = SESSION_TIMEOUT;
        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout) {
            session_destroy();
            return false;
        }
        
        $_SESSION['last_activity'] = time();
        return true;
    }

}