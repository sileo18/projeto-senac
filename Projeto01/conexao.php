<?php
// Definições de conexão com o banco de dados (MySQLi) <Utilizado> 
$servername = "localhost"; 
$username = "root";         
$password = "";             
$dbname = "contagem_calorias"; // *** NOME DO BANCO DE DADOS ***

// Cria a conexão
$conn = new mysqli($servername, $username, $password, $dbname);

// Verifica a conexão
if ($conn->connect_error) {
    // Se a conexão falhar, interrompe o script e mostra o erro
    die("Falha na conexão: " . $conn->connect_error . 
        "<br>Verifique se o MySQL está rodando no XAMPP e se o nome do banco de dados no arquivo 'conexao.php' está correto."
    );
}
// Opcional: Define o conjunto de caracteres para evitar problemas com acentuação
$conn->set_charset("utf8");

 
?>
