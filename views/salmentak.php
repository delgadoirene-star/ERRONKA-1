<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../model/usuario.php';
require_once __DIR__ . '/../model/langilea.php';
require_once __DIR__ . '/../model/salmenta.php';
require_once __DIR__ . '/../model/produktua.php';
require_once __DIR__ . '/../model/seguritatea.php';

global $db_ok, $conn;
if (!$db_ok || !$conn) { echo '<div class="alert alert-error">DB ez dago prest.</div>'; return; }
if (empty($_SESSION['usuario_id'])) { redirect_to('/index.php'); }

$csrf = $_SESSION['csrf_token'] ?? ($_SESSION['csrf_token']=Seguritatea::generateCSRFToken());

$mezua=''; $errorea='';
$isAdmin = ($_SESSION['usuario_rol'] ?? '') === 'admin';
$userId = (int)$_SESSION['usuario_id'];

if ($_SERVER['REQUEST_METHOD']==='POST') {
    if (!Seguritatea::verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errorea='CSRF errorea.';
    } else {
        $action = $_POST['action'] ?? '';
        if ($action==='create') {
            $d = [
                'langile_id'        => (int)($_POST['langile_id'] ?? $userId),
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
            if ($d['produktu_id'] && Salmenta::create($conn,$d)) $mezua='Salmenta sortua.';
            else $errorea='Sortze huts.';
        } elseif ($action==='delete') {
            $id=(int)($_POST['id']??0);
            if ($id) {
                // allow delete if admin or owns (need fetch)
                $row = Salmenta::find($conn,$id);
                if ($row && ($isAdmin || (int)$row['langile_id']===$userId) && Salmenta::delete($conn,$id)) $mezua='Ezabatua.';
                else $errorea='Ezabaketak huts egin du.';
            }
        }
    }
}

$salmentak = $isAdmin
    ? Salmenta::lortuGuztiak($conn)                     // joined, includes produktu_izena
    : Salmenta::lortuGuztiak($conn, $userId);           // joined, filtered by current user

$produktua = $conn->query("SELECT id, izena FROM produktua ORDER BY izena ASC")->fetch_all(MYSQLI_ASSOC);
$dashboardLink = function_exists('page_link') ? page_link(1, 'dashboard') : '/views/dashboard.php';
?>
<link rel="stylesheet" href="/style/style.css">
<div class="page-wrapper" style="max-width:1100px;margin:0 auto;padding:20px;">
    <h2>Salmentak</h2>
    <?php if($mezua):?><div class="alert alert-success"><?=htmlspecialchars($mezua)?></div><?php endif;?>
    <?php if($errorea):?><div class="alert alert-error"><?=htmlspecialchars($errorea)?></div><?php endif;?>

    <form method="POST" style="display:flex;flex-wrap:wrap;gap:8px;margin-bottom:16px;">
        <input type="hidden" name="csrf_token" value="<?=htmlspecialchars($csrf)?>">
        <input type="hidden" name="action" value="create">
        <select name="produktu_id" required>
            <option value="">Produktua...</option>
            <?php foreach($produktua as $p):?><option value="<?=$p['id']?>"><?=htmlspecialchars($p['izena'])?></option><?php endforeach;?>
        </select>
        <input name="kantitatea" type="number" value="1" min="1">
        <input name="prezioa_unitarioa" type="number" step="0.01" placeholder="Unitarioa">
        <input name="prezioa_totala" type="number" step="0.01" placeholder="Totala">
        <input name="data_salmenta" type="date" value="<?=date('Y-m-d')?>">
        <input name="bezeroa_izena" type="text" placeholder="Bezeroa izena">
        <input name="bezeroa_nif" type="text" placeholder="Bezeroa NIF">
        <input name="bezeroa_telefonoa" type="text" placeholder="Bezeroa telefonoa">
        <textarea name="oharra" placeholder="Oharrak"></textarea>
        <button type="submit" class="btn btn-primary">Gorde</button>
    </form>

    <table class="table">
        <thead>
            <tr>
                <th>Data</th>
                <th>Produktua</th>
                <th>Kantitatea</th>
                <th>Prezioa</th>
                <th>Bezeroa</th>
                <th>Akzioak</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($salmentak as $s):?>
            <tr>
                <td><?=htmlspecialchars($s['data_salmenta'])?></td>
                <td><?=htmlspecialchars($s['produktu_izena'])?></td>
                <td class="text-end"><?=htmlspecialchars($s['kantitatea'])?></td>
                <td class="text-end"><?=htmlspecialchars($s['prezioa_totala'])?></td>
                <td><?=htmlspecialchars($s['bezeroa_izena'])?><br><?=htmlspecialchars($s['bezeroa_nif'])?><br><?=htmlspecialchars($s['bezeroa_telefonoa'])?></td>
                <td>
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="csrf_token" value="<?=htmlspecialchars($csrf)?>">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?=htmlspecialchars($s['id'])?>">
                        <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Ezabatu salmenta hau?');">Ezabatu</button>
                    </form>
                </td>
            </tr>
            <?php endforeach;?>
        </tbody>
    </table>

    <!-- Optional footer actions -->
    <div style="margin-top:12px;">
        <a href="<?= htmlspecialchars($dashboardLink) ?>" class="btn btn-secondary">← Dashboard</a>
    </div>
</div>

