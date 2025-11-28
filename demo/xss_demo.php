<?php
// Vulnerable: inyecta HTML/JS
if (isset($_GET['q'])) {
    echo "Bilaketa: " . $_GET['q']; // ❌ vulnerable
}
if (isset($_GET['safe'])) {
    echo "Bilaketa (segurua): " . htmlspecialchars($_GET['safe'], ENT_QUOTES, 'UTF-8'); // ✅ seguro
}