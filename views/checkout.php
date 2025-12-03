<?php
/**
 * Checkout - Order processing page with secure payment simulation
 * Collects customer information and creates order
 */
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../model/produktua.php';
require_once __DIR__ . '/../model/saskia.php';
require_once __DIR__ . '/../model/eskaera.php';
require_once __DIR__ . '/../model/bezero.php';

// Security headers
if (!headers_sent()) {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'; img-src 'self' data: https:;");
    header("X-XSS-Protection: 1; mode=block");
    header("Strict-Transport-Security: max-age=31536000; includeSubDomains");
}

global $db_ok, $conn;

// CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = Seguritatea::generateCSRFToken();
}
$csrf_token = $_SESSION['csrf_token'];

$errorea = '';
$eskaera_id = null;

// Check if cart is empty
$saskia_items = [];
$saskia_totala = 0;

if ($db_ok && $conn) {
    $bezero_id = $_SESSION['bezero_id'] ?? null;
    $saskia_id = Saskia::lortuEdoSortu($conn, $bezero_id);
    
    if ($saskia_id) {
        $saskia_items = Saskia::lortuItemak($conn, $saskia_id);
        $saskia_totala = Saskia::lortuTotala($conn, $saskia_id);
    }
}

// Redirect to cart if empty
if (empty($saskia_items)) {
    header('Location: /saskia.php');
    exit;
}

// Pre-fill form with customer data if logged in
$bezero_data = [];
if ($db_ok && $conn && !empty($_SESSION['bezero_id'])) {
    $bezero_data = Bezero::lortuIdAgatik($conn, $_SESSION['bezero_id']) ?? [];
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!Seguritatea::verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errorea = 'Segurtasun errorea. Saiatu berriro.';
        if ($conn) Seguritatea::logSeguritatea($conn, "CSRF_ATTACK", "Checkout orrialdean", null);
    } 
    // Rate limiting
    elseif (!Seguritatea::egiaztaRateLimit('checkout', $_SERVER['REMOTE_ADDR'], 10)) {
        $errorea = 'Saiakera gehiegi. Itxaron minutu batzuk.';
        if ($conn) Seguritatea::logSeguritatea($conn, "CHECKOUT_RATE_LIMIT", $_SERVER['REMOTE_ADDR'], null);
    }
    else {
        // Validate inputs
        $izena = trim($_POST['izena'] ?? '');
        $abizena = trim($_POST['abizena'] ?? '');
        $email = strtolower(trim($_POST['email'] ?? ''));
        $telefonoa = trim($_POST['telefonoa'] ?? '');
        $helbidea = trim($_POST['helbidea'] ?? '');
        $hiria = trim($_POST['hiria'] ?? '');
        $posta_kodea = trim($_POST['posta_kodea'] ?? '');
        $probintzia = trim($_POST['probintzia'] ?? '');
        $ordainketa_metodoa = $_POST['ordainketa_metodoa'] ?? 'tarjeta';
        $oharra = trim($_POST['oharra'] ?? '');
        
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
        if (!empty($telefonoa) && !preg_match('/^[0-9\s\+\-]{6,20}$/', $telefonoa)) {
            $errors[] = 'Telefono formatu okerra';
        }
        if (empty($helbidea) || strlen($helbidea) < 5) {
            $errors[] = 'Helbidea beharrezkoa da';
        }
        if (empty($hiria) || strlen($hiria) < 2) {
            $errors[] = 'Hiria beharrezkoa da';
        }
        if (empty($posta_kodea) || !preg_match('/^[0-9]{5}$/', $posta_kodea)) {
            $errors[] = 'Posta kodea beharrezkoa da (5 digitu)';
        }
        if (empty($probintzia)) {
            $errors[] = 'Probintzia beharrezkoa da';
        }
        if (!in_array($ordainketa_metodoa, ['tarjeta', 'transferentzia', 'ordainketa_entrega'])) {
            $errors[] = 'Ordainketa metodo okerra';
        }
        
        if (!empty($errors)) {
            $errorea = implode('<br>', $errors);
        } else {
            // Create order
            $bezero_datuak = [
                'bezero_id' => $_SESSION['bezero_id'] ?? null,
                'izena' => $izena,
                'abizena' => $abizena,
                'email' => $email,
                'telefonoa' => $telefonoa,
                'helbidea' => $helbidea,
                'hiria' => $hiria,
                'posta_kodea' => $posta_kodea,
                'probintzia' => $probintzia,
                'ordainketa_metodoa' => $ordainketa_metodoa,
                'oharra' => $oharra
            ];
            
            $eskaera_id = Eskaera::sortuSaskiatik($conn, $saskia_id, $bezero_datuak);
            
            if ($eskaera_id) {
                // Log successful order
                if ($conn) {
                    Seguritatea::logSeguritatea($conn, "ESKAERA_CREATED", "Order #$eskaera_id - $email", $_SESSION['bezero_id'] ?? null);
                }
                
                // Regenerate CSRF token
                $_SESSION['csrf_token'] = Seguritatea::generateCSRFToken();
                $csrf_token = $_SESSION['csrf_token'];
            } else {
                $errorea = 'Ezin izan da eskaera sortu. Saiatu berriro.';
            }
        }
    }
}

