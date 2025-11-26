<?php
// Ensure bootstrap is loaded so session/CSRF helper is available
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../config/konexioa.php';

$baseUrl = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
if ($baseUrl === '/' || $baseUrl === '\\') { $baseUrl = ''; }
$cssPath = $baseUrl . '/style/style.css';

global $db_ok, $conn;

$produktuCount = null;
$sampleProduktuak = [];
if ($db_ok && $conn) {
    try {
        $r = $conn->query("SELECT COUNT(*) c FROM produktua");
        $produktuCount = $r ? ($r->fetch_assoc()['c'] ?? 0) : 0;
        $r2 = $conn->query("SELECT izena FROM produktua ORDER BY id DESC LIMIT 5");
        while ($r2 && ($row = $r2->fetch_assoc())) { $sampleProduktuak[] = $row['izena']; }
    } catch (Throwable $e) {
        error_log("Home produktuak query error: ".$e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="eu">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo htmlspecialchars(EMPRESA_IZENA ?? 'Galletas Zabala'); ?> — Enpresa</title>
    <link rel="stylesheet" href="<?php echo htmlspecialchars($cssPath); ?>">
</head>
<body class="home-page">
<?php
// Show DB status notice if DB is not available (graceful message)
global $db_ok;
if (empty($db_ok)) {
    echo '<div class="alert alert-warning" style="text-align:center; padding:8px; margin:0;">' .
         'Datu-basea momentuz ez dago eskuragarri — webgunearen parte bat desgaituta egon daiteke. Saiatu berriro gutxienez minuturen baten buruan.' .
         '</div>';
}
?>
<!-- HERO BANNER (Full width image) -->
<section class="hero-banner" style="background-image: url('<?php echo htmlspecialchars($headerBg); ?>');">
    <div class="hero-overlay"></div>
    <div class="hero-header">
        <div class="hero-header-inner">
            <button class="btn btn-primary" id="openLoginBtn" aria-haspopup="dialog">Saioa hasi</button>
        </div>
    </div>
</section>
<main role="main">
    <!-- Tabs Section -->
    <div class="tabs-wrapper">
        <div class="container">
            <div class="tabs">
                <div class="tab-buttons" role="tablist" aria-label="Secciones">
                    <button class="tab-btn active" data-tab="about" type="button" role="tab" aria-selected="true">Enpresa</button>
                    <button class="tab-btn" data-tab="process" type="button" role="tab" aria-selected="false">Prozesua</button>
                    <button class="tab-btn" data-tab="sustain" type="button" role="tab" aria-selected="false">Jasangarritasuna</button>
                    <button class="tab-btn" data-tab="contact" type="button" role="tab" aria-selected="false">Kontaktua</button>
                </div>
                <div class="tab-panels">
                    <section id="tab-about" class="tab-panel active" role="tabpanel">
                        <!-- ...contenido Enpresa... -->
                        <div class="tab-content-grid">
                            <div class="tab-col-main">
                                <h3>Nor gara</h3>
                                <p>Zabala es una de las empresas galleteras más antiguas de Europa. Fundada por la familia Zabala, abrimos nuestro primer horno de galletas en el año 1907, en el corazón de la ciudad de Bilbao.</p>
                                <p>Desde siempre hemos destacado por nuestras innovadoras recetas, horneando cada galleta con un único propósito: hacer de las cosas cotidianas, algo extraordinario.</p>
                            </div>
                            <aside class="tab-col-aside">
                                <div class="info-box">
                                    <h4>Enpresa datuak</h4>
                                    <div class="stat-row">
                                        <span class="stat-label">Urteak</span>
                                        <span class="stat-value"><?php echo date('Y') - 1907; ?>+</span>
                                    </div>
                                    <div class="stat-row">
                                        <span class="stat-label">Fabrikak</span>
                                        <span class="stat-value">4</span>
                                    </div>
                                    <div class="stat-row">
                                        <span class="stat-label">Langileak</span>
                                        <span class="stat-value">136</span>
                                    </div>
                                    <div class="stat-row">
                                        <span class="stat-label">Ikertzaileak</span>
                                        <span class="stat-value">12</span>
                                    </div>
                                </div>
                            </aside>
                        </div>
                    </section>
                    <section id="tab-process" class="tab-panel" role="tabpanel" hidden>
                        <!-- ...contenido Prozesua... -->
                        <div class="tab-content-grid">
                            <div class="tab-col-main">
                                <h3>Produktu eta Prozesu xehetuak</h3>
                                <p>Gailetak irinak, koipeak, ura, txokolatea eta beste osagai batzuk nahastuz egiten dira, landutako espezialitatearen arabera. Orea prestatu, luzatu, forma eman eta tratamendu bereziak egin ondoren, betegarriak edo bainu-aukera ematen zaie.</p>
                                <h4 style="margin-top: 1.5rem;">Prozesua pausoz pauso</h4>
                                <ul class="process-steps">
                                    <li><strong>1. Lehengaiak:</strong> Pisatu eta prestatu kalitate-kontrol zorrotzarekin</li>
                                    <li><strong>2. Ore-lanak:</strong> Nahastea eta ijeztea makineria modernoan</li>
                                    <li><strong>3. Formatu eta labeatu:</strong> Forma eta tratamendu termikoa</li>
                                    <li><strong>4. Azalaren tratamendua:</strong> Betegarriak, glazea, txokolatea</li>
                                    <li><strong>5. Kontrol kalitatea:</strong> Jatorri eta osagai kontrola osoa</li>
                                </ul>
                            </div>
                            <aside class="tab-col-aside">
                                <div class="info-box">
                                    <h4>Makineria</h4>
                                    <ul class="machine-list">
                                        <li>Amasatzeko makinak</li>
                                        <li>Laminazio makinak</li>
                                        <li>Labeak 200-250°C</li>
                                        <li>Txokolate-bainu sistema</li>
                                        <li>Envasatze automatikoa</li>
                                    </ul>
                                </div>
                            </aside>
                        </div>
                    </section>
                    <section id="tab-sustain" class="tab-panel" role="tabpanel" hidden>
                        <!-- ...contenido Jasangarritasuna... -->
                        <div class="tab-content-grid">
                            <div class="tab-col-main">
                                <h3>Jasangarritasuna eta kalitatea</h3>
                                <p>Kalitatea eta bezeroaren osasuna gure lehentasunak dira. Gure engaiamendua garraian dago erabakietan:</p>
                                <div class="commitment-box" style="margin-top: 1.5rem;">
                                    <h4>Kalitate-estaindarrak</h4>
                                    <ul class="commitment-list">
                                        <li>ISO 9001 sertifikazioa</li>
                                        <li>HACCP segurtasun-sistema</li>
                                        <li>Osagai naturalen lehentasuna</li>
                                        <li>Etengabeko ikerkuntzaren inbertsioa</li>
                                    </ul>
                                </div>
                                <p style="margin-top: 1.5rem;"><strong>Hemen dagoen inkieta:</strong> Duela urtebete egindako ikerketa batean bi barietate palma-olioarekin lotu ziren. Horren ondorioz banaketetan eta produktuen aldaketetan jardun genuen, osasuna-lehentasunean. Gaur egun, formula aldatuak eta olioak naturalagoak dira.</p>
                            </div>
                            <aside class="tab-col-aside">
                                <div class="info-box">
                                    <h4>Gure konpromisoa</h4>
                                    <p style="font-size: 0.9rem; color: var(--text-light);">Etengabeko bilakaera teknologikoan, osasun-kontzientziaren arabera, eta bezeroaren zaintza osoa gure egunerokoan.</p>
                                </div>
                            </aside>
                        </div>
                    </section>
                    <section id="tab-contact" class="tab-panel" role="tabpanel" hidden>
                        <!-- ...contenido Kontaktua... -->
                        <div class="tab-content-grid">
                            <div class="tab-col-main">
                                <h3>Kontaktua</h3>
                                <div class="contact-info">
                                    <div class="contact-item">
                                        <h4>Helbidea</h4>
                                        <p>Galletas Zabala S.L.<br>Polígono Industrial<br>Euskal Herria</p>
                                    </div>
                                    <div class="contact-item">
                                        <h4>Telefonoa</h4>
                                        <p><a href="tel:+34600000000">+34 600 000 000</a></p>
                                    </div>
                                    <div class="contact-item">
                                        <h4>Email</h4>
                                        <p><a href="mailto:info@zabala.eus">info@zabala.eus</a></p>
                                    </div>
                                </div>
                            </div>
                            <aside class="tab-col-aside">
                                <div class="info-box">
                                    <h4>Ordutegia</h4>
                                    <p>Astelehena - Ostirala<br>09:00 - 17:00<br><br>Zapatua - Igandea<br>Itxita</p>
                                </div>
                            </aside>
                        </div>
                    </section>
                </div>
            </div>
        </div>
    </div>

    <div class="page-wrapper" style="max-width:900px;margin:0 auto;padding:30px;text-align:center;">
        <h1>Zabala Gailetak</h1>
        <p style="font-size:1.1em;">
            Zabala Gailetak artisau <strong>gaileta</strong> ekoizlea da: kalitatezko osagai lokalak,
            errezeta tradizionalak eta berrikuntza (glutenik gabe, vegan, azukre gutxiko aukerak).
        </p>

        <?php if(!$db_ok):?>
            <div class="alert alert-error" style="margin:20px 0;">DB ez dago prest. Saiatu geroago.</div>
        <?php endif;?>

        <div style="display:flex;flex-wrap:wrap;gap:18px;justify-content:center;margin:30px 0;">
            <div class="card" style="flex:1;min-width:220px;">
                <h3>Helburua</h3>
                <p>Gozoa + osasuntsua + jasangarria.</p>
            </div>
            <div class="card" style="flex:1;min-width:220px;">
                <h3>Balioak</h3>
                <p>Tokikoa · Gardentasuna · Zero xahutzea</p>
            </div>
            <div class="card" style="flex:1;min-width:220px;">
                <h3>Segmentuak</h3>
                <p>Txokolatea · Oloa · Fruitu lehorrak · Vegan · Denboraldiko</p>
            </div>
            <div class="card" style="flex:1;min-width:220px;">
                <h3>Produktu kopurua</h3>
                <p><?= $produktuCount!==null ? (int)$produktuCount : '—' ?></p>
            </div>
        </div>

        <?php if($produktuCount && $sampleProduktuak):?>
            <div class="card" style="margin-bottom:30px;">
                <h3>Azken produktuak</h3>
                <ul style="list-style:none;padding:0;margin:10px 0;">
                    <?php foreach($sampleProduktuak as $p):?>
                        <li>🍪 <?= htmlspecialchars($p) ?></li>
                    <?php endforeach;?>
                </ul>
            </div>
        <?php endif;?>

        <div class="card" style="margin-bottom:30px;">
            <h3>Jasangarritasuna</h3>
            <p>
                Ontzi birziklagarriak, energia berriztagarria eta hornitzaile etikoak.
                Ekoizpen prozesuan ur eta energia kontsumoa optimizatua.
            </p>
        </div>

        <div class="card" style="margin-bottom:30px;">
            <h3>Kontaktua</h3>
            <p>Email: info@zabalagailetak.eus · Tel: +34 600 000 000 · Donostia</p>
        </div>

        <?php if(!empty($_SESSION['usuario_id'])):?>
            <a class="btn" href="dashboard.php">Dashboard</a>
            <a class="btn btn-secondary" href="profile.php">Profila</a>
            <form method="POST" action="logout.php" style="display:inline;">
                <button class="btn btn-danger">Logout</button>
            </form>
        <?php else:?>
            <a class="btn" href="signin.php">Saioa hasi</a>
        <?php endif;?>
    </div>
</main>
<footer class="site-footer">
    <div class="container footer-inner">
        <div class="footer-col">
            <p>&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars(EMPRESA_IZENA ?? 'Galletas Zabala'); ?>. Eskubide guztiak erreserbatuta.</p>
        </div>
        <div class="footer-col">
            <nav>
                <a class="footer-link" href="#" data-tab="about">Enpresa</a> · 
                <a class="footer-link" href="#" data-tab="process">Prozesua</a> · 
                <a class="footer-link" href="#" data-tab="sustain">Jasangarritasuna</a> · 
                <a class="footer-link" href="#" data-tab="contact">Kontaktua</a>
            </nav>
        </div>
    </div>
</footer>
<!-- Login Overlay Modal -->
<div id="loginOverlay" class="overlay" role="dialog" aria-modal="true" aria-hidden="true" aria-labelledby="loginTitle">
    <div class="overlay-panel">
        <button class="overlay-close" id="closeLoginBtn" aria-label="Itxi">✕</button>
        <h3 id="loginTitle">Saioa hasi</h3>
        <?php if (!empty($errorea)): ?>
            <div class="alert alert-error" role="alert">
                <strong>⚠️ Errorea:</strong> <?php echo htmlspecialchars($errorea); ?>
            </div>
        <?php endif; ?>
        <form method="POST" action="<?php echo htmlspecialchars($indexPath); ?>" class="form" autocomplete="off" novalidate>
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
            <label class="form-label">Email
                <input name="email" type="email" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
            </label>
            <label class="form-label">Pasahitza
                <input name="password" type="password" required>
            </label>
            <div class="form-actions" style="display: flex; align-items: center; gap: 1rem;">
                <!--<div class="g-recaptcha" data-sitekey="6LcZkRAsAAAAAB8lGFlpVvgrfBj_QcgiEqqjFbCO"></div>-->
                <button type="submit" class="btn btn-secondary">Saioa hasi</button>
                <a class="btn btn-ghost" href="<?php echo htmlspecialchars($signinPath); ?>">Erregistratu</a>
            </div>
        </form>
    </div>
</div>
<!--<script src="https://www.google.com/recaptcha/api.js" async defer></script>-->
<script>
(function() {
    // Tab system
    const tabBtns = document.querySelectorAll('.tab-btn');
    const tabPanels = document.querySelectorAll('.tab-panel');
    const navLinks = document.querySelectorAll('[data-tab]');
    function activateTab(name) {
        tabBtns.forEach(b => {
            const is = b.dataset.tab === name;
            b.classList.toggle('active', is);
            b.setAttribute('aria-selected', is ? 'true' : 'false');
        });
        tabPanels.forEach(p => {
            const match = p.id === 'tab-' + name;
            p.classList.toggle('active', match);
            p.toggleAttribute('hidden', !match);
        });
        navLinks.forEach(a => a.classList.toggle('active', a.dataset.tab === name));
        history.replaceState(null, '', '#' + name);
    }
    tabBtns.forEach(b => b.addEventListener('click', () => activateTab(b.dataset.tab)));
    navLinks.forEach(a => a.addEventListener('click', function(e) {
        e.preventDefault();
        if (this.dataset.tab) activateTab(this.dataset.tab);
    }));
    const initial = location.hash ? location.hash.replace('#', '') : 'about';
    activateTab(initial);
    // Login overlay
    const overlay = document.getElementById('loginOverlay');
    const openBtn = document.getElementById('openLoginBtn');
    const closeBtn = document.getElementById('closeLoginBtn');
    function openLogin() {
        overlay.classList.add('open');
        overlay.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
        setTimeout(() => overlay.querySelector('input[name="email"]')?.focus(), 150);
    }
    function closeLogin() {
        overlay.classList.remove('open');
        overlay.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
    }
    openBtn?.addEventListener('click', openLogin);
    closeBtn?.addEventListener('click', closeLogin);
    overlay.addEventListener('click', e => { if (e.target === overlay) closeLogin(); });
    if (<?php echo json_encode(!empty($errorea)); ?>) openLogin();
})();
</script>
</body>
</html>