<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../model/usuario.php';
require_once __DIR__ . '/../model/seguritatea.php';

if (empty($_SESSION['usuario_id'])) {
    redirect_to(function_exists('page_link') ? page_link(9,'home') : '/index.php');
}

$userId   = (int)$_SESSION['usuario_id'];
$errorea  = '';
$mensaje  = '';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = Seguritatea::generateCSRFToken();
}
$csrf_token = $_SESSION['csrf_token'];

// If DB not ready show message and exit early (non-blocking UX)
global $db_ok, $conn;
if (empty($db_ok) || !$conn) {
    echo '<div class="alert alert-error">Datu-basea ez dago prest. Saiatu berriro geroago.</div>';
    return;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postedToken = $_POST['csrf_token'] ?? '';
    if (!Seguritatea::verifyCSRFToken($postedToken)) {
        $errorea = "CSRF errorea.";
        if ($conn) {
            Seguritatea::logSeguritatea($conn, "CSRF_ATTACK", "profile:update", $userId);
        }
    } elseif (isset($_POST['actualizar_datos'])) {
        $izena     = trim($_POST['izena'] ?? '');
        $abizena   = trim($_POST['abizena'] ?? '');
        $nan       = trim($_POST['nan'] ?? '');
        $jaiotegun = $_POST['jaiotegun'] ?? null;

        if ($izena === '' || $abizena === '' || $nan === '') {
            $errorea = "Eremu derrigorrezkoak hutsik daude.";
        } else {
            $stmt = $conn->prepare("UPDATE usuario SET izena=?, abizena=?, nan=?, jaiotegun=? WHERE id=?");
            $stmt->bind_param("ssssi", $izena, $abizena, $nan, $jaiotegun, $userId);
            if ($stmt->execute()) {
                $mensaje = "Datu pertsonalak eguneratu dira.";
            } else {
                $errorea = "Eguneratzeak huts egin du.";
            }
            $stmt->close();
        }
    } elseif (isset($_POST['actualizar_credenciales'])) {
        $nuevo_user         = trim($_POST['nuevo_user'] ?? '');
        $nuevo_password     = $_POST['nuevo_password'] ?? '';
        $confirmar_password = $_POST['confirmar_password'] ?? '';

        if ($nuevo_password !== $confirmar_password) {
            $errorea = "Pasahitzak ez datoz bat.";
        } elseif (!Seguritatea::balioztaPasahitza($nuevo_password)) {
            $errorea = "Pasahitza ahula.";
        } else {
            $hash = password_hash($nuevo_password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE usuario SET user=?, password=? WHERE id=?");
            $stmt->bind_param("ssi", $nuevo_user, $hash, $userId);
            if ($stmt->execute()) {
                $mensaje = "Kredentzialak eguneratu dira.";
            } else {
                $errorea = "Errorea kredentzialak eguneratzean.";
            }
            $stmt->close();
        }
    } elseif (isset($_POST['logoff'])) {
        redirect_to('/logout.php');
    } elseif (isset($_POST['hasiera'])) {
        $home = function_exists('page_link') ? page_link(9,'home') : '/index.php';
        redirect_to($home);
    }
}

$user = Usuario::lortuIdAgatik($conn, $userId) ?: [];

?>
<div class="page-wrapper" style="max-width:900px;margin:0 auto;padding:20px;">
    <div style="display:flex;justify-content:flex-end;gap:8px;margin-bottom:12px;">
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
            <button type="submit" name="hasiera" class="btn">Hasiera</button>
        </form>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
            <button type="submit" name="logoff" class="btn btn-secondary">Saioa amaitu</button>
        </form>
    </div>

    <?php if ($mensaje): ?>
        <div class="alert alert-success">✓ <?= htmlspecialchars($mensaje) ?></div>
    <?php endif; ?>
    <?php if ($errorea): ?>
        <div class="alert alert-error">⚠ <?= htmlspecialchars($errorea) ?></div>
    <?php endif; ?>

    <div class="card" style="margin-bottom:24px;">
        <h2 style="text-align:center;">Datu pertsonalak</h2>
        <div style="text-align:center;margin-bottom:16px;">
            <p><strong>Izena:</strong> <?= htmlspecialchars(($user['izena'] ?? '') . ' ' . ($user['abizena'] ?? '')) ?></p>
            <p><strong>NAN:</strong> <?= htmlspecialchars($user['nan'] ?? '-') ?></p>
            <p><strong>Jaioteguna:</strong> <?= htmlspecialchars($user['jaiotegun'] ?? '-') ?></p>
            <p><strong>Email:</strong> <?= htmlspecialchars($user['email'] ?? '-') ?></p>
            <p><strong>IBAN:</strong> <?= htmlspecialchars($user['iban'] ?? '-') ?></p>
        </div>
        <form method="POST" style="max-width:420px;margin:0 auto;">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
            <label>Izena:</label>
            <input type="text" name="izena" required value="<?= htmlspecialchars($user['izena'] ?? '') ?>" class="input" style="width:100%;margin-bottom:10px;">
            <label>Abizena:</label>
            <input type="text" name="abizena" required value="<?= htmlspecialchars($user['abizena'] ?? '') ?>" class="input" style="width:100%;margin-bottom:10px;">
            <label>NAN:</label>
            <input type="text" name="nan" required value="<?= htmlspecialchars($user['nan'] ?? '') ?>" class="input" style="width:100%;margin-bottom:10px;">
            <label>Jaioteguna:</label>
            <input type="date" name="jaiotegun" value="<?= htmlspecialchars($user['jaiotegun'] ?? '') ?>" class="input" style="width:100%;margin-bottom:10px;">
            <button type="submit" name="actualizar_datos" class="btn" style="width:100%;margin-top:12px;">Datuak eguneratu</button>
        </form>
    </div>

    <div class="card">
        <h2 style="text-align:center;">Kredentzialak</h2>
        <form method="POST" style="max-width:420px;margin:0 auto;">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
            <label>Erabiltzailea:</label>
            <input type="text" name="nuevo_user" required value="<?= htmlspecialchars($user['user'] ?? '') ?>" class="input" style="width:100%;margin-bottom:10px;">
            <label>Pasahitz berria:</label>
            <input type="password" name="nuevo_password" required class="input" style="width:100%;margin-bottom:10px;">
            <label>Berretsi pasahitza:</label>
            <input type="password" name="confirmar_password" required class="input" style="width:100%;margin-bottom:10px;">
            <button type="submit" name="actualizar_credenciales" class="btn" style="width:100%;margin-top:12px;">Kredentzialak eguneratu</button>
        </form>
    </div>
</div>