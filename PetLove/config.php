<?php
$host = "localhost";
$usuario = "root";
$senha = "";
$banco = "petlove";

// Criando a conexão
$conn = mysqli_connect($host, $usuario, $senha, $banco);

// Verifica se a conexão foi bem-sucedida
if (!$conn) {
    die("Falha na conexão: " . mysqli_connect_error());
}
?>