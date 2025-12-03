<?php
/**
 * Saskia (Shopping Cart) - Cart management page
 * Displays cart items and allows quantity updates
 */
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../model/produktua.php';
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

// CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = Seguritatea::generateCSRFToken();
}
$csrf_token = $_SESSION['csrf_token'];

$errorea = '';
$arrakasta = '';

// Handle cart actions (AJAX)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    if (!Seguritatea::verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        echo json_encode(['success' => false, 'error' => 'Segurtasun errorea']);
        exit;
    }
    
    if (!$db_ok || !$conn) {
        echo json_encode(['success' => false, 'error' => 'Datu-base errorea']);
        exit;
    }
    
    $action = $_POST['action'] ?? '';
    $bezero_id = $_SESSION['bezero_id'] ?? null;
    $saskia_id = Saskia::lortuEdoSortu($conn, $bezero_id);
    
    if (!$saskia_id) {
        echo json_encode(['success' => false, 'error' => 'Saskia ezin izan da lortu']);
        exit;
    }
    
    switch ($action) {
        case 'eguneratu':
            $produktu_id = filter_input(INPUT_POST, 'produktu_id', FILTER_VALIDATE_INT);
            $kantitatea = filter_input(INPUT_POST, 'kantitatea', FILTER_VALIDATE_INT);
            
            if ($produktu_id && $kantitatea !== false) {
                $ok = Saskia::eguneratuKantitatea($conn, $saskia_id, $produktu_id, $kantitatea);
                $items = Saskia::lortuItemak($conn, $saskia_id);
                $totala = Saskia::lortuTotala($conn, $saskia_id);
                $kopurua = Saskia::lortuKopurua($conn, $saskia_id);
                
                echo json_encode([
                    'success' => $ok,
                    'items' => $items,
                    'totala' => $totala,
                    'kopurua' => $kopurua
                ]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Datu okerrak']);
            }
            break;
            
        case 'kendu':
            $produktu_id = filter_input(INPUT_POST, 'produktu_id', FILTER_VALIDATE_INT);
            
            if ($produktu_id) {
                $ok = Saskia::kenduProduktua($conn, $saskia_id, $produktu_id);
                $items = Saskia::lortuItemak($conn, $saskia_id);
                $totala = Saskia::lortuTotala($conn, $saskia_id);
                $kopurua = Saskia::lortuKopurua($conn, $saskia_id);
                
                echo json_encode([
                    'success' => $ok,
                    'items' => $items,
                    'totala' => $totala,
                    'kopurua' => $kopurua
                ]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Produktu ID beharrezkoa']);
            }
            break;
            
        case 'hutsitu':
            $ok = Saskia::hutsitu($conn, $saskia_id);
            echo json_encode(['success' => $ok, 'items' => [], 'totala' => 0, 'kopurua' => 0]);
            break;
            
        default:
            echo json_encode(['success' => false, 'error' => 'Ekintza ezezaguna']);
    }
    exit;
}

// Get cart data
$saskia_items = [];
$saskia_totala = 0;
$saskia_kopurua = 0;

if ($db_ok && $conn) {
    $bezero_id = $_SESSION['bezero_id'] ?? null;
    $saskia_id = Saskia::lortuEdoSortu($conn, $bezero_id);
    
    if ($saskia_id) {
        $saskia_items = Saskia::lortuItemak($conn, $saskia_id);
        $saskia_totala = Saskia::lortuTotala($conn, $saskia_id);
        $saskia_kopurua = Saskia::lortuKopurua($conn, $saskia_id);
    }
}

$cssPath = '/public/assets/style.css';
$dendaPath = '/denda.php';
$checkoutPath = '/checkout.php';
?>
<!DOCTYPE html>
<html lang="eu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Saskia - <?= htmlspecialchars(EMPRESA_IZENA ?? 'Artisau Denda') ?></title>
    <link rel="stylesheet" href="<?= htmlspecialchars($cssPath) ?>">
    <style>
        .cart-page {
            background: #f8f9fa;
            min-height: 100vh;
        }
        .cart-header {
            background: linear-gradient(135deg, var(--brand) 0%, var(--brand-dark) 100%);
            color: white;
            padding: 1rem 0;
        }
        .cart-header-inner {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .cart-header a {
            color: white;
            text-decoration: none;
            font-size: 1.25rem;
            font-weight: 700;
        }
        .cart-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }
        .cart-title {
            margin-bottom: 2rem;
        }
        .cart-title h1 {
            font-size: 2rem;
            color: var(--text-dark);
            margin-bottom: 0.25rem;
        }
        .cart-title p {
            color: var(--text-light);
        }
        .cart-layout {
            display: grid;
            grid-template-columns: 1fr 380px;
            gap: 2rem;
            align-items: start;
        }
        @media (max-width: 900px) {
            .cart-layout {
                grid-template-columns: 1fr;
            }
        }
        
        /* Cart Items */
        .cart-items {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            overflow: hidden;
        }
        .cart-item {
            display: grid;
            grid-template-columns: 100px 1fr auto;
            gap: 1.5rem;
            padding: 1.5rem;
            border-bottom: 1px solid var(--border-color);
            align-items: center;
        }
        .cart-item:last-child {
            border-bottom: none;
        }
        .cart-item-image {
            width: 100px;
            height: 100px;
            border-radius: 8px;
            background: #f0f0f0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            overflow: hidden;
        }
        .cart-item-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .cart-item-info {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        .cart-item-name {
            font-weight: 600;
            color: var(--text-dark);
            font-size: 1.1rem;
        }
        .cart-item-category {
            font-size: 0.8rem;
            color: var(--text-light);
            text-transform: uppercase;
        }
        .cart-item-price {
            color: var(--brand-dark);
            font-weight: 600;
        }
        .cart-item-actions {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 0.75rem;
        }
        .quantity-control {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            background: #f8f9fa;
            border-radius: 8px;
            padding: 0.25rem;
        }
        .quantity-btn {
            width: 32px;
            height: 32px;
            border: none;
            background: white;
            border-radius: 6px;
            cursor: pointer;
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--text-dark);
            transition: background 0.2s;
        }
        .quantity-btn:hover {
            background: var(--brand);
            color: white;
        }
        .quantity-value {
            width: 40px;
            text-align: center;
            font-weight: 600;
            font-size: 1rem;
        }
        .cart-item-subtotal {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--brand-dark);
        }
        .remove-btn {
            background: none;
            border: none;
            color: #dc3545;
            cursor: pointer;
            font-size: 0.85rem;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            transition: background 0.2s;
        }
        .remove-btn:hover {
            background: rgba(220, 53, 69, 0.1);
        }
        
        /* Cart Summary */
        .cart-summary {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            padding: 1.5rem;
            position: sticky;
            top: 2rem;
        }
        .cart-summary h2 {
            font-size: 1.25rem;
            color: var(--text-dark);
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--border-color);
        }
        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 0.75rem 0;
            font-size: 0.95rem;
        }
        .summary-row.total {
            border-top: 2px solid var(--border-color);
            margin-top: 1rem;
            padding-top: 1rem;
            font-size: 1.25rem;
            font-weight: 700;
        }
        .summary-row.total .amount {
            color: var(--brand-dark);
        }
        .checkout-btn {
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
            text-align: center;
            text-decoration: none;
            margin-top: 1.5rem;
            transition: background 0.2s;
        }
        .checkout-btn:hover {
            background: var(--brand-dark);
        }
        .checkout-btn:disabled {
            background: #ccc;
            cursor: not-allowed;
        }
        .continue-shopping {
            display: block;
            text-align: center;
            margin-top: 1rem;
            color: var(--brand);
            text-decoration: none;
            font-weight: 600;
        }
        .continue-shopping:hover {
            text-decoration: underline;
        }
        
        /* Empty Cart */
        .cart-empty {
            text-align: center;
            padding: 4rem 2rem;
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
        }
        .cart-empty-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
        }
        .cart-empty h2 {
            color: var(--text-dark);
            margin-bottom: 0.5rem;
        }
        .cart-empty p {
            color: var(--text-light);
            margin-bottom: 1.5rem;
        }
        
        /* Security notice */
        .security-notice {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-top: 1.5rem;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 8px;
            font-size: 0.85rem;
            color: var(--text-light);
        }
        .security-notice span {
            font-size: 1.25rem;
        }
        
        @media (max-width: 600px) {
            .cart-item {
                grid-template-columns: 80px 1fr;
                gap: 1rem;
            }
            .cart-item-image {
                width: 80px;
                height: 80px;
            }
            .cart-item-actions {
                grid-column: 1 / -1;
                flex-direction: row;
                justify-content: space-between;
                align-items: center;
            }
        }
    </style>
