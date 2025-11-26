<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../model/seguritatea.php';
require_once __DIR__ . '/../model/langilea.php';
require_once __DIR__ . '/../model/usuario.php';

global $db_ok, $conn;
if (!$db_ok || !$conn) { echo '<div class="alert alert-error">DB ez dago prest.</div>'; return; }

if (($_SESSION['usuario_rol'] ?? '') !== 'admin') { redirect_to('/index.php'); }

$csrf = $_SESSION['csrf_token'] ?? ($_SESSION['csrf_token']=Seguritatea::generateCSRFToken());
$mezua=''; $errorea='';

if ($_SERVER['REQUEST_METHOD']==='POST') {
    if (!Seguritatea::verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errorea='CSRF errorea.';
    } else {
        $action = $_POST['action'] ?? '';
        if ($action==='delete_langilea') {
            $id=(int)($_POST['id']??0);
            if ($id && Langilea::delete($conn,$id)) $mezua='Ezabatua.';
            else $errorea='Ezabaketak huts egin du.';
        }
    }
}

$langileak = Langilea::all($conn);
$dashboardLink = function_exists('page_link') ? page_link(1, 'dashboard') : '/views/dashboard.php';
$pageTitle = "Langileak Kudeaketa";
$active = 'langileak';
require __DIR__ . '/partials/header.php';
?>
<main class="container">
    <h2>👥 Langileak Kudeaketa</h2>
    <?php if (isset($exito)): ?><div class="alert alert-success"><?php echo $exito; ?></div><?php endif; ?>
    <?php if (isset($error)): ?><div class="alert alert-error"><?php echo $error; ?></div><?php endif; ?>

    <button class="btn btn-primary" onclick="toggleFormSortu()">➕ Langilea Gehitu</button>

    <div id="form-sortu" class="form-container" style="display:none; margin: 20px 0; padding: 20px; border: 1px solid #ddd; border-radius: 8px;">
        <h3>Langilea Sortu</h3>
        <form method="POST" action="../controllers/AdminController.php?action=add_langilea">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
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
                            <form method="POST" action="../controllers/AdminController.php?action=delete_langilea" style="display:inline;">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
                                <input type="hidden" name="id" value="<?= (int)$langilea['id'] ?>">
                                <button class="btn btn-danger btn-sm" onclick="return confirm('Seguru zaude?');">Ezabatu</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div style="margin-top:16px;">
        <a href="<?= htmlspecialchars($dashboardLink) ?>" class="btn btn-secondary">← Dashboard</a>
    </div>
</main>
<script>
function toggleFormSortu() {
    var form = document.getElementById('form-sortu');
    form.style.display = (form.style.display === 'block') ? 'none' : 'block';
}
</script>
<?php require __DIR__ . '/partials/footer.php'; ?>