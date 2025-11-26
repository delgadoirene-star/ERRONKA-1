<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../model/seguritatea.php';
require_once __DIR__ . '/../model/langilea.php';
require_once __DIR__ . '/../model/usuario.php';

global $db_ok, $conn;
if (!$db_ok || !$conn) { echo '<div class="alert alert-error">DB ez dago prest.</div>'; return; }

if (empty($_SESSION['usuario_id'])) { redirect_to('/index.php'); }

$admin = ($_SESSION['usuario_rol'] ?? '') === 'admin';

if (empty($_SESSION['csrf_token'])) { $_SESSION['csrf_token'] = Seguritatea::generateCSRFToken(); }
$csrf = $_SESSION['csrf_token'];

$mezua=''; $errorea='';

if ($_SERVER['REQUEST_METHOD']==='POST') {
    $tok = $_POST['csrf_token'] ?? '';
    if (!Seguritatea::verifyCSRFToken($tok)) {
        $errorea='CSRF errorea.';
    } elseif ($admin) {
        $action = $_POST['action'] ?? '';
        if ($action==='create') {
            $data = [
                'usuario_id'      => (int)($_POST['usuario_id'] ?? 0),
                'departamendua'   => trim($_POST['departamendua'] ?? ''),
                'pozisio'         => trim($_POST['pozisio'] ?? ''),
                'data_kontratazio'=> trim($_POST['data_kontratazio'] ?? ''),
                'soldata'         => (int)($_POST['soldata'] ?? 0),
                'telefonoa'       => trim($_POST['telefonoa'] ?? ''),
                'foto'            => trim($_POST['foto'] ?? '')
            ];
            if (!$data['usuario_id']) $errorea='Erabiltzailea behar da.';
            elseif (Langilea::create($conn,$data)) $mezua='Langilea sortua.';
            else $errorea='Sortzeak huts egin du.';
        } elseif ($action==='update') {
            $id = (int)($_POST['id'] ?? 0);
            $data = [
                'departamendua'   => trim($_POST['departamendua'] ?? ''),
                'pozisio'         => trim($_POST['pozisio'] ?? ''),
                'data_kontratazio'=> trim($_POST['data_kontratazio'] ?? ''),
                'soldata'         => (int)($_POST['soldata'] ?? 0),
                'telefonoa'       => trim($_POST['telefonoa'] ?? ''),
                'foto'            => trim($_POST['foto'] ?? '')
            ];
            if ($id && Langilea::update($conn,$id,$data)) $mezua='Eguneratua.';
            else $errorea='Eguneratzeak huts egin du.';
        } elseif ($action==='delete') {
            $id = (int)($_POST['id'] ?? 0);
            if ($id && Langilea::delete($conn,$id)) $mezua='Ezabatua.';
            else $errorea='Ezabaketak huts egin du.';
        }
    }
}

$langileak = Langilea::all($conn);
$usuarios = $conn->query("SELECT id, user FROM usuario ORDER BY id DESC")->fetch_all(MYSQLI_ASSOC);
$dashboardLink = function_exists('page_link') ? page_link(1, 'dashboard') : '/views/dashboard.php';
?>
<link rel="stylesheet" href="/style/style.css">
<div class="page-wrapper" style="max-width:1100px;margin:0 auto;padding:20px;">
    <h2>Langileak</h2>
    <?php if($mezua):?><div class="alert alert-success"><?=htmlspecialchars($mezua)?></div><?php endif;?>
    <?php if($errorea):?><div class="alert alert-error"><?=htmlspecialchars($errorea)?></div><?php endif;?>

    <?php if($admin):?>
    <form method="POST" style="margin-bottom:16px;display:flex;flex-wrap:wrap;gap:8px;">
        <input type="hidden" name="csrf_token" value="<?=htmlspecialchars($csrf)?>">
        <input type="hidden" name="action" value="create">
        <select name="usuario_id" required>
            <option value="">Erabiltzailea...</option>
            <?php foreach($usuarios as $u):?>
            <option value="<?=$u['id']?>"><?=htmlspecialchars($u['user'])?></option>
            <?php endforeach;?>
        </select>
        <input name="departamendua" placeholder="Departamendua">
        <input name="pozisio" placeholder="Pozizioa">
        <input name="data_kontratazio" type="date">
        <input name="soldata" type="number" placeholder="Soldata">
        <input name="telefonoa" placeholder="Telefonoa">
        <input name="foto" placeholder="Foto URL">
        <button class="btn">Gehitu</button>
    </form>
    <?php endif;?>

    <table class="table" style="width:100%;border-collapse:collapse;">
        <thead>
            <tr>
                <th>ID</th><th>Erabiltzailea</th><th>Depart.</th><th>Pozisio</th><th>Kontratazio Data</th><th>Soldata</th><th>Telefonoa</th><th></th>
            </tr>
        </thead>
        <tbody>
        <?php foreach($langileak as $l):?>
            <tr>
                <td><?=htmlspecialchars($l['id'])?></td>
                <td><?=htmlspecialchars($l['usuario_id'])?></td>
                <td><?=htmlspecialchars($l['departamendua'])?></td>
                <td><?=htmlspecialchars($l['pozisio'])?></td>
                <td><?=htmlspecialchars($l['data_kontratazio'])?></td>
                <td><?=htmlspecialchars($l['soldata'])?></td>
                <td><?=htmlspecialchars($l['telefonoa'])?></td>
                <td style="white-space:nowrap;">
                    <?php if($admin):?>
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="csrf_token" value="<?=$csrf?>">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?=$l['id']?>">
                        <button class="btn btn-danger" onclick="return confirm('Ezabatu?')">✕</button>
                    </form>
                    <details style="display:inline;">
                        <summary class="btn btn-secondary">Edit</summary>
                        <form method="POST" style="background:#f7f7f7;padding:8px;">
                            <input type="hidden" name="csrf_token" value="<?=$csrf?>">
                            <input type="hidden" name="action" value="update">
                            <input type="hidden" name="id" value="<?=$l['id']?>">
                            <input name="departamendua" value="<?=htmlspecialchars($l['departamendua'])?>">
                            <input name="pozisio" value="<?=htmlspecialchars($l['pozisio'])?>">
                            <input name="data_kontratazio" type="date" value="<?=htmlspecialchars($l['data_kontratazio'])?>">
                            <input name="soldata" type="number" value="<?=htmlspecialchars($l['soldata'])?>">
                            <input name="telefonoa" value="<?=htmlspecialchars($l['telefonoa'])?>">
                            <input name="foto" value="<?=htmlspecialchars($l['foto'])?>">
                            <button class="btn btn-secondary">Gorde</button>
                        </form>
                    </details>
                    <?php endif;?>
                </td>
            </tr>
        <?php endforeach;?>
        </tbody>
    </table>
    <div style="margin-top:12px;">
        <a href="<?= htmlspecialchars($dashboardLink) ?>" class="btn btn-secondary">← Dashboard</a>
    </div>
</div>