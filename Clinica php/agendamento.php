<?php
include 'config.php';


// Verificar se o usuário está logado
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$mensagem = '';
$erro = '';

// Processar o formulário de agendamento
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $pet_id = $_POST['pet_id'];
    $servico_id = $_POST['servico_id'];
    $veterinario_id = $_POST['veterinario_id'] ?: null;
    $data_agendamento = $_POST['data_agendamento'];
    $hora_agendamento = $_POST['hora_agendamento'];
    $observacoes = $_POST['observacoes'];
    
    // Validações
    if (empty($pet_id) || empty($servico_id) || empty($data_agendamento) || empty($hora_agendamento)) {
        $erro = 'Por favor, preencha todos os campos obrigatórios.';
    } else {
        // Verificar se a data não é no passado
        $data_atual = date('Y-m-d');
        if ($data_agendamento < $data_atual) {
            $erro = 'Não é possível agendar para datas passadas.';
        } else {
            // Verificar se o horário está disponível
            $stmt = $pdo->prepare("
                SELECT COUNT(*) FROM agendamentos 
                WHERE data_agendamento = ? AND hora_agendamento = ? 
                AND veterinario_id = ? AND status != 'cancelado'
            ");
            $stmt->execute([$data_agendamento, $hora_agendamento, $veterinario_id]);
            
            if ($stmt->fetchColumn() > 0) {
                $erro = 'Este horário já está ocupado. Por favor, escolha outro horário.';
            } else {
                // Inserir o agendamento
                $stmt = $pdo->prepare("
                    INSERT INTO agendamentos (cliente_id, pet_id, veterinario_id, servico_id, data_agendamento, hora_agendamento, observacoes) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                
                if ($stmt->execute([$user_id, $pet_id, $veterinario_id, $servico_id, $data_agendamento, $hora_agendamento, $observacoes])) {
                    $mensagem = 'Agendamento realizado com sucesso! Você receberá uma confirmação em breve.';
                } else {
                    $erro = 'Erro ao realizar o agendamento. Tente novamente.';
                }
            }
        }
    }
}

// Buscar pets do usuário
$stmt = $pdo->prepare("SELECT * FROM pets WHERE cliente_id = ? ORDER BY nome");
$stmt->execute([$user_id]);
$pets = $stmt->fetchAll();

// Buscar serviços
$stmt = $pdo->query("SELECT * FROM servicos ORDER BY nome");
$servicos = $stmt->fetchAll();

// Buscar veterinários
$stmt = $pdo->query("SELECT * FROM veterinarios ORDER BY nome");
$veterinarios = $stmt->fetchAll();

// Pegar serviço pré-selecionado se vier da URL
$servico_selecionado = $_GET['servico_id'] ?? '';
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agendamento - Clínica Veterinária PetLove</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <header>
        <nav class="container">
            <div class="logo">
                <h1>🐾 PetLove</h1>
            </div>
            <ul class="nav-links">
                <li><a href="index.php">Início</a></li>
                <li><a href="servicos.php">Serviços</a></li>
                <li><a href="agendamento.php">Agendar</a></li>
                <li><a href="meus_agendamentos.php">Meus Agendamentos</a></li>
                <?php if (isVeterinario()): ?>
                    <li><a href="area_veterinario.php">Área Veterinário</a></li>
                <?php endif; ?>
                <li><a href="logout.php">Sair</a></li>
            </ul>
        </nav>
    </header>

    <section class="page-header">
        <div class="container">
            <h1>Agendar Consulta</h1>
            <p>Escolha o melhor horário para cuidar do seu pet</p>
        </div>
    </section>

    <section class="main-content">
        <div class="container">
            <div class="form-container">
                <?php if ($mensagem): ?>
                    <div class="alert success"><?= htmlspecialchars($mensagem) ?></div>
                <?php endif; ?>

                <?php if ($erro): ?>
                    <div class="alert error"><?= htmlspecialchars($erro) ?></div>
                <?php endif; ?>

                <?php if (empty($pets)): ?>
                    <div class="no-pets-warning">
                        <h3>⚠️ Nenhum pet cadastrado</h3>
                        <p>Você precisa cadastrar pelo menos um pet antes de agendar uma consulta.</p>
                        <a href="cadastrar_pet.php" class="btn" style="margin-top: 1rem; display: inline-block; width: auto; padding: 0.5rem 1rem;">Cadastrar Pet</a>
                    </div>
                <?php else: ?>
                    <div class="info-card">
                        <h3>📋 Informações Importantes</h3>
                        <ul>
                            <li>Chegue com 15 minutos de antecedência</li>
                            <li>Traga a carteirinha de vacinação do seu pet</li>
                            <li>Para cancelamentos, entre em contato com até 2 horas de antecedência</li>
                            <li>Em casos de emergência, ligue diretamente: (11) 99999-9999</li>
                        </ul>
                    </div>

                    <form method="POST" id="agendamentoForm">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="pet_id">Selecione o Pet <span class="required">*</span></label>
                                <select name="pet_id" id="pet_id" class="form-control" required>
                                    <option value="">Escolha um pet</option>
                                    <?php foreach ($pets as $pet): ?>
                                        <option value="<?= $pet['id'] ?>">
                                            <?= htmlspecialchars($pet['nome']) ?> - <?= htmlspecialchars($pet['especie']) ?> (<?= htmlspecialchars($pet['raca']) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="servico_id">Tipo de Serviço <span class="required">*</span></label>
                                <select name="servico_id" id="servico_id" class="form-control" required>
                                    <option value="">Escolha um serviço</option>
                                    <?php foreach ($servicos as $servico): ?>
                                        <option value="<?= $servico['id'] ?>" <?= $servico_selecionado == $servico['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($servico['nome']) ?> - R$ <?= number_format($servico['preco'], 2, ',', '.') ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="veterinario_id">Veterinário (Opcional)</label>
                            <select name="veterinario_id" id="veterinario_id" class="form-control">
                                <option value="">Sem preferência</option>
                                <?php foreach ($veterinarios as $veterinario): ?>
                                    <option value="<?= $veterinario['id'] ?>">
                                        <?= htmlspecialchars($veterinario['nome']) ?> - <?= htmlspecialchars($veterinario['especialidade']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="data_agendamento">Data <span class="required">*</span></label>
                                <input type="date" name="data_agendamento" id="data_agendamento" class="form-control" 
                                       min="<?= date('Y-m-d') ?>" required>
                            </div>

                            <div class="form-group">
                                <label for="hora_agendamento">Horário <span class="required">*</span></label>
                                <select name="hora_agendamento" id="hora_agendamento" class="form-control" required>
                                    <option value="">Escolha um horário</option>
                                    <option value="08:00">08:00</option>
                                    <option value="08:30">08:30</option>
                                    <option value="09:00">09:00</option>
                                    <option value="09:30">09:30</option>
                                    <option value="10:00">10:00</option>
                                    <option value="10:30">10:30</option>
                                    <option value="11:00">11:00</option>
                                    <option value="11:30">11:30</option>
                                    <option value="14:00">14:00</option>
                                    <option value="14:30">14:30</option>
                                    <option value="15:00">15:00</option>
                                    <option value="15:30">15:30</option>
                                    <option value="16:00">16:00</option>
                                    <option value="16:30">16:30</option>
                                    <option value="17:00">17:00</option>
                                    <option value="17:30">17:30</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="observacoes">Observações</label>
                            <textarea name="observacoes" id="observacoes" class="form-control" rows="4" 
                                      placeholder="Descreva sintomas, comportamentos ou outras informações importantes sobre seu pet..."></textarea>
                        </div>

                        <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
                            <button type="button" class="btn btn-secondary" onclick="history.back()">Voltar</button>
                            <button type="submit" class="btn" style="flex-grow: 1;">Confirmar Agendamento</button>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </section>
    <script src="js/script.js"></script>
</body>
</html>