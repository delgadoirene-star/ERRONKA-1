<?php
require_once __DIR__ . '/../bootstrap.php';  // Loads global $hashids
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../model/seguritatea.php';
require_once __DIR__ . '/../model/langilea.php';
require_once __DIR__ . '/../model/usuario.php';

global $hashids;  // Access global Hashids

// remove debug
// echo "Debug: Hashids loaded: " . ($hashids !== null ? 'Yes' : 'No') . "<br>";

if (empty($_SESSION['usuario_id'])) {
    $home = function_exists('page_link') ? page_link(9, 'home') : '/index.php';
    redirect_to($home);
}

// CSRF only once
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = Seguritatea::generateCSRFToken();
}
$csrf_token = $_SESSION['csrf_token'];

$flash_error = $_SESSION['flash_error'] ?? '';
$flash_success = $_SESSION['flash_success'] ?? '';
unset($_SESSION['flash_error'], $_SESSION['flash_success']);

$usuario_datos = Usuario::lortuIdAgatik($conn, $_SESSION['usuario_id']);
$active = 'langileak';
include __DIR__ . '/partials/navbar.php';

$langileak = Langilea::lortuGuztiak($conn);

// Use page_link helper for navbar and manage link
$dashboardLink    = function_exists('page_link') ? page_link(1, 'dashboard') : '/dashboard.php';
$langileakLink    = function_exists('page_link') ? page_link(2, 'langileak') : '/langileak.php';
$produktuakLink   = function_exists('page_link') ? page_link(3, 'produktuak') : '/produktuak.php';
$salmentakLink    = function_exists('page_link') ? page_link(4, 'salmentak') : '/salmentak.php';
$nireSalmentakLink= function_exists('page_link') ? page_link(5, 'nire_salmentak') : '/nire_salmentak.php';
$profileLink      = function_exists('page_link') ? page_link(6, 'profile') : '/profile.php';
?>
<!DOCTYPE html>
<html lang="eu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Langileak - <?php echo EMPRESA_IZENA; ?></title>
    <link rel="stylesheet" href="../style/style.css">
</head>
<body>
    <div class="container">
        <h1>👥 Langileak kudeaketa</h1>

        <?php if ($flash_error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($flash_error); ?></div>
        <?php endif; ?>
        <?php if ($flash_success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($flash_success); ?></div>
        <?php endif; ?>

        <div class="form-section">
            <h2>➕ Langilea gehitu</h2>
            <form method="POST" action="../controllers/AdminController.php?action=add" class="register-form">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">

                <div class="form-row">
                    <div class="form-group">
                        <label>Izena</label>
                        <input type="text" name="izena" required>
                    </div>
                    <div class="form-group">
                        <label>Abizena</label>
                        <input type="text" name="abizena" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group"><label>NAN</label><input type="text" name="nan"></div>
                    <div class="form-group"><label>Email</label><input type="email" name="email" required></div>
                </div>
                <div class="form-row">
                    <div class="form-group"><label>Telefonoa</label><input type="text" name="telefonoa"></div>
                    <div class="form-group"><label>Departamendua</label><input type="text" name="departamendua"></div>
                </div>
                <div class="form-row">
                    <div class="form-group"><label>Pozisioa</label><input type="text" name="pozisio"></div>
                    <div class="form-group"><label>Pasahitza</label><input type="password" name="pasahitza" required></div>
                </div>
                <button class="btn btn-primary btn-block" type="submit">Gorde</button>
            </form>
        </div>

        <div class="table-section">
            <h2>Langileak zerrendatua</h2>
            
            <?php if (count($langileak) > 0): ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Izena</th>
                            <th>Abizena</th>
                            <th>Email</th>
                            <th>Departamendua</th>
                            <th>Pozisioa</th>
                            <th>Soldata</th>
                            <th>Ekintzak</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($langileak as $langilea): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($langilea['izena']); ?></td>
                                <td><?php echo htmlspecialchars($langilea['abizena']); ?></td>
                                <td><?php echo htmlspecialchars($langilea['email']); ?></td>
                                <td><?php echo htmlspecialchars($langilea['departamendua'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($langilea['pozisio'] ?? '-'); ?></td>
                                <td><?php echo number_format($langilea['soldata'] ?? 0, 2); ?>€</td>
                                <?php
                                $refEnc = function_exists('encode_id') ? encode_id((int)$langilea['id']) : (int)$langilea['id'];
                                $manageLink = function_exists('page_link') ? page_link(8, 'langilea_kudeaketa') : '/langilea_kudeaketa.php';
                                ?>
                                <td>
                                    <a href="<?php echo htmlspecialchars($manageLink); ?>?ref=<?php echo htmlspecialchars($refEnc); ?>">Editatu</a>
                                     <form method="POST" action="../controllers/AdminController.php?action=delete" style="display:inline;">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                                        <input type="hidden" name="id" value="<?php echo (int)$langilea['id']; ?>">
                                        <button class="btn btn-danger btn-sm" onclick="return confirm('Seguru zaude?');">Ezabatu</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="no-data">Ez dago langilerik.</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>