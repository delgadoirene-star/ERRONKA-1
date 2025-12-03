<?php
/**
 * Customer Registration page for e-commerce
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
    $izena = trim($_POST['izena'] ?? '');
    $abizena = trim($_POST['abizena'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';
    $password2 = $_POST['password2'] ?? '';
    $telefonoa = trim($_POST['telefonoa'] ?? '');
    
    // Honeypot for bots
    $honeypot = trim($_POST['website'] ?? '');
    
    if (!empty($honeypot)) {
        // Bot detected
        if ($conn) Seguritatea::logSeguritatea($conn, "BOT_BEZERO_SIGNUP", $email, null);
        sleep(2);
        $errorea = 'Errorea erregistroan. Saiatu berriro.';
    } elseif (!Seguritatea::verifyCSRFToken($postedToken)) {
        $errorea = 'Segurtasun errorea. Saiatu berriro.';
        if ($conn) Seguritatea::logSeguritatea($conn, "CSRF_ATTACK", "Bezero erregistroa", null);
    } elseif (!Seguritatea::egiaztaRateLimit('bezero_signup', $email, 3)) {
        $errorea = 'Saiakera gehiegi. Itxaron 15 minutu.';
        if ($conn) Seguritatea::logSeguritatea($conn, "BEZERO_SIGNUP_RATE_LIMIT", $email, null);
    } else {
        // Validation
        $errors = [];
        
        if (empty($izena) || strlen($izena) < 2 || strlen($izena) > 80) {
            $errors[] = 'Izena beharrezkoa da (2-80 karaktere)';
        }
        if (empty($abizena) || strlen($abizena) < 2 || strlen($abizena) > 120) {
            $errors[] = 'Abizena beharrezkoa da (2-120 karaktere)';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Email formatu okerra';
        }
        if (!Seguritatea::balioztaPasahitza($password)) {
            $errors[] = 'Pasahitza ahula da. Gutxienez 8 karaktere, maiuskula, minuskula, zenbaki eta karaktere berezia behar ditu.';
        }
        if ($password !== $password2) {
            $errors[] = 'Pasahitzak ez datoz bat';
        }
        if (!empty($telefonoa) && !preg_match('/^[0-9\s\+\-]{6,20}$/', $telefonoa)) {
            $errors[] = 'Telefono formatu okerra';
        }
        
        // Check if email already exists
        if ($db_ok && $conn && empty($errors)) {
            $existing = Bezero::lortuEmailAgatik($conn, $email);
            if ($existing) {
                $errors[] = 'Email hau dagoeneko erregistratuta dago';
            }
        }
        
        if (!empty($errors)) {
            $errorea = implode('<br>', $errors);
        } else {
            // Create customer
            $bezero = new Bezero($izena, $abizena, $email, $password, $telefonoa);
            
            if ($bezero->sortu($conn)) {
                Seguritatea::zuritu_rate_limit('bezero_signup', $email);
                
                $_SESSION['bezero_id'] = $bezero->getId();
                $_SESSION['bezero_email'] = $email;
                $_SESSION['last_activity'] = time();
                session_regenerate_id(true);
                $_SESSION['csrf_token'] = Seguritatea::generateCSRFToken();
                
                // Merge guest cart
                Saskia::bateratu($conn, $bezero->getId());
                
                if ($conn) Seguritatea::logSeguritatea($conn, "BEZERO_REGISTERED", $email, $bezero->getId());
                
                $arrakasta = true;
            } else {
                $errorea = 'Ezin izan da kontua sortu. Saiatu berriro.';
            }
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
    <title>Erregistratu - <?= htmlspecialchars(EMPRESA_IZENA ?? 'Artisau Denda') ?></title>
    <link rel="stylesheet" href="<?= htmlspecialchars($cssPath) ?>">
    <style>
        .register-page {
            background: linear-gradient(135deg, var(--brand) 0%, var(--brand-dark) 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }
        .register-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            padding: 2.5rem;
            width: 100%;
            max-width: 500px;
        }
        .register-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        .register-header h1 {
            font-size: 1.75rem;
            color: var(--text-dark);
            margin-bottom: 0.5rem;
        }
        .register-header p {
            color: var(--text-light);
        }
        .register-form {
            display: flex;
            flex-direction: column;
            gap: 1.25rem;
        }
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }
        @media (max-width: 500px) {
            .form-row {
                grid-template-columns: 1fr;
            }
        }
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--text-dark);
        }
        .form-group label .required {
            color: #dc3545;
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
        .form-group small {
            display: block;
            margin-top: 0.25rem;
            font-size: 0.8rem;
            color: var(--text-light);
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
        .register-footer {
            text-align: center;
            margin-top: 1.5rem;
            color: var(--text-light);
        }
        .register-footer a {
            color: var(--brand);
            font-weight: 600;
            text-decoration: none;
        }
        .register-alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
        }
        .register-alert.error {
            background: #fff5f5;
            border: 1px solid #feb2b2;
            color: #c53030;
        }
        .register-alert.success {
            background: #e8f5e9;
            border: 1px solid #a5d6a7;
            color: #2e7d32;
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
<body class="register-page">
    <div>
        <a href="/denda.php" class="back-link">← Dendara itzuli</a>
        <div class="register-card">
            <div class="register-header">
                <h1>🛍️ Erregistratu</h1>
                <p>Sortu zure kontua erosten hasteko</p>
            </div>
            
            <?php if ($arrakasta): ?>
                <div class="register-alert success">
                    <strong>✓ Ongietorri!</strong><br>
                    Zure kontua sortu da. <a href="/denda.php">Erosten hasi</a>
                </div>
            <?php elseif ($errorea): ?>
                <div class="register-alert error">
                    <?= $errorea ?>
                </div>
            <?php endif; ?>
            
            <?php if (!$arrakasta): ?>
            <form method="POST" class="register-form" novalidate>
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                
                <!-- Honeypot -->
                <div style="position: absolute; left: -5000px;" aria-hidden="true">
                    <input type="text" name="website" tabindex="-1" autocomplete="off">
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Izena <span class="required">*</span></label>
                        <input type="text" name="izena" required maxlength="80"
                               value="<?= htmlspecialchars($_POST['izena'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>Abizena <span class="required">*</span></label>
                        <input type="text" name="abizena" required maxlength="120"
                               value="<?= htmlspecialchars($_POST['abizena'] ?? '') ?>">
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Email <span class="required">*</span></label>
                    <input type="email" name="email" required maxlength="160"
                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                           placeholder="zure@emaila.eus">
                </div>
                
                <div class="form-group">
                    <label>Telefonoa</label>
                    <input type="tel" name="telefonoa" maxlength="20"
                           value="<?= htmlspecialchars($_POST['telefonoa'] ?? '') ?>"
                           placeholder="+34 600 000 000">
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Pasahitza <span class="required">*</span></label>
                        <input type="password" name="password" required minlength="8">
                        <small>Gutx. 8 karaktere, maiusk., minusk., zenb. eta berezia</small>
                    </div>
                    <div class="form-group">
                        <label>Errepikatu pasahitza <span class="required">*</span></label>
                        <input type="password" name="password2" required minlength="8">
                    </div>
                </div>
                
                <button type="submit" class="submit-btn">Erregistratu</button>
            </form>
            <?php endif; ?>
            
            <div class="register-footer">
                <p>Dagoeneko kontu bat duzu? <a href="/denda-login.php">Sartu</a></p>
            </div>
        </div>
    </div>
</body>
</html>
