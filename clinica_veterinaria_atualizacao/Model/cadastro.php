<?php
    include dirname(__DIR__) . '/config/config.php';

    $error = '';
    $success = '';

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        // Dados do cliente
        $nome = trim($_POST['nome']);
        $email = trim($_POST['email']);
        $senha = $_POST['senha'];
        $telefone = trim($_POST['telefone']);
        $endereco = trim($_POST['endereco']);
        
        // Dados do pet
        $pet_nome = trim($_POST['pet_nome']);
        $pet_especie = trim($_POST['pet_especie']);
        $pet_raca = trim($_POST['pet_raca']);
        $pet_idade = (int)$_POST['pet_idade'];
        $pet_peso = (float)$_POST['pet_peso'];
        $pet_observacoes = trim($_POST['pet_observacoes']);

        // Validação
        if (empty($nome) || empty($email) || empty($senha) || empty($pet_nome) || empty($pet_especie)) {
            $error = 'Por favor, preencha todos os campos obrigatórios.';
        } elseif (strlen($senha) < 6) {
            $error = 'A senha deve ter pelo menos 6 caracteres.';
        } else {
            try {
                // Verificar se email já existe
                $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
                $stmt->execute([$email]);
                if ($stmt->fetch()) {
                    $error = 'Este email já está cadastrado.';
                } else {
                    // Iniciar transação
                    $pdo->beginTransaction();

                    // Inserir cliente
                    $stmt = $pdo->prepare("INSERT INTO usuarios (nome, email, senha, telefone, endereco, tipo) VALUES (?, ?, ?, ?, ?, 'cliente')");
                    $stmt->execute([
                        $nome,
                        $email,
                        password_hash($senha, PASSWORD_DEFAULT),
                        $telefone,
                        $endereco
                    ]);

                    $cliente_id = $pdo->lastInsertId();

                    // Inserir pet
                    $stmt = $pdo->prepare("INSERT INTO pets (nome, especie, raca, idade, peso, observacoes, cliente_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([
                        $pet_nome,
                        $pet_especie,
                        $pet_raca,
                        $pet_idade ?: null,
                        $pet_peso ?: null,
                        $pet_observacoes,
                        $cliente_id
                    ]);

                    // Confirmar transação
                    $pdo->commit();

                    $success = 'Cadastro realizado com sucesso! Você pode fazer login agora.';
                    
                    // Limpar campos
                    $_POST = array();
                }
            } catch (PDOException $e) {
                $pdo->rollBack();
                $error = 'Erro no sistema. Tente novamente.';
            }
        }
    }
    ?>


<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro - Clínica Veterinária PetLove</title>
    <link rel="stylesheet" href="../View/css/style.css">
</head>
<body>
    <div class="container">
        <div class="cadastro-container">
            <div class="logo">
                <h1>🐾 PetLove</h1>
                <p>Cadastre-se e cuide do seu pet conosco</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>

            <form method="POST">
                <!-- Dados do Cliente -->
                <div class="form-section">
                    <h2 class="section-title cliente">Dados do Tutor</h2>
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="nome">Nome Completo <span class="required">*</span>:</label>
                            <input type="text" id="nome" name="nome" required value="<?= isset($_POST['nome']) ? htmlspecialchars($_POST['nome']) : '' ?>">
                        </div>

                        <div class="form-group">
                            <label for="email">Email <span class="required">*</span>:</label>
                            <input type="email" id="email" name="email" required value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>">
                        </div>

                        <div class="form-group">
                            <label for="senha">Senha <span class="required">*</span>:</label>
                            <input type="password" id="senha" name="senha" required minlength="6">
                        </div>

                        <div class="form-group">
                            <label for="telefone">Telefone:</label>
                            <input type="tel" id="telefone" name="telefone" placeholder="(11) 99999-9999" value="<?= isset($_POST['telefone']) ? htmlspecialchars($_POST['telefone']) : '' ?>">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="endereco">Endereço:</label>
                        <textarea id="endereco" name="endereco" placeholder="Rua, número, bairro, cidade..."><?= isset($_POST['endereco']) ? htmlspecialchars($_POST['endereco']) : '' ?></textarea>
                    </div>
                </div>

                <!-- Dados do Pet -->
                <div class="form-section">
                    <h2 class="section-title pet">Dados do Pet</h2>
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="pet_nome">Nome do Pet <span class="required">*</span>:</label>
                            <input type="text" id="pet_nome" name="pet_nome" required value="<?= isset($_POST['pet_nome']) ? htmlspecialchars($_POST['pet_nome']) : '' ?>">
                        </div>

                        <div class="form-group">
                            <label for="pet_especie">Espécie <span class="required">*</span>:</label>
                            <select id="pet_especie" name="pet_especie" required>
                                <option value="">Selecione...</option>
                                <option value="Cão" <?= (isset($_POST['pet_especie']) && $_POST['pet_especie'] == 'Cão') ? 'selected' : '' ?>>Cão</option>
                                <option value="Gato" <?= (isset($_POST['pet_especie']) && $_POST['pet_especie'] == 'Gato') ? 'selected' : '' ?>>Gato</option>
                                <option value="Pássaro" <?= (isset($_POST['pet_especie']) && $_POST['pet_especie'] == 'Pássaro') ? 'selected' : '' ?>>Pássaro</option>
                                <option value="Roedor" <?= (isset($_POST['pet_especie']) && $_POST['pet_especie'] == 'Roedor') ? 'selected' : '' ?>>Roedor</option>
                                <option value="Outro" <?= (isset($_POST['pet_especie']) && $_POST['pet_especie'] == 'Outro') ? 'selected' : '' ?>>Outro</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="pet_raca">Raça:</label>
                            <input type="text" id="pet_raca" name="pet_raca" placeholder="Ex: Labrador, Persa, SRD..." value="<?= isset($_POST['pet_raca']) ? htmlspecialchars($_POST['pet_raca']) : '' ?>">
                        </div>

                        <div class="form-group">
                            <label for="pet_idade">Idade (anos):</label>
                            <input type="number" id="pet_idade" name="pet_idade" min="0" max="30" step="1" value="<?= isset($_POST['pet_idade']) ? $_POST['pet_idade'] : '' ?>">
                        </div>

                        <div class="form-group">
                            <label for="pet_peso">Peso (kg):</label>
                            <input type="number" id="pet_peso" name="pet_peso" min="0" max="200" step="0.1" value="<?= isset($_POST['pet_peso']) ? $_POST['pet_peso'] : '' ?>">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="pet_observacoes">Observações sobre o Pet:</label>
                        <textarea id="pet_observacoes" name="pet_observacoes" placeholder="Informações adicionais, alergias, comportamento..."><?= isset($_POST['pet_observacoes']) ? htmlspecialchars($_POST['pet_observacoes']) : '' ?></textarea>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn">Cadastrar</button>
                    <a href="../index.php" class="btn btn-secondary">Cancelar</a>
                </div>
            </form>

            <div class="login-link">
                <p>Já tem uma conta? <a href="../Controller/login.php">Faça login aqui</a></p>
            </div>
        </div>
    </div>
</body>
</html>