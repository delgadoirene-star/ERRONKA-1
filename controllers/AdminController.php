<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../model/seguritatea.php';
require_once __DIR__ . '/../model/langilea.php';
require_once __DIR__ . '/../model/usuario.php';
require_once __DIR__ . '/../config/konexioa.php';

if (($_SESSION['usuario_rol'] ?? '') !== 'admin') {
    redirect_to('/index.php');
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

function back_with($type, $msg) {
    $_SESSION['flash_'.$type] = $msg;
    redirect_to($_SERVER['HTTP_REFERER'] ?? '/index.php');
}

if (!$db_ok || !$conn) {
    back_with('error','DB ez dago prest');
    exit;
}

$token = $_POST['csrf_token'] ?? '';
if (!Seguritatea::verifyCSRFToken($token)) {
    back_with('error','CSRF errorea');
    exit;
}

try {
    switch ($action) {
        case 'delete_langilea':
            $id = (int)($_POST['id'] ?? 0);
            $stmt = $conn->prepare("DELETE FROM langilea WHERE id=?");
            $stmt->bind_param("i",$id);
            $stmt->execute();
            $stmt->close();
            back_with('ok','Langilea ezabatu da');
            break;

        case 'edit_langilea':
            $id = (int)($_POST['id'] ?? 0);
            $dep = trim($_POST['departamendua'] ?? '');
            $poz = trim($_POST['pozisio'] ?? '');
            $tel = trim($_POST['telefonoa'] ?? '');
            $stmt = $conn->prepare("UPDATE langilea SET departamendua=?, pozisio=?, telefonoa=? WHERE id=?");
            $stmt->bind_param("sssi",$dep,$poz,$tel,$id);
            $stmt->execute();
            $stmt->close();
            back_with('ok','Langilea eguneratu da');
            break;

        case 'add_langilea':
            // create usuario + langilea minimal flow
            $izena   = trim($_POST['izena'] ?? '');
            $abizena = trim($_POST['abizena'] ?? '');
            $nan     = trim($_POST['nan'] ?? '');
            $email   = trim($_POST['email'] ?? '');
            $tel     = trim($_POST['telefonoa'] ?? '');
            $dep     = trim($_POST['departamendua'] ?? '');
            $poz     = trim($_POST['pozisio'] ?? '');
            $pass    = $_POST['pasahitza'] ?? '';

            if ($izena==='' || $abizena==='' || $nan==='' || $email==='' || $pass==='') {
                back_with('error','Eremu derrigorrezkoak');
                break;
            }
            // Create usuario
            $userName = strtolower(preg_replace('/\s+/', '.', $izena)) . '.' . strtolower(preg_replace('/\s+/', '.', $abizena));
            $hash = password_hash($pass, PASSWORD_DEFAULT);
            $st = $conn->prepare("INSERT INTO usuario (izena, abizena, nan, email, user, password, rol, aktibo) VALUES (?,?,?,?,?,?,?,1)");
            $rol = 'langilea';
            $st->bind_param("sssssss",$izena,$abizena,$nan,$email,$userName,$hash,$rol);
            $st->execute();
            $uid = $conn->insert_id;
            $st->close();

            // Create langilea
            $st2 = $conn->prepare("INSERT INTO langilea (usuario_id, departamendua, pozisio, telefonoa) VALUES (?,?,?,?)");
            $st2->bind_param("isss",$uid,$dep,$poz,$tel);
            $st2->execute();
            $st2->close();

            back_with('ok','Langilea sortu da');
            break;

        case 'delete_produktua':
            $id = (int)($_POST['id'] ?? 0);
            $stmt = $conn->prepare("DELETE FROM produktua WHERE id=?");
            $stmt->bind_param("i",$id);
            $stmt->execute();
            $stmt->close();
            back_with('ok','Produktua ezabatu da');
            break;

        case 'edit_produktua':
            $id  = (int)($_POST['id'] ?? 0);
            $iz  = trim($_POST['izena'] ?? '');
            $kat = trim($_POST['kategoria'] ?? '');
            $pr  = (float)($_POST['prezioa'] ?? 0);
            $st  = (int)($_POST['stock'] ?? 0);
            $stmt = $conn->prepare("UPDATE produktua SET izena=?, kategoria=?, prezioa=?, stock=? WHERE id=?");
            $stmt->bind_param("ssdii",$iz,$kat,$pr,$st,$id);
            $stmt->execute();
            $stmt->close();
            back_with('ok','Produktua eguneratu da');
            break;

        case 'delete_salmenta':
            $id = (int)($_POST['id'] ?? 0);
            $stmt = $conn->prepare("DELETE FROM salmenta WHERE id=?");
            $stmt->bind_param("i",$id);
            $stmt->execute();
            $stmt->close();
            back_with('ok','Salmenta ezabatu da');
            break;

        case 'edit_salmenta':
            $id   = (int)($_POST['id'] ?? 0);
            $kant = (int)($_POST['kantitatea'] ?? 0);
            $unit = (float)($_POST['prezioa_unitarioa'] ?? 0);
            $ohar = trim($_POST['oharra'] ?? '');
            $stmt = $conn->prepare("UPDATE salmenta SET kantitatea=?, prezioa_unitarioa=?, oharra=? WHERE id=?");
            $stmt->bind_param("idsi",$kant,$unit,$ohar,$id);
            $stmt->execute();
            $stmt->close();
            back_with('ok','Salmenta eguneratu da');
            break;

        default:
            back_with('error','Ekintza ezezaguna');
    }
} catch (Throwable $e) {
    back_with('error','Errorea: '.$e->getMessage());
}