<?php
session_start();
$logado = isset($_SESSION['cliente_logado']);
$nome_cliente = $logado ? $_SESSION['cliente_nome'] : '';
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Sistema de Funcionários</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f4f4;
            margin: 0;
        }
        header {
            background: #007bff;
            color: white;
            padding: 15px;
            text-align: right;
        }
        iframe {
            border: none;
            width: 100%;
            height: calc(100vh - 60px); /* ajusta a altura com base no header */
        }
    </style>
</head>
<body>
    <header>
        <?php if ($logado): ?>
            Bem-vindo, <?php echo htmlspecialchars($nome_cliente); ?> |
            <a href="login.php?logout=1" style="color:white;">Sair</a>
        <?php else: ?>
            <a href="login.php" style="color:white;">Login</a>
        <?php endif; ?>
    </header>

    <iframe src="index.html"></iframe>
</body>
</html>
