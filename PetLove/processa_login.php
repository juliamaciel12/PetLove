<?php
session_start();
require 'config.php'; // já incluído nos seus arquivos

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
        header("Location: cliente_dashboard.php");
        exit;
    } else {
        echo "Senha incorreta!";
    }
} else {
    echo "CPF não encontrado!";
}
?>
