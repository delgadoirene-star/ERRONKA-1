<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../model/seguritatea.php';
require_once __DIR__ . '/../model/langilea.php';
require_once __DIR__ . '/../model/usuario.php';

$action = $_GET['action'] ?? $_GET['accion'] ?? $_POST['action'] ?? '';

function back_with($type, $msg) {
    $_SESSION["flash_$type"] = $msg;
    header('Location: ../views/langileak.php'); exit;
}

try {
    if ($action === 'add' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!Seguritatea::verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            Seguritatea::logSeguritatea($conn, "CSRF_ATTACK", "admin:add", $_SESSION['usuario_id'] ?? null);
            back_with('error', 'Segurtasun-errorea (CSRF).');
        }

        $izena = trim($_POST['izena'] ?? '');
        $abizena = trim($_POST['abizena'] ?? '');
        $nan = trim($_POST['nan'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $telefonoa = trim($_POST['telefonoa'] ?? '');
        $departamendua = trim($_POST['departamendua'] ?? '');
        $pozisio = trim($_POST['pozisio'] ?? '');
        $pasahitza = $_POST['pasahitza'] ?? '';

        if (!$izena || !$abizena || !$email || !$pasahitza) {
            back_with('error', 'Izena, abizena, email eta pasahitza beharrezkoak dira.');
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            back_with('error', 'Emaila ez da baliogarria.');
        }
        if (!Seguritatea::balioztaPasahitza($pasahitza)) {
            back_with('error', 'Pasahitza ahula da.');
        }
        if (Usuario::lortuEmailAgatik($conn, $email)) {
            back_with('error', 'Emaila dagoeneko existitzen da.');
        }

        $username = $email; // edo substr($email, 0, strpos($email,'@')) ?: $email;
        $usuario = new Usuario($izena, $abizena, $nan, $email, $username, $pasahitza);

        $conn->begin_transaction();
        try {
            if (!$usuario->sortu($conn)) {
                throw new Exception('Ezin izan da erabiltzailea sortu.');
            }
            $lang = new Langilea($usuario->getId(), $departamendua, $pozisio, null, 0, $telefonoa);
            if (!$lang->sortu($conn)) {
                throw new Exception('Ezin izan da langilea sortu.');
            }
            $conn->commit();
        } catch (Throwable $e) {
            $conn->rollback();
            throw $e;
        }

        Seguritatea::logSeguritatea($conn, "LANGILEA_SORTU", "$izena $abizena", $_SESSION['usuario_id'] ?? null);
        back_with('success', 'Langilea ondo sortu da.');
    }

    if ($action === 'delete') {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            back_with('error', 'Metodoa ez da onartzen.');
        }
        if (!Seguritatea::verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            Seguritatea::logSeguritatea($conn, "CSRF_ATTACK", "admin:delete", $_SESSION['usuario_id'] ?? null);
            back_with('error', 'Segurtasun-errorea (CSRF).');
        }
        $id = intval($_POST['id'] ?? 0);
        if ($id <= 0) back_with('error', 'ID baliogabea.');
        if (Langilea::desaktibatu($conn, $id)) {
            Seguritatea::logSeguritatea($conn, "LANGILEA_EZABATU", "ID: $id", $_SESSION['usuario_id'] ?? null);
            back_with('success', 'Langilea ezabatuta.');
        } else {
            back_with('error', 'Errorea ezabatzean.');
        }
    }

    header('Location: ../views/langileak.php'); exit;
} catch (Throwable $e) {
    error_log("AdminController error: " . $e->getMessage());
    back_with('error', 'Barneko errorea.');
}