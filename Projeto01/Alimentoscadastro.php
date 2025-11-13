<?php
// =========================================================
// CONFIGURAÇÃO DO BANCO
// =========================================================
$host = "localhost";
$usuario = "root";
$senha = "";
$banco = "contagem_calorias";

$conn = new mysqli($host, $usuario, $senha, $banco);

// Verifica conexão
if ($conn->connect_error) {
    die("Erro na conexão: " . $conn->connect_error);
}

// =========================================================
// PROCESSAMENTO DO FORMULÁRIO
// =========================================================
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $nome = $_POST['nome'] ?? '';
    $calorias = $_POST['calorias'] ?? 0;
    $carboidratos = $_POST['carboidratos'] ?? 0;
    $proteina = $_POST['proteina'] ?? 0;
    $gordura = $_POST['gordura'] ?? 0;
    $acucar = $_POST['acucar'] ?? 0;
    $data_cadastro = date('Y-m-d H:i:s');

    // Verificação básica
    if (empty($nome) || $calorias <= 0) {
        die("Por favor, preencha os campos corretamente.");
    }

    // Uso de prepared statement para evitar SQL injection
    $sql = "INSERT INTO alimentos (nome, calorias, carboidratos, proteina, gordura, acucar, data_cadastro)
            VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sddddds", $nome, $calorias, $carboidratos, $proteina, $gordura, $acucar, $data_cadastro);

    if ($stmt->execute()) {
        // =========================================================
        // EXIBE MENSAGEM DE SUCESSO COM BOTÃO VERDE DE VOLTAR
        // =========================================================
        echo "
        <!DOCTYPE html>
        <html lang='pt-BR'>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>Cadastro de Alimento</title>
            <style>
                body {
                    font-family: Arial, sans-serif;
                    background-color: #f4f4f4;
                    text-align: center;
                    padding-top: 100px;
                    color: #333;
                }
                .msg-sucesso {
                    background-color: #e8f5e9;
                    border: 2px solid #4CAF50;
                    border-radius: 15px;
                    display: inline-block;
                    padding: 30px 50px;
                    box-shadow: 0 4px 10px rgba(0,0,0,0.1);
                }
                h1 {
                    color: #4CAF50;
                    margin-bottom: 15px;
                }
                .btn-voltar {
                    display: inline-block;
                    background-color: #4CAF50;
                    color: white;
                    padding: 12px 25px;
                    font-size: 1.1em;
                    font-weight: bold;
                    border: none;
                    border-radius: 25px;
                    cursor: pointer;
                    margin-top: 15px;
                    text-decoration: none;
                    transition: background-color 0.3s ease;
                }
                .btn-voltar:hover {
                    background-color: #43a047;
                }
            </style>
        </head>
        <body>
            <div class='msg-sucesso'>
                <h1>Alimento cadastrado com sucesso!</h1>
                <a href='CadastroAlimento.html' class='btn-voltar'>Voltar</a>
            </div>
        </body>
        </html>";
    } else {
        echo "<h1 style='color: red;'>Erro ao cadastrar alimento!</h1>";
        echo "Detalhes do erro: " . $stmt->error;
    }

    $stmt->close();

} else {
    echo "Método inválido. Por favor, utilize o formulário.";
}

$conn->close();
?>