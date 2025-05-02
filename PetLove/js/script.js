// Redirecionamento
function redirecionarPara(pagina) {
    window.location.href = pagina;
}

// Adiciona item ao carrinho
function adicionarAoCarrinho(nomeProduto, precoProduto = null) {
    const cards = document.querySelectorAll('.produto-card');
    if (!precoProduto) {
        cards.forEach(card => {
            if (card.querySelector('h3').textContent === nomeProduto) {
                precoProduto = card.querySelector('.produto-preco').textContent.replace('R$ ', '').trim();
            }
        });
    }

    const precoNumerico = parseFloat(precoProduto.replace(',', '.'));
    const precoFormatado = 'R$ ' + precoNumerico.toFixed(2).replace('.', ',');
    let carrinho = JSON.parse(localStorage.getItem('carrinho')) || [];
    const existente = carrinho.find(item => item.nome === nomeProduto);

    if (existente) {
        existente.quantidade++;
    } else {
        carrinho.push({ nome: nomeProduto, preco: precoFormatado, quantidade: 1, imagem: encontrarImagemPorNome(nomeProduto) });
    }

    localStorage.setItem('carrinho', JSON.stringify(carrinho));
    mostrarFeedback(nomeProduto + ' adicionado ao carrinho!');
    atualizarContadorCarrinho();
    if (document.getElementById('tabela-carrinho')) carregarCarrinho();
}

// Carrega carrinho na tabela
function carregarCarrinho() {
    const carrinho = JSON.parse(localStorage.getItem('carrinho')) || [];
    const tabela = document.getElementById('tabela-carrinho');
    while (tabela.rows.length > 1) tabela.deleteRow(1);

    let totalGeral = 0;
    carrinho.forEach((item, i) => {
        const row = tabela.insertRow();
        row.insertCell(0).textContent = item.nome;
        row.insertCell(1).textContent = item.preco;

        const cellQtd = row.insertCell(2);
        const divQtd = document.createElement('div');
        divQtd.innerHTML = `
            <button class="btn-menos">-</button>
            <span>${item.quantidade}</span>
            <button class="btn-mais">+</button>`;
        divQtd.style.display = 'flex';
        divQtd.style.alignItems = 'center';
        divQtd.style.justifyContent = 'center';
        cellQtd.appendChild(divQtd);

        const precoNum = parseFloat(item.preco.replace('R$ ', '').replace(',', '.'));
        const totalItem = precoNum * item.quantidade;
        totalGeral += totalItem;
        row.insertCell(3).textContent = 'R$ ' + totalItem.toFixed(2).replace('.', ',');

        divQtd.querySelector('.btn-menos').onclick = () => {
            if (item.quantidade > 1) {
                item.quantidade--;
                localStorage.setItem('carrinho', JSON.stringify(carrinho));
                carregarCarrinho();
            }
        };
        divQtd.querySelector('.btn-mais').onclick = () => {
            item.quantidade++;
            localStorage.setItem('carrinho', JSON.stringify(carrinho));
            carregarCarrinho();
        };
    });

    if (carrinho.length) {
        const rowTotal = tabela.insertRow();
        rowTotal.insertCell(0).colSpan = 3;
        rowTotal.insertCell(0).textContent = 'Total Geral';
        rowTotal.insertCell(1).textContent = 'R$ ' + totalGeral.toFixed(2).replace('.', ',');
    }
}

// Botões principais
function configurarEventos() {
    document.querySelectorAll('.produto-card button').forEach(botao => {
        botao.onclick = () => {
            const card = botao.closest('.produto-card');
            const nome = card.querySelector('h3').textContent;
            adicionarAoCarrinho(nome);
        };
    });

    const paginas = ['cachorro.html', 'gato.html', 'produtos.html'];
    document.querySelectorAll('.menu button').forEach((botao, i) => {
        if (paginas[i]) botao.onclick = () => redirecionarPara(paginas[i]);
    });

    const menuRight = ['agendamento.html', 'login.html'];
    document.querySelectorAll('.menu-right button').forEach((botao, i) => {
        if (menuRight[i]) botao.onclick = () => redirecionarPara(menuRight[i]);
    });

    const botaoCarrinho = document.querySelector('.cart-icon');
    if (botaoCarrinho) botaoCarrinho.onclick = () => redirecionarPara('carrinho.html');
}

// Feedback visual
function mostrarFeedback(msg) {
    const div = document.createElement('div');
    div.className = 'feedback-carrinho';
    div.textContent = msg;
    document.body.appendChild(div);
    setTimeout(() => div.remove(), 3000);
}

// Atualiza contador
function atualizarContadorCarrinho() {
    const carrinho = JSON.parse(localStorage.getItem('carrinho')) || [];
    const total = carrinho.reduce((soma, item) => soma + item.quantidade, 0);
    document.querySelectorAll('.cart-count').forEach(el => el.textContent = total);
}

// Imagem do produto
function encontrarImagemPorNome(nome) {
    const mapa = {
        'Raçao Premium para Cães': 'racao-cachorro.jpg',
        'Raçao Premium para Gatos': 'racao-gato.jpg',
    };
    return mapa[nome] || 'default-product.jpg';
}

// Validação de formulários
function validarFormularios() {
    const login = document.querySelector('.login-box form');
    if (login) {
        login.onsubmit = e => {
            e.preventDefault();
            const email = login.querySelector('#email').value;
            const senha = login.querySelector('#senha').value;
            alert(email && senha ? 'Login realizado com sucesso! (simulação)' : 'Por favor, preencha todos os campos.');
            if (email && senha) window.location.href = 'index.html';
        };
    }

    const cadastro = document.querySelector('.form-container form');
    if (cadastro) {
        cadastro.onsubmit = e => {
            e.preventDefault();
            const [senha, confirmarSenha] = cadastro.querySelectorAll('input[type="password"]');
            if (senha.value !== confirmarSenha.value) {
                alert('As senhas não coincidem!');
                return;
            }
            alert('Cadastro realizado com sucesso! (simulação)');
            window.location.href = 'index.html';
        };
    }

    const agendamento = document.querySelector('.agendamento-form form');
    if (agendamento) {
        agendamento.onsubmit = e => {
            e.preventDefault();
            const servico = agendamento.querySelector('input[name="servico"]:checked');
            const data = agendamento.querySelector('input[type="date"]').value;
            const hora = agendamento.querySelector('input[type="time"]').value;
            if (!servico || !data || !hora) return alert('Por favor, preencha todos os campos.');
            alert(`Agendamento confirmado!\nServiço: ${servico.value}\nData: ${data}\nHora: ${hora}`);
            window.location.href = 'index.html';
        };
    }
}

// Eventos da página
document.addEventListener('DOMContentLoaded', () => {
    configurarEventos();
    validarFormularios();
    atualizarContadorCarrinho();
    if (document.getElementById('tabela-carrinho')) carregarCarrinho();
    document.querySelector('.btn-finalizar')?.addEventListener('click', () => {
        if (carrinho.length === 0) return alert('Seu carrinho está vazio!');
        if (confirm('Finalizar compra?')) {
            alert('Compra finalizada com sucesso! (simulação)');
            localStorage.removeItem('carrinho');
            carregarCarrinho();
        }
    });
    const btnEsvaziar = document.getElementById('esvaziar-carrinho');
    if (btnEsvaziar) {
        btnEsvaziar.onclick = e => {
            e.preventDefault();
            if (confirm('Tem certeza que deseja esvaziar o carrinho?')) {
                localStorage.removeItem('carrinho');
                carregarCarrinho();
            }
        };
    }
});
