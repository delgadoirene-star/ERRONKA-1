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
        <input type="submit" name="hasiera" value="Hasiera" class="btn" onclick="window.location.href='index.php'; return false;"/>
    </form>
    <form method="POST" style="display:inline;">
        <input type="submit" name="logoff" value="Saioa amaitu" class="btn"/>
    </form>
</div>

<h1>Profila</h1>

<?php
$hashids = new Hashids('ZAB_IGAI_PLAT_GEN', 8);
$ref = $_GET['ref'] ?? '';
$decoded = $hashids->decode($ref);
$userId = $decoded[0] ?? null;
if (!$userId) { echo 'Invalid user.'; exit; }

$user = $usuario->getUser();

// Mostrar mensajes
if (isset($_SESSION['mensaje'])) {
    echo '<div style="color: green; margin-bottom: 15px; padding: 10px; border: 1px solid green; background: #e6ffe6;">';
    echo htmlspecialchars($_SESSION['mensaje']);
    echo '</div>';
    unset($_SESSION['mensaje']);
}

if (isset($_SESSION['error'])) {
    echo '<div style="color: red; margin-bottom: 15px; padding: 10px; border: 1px solid red; background: #ffe6e6;">';
    echo htmlspecialchars($_SESSION['error']);
    echo '</div>';
    unset($_SESSION['error']);
}
?>

<!-- Pestañas -->
<div class="tab" style="display: flex; gap: 8px; margin-bottom: 20px;">
    <button class="tablinks active" onclick="openTab(event, 'datos')" style="flex: 1;">Datu pertsonalak</button>
    <button class="tablinks" onclick="openTab(event, 'credenciales')" style="flex: 1;">Kredentzialak</button>
    <button class="tablinks" onclick="openTab(event, 'activos')" style="flex: 1;">Kurtso aktiboak</button>
    <button class="tablinks" onclick="openTab(event, 'historial')" style="flex: 1;">Historia</button>
</div>

<!-- Datos personales -->
<div id="datos" class="tabcontent active card" style="max-width:600px;margin:0 auto;">
    <h2 style="text-align:center;">Datu pertsonalak</h2>
    <div style="text-align:center;margin-bottom:20px;">
        <p><strong>Izena:</strong> <?php echo htmlspecialchars($usuario->getIzena()) . " " . htmlspecialchars($usuario->getAbizena()); ?></p>
        <p><strong>NAN:</strong> <?php echo htmlspecialchars($usuario->getNan()); ?></p>
        <p><strong>Jaioteguna:</strong> <?php echo htmlspecialchars($usuario->getJaiotegun()); ?></p>
        <p><strong>Email:</strong> <?php echo htmlspecialchars($usuario->getEmail()); ?></p>
        <p><strong>IBAN:</strong> <?php echo htmlspecialchars($usuario->getIban()); ?></p>
    </div>
    
    <form method="POST" style="max-width:400px;margin:0 auto;">
        <h3 style="text-align:center;">Datuak eguneratu</h3>
        <label>Izena:</label>
        <input type="text" name="izena" required value="<?php echo htmlspecialchars($usuario->getIzena()); ?>" style="width:100%;margin-bottom:10px;">
        
        <label>Abizena:</label>
        <input type="text" name="abizena" required value="<?php echo htmlspecialchars($usuario->getAbizena()); ?>" style="width:100%;margin-bottom:10px;">
        
        <label>NAN:</label>
        <input type="text" name="nan" required value="<?php echo htmlspecialchars($usuario->getNan()); ?>" style="width:100%;margin-bottom:10px;">
        
        <label>Jaioteguna:</label>
        <input type="date" name="jaiotegun" required value="<?php echo htmlspecialchars($usuario->getJaiotegun()); ?>" style="width:100%;margin-bottom:10px;">
        
        <input type="submit" name="actualizar_datos" value="Datuak eguneratu" class="btn" style="width:100%;margin-top:12px;">
    </form>
</div>

<!-- Credenciales -->
<div id="credenciales" class="tabcontent card" style="max-width:600px;margin:0 auto;">
    <h2 style="text-align:center;">Kredentzialak eguneratu</h2>
    <form method="POST" style="max-width:400px;margin:0 auto;">
        <label>Erabiltzaile berria:</label>
        <input type="text" name="nuevo_user" required value="<?php echo htmlspecialchars($usuario->getUser()); ?>" style="width:100%;margin-bottom:10px;">
        
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