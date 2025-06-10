<?php
include dirname(__DIR__) . '/config/config.php';

// Verificar se o usuário está logado e é veterinário
if (!isLoggedIn() || !isVeterinario()) {
    header('Location: ../Controller/login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$mensagem = '';
$erro = '';

// Buscar informações do veterinário
$stmt = $pdo->prepare("
    SELECT v.*, u.nome as usuario_nome 
    FROM veterinarios v 
    JOIN usuarios u ON v.usuario_id = u.id 
    WHERE v.usuario_id = ?
");
$stmt->execute([$user_id]);
$veterinario = $stmt->fetch();

// Processar ações
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $acao = $_POST['acao'];
    
    if ($acao == 'confirmar_agendamento') {
        $agendamento_id = $_POST['agendamento_id'];
        $stmt = $pdo->prepare("UPDATE agendamentos SET status = 'confirmado' WHERE id = ?");
        if ($stmt->execute([$agendamento_id])) {
            $mensagem = 'Agendamento confirmado com sucesso!';
        } else {
            $erro = 'Erro ao confirmar agendamento.';
        }
    }
    
    if ($acao == 'cancelar_agendamento') {
        $agendamento_id = $_POST['agendamento_id'];
        $motivo = $_POST['motivo_cancelamento'];
        $stmt = $pdo->prepare("UPDATE agendamentos SET status = 'cancelado', observacoes = CONCAT(observacoes, '\nCancelado pelo veterinário: ', ?) WHERE id = ?");
        if ($stmt->execute([$motivo, $agendamento_id])) {
            $mensagem = 'Agendamento cancelado com sucesso!';
        } else {
            $erro = 'Erro ao cancelar agendamento.';
        }
    }
    
    if ($acao == 'concluir_agendamento') {
        $agendamento_id = $_POST['agendamento_id'];
        $observacoes_consulta = $_POST['observacoes_consulta'];
        $stmt = $pdo->prepare("UPDATE agendamentos SET status = 'concluido', observacoes = CONCAT(observacoes, '\nConsulta realizada: ', ?) WHERE id = ?");
        if ($stmt->execute([$observacoes_consulta, $agendamento_id])) {
            $mensagem = 'Consulta marcada como concluída!';
        } else {
            $erro = 'Erro ao finalizar consulta.';
        }
    }
}

// Buscar agendamentos do veterinário para hoje
$hoje = date('Y-m-d');
$stmt = $pdo->prepare("
    SELECT a.*, 
           u.nome as cliente_nome, u.telefone as cliente_telefone,
           p.nome as pet_nome, p.especie, p.raca, p.idade, p.peso,
           s.nome as servico_nome, s.preco, s.duracao_minutos
    FROM agendamentos a
    JOIN usuarios u ON a.cliente_id = u.id
    JOIN pets p ON a.pet_id = p.id
    JOIN servicos s ON a.servico_id = s.id
    WHERE a.veterinario_id = ? AND a.data_agendamento = ?
    ORDER BY a.hora_agendamento
");
$stmt->execute([$veterinario['id'], $hoje]);
$agendamentos_hoje = $stmt->fetchAll();

// Buscar próximos agendamentos (próximos 7 dias)
$stmt = $pdo->prepare("
    SELECT a.*, 
           u.nome as cliente_nome, u.telefone as cliente_telefone,
           p.nome as pet_nome, p.especie, p.raca,
           s.nome as servico_nome
    FROM agendamentos a
    JOIN usuarios u ON a.cliente_id = u.id
    JOIN pets p ON a.pet_id = p.id
    JOIN servicos s ON a.servico_id = s.id
    WHERE a.veterinario_id = ? AND a.data_agendamento > ? AND a.data_agendamento <= DATE_ADD(?, INTERVAL 7 DAY)
    AND a.status IN ('pendente', 'confirmado')
    ORDER BY a.data_agendamento, a.hora_agendamento
");
$stmt->execute([$veterinario['id'], $hoje, $hoje]);
$proximos_agendamentos = $stmt->fetchAll();

// Estatísticas
$stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total_consultas,
        SUM(CASE WHEN status = 'concluido' THEN 1 ELSE 0 END) as consultas_concluidas,
        SUM(CASE WHEN status = 'pendente' THEN 1 ELSE 0 END) as consultas_pendentes,
        SUM(CASE WHEN status = 'confirmado' THEN 1 ELSE 0 END) as consultas_confirmadas
    FROM agendamentos 
    WHERE veterinario_id = ? AND MONTH(data_agendamento) = MONTH(CURRENT_DATE) AND YEAR(data_agendamento) = YEAR(CURRENT_DATE)
");
$stmt->execute([$veterinario['id']]);
$estatisticas = $stmt->fetch();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Área do Veterinário - Clínica Veterinária PetLove</title>
    <link rel="stylesheet" href="../View/css/style.css">
</head>
<body>
    <header>
        <nav class="container">
            <div class="logo">
                <h1>🐾 PetLove</h1>
            </div>
            <ul class="nav-links">
                <li><a href="../index.php">Início</a></li>
                <li><a href="../Controller/servicos.php">Serviços</a></li>
                <li><a href="../Model/agendamento.php">Agendar</a></li>
                <li><a href="../Model/meus_agendamentos.php">Meus Agendamentos</a></li>
                <li><a href="area_veterinario.php">Área Veterinário</a></li>
                <li><a href="../Controller/logout.php">Sair</a></li>
            </ul>
        </nav>
    </header>

    <section class="page-header">
        <div class="container">
            <h1>Área do Veterinário</h1>
            <p>Bem-vindo, Dr. <?= htmlspecialchars($veterinario['nome']) ?>!</p>
            <small>CRMV: <?= htmlspecialchars($veterinario['crmv']) ?> | Especialidade: <?= htmlspecialchars($veterinario['especialidade']) ?></small>
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

            <!-- Estatísticas -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number"><?= $estatisticas['total_consultas'] ?></div>
                    <div class="stat-label">Total Consultas (Mês)</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?= $estatisticas['consultas_pendentes'] ?></div>
                    <div class="stat-label">Pendentes</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?= $estatisticas['consultas_confirmadas'] ?></div>
                    <div class="stat-label">Confirmadas</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?= $estatisticas['consultas_concluidas'] ?></div>
                    <div class="stat-label">Concluídas</div>
                </div>
            </div>

            <div class="dashboard-grid">
                <!-- Agendamentos de Hoje -->
                <div class="card">
                    <h3>Agendamentos de Hoje (<?= date('d/m/Y') ?>)</h3>
                    
                    <?php if (empty($agendamentos_hoje)): ?>
                        <div class="empty-state">
                            <div style="font-size: 3rem; margin-bottom: 1rem;">🎉</div>
                            <p>Nenhum agendamento para hoje!</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($agendamentos_hoje as $agendamento): ?>
                            <div class="agenda-item">
                                <div class="agenda-header">
                                    <span class="agenda-time"><?= date('H:i', strtotime($agendamento['hora_agendamento'])) ?></span>
                                    <span class="status-badge status-<?= $agendamento['status'] ?>">
                                        <?= ucfirst($agendamento['status']) ?>
                                    </span>
                                </div>
                                
                                <h4><?= htmlspecialchars($agendamento['cliente_nome']) ?> - <?= htmlspecialchars($agendamento['pet_nome']) ?></h4>
                                <p><strong>Serviço:</strong> <?= htmlspecialchars($agendamento['servico_nome']) ?> (<?= $agendamento['duracao_minutos'] ?> min)</p>
                                <p><strong>Telefone:</strong> <?= htmlspecialchars($agendamento['cliente_telefone']) ?></p>
                                
                                <div class="pet-info">
                                    <div class="pet-detail">
                                        <strong>Espécie</strong>
                                        <?= htmlspecialchars($agendamento['especie']) ?>
                                    </div>
                                    <div class="pet-detail">
                                        <strong>Raça</strong>
                                        <?= htmlspecialchars($agendamento['raca']) ?>
                                    </div>
                                    <div class="pet-detail">
                                        <strong>Idade</strong>
                                        <?= $agendamento['idade'] ? $agendamento['idade'] . ' anos' : 'N/I' ?>
                                    </div>
                                    <div class="pet-detail">
                                        <strong>Peso</strong>
                                        <?= $agendamento['peso'] ? $agendamento['peso'] . ' kg' : 'N/I' ?>
                                    </div>
                                </div>
                                
                                <?php if ($agendamento['observacoes']): ?>
                                    <p><strong>Observações:</strong> <?= nl2br(htmlspecialchars($agendamento['observacoes'])) ?></p>
                                <?php endif; ?>
                                
                                <div style="margin-top: 1rem;">
                                    <?php if ($agendamento['status'] == 'pendente'): ?>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="acao" value="confirmar_agendamento">
                                            <input type="hidden" name="agendamento_id" value="<?= $agendamento['id'] ?>">
                                            <button type="submit" class="btn btn-sm">Confirmar</button>
                                        </form>
                                    <?php endif; ?>
                                    
                                    <?php if (in_array($agendamento['status'], ['pendente', 'confirmado'])): ?>
                                        <button class="btn btn-sm btn-warning" onclick="openModal('concluir', <?= $agendamento['id'] ?>)">Concluir</button>
                                        <button class="btn btn-sm btn-danger" onclick="openModal('cancelar', <?= $agendamento['id'] ?>)">Cancelar</button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- Próximos Agendamentos -->
                <div class="card">
                    <h3>Próximos Agendamentos</h3>
                    
                    <?php if (empty($proximos_agendamentos)): ?>
                        <div class="empty-state">
                            <div style="font-size: 3rem; margin-bottom: 1rem;"></div>
                            <p>Nenhum agendamento nos próximos dias.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($proximos_agendamentos as $agendamento): ?>
                            <div class="agenda-item">
                                <div class="agenda-header">
                                    <span class="agenda-time">
                                        <?= date('d/m', strtotime($agendamento['data_agendamento'])) ?> - 
                                        <?= date('H:i', strtotime($agendamento['hora_agendamento'])) ?>
                                    </span>
                                    <span class="status-badge status-<?= $agendamento['status'] ?>">
                                        <?= ucfirst($agendamento['status']) ?>
                                    </span>
                                </div>
                                
                                <h4><?= htmlspecialchars($agendamento['cliente_nome']) ?> - <?= htmlspecialchars($agendamento['pet_nome']) ?></h4>
                                <p><strong>Serviço:</strong> <?= htmlspecialchars($agendamento['servico_nome']) ?></p>
                                <p><strong>Pet:</strong> <?= htmlspecialchars($agendamento['especie']) ?> - <?= htmlspecialchars($agendamento['raca']) ?></p>
                                <p><strong>Telefone:</strong> <?= htmlspecialchars($agendamento['cliente_telefone']) ?></p>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <!-- Modal para cancelar agendamento -->
    <div id="modalCancelar" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <h3>Cancelar Agendamento</h3>
            <form method="POST">
                <input type="hidden" name="acao" value="cancelar_agendamento">
                <input type="hidden" name="agendamento_id" id="cancelar_agendamento_id">
                
                <div class="form-group">
                    <label for="motivo_cancelamento">Motivo do Cancelamento:</label>
                    <textarea name="motivo_cancelamento" id="motivo_cancelamento" class="form-control" rows="3" required></textarea>
                </div>
                
                <button type="submit" class="btn btn-danger">Confirmar Cancelamento</button>
                <button type="button" class="btn" onclick="closeModal()">Voltar</button>
            </form>
        </div>
    </div>

    <!-- Modal para concluir agendamento -->
    <div id="modalConcluir" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <h3>Concluir Consulta</h3>
            <form method="POST">
                <input type="hidden" name="acao" value="concluir_agendamento">
                <input type="hidden" name="agendamento_id" id="concluir_agendamento_id">
                
                <div class="form-group">
                    <label for="observacoes_consulta">Observações da Consulta:</label>
                    <textarea name="observacoes_consulta" id="observacoes_consulta" class="form-control" rows="4" 
                              placeholder="Descreva os procedimentos realizados, diagnóstico, medicações prescritas, etc."></textarea>
                </div>
                
                <button type="submit" class="btn">Marcar como Concluída</button>
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Voltar</button>
            </form>
        </div>
    </div>

    <script src="../View/js/script.js"></script>
</body>
</html>