<?php
// =========================================================
// ProcessoDeletarPerfil.php
//  
// =========================================================

// 1. INICIAR SESSÃO
session_start();

// 2. CONFIGURAÇÃO DO BANCO DE DADOS
$host = "localhost";
$usuario = "root";
$senha = "";
$banco = "cadastrarusuario";

// 3. VERIFICAR CONDIÇÕES PARA DELEÇÃO
if (!isset($_POST['confirmacao_exclusao']) || $_POST['confirmacao_exclusao'] !== 'sim') {
    // Acesso direto ou confirmação ausente
    header("Location: Perfil.php?status=erro_confirmacao");
    exit();
}

if (!isset($_SESSION['user_id'])) {
    // ID do usuário não encontrado na sessão
    header("Location: Perfil.php?status=erro_sessao");
    exit();
}

$id_usuario_deletar = $_SESSION['user_id'];

// 4. CONEXÃO COM O BANCO
$conn = @new mysqli($host, $usuario, $senha, $banco);

if ($conn->connect_error) {
    // Erro de conexão
    header("Location: Perfil.php?status=erro_conexao&detalhe=" . urlencode($conn->connect_error));
    exit();
}

// 5. EXECUTAR O DELETE
 
$sql = "DELETE FROM usuarios WHERE id = ?";

$stmt = $conn->prepare($sql);
// "i" significa que o parâmetro é um integer (inteiro)
$stmt->bind_param("i", $id_usuario_deletar); 

if ($stmt->execute()) {
    // SUCESSO na exclusão

    // 6. DESTRUIR SESSÃO E REDIRECIONAR
    session_unset();
    session_destroy();

    // Redireciona para a página de cadastro ou outra página inicial
    header("Location: Cadastrousuario.html?status=perfil_deletado");
} else {
    // Erro no banco de dados durante a exclusão
    header("Location: Perfil.php?status=erro_deletar&erro=" . urlencode($stmt->error));
}

$stmt->close();
$conn->close();
exit();

?>