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

// Processar cancelamento de agendamento
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['cancelar_agendamento'])) {
    $agendamento_id = $_POST['agendamento_id'];
    
    // Verificar se o agendamento pertence ao usuário
    $stmt = $pdo->prepare("SELECT * FROM agendamentos WHERE id = ? AND cliente_id = ?");
    $stmt->execute([$agendamento_id, $user_id]);
    $agendamento = $stmt->fetch();
    
    if ($agendamento) {
        // Verificar se pode cancelar (pelo menos 2 horas de antecedência)
        $data_hora_agendamento = $agendamento['data_agendamento'] . ' ' . $agendamento['hora_agendamento'];
        $limite_cancelamento = date('Y-m-d H:i:s', strtotime($data_hora_agendamento . ' -2 hours'));
        
        if (date('Y-m-d H:i:s') > $limite_cancelamento) {
            $erro = 'Não é possível cancelar com menos de 2 horas de antecedência. Entre em contato pelo telefone.';
        } else {
            // Atualizar status para cancelado
            $stmt = $pdo->prepare("UPDATE agendamentos SET status = 'cancelado' WHERE id = ?");
            if ($stmt->execute([$agendamento_id])) {
                $mensagem = 'Agendamento cancelado com sucesso.';
            } else {
                $erro = 'Erro ao cancelar agendamento. Tente novamente.';
            }
        }
    } else {
        $erro = 'Agendamento não encontrado.';
    }
}

