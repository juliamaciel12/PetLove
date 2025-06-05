<?php
// Iniciar sessão DEVE ser a primeira operação no script
session_start();

// Configurações do banco de dados
define('DB_HOST', 'localhost');
define('DB_NAME', 'clinica_veterinaria');
define('DB_USER', 'root');
define('DB_PASS', '');

// Conexão com o banco de dados
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8", 
        DB_USER, 
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch(PDOException $e) {
    die("Erro na conexão: " . $e->getMessage());
}

/**
 * Verifica se o usuário está logado
 * @return bool
 */
function isLoggedIn(): bool {
    return isset($_SESSION['user_id']);
}

/**
 * Verifica se o usuário é veterinário
 * @return bool
 */
function isVeterinario(): bool {
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'veterinario';
}

/**
 * Redireciona usuários não logados
 */
function requireLogin(): void {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit();
    }
}

/**
 * Redireciona usuários não veterinários
 */
function requireVeterinario(): void {
    if (!isVeterinario()) {
        header('Location: index.php');
        exit();
    }
}

?>