<?php
require_once __DIR__ . '/../config/konexioa.php';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../model/seguritatea.php';
require_once __DIR__ . '/../model/langilea.php'; // optional: if model provides helpers

session_start();
if (!isset($_SESSION['usuario_id'])) {
    header('Location: ../index.php');
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

function redirect_back($msg = null) {
    if ($msg) $_SESSION['flash_error'] = $msg;
    header('Location: ../views/langileak.php');
    exit;
}

try {
    if ($action === 'add' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!Seguritatea::verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            Seguritatea::logSeguritatea($conn, "CSRF_ATTACK", "langileak:add", $_SESSION['usuario_id']);
            redirect_back('Segurtasun-errorea (CSRF).');
        }

        // sanitize inputs (adjust names to your form fields)
        $izena     = trim($_POST['izena'] ?? '');
        $abizena   = trim($_POST['abizena'] ?? '');
        $nif       = trim($_POST['nif'] ?? '');
        $telefono  = trim($_POST['telefono'] ?? '');
        $depart    = trim($_POST['departamento'] ?? '');
        $posizioa  = trim($_POST['posizioa'] ?? '');

        if (empty($izena) || empty($abizena)) {
            redirect_back('Izena eta abizena beharrezkoak dira.');
        }

        // Prefer model method if exists
        if (method_exists('Langilea', 'sortu')) {
            $ok = Langilea::sortu($conn, [
                'izena' => $izena,
                'abizena' => $abizena,
                'nif' => $nif,
                'telefono' => $telefono,
                'departamento' => $depart,
                'posizioa' => $posizioa,
                'usuario_id' => $_SESSION['usuario_id'],
            ]);
        } else {
            // fallback simple insert - adjust column names to your DB
            $sql = "INSERT INTO langilea (izena, abizena, nif, telefono, departamento, posizioa, usuario_id, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssssis", $izena, $abizena, $nif, $telefono, $depart, $posizioa, $_SESSION['usuario_id']);
            $ok = $stmt->execute();
            if ($stmt) $stmt->close();
        }

        if ($ok) {
            Seguritatea::logSeguritatea($conn, "LANGILEA_SORTU", "Langilea: $izena $abizena", $_SESSION['usuario_id']);
            $_SESSION['flash_success'] = 'Langilea ondo sortu da.';
            header('Location: ../views/langileak.php');
            exit;
        } else {
            redirect_back('Errorea langilea sortzean.');
        }
    }

    if ($action === 'delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!Seguritatea::verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            Seguritatea::logSeguritatea($conn, "CSRF_ATTACK", "langileak:delete", $_SESSION['usuario_id']);
            redirect_back('Segurtasun-errorea (CSRF).');
        }

        $id = intval($_POST['id'] ?? 0);
        if ($id <= 0) redirect_back('ID baliogabea.');

        if (method_exists('Langilea', 'ezabatu')) {
            $ok = Langilea::ezabatu($conn, $id);
        } else {
            // Siempre usar prepared statements, incluso en fallback
            $stmt = $conn->prepare("DELETE FROM langilea WHERE id = ?");
            $stmt->bind_param("i", $id);
            $ok = $stmt->execute();
            $stmt->close();
        }

        if ($ok) {
            Seguritatea::logSeguritatea($conn, "LANGILEA_EZABATU", "ID: $id", $_SESSION['usuario_id']);
            $_SESSION['flash_success'] = 'Langilea ezabatuta.';
        } else {
            $_SESSION['flash_error'] = 'Errorea ezabatzean.';
        }
        header('Location: ../views/langileak.php');
        exit;
    }

    // Unknown action -> redirect to list
    header('Location: ../views/langileak.php');
    exit;

} catch (Throwable $e) {
    error_log("AdminController error: " . $e->getMessage());
    redirect_back('Barneko errorea.');
}
?>