<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../model/seguritatea.php';
require_once __DIR__ . '/../model/langilea.php';
require_once __DIR__ . '/../model/usuario.php';

global $hashids;

// Ensure admin
if (($_SESSION['usuario_rol'] ?? '') !== 'admin') {
    $home = function_exists('page_link') ? page_link(9, 'home') : '/index.php';
    redirect_to($home);
}

// CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = Seguritatea::generateCSRFToken();
}
$csrf_token = $_SESSION['csrf_token'];

// Load usuario info for navbar/profile display
$usuario_datos = Usuario::lortuIdAgatik($conn, $_SESSION['usuario_id'] ?? null);
$active = 'langileak';
include __DIR__ . '/partials/navbar.php';

$langileak = Langilea::lortuGuztiak($conn);
?>
<!DOCTYPE html>
<html lang="eu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Langileak - Zabala</title>
    <link rel="stylesheet" href="../style/style.css">
</head>
<body>
    <main class="container">
        <h2>👥 Langileak Kudeaketa</h2>
        
        <?php if (isset($exito)): ?>
            <div class="alert alert-success"><?php echo $exito; ?></div>
        <?php endif; ?>
        <?php if (isset($error)): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>

        <button class="btn btn-primary" onclick="toggleFormSortu()">➕ Langilea Gehitu</button>

        <div id="form-sortu" class="form-container" style="display:none; margin: 20px 0; padding: 20px; border: 1px solid #ddd; border-radius: 8px;">
            <h3>Langilea Sortu</h3>
            <form method="POST" action="../controllers/AdminController.php?action=add">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                <div class="form-row">
                    <div class="form-group"><label>Izena*</label><input type="text" name="izena" required></div>
                    <div class="form-group"><label>Abizena*</label><input type="text" name="abizena" required></div>
                </div>
                <div class="form-row">
                    <div class="form-group"><label>NAN*</label><input type="text" name="nan" required></div>
                    <div class="form-group"><label>Email*</label><input type="email" name="email" required></div>
                </div>
                <div class="form-row">
                    <div class="form-group"><label>Telefonoa</label><input type="text" name="telefonoa"></div>
                    <div class="form-group"><label>Departamendua</label><input type="text" name="departamendua"></div>
                </div>
                <div class="form-row">
                    <div class="form-group"><label>Pozisioa</label><input type="text" name="pozisio"></div>
                    <div class="form-group"><label>Pasahitza*</label><input type="password" name="pasahitza" required></div>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn-success">Gorde</button>
                    <button type="button" class="btn btn-secondary" onclick="toggleFormSortu()">Utzi</button>
                </div>
            </form>
        </div>

        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Izena</th><th>Abizena</th><th>NAN</th><th>Email</th>
                        <th>Telefonoa</th><th>Departamendua</th><th>Pozisioa</th><th>Ekintzak</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($langileak as $langilea): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($langilea['izena']); ?></td>
                            <td><?php echo htmlspecialchars($langilea['abizena']); ?></td>
                            <td><?php echo htmlspecialchars($langilea['nan']); ?></td>
                            <td><?php echo htmlspecialchars($langilea['email']); ?></td>
                            <td><?php echo htmlspecialchars($langilea['telefonoa'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($langilea['departamendua'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($langilea['pozisio'] ?? ''); ?></td>
                            <td>
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
        </div>
    </main>
    <script>
    function toggleFormSortu() {
        var form = document.getElementById('form-sortu');
        form.style.display = (form.style.display === 'block') ? 'none' : 'block';
    }
    </script>
</body>
</html>