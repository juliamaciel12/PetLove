<?php
session_start();
require 'config.php';

// Logout se ?logout=1
if (isset($_GET['logout'])) {
    session_unset();
    session_destroy();
    header("Location: home.php");
    exit;
}

$erro = "";

// Se o formulário foi enviado, processa o login
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $cpf = $_POST['cpf'];
    $senha = $_POST['senha'];

    $sql = "SELECT * FROM Cadastro_Clientes WHERE Cpf_cliente = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $cpf);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $cliente = $result->fetch_assoc();
        if (password_verify($senha, $cliente['Senha'])) {
            $_SESSION['cliente_logado'] = $cliente['Cpf_cliente'];
            $_SESSION['cliente_nome'] = $cliente['Nome_cliente'];
            header("Location: home.php");
            exit;
        } else {
            $erro = "Senha incorreta.";
        }
    } else {
        $erro = "CPF não encontrado.";
    }
}
?>


<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Login - PetLove</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <header><h1 class="logo">PetLove♡</h1>
        <a href="index.html" class="nav-button">Voltar</a>
    </header>
    <main class="login-box">
        <form action="login.php" method="POST">
        <label>CPF:</label><br>
        <input type="text" name="cpf" maxlength="11" required><br>

        <label>Senha:</label><br>
        <input type="password" name="senha" id="senha" required>
        <label>
            <input type="checkbox" id="toggle-senha"> Mostrar senha
        </label><br><br>

        <button type="submit">Entrar</button>
    </form>
    <p><a href="cadastro_cliente.php">Não tem conta? Cadastre-se</a></p>
    </main>
    <script src="js/script.js"></script>
</body>
</html>