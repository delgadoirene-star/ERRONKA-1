<?php
/**
 * Denda (Store) - Public e-commerce storefront similar to Etsy
 * Displays products for customers to browse and add to cart
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

// Handle add to cart (AJAX)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'gehitu_saskira') {
    header('Content-Type: application/json');
    
    if (!Seguritatea::verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        echo json_encode(['success' => false, 'error' => 'Segurtasun errorea']);
        exit;
    }
    
    if (!$db_ok || !$conn) {
        echo json_encode(['success' => false, 'error' => 'Datu-base errorea']);
        exit;
    }
    
    $produktu_id = filter_input(INPUT_POST, 'produktu_id', FILTER_VALIDATE_INT);
    $kantitatea = filter_input(INPUT_POST, 'kantitatea', FILTER_VALIDATE_INT) ?: 1;
    
    if (!$produktu_id || $kantitatea < 1) {
        echo json_encode(['success' => false, 'error' => 'Datu okerrak']);
        exit;
    }
    
    $bezero_id = $_SESSION['bezero_id'] ?? null;
    $saskia_id = Saskia::lortuEdoSortu($conn, $bezero_id);
    
    if (!$saskia_id) {
        echo json_encode(['success' => false, 'error' => 'Saskia sortu ezin izan da']);
        exit;
    }
    
    $ok = Saskia::gehituProduktua($conn, $saskia_id, $produktu_id, $kantitatea);
    $kopurua = Saskia::lortuKopurua($conn, $saskia_id);
    
    echo json_encode([
        'success' => $ok,
        'kopurua' => $kopurua,
        'message' => $ok ? 'Produktua saskira gehitu da' : 'Errorea produktua gehitzean'
    ]);
    exit;
}

// Get filter parameters
$kategoria = isset($_GET['kategoria']) ? trim($_GET['kategoria']) : null;
$bilaketa = isset($_GET['bilaketa']) ? trim($_GET['bilaketa']) : null;

// Get products
$produktuak = [];
$kategoriak = [];
$saskia_kopurua = 0;

if ($db_ok && $conn) {
    $produktuak = Produktua::lortuDendarako($conn, $kategoria, $bilaketa);
    $kategoriak = Produktua::lortuKategoriak($conn);
    
    // Get cart count
    $bezero_id = $_SESSION['bezero_id'] ?? null;
    $saskia_id = Saskia::lortuEdoSortu($conn, $bezero_id);
    if ($saskia_id) {
        $saskia_kopurua = Saskia::lortuKopurua($conn, $saskia_id);
    }
}

$cssPath = '/public/assets/style.css';
$dendaLoginPath = '/denda-login.php';
$saskiaPath = '/saskia.php';
?>
<!DOCTYPE html>
<html lang="eu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Denda - <?= htmlspecialchars(EMPRESA_IZENA ?? 'Artisau Denda') ?></title>
    <link rel="stylesheet" href="<?= htmlspecialchars($cssPath) ?>">
    <style>
        /* Store-specific styles */
        .store-header {
            background: linear-gradient(135deg, var(--brand) 0%, var(--brand-dark) 100%);
            color: white;
            padding: 1rem 0;
            position: sticky;
            top: 0;
            z-index: 100;
        }
        .store-header-inner {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 2rem;
        }
        .store-brand {
            font-size: 1.5rem;
            font-weight: 700;
            color: white;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .store-search {
            flex: 1;
            max-width: 500px;
        }
        .store-search form {
            display: flex;
            gap: 0.5rem;
        }
        .store-search input {
            flex: 1;
            padding: 0.6rem 1rem;
            border: none;
            border-radius: 25px;
            font-size: 0.95rem;
        }
        .store-search button {
            padding: 0.6rem 1.2rem;
            border: none;
            border-radius: 25px;
            background: var(--accent);
            color: white;
            cursor: pointer;
            font-weight: 600;
        }
        .store-nav {
            display: flex;
            gap: 1rem;
            align-items: center;
        }
        .store-nav a {
            color: white;
            text-decoration: none;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            transition: background 0.2s;
        }
        .store-nav a:hover {
            background: rgba(255,255,255,0.15);
        }
        .cart-icon {
            position: relative;
            font-size: 1.3rem;
        }
        .cart-badge {
            position: absolute;
            top: -8px;
            right: -8px;
            background: var(--accent);
            color: white;
            font-size: 0.7rem;
            padding: 2px 6px;
            border-radius: 50%;
            font-weight: 700;
        }
        
        /* Categories bar */
        .categories-bar {
            background: #f8f9fa;
            padding: 0.75rem 0;
            border-bottom: 1px solid var(--border-color);
        }
        .categories-inner {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 2rem;
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }
        .category-link {
            padding: 0.4rem 1rem;
            border-radius: 20px;
            background: white;
            color: var(--text-dark);
            text-decoration: none;
            font-size: 0.9rem;
            border: 1px solid var(--border-color);
            transition: all 0.2s;
        }
        .category-link:hover, .category-link.active {
            background: var(--brand);
            color: white;
            border-color: var(--brand);
        }
        
        /* Product grid */
        .store-content {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem;
        }
        .store-title {
            margin-bottom: 1.5rem;
        }
        .store-title h1 {
            font-size: 1.75rem;
            color: var(--text-dark);
            margin-bottom: 0.25rem;
        }
        .store-title p {
            color: var(--text-light);
        }
        .product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
            gap: 1.5rem;
        }
        .product-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            transition: transform 0.2s, box-shadow 0.2s;
            display: flex;
            flex-direction: column;
        }
        .product-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 24px rgba(0,0,0,0.12);
        }
        .product-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
            background: linear-gradient(135deg, #f0f0f0, #e0e0e0);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-light);
            font-size: 3rem;
        }
        .product-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .product-info {
            padding: 1rem;
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        .product-category {
            font-size: 0.75rem;
            color: var(--text-light);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 0.25rem;
        }
        .product-name {
            font-size: 1rem;
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 0.5rem;
            line-height: 1.3;
        }
        .product-description {
            font-size: 0.85rem;
            color: var(--text-light);
            margin-bottom: 0.75rem;
            line-height: 1.4;
            flex: 1;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .product-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: auto;
            padding-top: 0.75rem;
            border-top: 1px solid var(--border-color);
        }
        .product-price {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--brand-dark);
        }
        .product-stock {
            font-size: 0.8rem;
            color: var(--text-light);
        }
        .product-stock.low {
            color: #e57c00;
        }
        .add-to-cart-btn {
            background: var(--brand);
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            font-size: 0.85rem;
            transition: background 0.2s;
        }
        .add-to-cart-btn:hover {
            background: var(--brand-dark);
        }
        .add-to-cart-btn:disabled {
            background: #ccc;
            cursor: not-allowed;
        }
        
        /* No products message */
        .no-products {
            text-align: center;
            padding: 4rem 2rem;
            color: var(--text-light);
        }
        .no-products h2 {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
            color: var(--text-dark);
        }
        
        /* Toast notification */
        .toast {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            background: var(--brand-dark);
            color: white;
            padding: 1rem 1.5rem;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
            transform: translateY(100px);
            opacity: 0;
            transition: all 0.3s;
            z-index: 1000;
        }
        .toast.show {
            transform: translateY(0);
            opacity: 1;
        }
        
        /* Footer */
        .store-footer {
            background: #1a1a2e;
            color: #a0a0b0;
            padding: 3rem 0 1.5rem;
            margin-top: 4rem;
        }
        .store-footer-inner {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 2rem;
        }
        .footer-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 2rem;
            margin-bottom: 2rem;
        }
        .footer-col h4 {
            color: white;
            margin-bottom: 1rem;
            font-size: 1rem;
        }
        .footer-col ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .footer-col li {
            margin-bottom: 0.5rem;
        }
        .footer-col a {
            color: #a0a0b0;
            text-decoration: none;
            font-size: 0.9rem;
        }
        .footer-col a:hover {
            color: white;
        }
        .footer-bottom {
            border-top: 1px solid #2a2a4e;
            padding-top: 1.5rem;
            text-align: center;
            font-size: 0.85rem;
        }
        
        @media (max-width: 768px) {
            .store-header-inner {
                flex-wrap: wrap;
                gap: 1rem;
            }
            .store-search {
                order: 3;
                max-width: 100%;
                width: 100%;
            }
            .product-grid {
                grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
                gap: 1rem;
            }
            .product-image {
                height: 150px;
            }
        }
    </style>