// Buscar agendamentos do usuário com informações completas
$stmt = $pdo->prepare("
    SELECT 
        a.*,
        p.nome as pet_nome,
        p.especie as pet_especie,
        p.raca as pet_raca,
        s.nome as servico_nome,
        s.preco as servico_preco,
        s.duracao_minutos,
        v.nome as veterinario_nome,
        v.especialidade as veterinario_especialidade
    FROM agendamentos a
    JOIN pets p ON a.pet_id = p.id
    JOIN servicos s ON a.servico_id = s.id
    LEFT JOIN veterinarios v ON a.veterinario_id = v.id
    WHERE a.cliente_id = ?
    ORDER BY a.data_agendamento DESC, a.hora_agendamento DESC
");
$stmt->execute([$user_id]);
$agendamentos = $stmt->fetchAll();

// Separar agendamentos por status
$agendamentos_futuros = [];
$agendamentos_passados = [];
$agendamentos_cancelados = [];

foreach ($agendamentos as $agendamento) {
    $data_hora = $agendamento['data_agendamento'] . ' ' . $agendamento['hora_agendamento'];
    $is_futuro = strtotime($data_hora) > time();
    
    if ($agendamento['status'] == 'cancelado') {
        $agendamentos_cancelados[] = $agendamento;
    } elseif ($is_futuro && in_array($agendamento['status'], ['pendente', 'confirmado'])) {
        $agendamentos_futuros[] = $agendamento;
    } else {
        $agendamentos_passados[] = $agendamento;
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meus Agendamentos - Clínica Veterinária PetLove</title>
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
            <h1>Meus Agendamentos</h1>
            <p>Gerencie suas consultas e acompanhe o histórico</p>
        </div>
    </section>

    <section class="main-content">
        <div class="container">
            <?php if ($mensagem): ?>
                <div class="alert success"><?= htmlspecialchars($mensagem) ?></div>
            <?php endif; ?>

            <?php if ($erro): ?>
                <div class="alert error"><?= htmlspecialchars($erro) ?></div>
            <?php endif; ?>

            <div class="quick-actions">
                <div class="action-buttons">
                    <a href="agendamento.php" class="btn btn-primary">Nova Consulta</a>
                </div>
            </div>

            <div class="tabs">
                <div class="tab active" data-tab="futuros">
                    Próximos (<?= count($agendamentos_futuros) ?>)
                </div>
                <div class="tab" data-tab="passados">
                    Histórico (<?= count($agendamentos_passados) ?>)
                </div>
                <div class="tab" data-tab="cancelados">
                    Cancelados (<?= count($agendamentos_cancelados) ?>)
                </div>
            </div>

            <div class="tab-content">
                <!-- Agendamentos Futuros -->
                <div class="tab-panel active" id="futuros">
                    <?php if (empty($agendamentos_futuros)): ?>
                        <div class="empty-state">
                            <h3>Nenhum agendamento futuro</h3>
                            <p>Você não possui consultas agendadas no momento.</p>
                            <a href="agendamento.php" class="btn btn-primary">Agendar Nova Consulta</a>
                        </div>
                    <?php else: ?>
                        <?php foreach ($agendamentos_futuros as $agendamento): ?>
                            <div class="agendamento-card <?= $agendamento['status'] ?>">
                                <div class="card-header">
                                    <div class="card-title">
                                        <?= htmlspecialchars($agendamento['servico_nome']) ?>
                                    </div>
                                    <div class="status-badge status-<?= $agendamento['status'] ?>">
                                        <?= ucfirst($agendamento['status']) ?>
                                    </div>
                                </div>

                                <div class="card-info">
                                    <div class="info-item">
                                        <span class="info-icon">🐕</span>
                                        <strong><?= htmlspecialchars($agendamento['pet_nome']) ?></strong>
                                        (<?= htmlspecialchars($agendamento['pet_especie']) ?> - <?= htmlspecialchars($agendamento['pet_raca']) ?>)
                                    </div>
                                    <div class="info-item">
                                        <span class="info-icon"></span>
                                        <?= date('d/m/Y', strtotime($agendamento['data_agendamento'])) ?>
                                    </div>
                                    <div class="info-item">
                                        <span class="info-icon"></span>
                                        <?= date('H:i', strtotime($agendamento['hora_agendamento'])) ?>
                                    </div>
                                    <div class="info-item">
                                        <span class="info-icon"></span>
                                        <?= $agendamento['veterinario_nome'] ? htmlspecialchars($agendamento['veterinario_nome']) : 'A definir' ?>
                                    </div>
                                    <div class="info-item">
                                        <span class="info-icon"></span>
                                        R$ <?= number_format($agendamento['servico_preco'], 2, ',', '.') ?>
                                    </div>
                                    <div class="info-item">
                                        <span class="info-icon"></span>
                                        <?= $agendamento['duracao_minutos'] ?> minutos
                                    </div>
                                </div>

                                <?php if ($agendamento['observacoes']): ?>
                                    <div class="observacoes">
                                        <strong>Observações:</strong><br>
                                        <?= nl2br(htmlspecialchars($agendamento['observacoes'])) ?>
                                    </div>
                                <?php endif; ?>

                                <div class="card-actions">
                                    <?php
                                    $data_hora = $agendamento['data_agendamento'] . ' ' . $agendamento['hora_agendamento'];
                                    $pode_cancelar = strtotime($data_hora . ' -2 hours') > time();
                                    ?>
                                    
                                    <?php if ($pode_cancelar): ?>
                                        <form method="POST" style="display: inline;" onsubmit="return confirm('Tem certeza que deseja cancelar este agendamento?')">
                                            <input type="hidden" name="agendamento_id" value="<?= $agendamento['id'] ?>">
                                            <button type="submit" name="cancelar_agendamento" class="btn btn-danger">
                                                Cancelar
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <span class="btn btn-secondary" style="opacity: 0.6;">
                                            Ligue para cancelar
                                        </span>
                                    <?php endif; ?>
                                    
                                    <a href="reagendar.php?id=<?= $agendamento['id'] ?>" class="btn btn-primary">
                                        Reagendar
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- Histórico -->
                <div class="tab-panel" id="passados">
                    <?php if (empty($agendamentos_passados)): ?>
                        <div class="empty-state">
                            <h3>Nenhum histórico encontrado</h3>
                            <p>Você ainda não possui consultas realizadas.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($agendamentos_passados as $agendamento): ?>
                            <div class="agendamento-card <?= $agendamento['status'] ?>">
                                <div class="card-header">
                                    <div class="card-title">
                                        <?= htmlspecialchars($agendamento['servico_nome']) ?>
                                    </div>
                                    <div class="status-badge status-<?= $agendamento['status'] ?>">
                                        <?= ucfirst($agendamento['status']) ?>
                                    </div>
                                </div>

                                <div class="card-info">
                                    <div class="info-item">
                                        <span class="info-icon">🐕</span>
                                        <strong><?= htmlspecialchars($agendamento['pet_nome']) ?></strong>
                                        (<?= htmlspecialchars($agendamento['pet_especie']) ?> - <?= htmlspecialchars($agendamento['pet_raca']) ?>)
                                    </div>
                                    <div class="info-item">
                                        <span class="info-icon"></span>
                                        <?= date('d/m/Y', strtotime($agendamento['data_agendamento'])) ?>
                                    </div>
                                    <div class="info-item">
                                        <span class="info-icon"></span>
                                        <?= date('H:i', strtotime($agendamento['hora_agendamento'])) ?>
                                    </div>
                                    <div class="info-item">
                                        <span class="info-icon"></span>
                                        <?= $agendamento['veterinario_nome'] ? htmlspecialchars($agendamento['veterinario_nome']) : 'Não informado' ?>
                                    </div>
                                </div>

                                <?php if ($agendamento['observacoes']): ?>
                                    <div class="observacoes">
                                        <strong>Observações:</strong><br>
                                        <?= nl2br(htmlspecialchars($agendamento['observacoes'])) ?>
                                    </div>
                                <?php endif; ?>

                                <div class="card-actions">
                                    <a href="agendamento.php?servico_id=<?= $agendamento['servico_id'] ?>" class="btn btn-primary">
                                        Agendar Novamente
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- Cancelados -->
                <div class="tab-panel" id="cancelados">
                    <?php if (empty($agendamentos_cancelados)): ?>
                        <div class="empty-state">
                            <h3>Nenhum agendamento cancelado</h3>
                            <p>Você não possui consultas canceladas.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($agendamentos_cancelados as $agendamento): ?>
                            <div class="agendamento-card cancelado">
                                <div class="card-header">
                                    <div class="card-title">
                                        <?= htmlspecialchars($agendamento['servico_nome']) ?>
                                    </div>
                                    <div class="status-badge status-cancelado">
                                        Cancelado
                                    </div>
                                </div>

                                <div class="card-info">
                                    <div class="info-item">
                                        <span class="info-icon">🐕</span>
                                        <strong><?= htmlspecialchars($agendamento['pet_nome']) ?></strong>
                                        (<?= htmlspecialchars($agendamento['pet_especie']) ?> - <?= htmlspecialchars($agendamento['pet_raca']) ?>)
                                    </div>
                                    <div class="info-item">
                                        <span class="info-icon"></span>
                                        <?= date('d/m/Y', strtotime($agendamento['data_agendamento'])) ?>
                                    </div>
                                    <div class="info-item">
                                        <span class="info-icon"></span>
                                        <?= date('H:i', strtotime($agendamento['hora_agendamento'])) ?>
                                    </div>
                                </div>

                                <div class="card-actions">
                                    <a href="agendamento.php?servico_id=<?= $agendamento['servico_id'] ?>" class="btn btn-primary">
                                        Reagendar Serviço
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <script src="js/script.js"></script>
</body>
</html>