<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../model/salmenta.php';
require_once __DIR__ . '/../model/produktua.php';
require_once __DIR__ . '/../model/seguritatea.php';

global $db_ok, $conn;
if (!$db_ok || !$conn) { echo '<div class="alert alert-error">DB ez dago prest.</div>'; return; }
if (empty($_SESSION['usuario_id'])) { redirect_to('/index.php'); }

$csrf = $_SESSION['csrf_token'] ?? ($_SESSION['csrf_token']=Seguritatea::generateCSRFToken());
$mezua=''; $errorea='';

if ($_SERVER['REQUEST_METHOD']==='POST') {
    if (!Seguritatea::verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errorea='CSRF errorea.';
    } else {
        $d = [
            'langile_id'        => (int)$_SESSION['usuario_id'],
            'produktu_id'       => (int)($_POST['produktu_id'] ?? 0),
            'kantitatea'        => (int)($_POST['kantitatea'] ?? 1),
            'prezioa_unitarioa' => (float)($_POST['prezioa_unitarioa'] ?? 0),
            'prezioa_totala'    => (float)($_POST['prezioa_totala'] ?? 0),
            'data_salmenta'     => $_POST['data_salmenta'] ?? date('Y-m-d'),
            'bezeroa_izena'     => trim($_POST['bezeroa_izena'] ?? ''),
            'bezeroa_nif'       => trim($_POST['bezeroa_nif'] ?? ''),
            'bezeroa_telefonoa' => trim($_POST['bezeroa_telefonoa'] ?? ''),
            'oharra'            => trim($_POST['oharra'] ?? '')
        ];
        if ($d['produktu_id'] && Salmenta::create($conn,$d)) $mezua='Salmenta gehituta.';
        else $errorea='Ezin izan da sortu.';
    }
}

$produktua = $conn->query("SELECT id, izena FROM produktua ORDER BY izena ASC")->fetch_all(MYSQLI_ASSOC);
?>
<div class="page-wrapper" style="max-width:700px;margin:0 auto;padding:20px;">
    <h2>Salmenta berria</h2>
    <?php if($mezua):?><div class="alert alert-success"><?=htmlspecialchars($mezua)?></div><?php endif;?>
    <?php if($errorea):?><div class="alert alert-error"><?=htmlspecialchars($errorea)?></div><?php endif;?>
    <form method="POST">
        <input type="hidden" name="csrf_token" value="<?=htmlspecialchars($csrf)?>">
        <label>Produktua:</label>
        <select name="produktu_id" required style="width:100%;margin-bottom:10px;">
            <option value="">Aukeratu...</option>
            <?php foreach($produktua as $p):?>
            <option value="<?=$p['id']?>"><?=htmlspecialchars($p['izena'])?></option>
            <?php endforeach; ?>
        </select>
        <label>Kantitatea:</label>
        <input type="number" name="kantitatea" value="1" min="1" style="width:100%;margin-bottom:10px;">
        <label>Prezio unitarioa:</label>
        <input type="number" step="0.01" name="prezioa_unitarioa" style="width:100%;margin-bottom:10px;">
        <label>Prezio totala:</label>
        <input type="number" step="0.01" name="prezioa_totala" style="width:100%;margin-bottom:10px;">
        <label>Data:</label>
        <input type="date" name="data_salmenta" value="<?=date('Y-m-d')?>" style="width:100%;margin-bottom:10px;">
        <label>Bezero izena:</label>
        <input name="bezeroa_izena" style="width:100%;margin-bottom:10px;">
        <label>Bezero NIF:</label>
        <input name="bezeroa_nif" style="width:100%;margin-bottom:10px;">
        <label>Telefonoa:</label>
        <input name="bezeroa_telefonoa" style="width:100%;margin-bottom:10px;">
        <label>Oharra:</label>
        <textarea name="oharra" style="width:100%;margin-bottom:14px;"></textarea>
        <button class="btn" style="width:100%;">Gorde</button>
    </form>
</div>