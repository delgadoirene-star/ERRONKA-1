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
    header('Location: ../index.php');
    exit;
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

$langileak = Langilea::lortuGuztiak($conn);

// Generate encoded page names
$dashboardEncoded = ($hashids !== null && class_exists('\\Hashids\\Hashids')) ? $hashids->encode(1) : 'dashboard';
$langileakEncoded = ($hashids !== null && class_exists('\\Hashids\\Hashids')) ? $hashids->encode(2) : 'langileak';
$produktuakEncoded = ($hashids !== null && class_exists('\\Hashids\\Hashids')) ? $hashids->encode(3) : 'produktuak';
$salmentakEncoded = ($hashids !== null && class_exists('\\Hashids\\Hashids')) ? $hashids->encode(4) : 'salmentak';
$nireSalmentakEncoded = ($hashids !== null && class_exists('\\Hashids\\Hashids')) ? $hashids->encode(5) : 'nire_salmentak';
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
    <div class="navbar">
        <div class="navbar-brand">
            <h2>🏭 <?php echo EMPRESA_IZENA; ?></h2>
        </div>
        <div class="navbar-menu">
            <a href="/<?php echo $dashboardEncoded; ?>.php" class="nav-link">📊 Dashboard</a>
            <a href="/<?php echo $langileakEncoded; ?>.php" class="nav-link active">👥 Langileak</a>
            <a href="/<?php echo $produktuakEncoded; ?>.php" class="nav-link">📦 Produktuak</a>
            <a href="/<?php echo $salmentakEncoded; ?>.php" class="nav-link">💰 Salmentak</a>
            <a href="/<?php echo $nireSalmentakEncoded; ?>.php" class="nav-link">📋 Nire salmentak</a>
            <span class="navbar-user">
                <?php echo htmlspecialchars($usuario_datos['izena'] . " " . $usuario_datos['abizena']); ?>
            </span>
            <a href="../logout.php" class="nav-link logout">🚪 Itxi saioa</a>
        </div>
    </div>

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
                                $editEncoded = ($hashids !== null) ? $hashids->encode($langilea['id']) : $langilea['id'];
                                ?>
                                <td>
                                    <a href="langile_edit.php?ref=<?php echo htmlspecialchars($editEncoded); ?>">Editatu</a>
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