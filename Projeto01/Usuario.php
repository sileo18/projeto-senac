<?php
// =========================================================
// Usuario.php
// Responsável por receber dados do CadastroUsuario.html e salvar no banco.
// =========================================================

// Configurações de Banco de Dados
$host = "localhost";
$usuario = "root";
$senha = "";
$banco = "cadastrarusuario"; 

// 1. Receber os dados do formulário
$nome = $_POST['nome-usuario'] ?? null;
$idade = $_POST['idade'] ?? 0;
$sexo = $_POST['sexo'] ?? null;
$frequencia = $_POST['frequencia'] ?? null;

// =========================================================
// 2. TRATAMENTO CRÍTICO: CONVERTER VÍRGULA PARA PONTO
//    
// =========================================================
$peso_raw = $_POST['peso'] ?? '0';
$altura_raw = $_POST['altura'] ?? '0';

// Substitui a vírgula (,) por ponto (.)
$peso_para_db = str_replace(',', '.', $peso_raw);
$altura_para_db = str_replace(',', '.', $altura_raw);


// 3. Conexão com o Banco de Dados
$conn = @new mysqli($host, $usuario, $senha, $banco);

if ($conn->connect_error) {
    die("Falha na conexão: " . $conn->connect_error);
}

// 4. Preparar e Executar a Query (Usando Prepared Statements para Segurança)
if ($nome && $idade > 0 && $sexo) {
    
    // Consulta SQL com placeholders 
    $sql = "INSERT INTO usuarios (nome, idade, sexo, peso, altura, frequencia_exercicios) VALUES (?, ?, ?, ?, ?, ?)";
    
    // Prepara a declaração
    $stmt = $conn->prepare($sql);
    
    // Verifica se a preparação foi bem-sucedida
    if ($stmt) {
        // Tipos dos parâmetros: s=string, i=integer, d=double(decimal)
        
        $stmt->bind_param("sisdds", $nome, $idade, $sexo, $peso_para_db, $altura_para_db, $frequencia);
        
        // Executa a declaração
        if ($stmt->execute()) {
            // Sucesso: Redireciona para o Perfil
            header("Location: Perfil.php?status=cadastro_sucesso");
            exit();
        } else {
            // Falha na execução  
            echo "Erro ao cadastrar: " . $stmt->error;
        }
        
        $stmt->close();
    } else {
        // Falha na preparação da consulta
        echo "Erro na preparação da consulta: " . $conn->error;
    }

} else {
    echo "Dados de usuário incompletos ou inválidos. Retorne ao formulário.";
}

$conn->close();
?>