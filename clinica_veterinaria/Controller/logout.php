<?php
session_start();

// Destruir todas as variáveis de sessão
$_SESSION = array();

// Se for desejado destruir a sessão completamente, apague também o cookie de sessão
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Finalmente, destruir a sessão
session_destroy();

// Redirecionar para a página inicial com uma mensagem de sucesso
header('Location: ../index.php?logout=success');
exit;
?>