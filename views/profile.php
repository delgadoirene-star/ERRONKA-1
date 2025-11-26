<?php
require_once __DIR__ . '/../bootstrap.php';

$userId = $_SESSION['usuario_id'] ?? null;
if (!$userId) { header('Location: /signin.php'); exit; }

$mensaje = '';
$errorea = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postedToken = $_POST['csrf_token'] ?? '';
    if (!Seguritatea::verifyCSRFToken($postedToken)) {
        $errorea = "Segurtasun-errorea (CSRF).";
        Seguritatea::logSeguritatea($conn, "CSRF_ATTACK", "profile:update", $userId);
    } elseif (isset($_POST['actualizar_datos'])) {
        $izena = trim($_POST['izena'] ?? '');
        $abizena = trim($_POST['abizena'] ?? '');
        $nan = trim($_POST['nan'] ?? '');
        $jaiotegun = $_POST['jaiotegun'] ?? null;

        $stmt = $conn->prepare("UPDATE usuario SET izena=?, abizena=?, nan=?, jaiotegun=? WHERE id=?");
        $stmt->bind_param("ssssi", $izena, $abizena, $nan, $jaiotegun, $userId);
        if ($stmt->execute()) {
            $mensaje = "Datuak eguneratu dira.";
        } else {
            $errorea = "Errorea datuak eguneratzean.";
        }
        $stmt->close();
    } elseif (isset($_POST['actualizar_credenciales'])) {
        $nuevo_user = trim($_POST['nuevo_user'] ?? '');
        $nuevo_password = $_POST['nuevo_password'] ?? '';
        $confirmar_password = $_POST['confirmar_password'] ?? '';

        if ($nuevo_password !== $confirmar_password) {
            $errorea = "Pasahitzak ez datoz bat.";
        } elseif (!Seguritatea::balioztaPasahitza($nuevo_password)) {
            $errorea = "Pasahitza ahula da.";
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
        header('Location: ../logout.php'); exit;
    } elseif (isset($_POST['hasiera'])) {
        header('Location: ../index.php'); exit;
    }
}

$user = Usuario::lortuIdAgatik($conn, $userId);
$aktiboak = $aktiboak ?? [];
$historia = $historia ?? [];
?>
<script>
document.addEventListener("DOMContentLoaded", function() {
    if (localStorage.getItem('darkMode') === '1') {
        document.body.classList.add('dark-mode');
    }
});

function openTab(evt, tabName) {
    var i, tabcontent, tablinks;
    tabcontent = document.getElementsByClassName("tabcontent");
    for (i = 0; i < tabcontent.length; i++) {
        tabcontent[i].classList.remove("active");
    }
    tablinks = document.getElementsByClassName("tablinks");
    for (i = 0; i < tablinks.length; i++) {
        tablinks[i].classList.remove("active");
    }
    document.getElementById(tabName).classList.add("active");
    evt.currentTarget.classList.add("active");
}
</script>

<div style="display: flex; justify-content: flex-end; gap: 8px; margin-bottom: 12px;">
    <form method="POST" style="display:inline;">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
        <input type="submit" name="hasiera" value="Hasiera" class="btn"/>
    </form>
    <form method="POST" style="display:inline;">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
        <input type="submit" name="logoff" value="Saioa amaitu" class="btn"/>
    </form>
</div>

<?php if (!empty($mensaje)): ?>
<div class="alert alert-success">✓ <?php echo htmlspecialchars($mensaje); ?></div>
<?php endif; ?>
<?php if (!empty($errorea)): ?>
<div class="alert alert-error">⚠ <?php echo htmlspecialchars($errorea); ?></div>
<?php endif; ?>