</head>
<body class="store-page">
    <!-- Store Header -->
    <header class="store-header">
        <div class="store-header-inner">
            <a href="/denda.php" class="store-brand">🛍️ <?= htmlspecialchars(EMPRESA_IZENA ?? 'Artisau Denda') ?></a>
            
            <div class="store-search">
                <form method="GET" action="/denda.php">
                    <?php if ($kategoria): ?>
                        <input type="hidden" name="kategoria" value="<?= htmlspecialchars($kategoria) ?>">
                    <?php endif; ?>
                    <input type="text" name="bilaketa" placeholder="Bilatu produktuak..." value="<?= htmlspecialchars($bilaketa ?? '') ?>">
                    <button type="submit">🔍 Bilatu</button>
                </form>
            </div>
            
            <nav class="store-nav">
                <?php if (isset($_SESSION['bezero_id'])): ?>
                    <a href="/bezero-kontua.php">👤 Nire kontua</a>
                <?php else: ?>
                    <a href="<?= htmlspecialchars($dendaLoginPath) ?>">Sartu</a>
                <?php endif; ?>
                <a href="<?= htmlspecialchars($saskiaPath) ?>" class="cart-icon">
                    🛒
                    <?php if ($saskia_kopurua > 0): ?>
                        <span class="cart-badge" id="cart-badge"><?= $saskia_kopurua ?></span>
                    <?php else: ?>
                        <span class="cart-badge" id="cart-badge" style="display: none;">0</span>
                    <?php endif; ?>
                </a>
            </nav>
        </div>
    </header>
    
    <!-- Categories Bar -->
    <?php if (!empty($kategoriak)): ?>
    <div class="categories-bar">
        <div class="categories-inner">
            <a href="/denda.php" class="category-link <?= !$kategoria ? 'active' : '' ?>">Guztiak</a>
            <?php foreach ($kategoriak as $kat): ?>
                <a href="/denda.php?kategoria=<?= urlencode($kat) ?>" 
                   class="category-link <?= $kategoria === $kat ? 'active' : '' ?>">
                    <?= htmlspecialchars($kat) ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Main Content -->
    <main class="store-content">
        <div class="store-title">
            <h1>
                <?php if ($bilaketa): ?>
                    "<?= htmlspecialchars($bilaketa) ?>" bilaketa
                <?php elseif ($kategoria): ?>
                    <?= htmlspecialchars($kategoria) ?>
                <?php else: ?>
                    Gure produktuak
                <?php endif; ?>
            </h1>
            <p><?= count($produktuak) ?> produktu aurkitu dira</p>
        </div>
        
        <?php if (empty($produktuak)): ?>
            <div class="no-products">
                <h2>Ez da produkturik aurkitu</h2>
                <p>Saiatu beste bilaketa batekin edo ikusi kategoria guztiak</p>
                <a href="/denda.php" class="btn btn-primary" style="margin-top: 1rem;">Produktu guztiak ikusi</a>
            </div>
        <?php else: ?>
            <div class="product-grid">
                <?php foreach ($produktuak as $produktu): ?>
                    <article class="product-card">
                        <div class="product-image">
                            <?php if (!empty($produktu['irudia'])): ?>
                                <img src="<?= htmlspecialchars($produktu['irudia']) ?>" alt="<?= htmlspecialchars($produktu['izena']) ?>">
                            <?php else: ?>
                                🎨
                            <?php endif; ?>
                        </div>
                        <div class="product-info">
                            <?php if (!empty($produktu['kategoria'])): ?>
                                <span class="product-category"><?= htmlspecialchars($produktu['kategoria']) ?></span>
                            <?php endif; ?>
                            <h3 class="product-name"><?= htmlspecialchars($produktu['izena']) ?></h3>
                            <?php if (!empty($produktu['deskripzioa'])): ?>
                                <p class="product-description"><?= htmlspecialchars($produktu['deskripzioa']) ?></p>
                            <?php endif; ?>
                            <div class="product-footer">
                                <div>
                                    <span class="product-price"><?= number_format($produktu['prezioa'], 2) ?> €</span>
                                    <span class="product-stock <?= $produktu['stock'] < 5 ? 'low' : '' ?>">
                                        <?php if ($produktu['stock'] < 5): ?>
                                            <?= $produktu['stock'] ?> geratzen dira!
                                        <?php else: ?>
                                            Stock-ean
                                        <?php endif; ?>
                                    </span>
                                </div>
                                <button class="add-to-cart-btn" 
                                        data-produktu-id="<?= $produktu['id'] ?>"
                                        <?= $produktu['stock'] < 1 ? 'disabled' : '' ?>>
                                    🛒 Gehitu
                                </button>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>
    
    <!-- Footer -->
    <footer class="store-footer">
        <div class="store-footer-inner">
            <div class="footer-grid">
                <div class="footer-col">
                    <h4>🛍️ <?= htmlspecialchars(EMPRESA_IZENA ?? 'Artisau Denda') ?></h4>
                    <p style="font-size: 0.9rem; line-height: 1.6;">
                        Eskuz egindako produktu bereziak, kalitate handienarekin.
                    </p>
                </div>
                <div class="footer-col">
                    <h4>Laguntza</h4>
                    <ul>
                        <li><a href="#">Bidalketa informazioa</a></li>
                        <li><a href="#">Itzulketa politika</a></li>
                        <li><a href="#">FAQ</a></li>
                        <li><a href="#">Kontaktua</a></li>
                    </ul>
                </div>
                <div class="footer-col">
                    <h4>Kontu</h4>
                    <ul>
                        <li><a href="<?= htmlspecialchars($dendaLoginPath) ?>">Sartu</a></li>
                        <li><a href="/denda-erregistratu.php">Erregistratu</a></li>
                        <li><a href="<?= htmlspecialchars($saskiaPath) ?>">Saskia</a></li>
                    </ul>
                </div>
                <div class="footer-col">
                    <h4>Kontaktua</h4>
                    <ul>
                        <li>📧 info@artisaudenda.eus</li>
                        <li>📞 +34 600 000 000</li>
                        <li>📍 Euskal Herria</li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; <?= date('Y') ?> <?= htmlspecialchars(EMPRESA_IZENA ?? 'Artisau Denda') ?>. Eskubide guztiak erreserbatuta.</p>
            </div>
        </div>
    </footer>
    
    <!-- Toast notification -->
    <div class="toast" id="toast"></div>
    
    <script>
    (function() {
        const csrfToken = <?= json_encode($csrf_token) ?>;
        const cartBadge = document.getElementById('cart-badge');
        const toast = document.getElementById('toast');
        
        function showToast(message, duration = 3000) {
            toast.textContent = message;
            toast.classList.add('show');
            setTimeout(() => toast.classList.remove('show'), duration);
        }
        
        function updateCartBadge(count) {
            if (count > 0) {
                cartBadge.textContent = count;
                cartBadge.style.display = 'block';
            } else {
                cartBadge.style.display = 'none';
            }
        }
        
        document.querySelectorAll('.add-to-cart-btn').forEach(btn => {
            btn.addEventListener('click', async function() {
                const produktuId = this.dataset.produktuId;
                
                this.disabled = true;
                this.textContent = '...';
                
                try {
                    const formData = new FormData();
                    formData.append('action', 'gehitu_saskira');
                    formData.append('produktu_id', produktuId);
                    formData.append('kantitatea', 1);
                    formData.append('csrf_token', csrfToken);
                    
                    const response = await fetch('/denda.php', {
                        method: 'POST',
                        body: formData
                    });
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        showToast('✓ ' + data.message);
                        updateCartBadge(data.kopurua);
                    } else {
                        showToast('⚠ ' + (data.error || 'Errorea'));
                    }
                } catch (error) {
                    showToast('⚠ Konexio errorea');
                }
                
                this.disabled = false;
                this.textContent = '🛒 Gehitu';
            });
        });
    })();
    </script>
</body>
</html>
