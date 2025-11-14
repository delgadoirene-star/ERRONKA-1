<?php
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
                <a href="dashboard.php" class="nav-link">Dashboard</a>
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
                        <label>Pozisioa</label>// filepath: c:\xampp\htdocs\ariketak\ERRONKA-1 (IGAI)\ERRONKA-1\views\langilea_kudeaketa.php
<?php
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
    <title>Langileak - Xabala</title>
    <link rel="stylesheet" href="../style/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="container">
            <div class="navbar-brand">
                <h1>🏢 Xabala</h1>
            </div>
            <div class="navbar-menu">
                <a href="dashboard.php" class="nav-link">Dashboard</a>
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