<?php
/**
 * Customer Login page for e-commerce
 */
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../model/bezero.php';
require_once __DIR__ . '/../model/saskia.php';

// Security headers
if (!headers_sent()) {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'; img-src 'self' data: https:;");
    header("X-XSS-Protection: 1; mode=block");
}

global $db_ok, $conn;

// Redirect if already logged in
if (!empty($_SESSION['bezero_id'])) {
    header('Location: /denda.php');
    exit;
}

// CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = Seguritatea::generateCSRFToken();
}
$csrf_token = $_SESSION['csrf_token'];

$errorea = '';
$arrakasta = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postedToken = $_POST['csrf_token'] ?? '';
    $email = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';
    
    if (!Seguritatea::verifyCSRFToken($postedToken)) {
        $errorea = 'Segurtasun errorea. Saiatu berriro.';
        if ($conn) Seguritatea::logSeguritatea($conn, "CSRF_ATTACK", "Bezero login", null);
    } elseif (!Seguritatea::egiaztaLoginIntentoa($email)) {
        $errorea = 'Saiakera gehiegi. Itxaron 15 minutu.';
        if ($conn) Seguritatea::logSeguritatea($conn, "BEZERO_LOGIN_BLOQUEADO", $email, null);
    } elseif (empty($email) || empty($password)) {
        $errorea = 'Email eta pasahitza beharrezkoak dira.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errorea = 'Email formatu okerra.';
    } else {
        $bezero = Bezero::egiaztautentifikazioa($conn, $email, $password);
        
        if ($bezero) {
            Seguritatea::zuritu_login_intentoak($email);
            
            $_SESSION['bezero_id'] = $bezero['id'];
            $_SESSION['bezero_email'] = $bezero['email'];
            $_SESSION['last_activity'] = time();
            session_regenerate_id(true);
            $_SESSION['csrf_token'] = Seguritatea::generateCSRFToken();
            
            // Merge guest cart to customer cart
            Saskia::bateratu($conn, $bezero['id']);
            
            if ($conn) Seguritatea::logSeguritatea($conn, "BEZERO_LOGIN_EXITOSO", $email, $bezero['id']);
            
            // Redirect to store or previous page
            $redirect = $_SESSION['redirect_after_login'] ?? '/denda.php';
            unset($_SESSION['redirect_after_login']);
            header('Location: ' . $redirect);
            exit;
        } else {
            $errorea = 'Email edo pasahitza okerra.';
            if ($conn) Seguritatea::logSeguritatea($conn, "BEZERO_LOGIN_FAILED", $email, null);
        }
    }
}

$cssPath = '/public/assets/style.css';
?>
<!DOCTYPE html>
<html lang="eu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sartu - <?= htmlspecialchars(EMPRESA_IZENA ?? 'Artisau Denda') ?></title>
    <link rel="stylesheet" href="<?= htmlspecialchars($cssPath) ?>">
    <style>
        .login-page {
            background: linear-gradient(135deg, var(--brand) 0%, var(--brand-dark) 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }
        .login-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            padding: 2.5rem;
            width: 100%;
            max-width: 420px;
        }
        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        .login-header h1 {
            font-size: 1.75rem;
            color: var(--text-dark);
            margin-bottom: 0.5rem;
        }
        .login-header p {
            color: var(--text-light);
        }
        .login-form {
            display: flex;
            flex-direction: column;
            gap: 1.25rem;
        }
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--text-dark);
        }
        .form-group input {
            width: 100%;
            padding: 0.85rem;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            font-size: 1rem;
        }
        .form-group input:focus {
            outline: none;
            border-color: var(--brand);
            box-shadow: 0 0 0 3px rgba(0, 87, 184, 0.1);
        }
        .submit-btn {
            width: 100%;
            padding: 1rem;
            background: var(--brand);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: 700;
            cursor: pointer;
            margin-top: 0.5rem;
        }
        .submit-btn:hover {
            background: var(--brand-dark);
        }
        .login-footer {
            text-align: center;
            margin-top: 1.5rem;
            color: var(--text-light);
        }
        .login-footer a {
            color: var(--brand);
            font-weight: 600;
            text-decoration: none;
        }
        .login-alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
        }
        .login-alert.error {
            background: #fff5f5;
            border: 1px solid #feb2b2;
            color: #c53030;
        }
        .back-link {
            display: block;
            text-align: center;
            margin-bottom: 1.5rem;
            color: white;
            text-decoration: none;
            font-size: 0.9rem;
        }
        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body class="login-page">
    <div>
        <a href="/denda.php" class="back-link">← Dendara itzuli</a>
        <div class="login-card">
            <div class="login-header">
                <h1>🛍️ Sartu</h1>
                <p>Sartu zure kontuan erosten jarraitzeko</p>
            </div>
            
            <?php if ($errorea): ?>
                <div class="login-alert error">
                    <?= htmlspecialchars($errorea) ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" class="login-form" novalidate>
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" required 
                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                           placeholder="zure@emaila.eus">
                </div>
                
                <div class="form-group">
                    <label>Pasahitza</label>
                    <input type="password" name="password" required
                           placeholder="••••••••">
                </div>
                
                <button type="submit" class="submit-btn">Sartu</button>
            </form>
            
            <div class="login-footer">
                <p>Konturik ez duzu? <a href="/denda-erregistratu.php">Erregistratu</a></p>
            </div>
        </div>
    </div>
</body>
</html>