<!-- Datos personales -->
<div id="datos" class="tabcontent active card" style="max-width:600px;margin:0 auto;">
    <h2 style="text-align:center;">Datu pertsonalak</h2>
    <div style="text-align:center;margin-bottom:20px;">
        <p><strong>Izena:</strong> <?php echo htmlspecialchars(($user['izena'] ?? '') . " " . ($user['abizena'] ?? '')); ?></p>
        <p><strong>NAN:</strong> <?php echo htmlspecialchars($user['nan'] ?? '-'); ?></p>
        <p><strong>Jaioteguna:</strong> <?php echo htmlspecialchars($user['jaiotegun'] ?? '-'); ?></p>
        <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email'] ?? '-'); ?></p>
        <p><strong>IBAN:</strong> <?php echo htmlspecialchars($user['iban'] ?? '-'); ?></p>
    </div>
    <form method="POST" style="max-width:400px;margin:0 auto;">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
        <h3 style="text-align:center;">Datuak eguneratu</h3>
        <label>Izena:</label>
        <input type="text" name="izena" required value="<?php echo htmlspecialchars($user['izena'] ?? ''); ?>" style="width:100%;margin-bottom:10px;">
        <label>Abizena:</label>
        <input type="text" name="abizena" required value="<?php echo htmlspecialchars($user['abizena'] ?? ''); ?>" style="width:100%;margin-bottom:10px;">
        <label>NAN:</label>
        <input type="text" name="nan" required value="<?php echo htmlspecialchars($user['nan'] ?? ''); ?>" style="width:100%;margin-bottom:10px;">
        <label>Jaioteguna:</label>
        <input type="date" name="jaiotegun" value="<?php echo htmlspecialchars($user['jaiotegun'] ?? ''); ?>" style="width:100%;margin-bottom:10px;">
        <input type="submit" name="actualizar_datos" value="Datuak eguneratu" class="btn" style="width:100%;margin-top:12px;">
    </form>
</div>

<!-- Credenciales -->
<div id="credenciales" class="tabcontent card" style="max-width:600px;margin:0 auto;">
    <h2 style="text-align:center;">Kredentzialak eguneratu</h2>
    <form method="POST" style="max-width:400px;margin:0 auto;">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
        <label>Erabiltzaile berria:</label>
        <input type="text" name="nuevo_user" required value="<?php echo htmlspecialchars($user['user'] ?? ''); ?>" style="width:100%;margin-bottom:10px;">
        <label>Pasahitz berria:</label>
        <input type="password" name="nuevo_password" required style="width:100%;margin-bottom:10px;">
        <label>Berretsi pasahitza:</label>
        <input type="password" name="confirmar_password" required style="width:100%;margin-bottom:10px;">
        <input type="submit" name="actualizar_credenciales" value="Kredentzialak eguneratu" class="btn" style="width:100%;margin-top:12px;">
    </form>
</div>

<!-- Cursos activos -->
<div id="activos" class="tabcontent card" style="max-width:600px;margin:0 auto;">
    <h2 style="text-align:center;">Kurtso aktiboak</h2>
    <?php
    if (count($aktiboak) > 0) {
        echo "<ul>";
        foreach ($aktiboak as $a) {
            $kurtsoa = $a['kurtsoa'];
            $fecini = $a['fecini'];
            $fecini_formateada = date("d/m/Y", strtotime($fecini));
            echo "<li><strong>" . htmlspecialchars($kurtsoa->getIzena()) . "</strong> (Inicio: $fecini_formateada)</li>";
        }
        echo "</ul>";
    } else {
        echo "<p>Ez duzu kurtso aktiborik.</p>";
    }
    ?>
</div>

<!-- Historia -->
<div id="historial" class="tabcontent card" style="max-width:600px;margin:0 auto;">
    <h2 style="text-align:center;">Kurtsoen historia</h2>
    <?php
    if (count($historia) > 0) {
        echo "<ul>";
        foreach ($historia as $h) {
            $kurtsoa = $h['kurtsoa'];
            $fecini = $h['fecini'];
            $fecfin = $h['fecfin'];
            $fecini_formateada = date("d/m/Y", strtotime($fecini));
            $fecfin_formateada = date("d/m/Y", strtotime($fecfin));
            echo "<li><strong>" . htmlspecialchars($kurtsoa->getIzena()) . "</strong> (Inicio: $fecini_formateada - Fin: $fecfin_formateada)</li>";
        }
        echo "</ul>";
    } else {
        echo "<p>Ez duzu kurtsoen historiarik.</p>";
    }
    ?>
</div>