$cssPath = '/public/assets/style.css';
$dendaPath = '/denda.php';
$saskiaPath = '/saskia.php';
?>
<!DOCTYPE html>
<html lang="eu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - <?= htmlspecialchars(EMPRESA_IZENA ?? 'Artisau Denda') ?></title>
    <link rel="stylesheet" href="<?= htmlspecialchars($cssPath) ?>">
    <style>
        .checkout-page {
            background: #f8f9fa;
            min-height: 100vh;
        }
        .checkout-header {
            background: linear-gradient(135deg, var(--brand) 0%, var(--brand-dark) 100%);
            color: white;
            padding: 1rem 0;
        }
        .checkout-header-inner {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .checkout-header a {
            color: white;
            text-decoration: none;
            font-size: 1.25rem;
            font-weight: 700;
        }
        .checkout-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }
        .checkout-layout {
            display: grid;
            grid-template-columns: 1fr 400px;
            gap: 2rem;
            align-items: start;
        }
        @media (max-width: 900px) {
            .checkout-layout {
                grid-template-columns: 1fr;
            }
        }
        
        /* Order Complete */
        .order-complete {
            text-align: center;
            padding: 3rem 2rem;
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
        }
        .order-complete-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
        }
        .order-complete h1 {
            color: var(--brand-dark);
            margin-bottom: 0.5rem;
        }
        .order-number {
            display: inline-block;
            background: #e8f5e9;
            color: #2e7d32;
            padding: 0.5rem 1.5rem;
            border-radius: 25px;
            font-size: 1.25rem;
            font-weight: 700;
            margin: 1rem 0;
        }
        
        /* Checkout Form */
        .checkout-form {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            padding: 2rem;
        }
        .checkout-form h2 {
            font-size: 1.25rem;
            color: var(--text-dark);
            margin-bottom: 1.5rem;
            padding-bottom: 0.75rem;
            border-bottom: 1px solid var(--border-color);
        }
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }
        .form-grid .full-width {
            grid-column: 1 / -1;
        }
        @media (max-width: 600px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
        }
        .form-group {
            margin-bottom: 1rem;
        }
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--text-dark);
            font-size: 0.9rem;
        }
        .form-group label .required {
            color: #dc3545;
        }
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            font-size: 1rem;
        }
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--brand);
            box-shadow: 0 0 0 3px rgba(0, 87, 184, 0.1);
        }
        
        /* Payment Methods */
        .payment-methods {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }
        .payment-method {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 1rem;
            border: 2px solid var(--border-color);
            border-radius: 8px;
            cursor: pointer;
            transition: border-color 0.2s;
        }
        .payment-method:hover {
            border-color: var(--brand);
        }
        .payment-method input {
            width: auto;
        }
        .payment-method input:checked + .payment-label {
            color: var(--brand-dark);
            font-weight: 600;
        }
        .payment-method:has(input:checked) {
            border-color: var(--brand);
            background: rgba(0, 87, 184, 0.05);
        }
        .payment-icon {
            font-size: 1.5rem;
        }
        
        /* Order Summary */
        .order-summary {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            padding: 1.5rem;
            position: sticky;
            top: 2rem;
        }
        .order-summary h2 {
            font-size: 1.25rem;
            color: var(--text-dark);
            margin-bottom: 1.5rem;
            padding-bottom: 0.75rem;
            border-bottom: 1px solid var(--border-color);
        }
        .order-items {
            max-height: 300px;
            overflow-y: auto;
            margin-bottom: 1rem;
        }
        .order-item {
            display: flex;
            gap: 1rem;
            padding: 0.75rem 0;
            border-bottom: 1px solid var(--border-color);
        }
        .order-item:last-child {
            border-bottom: none;
        }
        .order-item-image {
            width: 60px;
            height: 60px;
            background: #f0f0f0;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            overflow: hidden;
        }
        .order-item-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .order-item-info {
            flex: 1;
        }
        .order-item-name {
            font-weight: 600;
            font-size: 0.9rem;
            color: var(--text-dark);
        }
        .order-item-qty {
            font-size: 0.8rem;
            color: var(--text-light);
        }
        .order-item-price {
            font-weight: 600;
            color: var(--brand-dark);
        }
        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 0.5rem 0;
            font-size: 0.95rem;
        }
        .summary-row.total {
            border-top: 2px solid var(--border-color);
            margin-top: 0.5rem;
            padding-top: 1rem;
            font-size: 1.25rem;
            font-weight: 700;
        }
        .summary-row.total .amount {
            color: var(--brand-dark);
        }
        .submit-btn {
            display: block;
            width: 100%;
            padding: 1rem;
            background: var(--brand);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: 700;
            cursor: pointer;
            margin-top: 1.5rem;
            transition: background 0.2s;
        }
        .submit-btn:hover {
            background: var(--brand-dark);
        }
        
        /* Security badges */
        .security-badges {
            display: flex;
            justify-content: center;
            gap: 1rem;
            margin-top: 1.5rem;
            padding-top: 1rem;
            border-top: 1px solid var(--border-color);
        }
        .security-badge {
            display: flex;
            align-items: center;
            gap: 0.25rem;
            font-size: 0.8rem;
            color: var(--text-light);
        }
        
        /* Alert */
        .checkout-alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
        }
        .checkout-alert.error {
            background: #fff5f5;
            border: 1px solid #feb2b2;
            color: #c53030;
        }
    </style>