</head>
<body class="cart-page">
    <!-- Header -->
    <header class="cart-header">
        <div class="cart-header-inner">
            <a href="<?= htmlspecialchars($dendaPath) ?>">🛍️ <?= htmlspecialchars(EMPRESA_IZENA ?? 'Artisau Denda') ?></a>
            <a href="<?= htmlspecialchars($dendaPath) ?>" style="font-size: 1rem; font-weight: 500;">← Dendan jarraitu</a>
        </div>
    </header>
    
    <main class="cart-container">
        <div class="cart-title">
            <h1>🛒 Zure saskia</h1>
            <p id="cart-count"><?= $saskia_kopurua ?> produktu saskian</p>
        </div>
        
        <?php if (empty($saskia_items)): ?>
            <div class="cart-empty">
                <div class="cart-empty-icon">🛒</div>
                <h2>Zure saskia hutsik dago</h2>
                <p>Ez duzu oraindik produkturik gehitu. Hasi erosketak egiten!</p>
                <a href="<?= htmlspecialchars($dendaPath) ?>" class="btn btn-primary">Produktuak ikusi</a>
            </div>
        <?php else: ?>
            <div class="cart-layout">
                <div class="cart-items" id="cart-items">
                    <?php foreach ($saskia_items as $item): ?>
                        <div class="cart-item" data-produktu-id="<?= $item['produktu_id'] ?>">
                            <div class="cart-item-image">
                                <?php if (!empty($item['irudia'])): ?>
                                    <img src="<?= htmlspecialchars($item['irudia']) ?>" alt="<?= htmlspecialchars($item['izena']) ?>">
                                <?php else: ?>
                                    🎨
                                <?php endif; ?>
                            </div>
                            <div class="cart-item-info">
                                <span class="cart-item-category"><?= htmlspecialchars($item['kategoria'] ?? '') ?></span>
                                <span class="cart-item-name"><?= htmlspecialchars($item['izena']) ?></span>
                                <span class="cart-item-price"><?= number_format($item['prezioa_unitarioa'], 2) ?> € / unitate</span>
                                <span style="font-size: 0.8rem; color: var(--text-light);">Stock: <?= $item['stock'] ?></span>
                            </div>
                            <div class="cart-item-actions">
                                <div class="cart-item-subtotal" data-subtotal><?= number_format($item['azpi_totala'], 2) ?> €</div>
                                <div class="quantity-control">
                                    <button type="button" class="quantity-btn" data-action="minus">−</button>
                                    <span class="quantity-value" data-kantitatea><?= $item['kantitatea'] ?></span>
                                    <button type="button" class="quantity-btn" data-action="plus" 
                                            <?= $item['kantitatea'] >= $item['stock'] ? 'disabled' : '' ?>>+</button>
                                </div>
                                <button type="button" class="remove-btn" data-action="remove">🗑️ Kendu</button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="cart-summary">
                    <h2>Laburpena</h2>
                    <div class="summary-row">
                        <span>Produktuak</span>
                        <span id="items-count"><?= $saskia_kopurua ?></span>
                    </div>
                    <div class="summary-row">
                        <span>Azpi-totala</span>
                        <span id="subtotal"><?= number_format($saskia_totala, 2) ?> €</span>
                    </div>
                    <div class="summary-row">
                        <span>Bidalketa</span>
                        <span>Doako</span>
                    </div>
                    <div class="summary-row total">
                        <span>Guztira</span>
                        <span class="amount" id="total"><?= number_format($saskia_totala, 2) ?> €</span>
                    </div>
                    
                    <a href="<?= htmlspecialchars($checkoutPath) ?>" class="checkout-btn" id="checkout-btn">
                        Ordaindu →
                    </a>
                    <a href="<?= htmlspecialchars($dendaPath) ?>" class="continue-shopping">← Erosten jarraitu</a>
                    
                    <div class="security-notice">
                        <span>🔒</span>
                        <div>
                            <strong>Ordainketa segurua</strong><br>
                            Zure datuak enkriptatuta daude
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </main>
    
    <script>
    (function() {
        const csrfToken = <?= json_encode($csrf_token) ?>;
        
        async function updateCart(action, produktuId, kantitatea = null) {
            const formData = new FormData();
            formData.append('action', action);
            formData.append('produktu_id', produktuId);
            formData.append('csrf_token', csrfToken);
            if (kantitatea !== null) {
                formData.append('kantitatea', kantitatea);
            }
            
            try {
                const response = await fetch('/saskia.php', {
                    method: 'POST',
                    body: formData
                });
                return await response.json();
            } catch (error) {
                console.error('Cart update error:', error);
                return { success: false, error: 'Konexio errorea' };
            }
        }
        
        function updateUI(data) {
            if (!data.success) return;
            
            // Update counts and totals
            document.getElementById('items-count').textContent = data.kopurua;
            document.getElementById('subtotal').textContent = data.totala.toFixed(2) + ' €';
            document.getElementById('total').textContent = data.totala.toFixed(2) + ' €';
            document.getElementById('cart-count').textContent = data.kopurua + ' produktu saskian';
            
            // Handle empty cart
            if (data.items.length === 0) {
                location.reload();
            }
        }
        
        // Event delegation for cart actions
        document.getElementById('cart-items')?.addEventListener('click', async function(e) {
            const btn = e.target.closest('[data-action]');
            if (!btn) return;
            
            const cartItem = btn.closest('.cart-item');
            const produktuId = cartItem.dataset.produktuId;
            const kantitateaEl = cartItem.querySelector('[data-kantitatea]');
            const subtotalEl = cartItem.querySelector('[data-subtotal]');
            const action = btn.dataset.action;
            
            let kantitatea = parseInt(kantitateaEl.textContent);
            
            if (action === 'minus') {
                kantitatea = Math.max(0, kantitatea - 1);
            } else if (action === 'plus') {
                kantitatea += 1;
            } else if (action === 'remove') {
                kantitatea = 0;
            }
            
            if (kantitatea === 0) {
                const data = await updateCart('kendu', produktuId);
                if (data.success) {
                    cartItem.remove();
                    updateUI(data);
                }
            } else {
                const data = await updateCart('eguneratu', produktuId, kantitatea);
                if (data.success) {
                    kantitateaEl.textContent = kantitatea;
                    // Find item in response and update subtotal
                    const item = data.items.find(i => i.produktu_id == produktuId);
                    if (item) {
                        subtotalEl.textContent = parseFloat(item.azpi_totala).toFixed(2) + ' €';
                        // Update plus button state
                        const plusBtn = cartItem.querySelector('[data-action="plus"]');
                        plusBtn.disabled = kantitatea >= item.stock;
                    }
                    updateUI(data);
                }
            }
        });
    })();
    </script>
</body>
</html>
