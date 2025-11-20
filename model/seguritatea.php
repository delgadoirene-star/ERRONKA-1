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
        self::hasieratuSesioa();
        
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
    
    // CSRF token egiaztatzea
    public static function verifyCSRFToken($token) {
        self::hasieratuSesioa();
        
        if (empty($_SESSION['csrf_token'])) {
            return false;
        }
        
        return hash_equals($_SESSION['csrf_token'], $token ?? '');
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
        $erroak = [];
        
        if (strlen($password) < PASSWORD_MIN_LENGTH) {
            $erroak[] = "Pasahitza " . PASSWORD_MIN_LENGTH . " karaktere gutxienez izan behar du";
        }
        
        if (PASSWORD_REQUIRE_UPPERCASE && !preg_match('/[A-Z]/', $password)) {
            $erroak[] = "Maiuskulen letra bat behar du (A-Z)";
        }
        
        if (PASSWORD_REQUIRE_LOWERCASE && !preg_match('/[a-z]/', $password)) {
            $erroak[] = "Minuskulen letra bat behar du (a-z)";
        }
        
        if (PASSWORD_REQUIRE_NUMBERS && !preg_match('/[0-9]/', $password)) {
            $erroak[] = "Zenbaki bat behar du (0-9)";
        }
        
        if (PASSWORD_REQUIRE_SPECIAL && !preg_match('/[!@#$%^&*(),.?":{}|<>]/', $password)) {
            $erroak[] = "Karaktere espezial bat behar du (!@#$%^&*)";
        }
        
        return $erroak;
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
    public static function logSeguritatea($conn, $evento, $detaleak, $usuario_id = null) {
        try {
            $ip_helbidea = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            $user_agent = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255);
            $data = date('Y-m-d H:i:s');
            
            // Datu-basean grabatu
            $sql = "INSERT INTO segurtasun_log (usuario_id, evento, detaleak, ip_helbidea, user_agent, data) 
                    VALUES (?, ?, ?, ?, ?, ?)";
            
            $stmt = $conn->prepare($sql);
            if ($stmt) {
                $stmt->bind_param("isssss", $usuario_id, $evento, $detaleak, $ip_helbidea, $user_agent, $data);
                $stmt->execute();
                $stmt->close();
            }
            
            // Txuleton fitxategian ere (backup)
            $log_file = __DIR__ . '/../logs/security.log';
            
            if (!is_dir(__DIR__ . '/../logs')) {
                mkdir(__DIR__ . '/../logs', 0755, true);
            }
            
            $log_message = "[$data] $evento | Usuario: $usuario_id | IP: $ip_helbidea | Detaleak: $detaleak\n";
            file_put_contents($log_file, $log_message, FILE_APPEND);
            
        } catch (Exception $e) {
            error_log("Log Exception: " . $e->getMessage());
        }
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