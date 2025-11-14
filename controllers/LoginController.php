<?php
require_once __DIR__ . '/../model/usuario.php';
require_once __DIR__ . '/../konexioa.php';

class LoginController {
    private $conn;
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    public function erakutsiLoginFormularioa($erroreMezua = "") {
        global $conn;
        $conn = $this->conn;
        $GLOBALS['erroreMezua'] = $erroreMezua;
        
        $title = "Saioa hasi - IGAI Ikastetxea";
        ob_start();
        require_once __DIR__ . '/../views/login.php';
        $content = ob_get_clean();
        require_once __DIR__ . '/../views/layouts/main.php';
    }
    
    public function prozesatuLogin() {
        $erroreMezua = "";
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $user = trim($_POST['user'] ?? '');
            $password = $_POST['password'] ?? '';

            if(!empty($user) && !empty($password)){
                $usuario = Usuario::lortuErabiltzaileagatik($this->conn, $user);
                
                if ($usuario !== null) {
                    if (password_verify($password, $usuario->getPassword())) {
                        $_SESSION['valid'] = true;
                        $_SESSION['timeout'] = time();
                        $_SESSION['usuario'] = $usuario;
                        
                        if ($usuario->getAdministratzailea()) {
                            header('Location: administrazioa.php');
                        } else {
                            header('Location: profila.php');
                        }
                        exit;
                    } else { 
                        $erroreMezua = "Pasahitz okerra";
                    }
                } else {
                    $erroreMezua = "Erabiltzaile okerra edo ez da existitzen";
                }
            } else {
                $erroreMezua = "Eremu guztiak bete behar dira";
            }
        }
        
        $this->erakutsiLoginFormularioa($erroreMezua);
    }
    
    public function kudeatuEskaerak() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->prozesatuLogin();
        } else {
            $this->erakutsiLoginFormularioa();
        }
    }
}
?>