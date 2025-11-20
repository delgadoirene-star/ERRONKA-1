<?php
require_once __DIR__ . '/../bootstrap.php';  // Loads global $hashids
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../model/seguritatea.php';
require_once __DIR__ . '/../model/langilea.php';
require_once __DIR__ . '/../model/usuario.php';

global $hashids;  // Access global Hashids

if (!isset($_SESSION['usuario']) || !$_SESSION['usuario']['admin']) {
    header('Location: ../index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="eu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Langileak - Zabala</title>
    <link rel="stylesheet" href="../style/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="container">
            <div class="navbar-brand">
                <h1>🏢 Zabala</h1>
            </div>
            <div class="navbar-menu">
                <?php
                // Generate encoded page names
                $dashboardEncoded = ($hashids !== null) ? $hashids->encode(1) : 'dashboard';
                ?>
                <a href="<?php echo $dashboardEncoded; ?>.php" class="nav-link">Dashboard</a>
                <a href="../controllers/AdminController.php?accion=langileak" class="nav-link active">Langileak</a>
                <a href="../controllers/AdminController.php?accion=produktuak" class="nav-link">Produktuak</a>
                <a href="../controllers/AdminController.php?accion=salmentak" class="nav-link">Salmentak</a>
                <a href="logout.php" class="nav-link">Saioa itxi</a>
            </div>
        </div>
    </nav>

    <main class="container">
        <h2>👥 Langileak Kudeaketa</h2>
        
        <?php if (isset($exito)): ?>
            <div class="alert alert-success"><?php echo $exito; ?></div>
        <?php endif; ?>
        <?php if (isset($error)): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>

        <button class="btn btn-primary" onclick="toggleFormSortu()">➕ Langilea Gehitu</button>

        <div id="form-sortu" class="form-container" style="display:none; margin: 20px 0; padding: 20px; border: 1px solid #ddd; border-radius: 8px;">
            <h3>Langilea Sortu</h3>
            <form method="POST" action="../controllers/AdminController.php?accion=langilea_sortu">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Izena*</label>
                        <input type="text" name="izena" required>
                    </div>
                    <div class="form-group">
                        <label>Abizena*</label>
                        <input type="text" name="abizena" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>NAN* (12345678A)</label>
                        <input type="text" name="nan" required>
                    </div>
                    <div class="form-group">
                        <label>Email*</label>
                        <input type="email" name="email" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Telefonoa</label>
                        <input type="tel" name="telefono">
                    </div>
                    <div class="form-group">
                        <label>Departamendua</label>
                        <input type="text" name="departamendua">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Pozisioa</label>
                        <input type="text" name="pozisioa">
                    </div>
                    <div class="form-group">
                        <label>Pasahitza*</label>
                        <input type="password" name="pasahitza" required>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-success">Gorde</button>
                    <button type="button" class="btn btn-secondary" onclick="toggleFormSortu()">Utzi</button>
                </div>
            </form>
        </div>

        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Izena</th>
                        <th>Abizena</th>
                        <th>NAN</th>
                        <th>Email</th>
                        <th>Telefonoa</th>
                        <th>Departamendua</th>
                        <th>Pozisioa</th>
                        <th>Ekintzak</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($langileak as $langilea): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($langilea['izena']); ?></td>
                            <td><?php echo htmlspecialchars($langilea['abizena']); ?></td>
                            <td><?php echo htmlspecialchars($langilea['nan']); ?></td>
                            <td><?php echo htmlspecialchars($langilea['email']); ?></td>
                            <td><?php echo htmlspecialchars($langilea['telefono']); ?></td>
                            <td><?php echo htmlspecialchars($langilea['departamendua']); ?></td>
                            <td><?php echo htmlspecialchars($langilea['pozisioa']); ?></td>
                            <td>
                                <a href="../controllers/AdminController.php?accion=langilea_editatu&id=<?php echo $langilea['id']; ?>" class="btn btn-warning btn-sm">Editatu</a>
                                <a href="../controllers/AdminController.php?accion=langilea_ezeztatu&id=<?php echo $langilea['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Seguru zaude langilea ezabatu nahi duzula?');">Ezabatu</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>

    <script>
    function toggleFormSortu() {
        var form = document.getElementById('form-sortu');
        if (form.style.display === 'none' || form.style.display === '') {
            form.style.display = 'block';
        } else {
            form.style.display = 'none';
        }
    }
    </script>
</body>
</html>