<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../model/produktua.php';
require_once __DIR__ . '/../model/seguritatea.php';

global $db_ok, $conn;
if (!$db_ok || !$conn) { echo '<div class="alert alert-error">DB ez dago prest.</div>'; return; }

if (empty($_SESSION['csrf_token'])) { $_SESSION['csrf_token'] = Seguritatea::generateCSRFToken(); }
$csrf = $_SESSION['csrf_token'];

$errorea=''; $mezua='';

if ($_SERVER['REQUEST_METHOD']==='POST') {
    $tok = $_POST['csrf_token'] ?? '';
    if (!Seguritatea::verifyCSRFToken($tok)) {
        $errorea="CSRF errorea.";
    } else {
        $action = $_POST['action'] ?? '';
        if ($action==='create') {
            $data = [
                'izena'=>trim($_POST['izena']??''),
                'deskripzioa'=>trim($_POST['deskripzioa']??''),
                'kategoria'=>trim($_POST['kategoria']??''),
                'prezioa'=>(int)($_POST['prezioa']??0),
                'stock'=>(int)($_POST['stock']??0),
                'stock_minimo'=>(int)($_POST['stock_minimo']??0),
            ];
            if ($data['izena']==='') $errorea="Izena derrigorrezkoa.";
            elseif (Produktua::create($conn,$data)) $mezua="Produktua sortuta.";
            else $errorea="Sortzeak huts egin du.";
        } elseif ($action==='update') {
            $id=(int)($_POST['id']??0);
            $data = [
                'izena'=>trim($_POST['izena']??''),
                'deskripzioa'=>trim($_POST['deskripzioa']??''),
                'kategoria'=>trim($_POST['kategoria']??''),
                'prezioa'=>(int)($_POST['prezioa']??0),
                'stock'=>(int)($_POST['stock']??0),
                'stock_minimo'=>(int)($_POST['stock_minimo']??0),
            ];
            if ($id && Produktua::update($conn,$id,$data)) $mezua="Produktua eguneratua.";
            else $errorea="Eguneratzeak huts egin du.";
        } elseif ($action==='delete') {
            $id=(int)($_POST['id']??0);
            if ($id && Produktua::delete($conn,$id)) $mezua="Ezabatua.";
            else $errorea="Ezabaketak huts egin du.";
        }
    }
}

$produktuak = Produktua::all($conn);
?>
<div class="page-wrapper" style="max-width:1000px;margin:0 auto;padding:20px;">
    <h2>Produktuak</h2>
    <?php if($mezua):?><div class="alert alert-success"><?=htmlspecialchars($mezua)?></div><?php endif;?>
    <?php if($errorea):?><div class="alert alert-error"><?=htmlspecialchars($errorea)?></div><?php endif;?>

    <form method="POST" style="margin-bottom:20px;">
        <input type="hidden" name="csrf_token" value="<?=htmlspecialchars($csrf)?>">
        <input type="hidden" name="action" value="create">
        <input name="izena" placeholder="Izena" required>
        <input name="deskripzioa" placeholder="Deskripzioa">
        <input name="kategoria" placeholder="Kategoria">
        <input name="prezioa" type="number" placeholder="Prezioa">
        <input name="stock" type="number" placeholder="Stock">
        <input name="stock_minimo" type="number" placeholder="Minimoa">
        <button class="btn">Gehitu</button>
    </form>

    <table class="table" style="width:100%;border-collapse:collapse;">
        <thead><tr><th>ID</th><th>Izena</th><th>Kategoria</th><th>Prezioa</th><th>Stock</th><th></th></tr></thead>
        <tbody>
        <?php foreach($produktuak as $p):?>
            <tr>
                <td><?=htmlspecialchars($p['id'])?></td>
                <td><?=htmlspecialchars($p['izena'])?></td>
                <td><?=htmlspecialchars($p['kategoria'])?></td>
                <td><?=htmlspecialchars($p['prezioa'])?></td>
                <td><?=htmlspecialchars($p['stock'])?></td>
                <td style="white-space:nowrap;">
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="csrf_token" value="<?=$csrf?>">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?=htmlspecialchars($p['id'])?>">
                        <button class="btn btn-danger" onclick="return confirm('Ezabatu?')">Ezabatu</button>
                    </form>
                    <!-- Simple inline edit -->
                    <details style="display:inline;">
                        <summary class="btn btn-secondary">Editatu</summary>
                        <form method="POST" style="background:#f7f7f7;padding:8px;display:block;">
                            <input type="hidden" name="csrf_token" value="<?=$csrf?>">
                            <input type="hidden" name="action" value="update">
                            <input type="hidden" name="id" value="<?=htmlspecialchars($p['id'])?>">
                            <input name="izena" value="<?=htmlspecialchars($p['izena'])?>">
                            <input name="deskripzioa" value="<?=htmlspecialchars($p['deskripzioa'])?>">
                            <input name="kategoria" value="<?=htmlspecialchars($p['kategoria'])?>">
                            <input name="prezioa" type="number" value="<?=htmlspecialchars($p['prezioa'])?>">
                            <input name="stock" type="number" value="<?=htmlspecialchars($p['stock'])?>">
                            <input name="stock_minimo" type="number" value="<?=htmlspecialchars($p['stock_minimo'])?>">
                            <button class="btn btn-secondary">Gorde</button>
                        </form>
                    </details>
                </td>
            </tr>
        <?php endforeach;?>
        </tbody>
    </table>
</div>