</head>
<body class="checkout-page">
    <!-- Header -->
    <header class="checkout-header">
        <div class="checkout-header-inner">
            <a href="<?= htmlspecialchars($dendaPath) ?>">🛍️ <?= htmlspecialchars(EMPRESA_IZENA ?? 'Artisau Denda') ?></a>
            <span style="display: flex; align-items: center; gap: 0.5rem;">🔒 Checkout segurua</span>
        </div>
    </header>
    
    <main class="checkout-container">
        <?php if ($eskaera_id): ?>
            <!-- Order Complete -->
            <div class="order-complete">
                <div class="order-complete-icon">✅</div>
                <h1>Eskerrik asko zure eskaeragatik!</h1>
                <p>Zure eskaera jaso dugu eta laster prozesatuko dugu.</p>
                <div class="order-number">Eskaera #<?= $eskaera_id ?></div>
                <p style="color: var(--text-light); margin-top: 1rem;">
                    Berresteko emaila bidaliko dizugu <strong><?= htmlspecialchars($_POST['email'] ?? '') ?></strong> helbidera.
                </p>
                <div style="margin-top: 2rem;">
                    <a href="<?= htmlspecialchars($dendaPath) ?>" class="btn btn-primary">Erosten jarraitu</a>
                </div>
            </div>
        <?php else: ?>
            <div class="checkout-layout">
                <!-- Checkout Form -->
                <div class="checkout-form">
                    <h1 style="font-size: 1.75rem; margin-bottom: 1.5rem;">🛒 Checkout</h1>
                    
                    <?php if ($errorea): ?>
                        <div class="checkout-alert error">
                            <?= $errorea ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" novalidate>
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                        
                        <h2>📦 Bidalketa datuak</h2>
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Izena <span class="required">*</span></label>
                                <input type="text" name="izena" required maxlength="80"
                                       value="<?= htmlspecialchars($_POST['izena'] ?? $bezero_data['izena'] ?? '') ?>">
                            </div>
                            <div class="form-group">
                                <label>Abizena <span class="required">*</span></label>
                                <input type="text" name="abizena" required maxlength="120"
                                       value="<?= htmlspecialchars($_POST['abizena'] ?? $bezero_data['abizena'] ?? '') ?>">
                            </div>
                            <div class="form-group">
                                <label>Email <span class="required">*</span></label>
                                <input type="email" name="email" required maxlength="160"
                                       value="<?= htmlspecialchars($_POST['email'] ?? $bezero_data['email'] ?? '') ?>">
                            </div>
                            <div class="form-group">
                                <label>Telefonoa</label>
                                <input type="tel" name="telefonoa" maxlength="20"
                                       value="<?= htmlspecialchars($_POST['telefonoa'] ?? $bezero_data['telefonoa'] ?? '') ?>">
                            </div>
                            <div class="form-group full-width">
                                <label>Helbidea <span class="required">*</span></label>
                                <input type="text" name="helbidea" required
                                       value="<?= htmlspecialchars($_POST['helbidea'] ?? $bezero_data['helbidea'] ?? '') ?>"
                                       placeholder="Kalea, zenbakia, pisua...">
                            </div>
                            <div class="form-group">
                                <label>Hiria <span class="required">*</span></label>
                                <input type="text" name="hiria" required maxlength="100"
                                       value="<?= htmlspecialchars($_POST['hiria'] ?? $bezero_data['hiria'] ?? '') ?>">
                            </div>
                            <div class="form-group">
                                <label>Posta kodea <span class="required">*</span></label>
                                <input type="text" name="posta_kodea" required pattern="[0-9]{5}" maxlength="5"
                                       value="<?= htmlspecialchars($_POST['posta_kodea'] ?? $bezero_data['posta_kodea'] ?? '') ?>"
                                       placeholder="01234">
                            </div>
                            <div class="form-group full-width">
                                <label>Probintzia <span class="required">*</span></label>
                                <select name="probintzia" required>
                                    <option value="">-- Aukeratu --</option>
                                    <?php
                                    $probintziak = ['Araba', 'Bizkaia', 'Gipuzkoa', 'Nafarroa', 'Lapurdi', 'Zuberoa', 'Behe Nafarroa'];
                                    $selected = $_POST['probintzia'] ?? $bezero_data['probintzia'] ?? '';
                                    foreach ($probintziak as $p): ?>
                                        <option value="<?= $p ?>" <?= $selected === $p ? 'selected' : '' ?>><?= $p ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <h2 style="margin-top: 2rem;">💳 Ordainketa metodoa</h2>
                        <div class="payment-methods">
                            <label class="payment-method">
                                <input type="radio" name="ordainketa_metodoa" value="tarjeta" 
                                       <?= ($_POST['ordainketa_metodoa'] ?? 'tarjeta') === 'tarjeta' ? 'checked' : '' ?>>
                                <span class="payment-icon">💳</span>
                                <span class="payment-label">Kreditu/Debitu txartela</span>
                            </label>
                            <label class="payment-method">
                                <input type="radio" name="ordainketa_metodoa" value="transferentzia"
                                       <?= ($_POST['ordainketa_metodoa'] ?? '') === 'transferentzia' ? 'checked' : '' ?>>
                                <span class="payment-icon">🏦</span>
                                <span class="payment-label">Banku transferentzia</span>
                            </label>
                            <label class="payment-method">
                                <input type="radio" name="ordainketa_metodoa" value="ordainketa_entrega"
                                       <?= ($_POST['ordainketa_metodoa'] ?? '') === 'ordainketa_entrega' ? 'checked' : '' ?>>
                                <span class="payment-icon">🚚</span>
                                <span class="payment-label">Ordainketa entregatzean</span>
                            </label>
                        </div>
                        
                        <h2 style="margin-top: 2rem;">📝 Oharrak (aukerakoa)</h2>
                        <div class="form-group">
                            <textarea name="oharra" rows="3" placeholder="Eskaerari buruzko oharrak..."><?= htmlspecialchars($_POST['oharra'] ?? '') ?></textarea>
                        </div>
                        
                        <button type="submit" class="submit-btn">
                            🔒 Eskaera konfirmatu (<?= number_format($saskia_totala, 2) ?> €)
                        </button>
                        
                        <div class="security-badges">
                            <span class="security-badge">🔒 SSL segurua</span>
                            <span class="security-badge">✓ GDPR betetzen</span>
                            <span class="security-badge">🛡️ Datuen babesa</span>
                        </div>
                    </form>
                </div>
                
                <!-- Order Summary -->
                <div class="order-summary">
                    <h2>Zure eskaera</h2>
                    <div class="order-items">
                        <?php foreach ($saskia_items as $item): ?>
                            <div class="order-item">
                                <div class="order-item-image">
                                    <?php if (!empty($item['irudia'])): ?>
                                        <img src="<?= htmlspecialchars($item['irudia']) ?>" alt="">
                                    <?php else: ?>
                                        🎨
                                    <?php endif; ?>
                                </div>
                                <div class="order-item-info">
                                    <div class="order-item-name"><?= htmlspecialchars($item['izena']) ?></div>
                                    <div class="order-item-qty">Kant: <?= $item['kantitatea'] ?></div>
                                </div>
                                <div class="order-item-price"><?= number_format($item['azpi_totala'], 2) ?> €</div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="summary-row">
                        <span>Azpi-totala</span>
                        <span><?= number_format($saskia_totala, 2) ?> €</span>
                    </div>
                    <div class="summary-row">
                        <span>Bidalketa</span>
                        <span>Doako</span>
                    </div>
                    <div class="summary-row total">
                        <span>Guztira</span>
                        <span class="amount"><?= number_format($saskia_totala, 2) ?> €</span>
                    </div>
                    
                    <p style="margin-top: 1.5rem; font-size: 0.85rem; color: var(--text-light); text-align: center;">
                        <a href="<?= htmlspecialchars($saskiaPath) ?>">← Saskira itzuli</a>
                    </p>
                </div>
            </div>
        <?php endif; ?>
    </main>
</body>
</html>
