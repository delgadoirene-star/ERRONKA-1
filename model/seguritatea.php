<?php
// Segurtasun-klasea (RA6 betetzeko)
class Seguritatea {

    // Sesioa hasieratzea
    public static function hasieratuSesioa(): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_set_cookie_params([
                'lifetime' => 0,
                'path' => '/',
                'domain' => '',
                'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
            ini_set('session.use_strict_mode', '1');
            session_start();
            $timeout = defined('SESSION_TIMEOUT') ? SESSION_TIMEOUT : 1800;
            if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $timeout)) {
                session_unset();
                session_destroy();
                session_start();
            }
            $_SESSION['last_activity'] = time();
            if (!isset($_SESSION['initiated'])) {
                session_regenerate_id(true);
                $_SESSION['initiated'] = true;
            }
        }
    }

    // CSRF token sortzea
    public static function generateCSRFToken(): string {
        $token = bin2hex(random_bytes(32));
        $_SESSION['csrf_token'] = $token;
        $_SESSION['csrf_token_time'] = time();
        return $token;
    }
    
    // CSRF token egiaztatzea
    public static function verifyCSRFToken(?string $token): bool {
        $sessionToken = $_SESSION['csrf_token'] ?? '';
        $ts = $_SESSION['csrf_token_time'] ?? 0;
        $lifetime = defined('CSRF_TOKEN_LIFETIME') ? CSRF_TOKEN_LIFETIME : 3600;
        if (!$token || !$sessionToken) return false;
        if (!hash_equals($sessionToken, $token)) return false;
        if ($ts && (time() - $ts) > $lifetime) return false;
        return true;
    }

    // ===== RATE LIMITING - Botaren kontrako (RA6) =====
    public static function egiaztaLoginIntentoa(string $email): bool {
        $key = 'login_attempts:' . strtolower($email);
        $_SESSION[$key] = ($_SESSION[$key] ?? 0) + 1;
        return ($_SESSION[$key] <= (defined('LOGIN_MAX_ATTEMPTS') ? LOGIN_MAX_ATTEMPTS : 5));
    }
    
    // Generic rate limiting for any action
    public static function egiaztaRateLimit(string $action, string $identifier, int $maxAttempts = 5): bool {
        $key = 'rate_limit:' . $action . ':' . strtolower($identifier);
        $_SESSION[$key] = ($_SESSION[$key] ?? 0) + 1;
        $_SESSION[$key . ':time'] = $_SESSION[$key . ':time'] ?? time();
        
        if (time() - ($_SESSION[$key . ':time'] ?? 0) > 900) {
            $_SESSION[$key] = 1;
            $_SESSION[$key . ':time'] = time();
        }
        
        return $_SESSION[$key] <= $maxAttempts;
    }
    
    // Login saioak garbitzea
    public static function zuritu_login_intentoak(string $email): void {
        $key = 'login_attempts:' . strtolower($email);
        unset($_SESSION[$key]);
    }
    
    // Generic rate limit reset
    public static function zuritu_rate_limit(string $action, string $identifier): void {
        $key = 'rate_limit:' . $action . ':' . strtolower($identifier);
        unset($_SESSION[$key]);
        unset($_SESSION[$key . ':time']);
    }

    // ===== PASAHITZAREN BALIOZTAPENA (RA6) =====
    public static function balioztaPasahitza(string $password): bool {
        $len = strlen($password) >= 8;
        $upp = preg_match('/[A-Z]/', $password);
        $low = preg_match('/[a-z]/', $password);
        $dig = preg_match('/\d/', $password);
        $spe = preg_match('/[^a-zA-Z0-9]/', $password);
        return $len && $upp && $low && $dig && $spe;
    }

    // ===== AUTENTIFIKAZIOA (RA6) =====
    public static function egiaztautentifikazioa($conn, $email, $password) {
        if (!$conn) {
            return false;
        }
        $stmt = $conn->prepare("SELECT id, email, password, rol, aktibo FROM usuario WHERE email = ?");
        if (!$stmt) return false;
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
    public static function logSeguritatea($conn, string $evento, string $detaleak, ?int $usuarioId = null): void {
        try {
            $ip = $_SERVER['REMOTE_ADDR'] ?? '';
            $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
            if (!$conn) {
                error_log("Seguritatea log (no DB): {$evento} - {$detaleak} - user: {$usuarioId} - ip: {$ip} - ua: {$ua}");
                return;
            }
            $stmt = $conn->prepare("INSERT INTO seguritatea_loga (event_type, event_scope, usuario_id, ip, detail) VALUES (?, ?, ?, ?, ?)");
            if (!$stmt) {
                error_log("Seguritatea log prepare error: " . $conn->error);
                return;
            }
            $stmt->bind_param("ssiss", $evento, $detaleak, $usuarioId, $ip, $ua);
            $stmt->execute();
            $stmt->close();
        } catch (\Throwable $e) {
            error_log("Seguritatea log error: " . $e->getMessage());
        }
    }
    
    // Sesioaren egiaztapena
    public static function egiaztaSesioa(): void {
        self::hasieratuSesioa();
        if (empty($_SESSION['usuario_id'])) {
            if (function_exists('redirect_to')) {
                redirect_to('/signin.php');
            } else {
                header('Location: /signin.php');
                exit;
            }
        }
    }

}