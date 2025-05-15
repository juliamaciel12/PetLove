<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Cadastro - PetLove</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <header><h1 class="logo">PetLove♡</h1>
        <a href="index.html" class="nav-button">Voltar</a></header>
    <main class="form-container">
        <h2>Criar uma conta</h2>
       <form method="POST">
        <label>CPF:</label><br>
        <input type="text" name="cpf" maxlength="11" required><br>

        <label>Nome:</label><br>
        <input type="text" name="nome" required><br>

        <label>Data de Nascimento:</label><br>
        <input type="date" name="Data_nasc" required><br>

        <label>Email:</label><br>
        <input type="email" name="email" required><br>

        <label>Número:</label><br>
        <input type="text" name="numero" required><br>

        <label for="nome_pet">Nome do Pet:</label><br>
        <input type="text" name="Nome_pet" id="nome_pet" required><br>

        <label for="tipo_pet">Tipo de Pet:</label><br>
        <input type="text" name="Tipo_pet" id="tipo_pet" required><br>

        <label for="raca">Raça:</label><br>
        <input type="text" name="Raca" id="raca" required><br>

        <label for="tamanho">Tamanho:</label><br>
        <input type="text" name="Tamanho" id="tamanho" required><br>

        <label for="data_nasc">Data de Nascimento:</label><br>
        <input type="date" name="Data_nasc" id="data_nasc" required><br>

        <label>Senha:</label><br>
        <input type="password" name="senha" maxlength="8" pattern="[A-Za-z0-9]{1,8}" required><br>
        <small>Máximo 8 caracteres (letras e números)</small><br><br>

        <button type="submit">Cadastrar</button>
    </form>
    <p><a href="login.php">Já tem conta? Faça login</a></p>
    </main>
    <script src="js/script.js"></script>
</body>
</html>


<?php
require 'config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Dados do cliente
    $cpf = $_POST['cpf'];
    $nome = $_POST['nome'];
    $data_nasc_cliente = $_POST['Data_nasc']; // Renomeado para evitar conflito
    $email = $_POST['email'];
    $numero = $_POST['numero'];
    $senha = $_POST['senha'];

    // Dados do pet
    $nome_pet = $_POST['Nome_pet'];
    $tipo_pet = $_POST['Tipo_pet'];
    $raca = $_POST['Raca'];
    $tamanho = $_POST['Tamanho'];
    $data_nasc_pet = $_POST['Data_nasc']; // Renomeado para evitar conflito

    // Validação da senha
    if (!preg_match('/^[A-Za-z0-9]{1,8}$/', $senha)) {
        die("A senha deve conter até 8 caracteres, apenas letras e números.");
    }

    // Verifica se CPF já está cadastrado
    $verifica = $conn->prepare("SELECT * FROM Cadastro_Clientes WHERE Cpf_cliente = ?");
    $verifica->bind_param("s", $cpf);
    $verifica->execute();
    $resultado = $verifica->get_result();

    if ($resultado->num_rows > 0) {
        die("Este CPF já está cadastrado.");
    }

    // Cadastra cliente
    $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
    $sql_cliente = "INSERT INTO Cadastro_Clientes (Cpf_cliente, Nome_cliente, Data_nasc, Email, Numero, Senha) 
                   VALUES (?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql_cliente);
    $stmt->bind_param("ssssss", $cpf, $nome, $data_nasc_cliente, $email, $numero, $senha_hash);

    if ($stmt->execute()) {
        // Se cadastro do cliente foi bem sucedido, cadastra o pet
        $sql_pet = "INSERT INTO Cadastro_Pet (Cpf_cliente, Nome_pet, Tipo_pet, Raca, Tamanho, Data_nasc)
                   VALUES (?, ?, ?, ?, ?, ?)";
        
        $stmt_pet = $conn->prepare($sql_pet);
        $stmt_pet->bind_param("ssssss", $cpf, $nome_pet, $tipo_pet, $raca, $tamanho, $data_nasc_pet);
        
        if ($stmt_pet->execute()) {
            echo "Cadastro de cliente e pet realizados com sucesso! <a href='login.php'>Fazer login</a>";
        } else {
            echo "Cliente cadastrado, mas erro ao cadastrar pet: " . $stmt_pet->error;
        }
    } else {
        echo "Erro ao cadastrar cliente: " . $stmt->error;
    }
}
?>