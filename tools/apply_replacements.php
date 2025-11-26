<?php
// Quick repo scan & safe replacements helper.
// Usage: php tools/apply_replacements.php
// Creates .bak backups and prints changed files.

$root = realpath(__DIR__ . '/..');
$excludeDirs = ['vendor', '.git', 'node_modules', 'logs', 'uploads', 'style'];
$extensions = ['php', 'inc', 'html', 'htm', 'js'];

$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root));
$changes = [];

foreach ($it as $file) {
    if ($file->isDir()) continue;
    $path = $file->getRealPath();
    $rel = substr($path, strlen($root) + 1);
    // Skip excluded dirs
    $skip = false;
    foreach ($excludeDirs as $d) {
        if (strpos($rel, $d . DIRECTORY_SEPARATOR) === 0) { $skip = true; break; }
    }
    if ($skip) continue;
    $ext = pathinfo($path, PATHINFO_EXTENSION);
    if (!in_array(strtolower($ext), $extensions)) continue;

    $src = file_get_contents($path);
    $orig = $src;

    // Replace function_exists('encode_id') ? encode_id(<num>) : (<num>) => page_link(<num>, 'page<num>')
    $src = preg_replace_callback(
        '/\\$hashids->encode\\(\s*([0-9]+)\s*\\)/',
        function($m){
            $num = intval($m[1]);
            // Use page_link with a sensible fallback name (common mapping exists in router)
            $fallback = match($num) {
                1 => 'dashboard',
                2 => 'langileak',
                3 => 'produktuak',
                4 => 'salmentak',
                5 => 'nire_salmentak',
                6 => 'profile',
                7 => 'salmenta_berria',
                8 => 'langilea_kudeaketa',
                9 => 'home',
                default => 'page' . $num
            };
            return "function_exists('page_link') ? page_link($num, '". $fallback ."') : '/". $fallback .".php'";
        },
        $src
    );

    // Replace function_exists('encode_id') ? encode_id(...non-numeric...) : (...non-numeric...) => encode_id(...)
    $src = preg_replace_callback(
        '/\\$hashids->encode\\(\s*([^\)]+)\s*\\)/',
        function($m){
            $arg = trim($m[1]);
            return "function_exists('encode_id') ? encode_id($arg) : ($arg)";
        },
        $src
    );

    // Replace header('Location: ../views/xxx.php') or header("Location: ../views/xxx.php")
    $src = preg_replace_callback(
        '/header\(\s*[\'"]Location:\s*([^\'"]*views\/([^\'"]+\.php))\s*[\'"]\s*\)\s*;\s*(exit\s*;)?/i',
        function($m){
            $path = $m[1];
            // Resolve to absolute path under web root; keep simple redirect_to fallback
            $clean = basename($path);
            $p = '/' . $clean;
            return "if (function_exists('redirect_to')) { redirect_to('{$p}'); } else { header('Location: {$p}'); exit; }";
        },
        $src
    );

    // Replace header('Location: /views/xxx.php') same as above
    $src = preg_replace_callback(
        '/header\(\s*[\'"]Location:\s*\/?([^\'"]*views\/([^\'"]+\.php))\s*[\'"]\s*\)\s*;\s*(exit\s*;)?/i',
        function($m){
            $clean = basename($m[1]);
            $p = '/' . $clean;
            return "if (function_exists('redirect_to')) { redirect_to('{$p}'); } else { header('Location: {$p}'); exit; }";
        },
        $src
    );

    // Slight safety: avoid touching .bak files or this script itself
    if ($src !== $orig) {
        // Backup
        $bak = $path . '.bak';
        if (!file_exists($bak)) file_put_contents($bak, $orig);
        file_put_contents($path, $src);
        $changes[] = $rel;
    }
}

if (count($changes) === 0) {
    echo "No replacements made.\n";
} else {
    echo "Applied replacements to:\n";
    foreach ($changes as $c) echo " - $c\n";
    echo "\nBackups created with .bak extension.\n";
}
