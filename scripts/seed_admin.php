<?php
require_once __DIR__ . '/../bootstrap.php';
$email = $argv[1] ?? 'admin@example.com';
$pass  = $argv[2] ?? 'Admin@1234!';
$izena = 'Admin';
$abiz  = 'User';
$user  = 'admin';

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { echo "Invalid email\n"; exit(1); }
$exists = Usuario::lortuEmailAgatik($conn, $email);
if ($exists) { echo "Admin already exists\n"; exit(0); }

$u = new Usuario($izena, $abiz, 'X0000000A', $email, $user, $pass, 'admin');
if (!$u->sortu($conn)) { echo "Failed to create admin\n"; exit(1); }
echo "Admin created with id: {$u->getId()}\n";