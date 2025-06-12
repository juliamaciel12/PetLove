// Script para página de agendamento
document.addEventListener('DOMContentLoaded', function() {
    // Verificar horários disponíveis quando data ou veterinário mudarem
    const dataAgendamento = document.getElementById('data_agendamento');
    const veterinarioId = document.getElementById('veterinario_id');
    const agendamentoForm = document.getElementById('agendamentoForm');

    if (dataAgendamento) {
        dataAgendamento.addEventListener('change', verificarHorarios);
    }

    if (veterinarioId) {
        veterinarioId.addEventListener('change', verificarHorarios);
    }

    // Validação do formulário
    if (agendamentoForm) {
        agendamentoForm.addEventListener('submit', function(e) {
            const pet = document.getElementById('pet_id').value;
            const servico = document.getElementById('servico_id').value;
            const data = document.getElementById('data_agendamento').value;
            const hora = document.getElementById('hora_agendamento').value;

            if (!pet || !servico || !data || !hora) {
                e.preventDefault();
                alert('Por favor, preencha todos os campos obrigatórios.');
                return;
            }

            // Confirmar agendamento
            if (!confirm('Confirma o agendamento com os dados informados?')) {
                e.preventDefault();
            }
        });
    }

    // Inicializar funcionalidades da área do veterinário
    initAreaVeterinario();
    
    // Inicializar funcionalidades de meus agendamentos
    initMeusAgendamentos();
});

function verificarHorarios() {
    const data = document.getElementById('data_agendamento').value;
    const veterinario = document.getElementById('veterinario_id').value;
    const horaSelect = document.getElementById('hora_agendamento');

    if (!data) return;

    // Aqui você pode implementar uma verificação AJAX para horários ocupados
    // Por enquanto, todos os horários estão disponíveis
    
    // Exemplo de como você poderia implementar a verificação AJAX:
    /*
    fetch('verificar_horarios.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            data: data,
            veterinario_id: veterinario
        })
    })
    .then(response => response.json())
    .then(data => {
        // Atualizar os horários disponíveis baseado na resposta
        atualizarHorariosDisponiveis(data.horariosOcupados);
    })
    .catch(error => {
        console.error('Erro ao verificar horários:', error);
    });
    */
}

// Função auxiliar para atualizar horários disponíveis (para futura implementação)
function atualizarHorariosDisponiveis(horariosOcupados) {
    const horaSelect = document.getElementById('hora_agendamento');
    const opcoes = horaSelect.querySelectorAll('option');
    
    opcoes.forEach(opcao => {
        if (opcao.value && horariosOcupados.includes(opcao.value)) {
            opcao.disabled = true;
            opcao.textContent = opcao.textContent + ' (Ocupado)';
        } else {
            opcao.disabled = false;
            opcao.textContent = opcao.textContent.replace(' (Ocupado)', '');
        }
    });
}

// ========================================
// SCRIPTS PARA PÁGINA MEUS AGENDAMENTOS
// ========================================

function initMeusAgendamentos() {
    // Sistema de tabs
    const tabs = document.querySelectorAll('.tab');
    const tabPanels = document.querySelectorAll('.tab-panel');
    
    if (tabs.length > 0) {
        tabs.forEach(tab => {
            tab.addEventListener('click', function() {
                // Remove active de todas as tabs
                tabs.forEach(t => t.classList.remove('active'));
                tabPanels.forEach(p => p.classList.remove('active'));
                
                // Ativa a tab clicada
                this.classList.add('active');
                const targetPanel = this.getAttribute('data-tab');
                const targetElement = document.getElementById(targetPanel);
                if (targetElement) {
                    targetElement.classList.add('active');
                }
            });
        });
    }

    // Auto-refresh da página a cada 5 minutos para atualizar status
    // (apenas se estivermos na página de agendamentos)
    if (document.querySelector('.agendamento-card')) {
        setTimeout(function() {
            location.reload();
        }, 300000); // 5 minutos
    }
}

// Confirmação de cancelamento
function confirmarCancelamento() {
    return confirm('⚠️ Tem certeza que deseja cancelar este agendamento?\n\nEsta ação não pode ser desfeita.');
}

// ========================================
// SCRIPTS PARA ÁREA DO VETERINÁRIO
// ========================================

function initAreaVeterinario() {
    // Auto-refresh da página a cada 5 minutos para manter os dados atualizados
    // (apenas se estivermos na área do veterinário)
    if (document.querySelector('.stats-grid') || document.querySelector('.dashboard-grid')) {
        setTimeout(function() {
            location.reload();
        }, 300000); // 5 minutos
    }

    // Fechar modal clicando fora dele
    const modalCancelar = document.getElementById('modalCancelar');
    const modalConcluir = document.getElementById('modalConcluir');
    
    if (modalCancelar || modalConcluir) {
        window.onclick = function(event) {
            if (event.target === modalCancelar) {
                modalCancelar.style.display = 'none';
            }
            if (event.target === modalConcluir) {
                modalConcluir.style.display = 'none';
            }
        }
    }
}

// Função para abrir modais da área do veterinário
function openModal(tipo, agendamentoId) {
    if (tipo === 'cancelar') {
        const cancelarIdInput = document.getElementById('cancelar_agendamento_id');
        const modalCancelar = document.getElementById('modalCancelar');
        
        if (cancelarIdInput && modalCancelar) {
            cancelarIdInput.value = agendamentoId;
            modalCancelar.style.display = 'block';
        }
    } else if (tipo === 'concluir') {
        const concluirIdInput = document.getElementById('concluir_agendamento_id');
        const modalConcluir = document.getElementById('modalConcluir');
        
        if (concluirIdInput && modalConcluir) {
            concluirIdInput.value = agendamentoId;
            modalConcluir.style.display = 'block';
        }
    }
}

// Função para fechar modais da área do veterinário
function closeModal() {
    const modalCancelar = document.getElementById('modalCancelar');
    const modalConcluir = document.getElementById('modalConcluir');
    
    if (modalCancelar) {
        modalCancelar.style.display = 'none';
    }
    if (modalConcluir) {
        modalConcluir.style.display = 'none';
    }
}