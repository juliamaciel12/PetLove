<?php
    include dirname(__DIR__) . '/config/config.php';

    $error = '';
    $success = '';

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $email = trim($_POST['email']);
        $senha = $_POST['senha'];

        if (empty($email) || empty($senha)) {
            $error = 'Por favor, preencha todos os campos.';
        } else {
            try {
                $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = ?");
                $stmt->execute([$email]);
                $usuario = $stmt->fetch();

                if ($usuario && password_verify($senha, $usuario['senha'])) {
                    $_SESSION['user_id'] = $usuario['id'];
                    $_SESSION['user_name'] = $usuario['nome'];
                    $_SESSION['user_type'] = $usuario['tipo'];

                    if ($usuario['tipo'] === 'veterinario') {
                        header('Location: area_veterinario.php');
                    } else {
                        header('Location: ../index.php');
                    }
                    exit;
                } else {
                    $error = 'Email ou senha incorretos.';
                }
            } catch (PDOException $e) {
                $error = 'Erro no sistema. Tente novamente.';
            }
        }
    }

    // Verificar se já está logado
    if (isLoggedIn()) {
        header('Location: ../index.php');
        exit;
    }
    ?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Clínica Veterinária PetLove</title>
    <link rel="stylesheet" href="../View/css/style.css">
   
</head>
<body>
    <div class="login-container">
        <div class="logo">
            <h1>🐾 PetLove</h1>
            <p>Faça login em sua conta</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" required value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>">
            </div>

            <div class="form-group">
                <label for="senha">Senha:</label>
                <input type="password" id="senha" name="senha" required>
            </div>

            <button type="submit" class="btn">Entrar</button>
        </form>

        <div class="divider">
            <span>ou</span>
        </div>

        <div class="register-link">
            <p>Não tem uma conta? <a href="../Model/cadastro.php">Cadastre-se aqui</a></p>
        </div>

        <div class="back-link">
            <a href="../index.php">← Voltar ao início</a>
        </div>

        <!-- Acesso de Teste
             Veterinário: veterinario@clinica.com / 123456 -->

    </div>
</body>
</html>