 <?php
// =========================================================
// ConfirmarDeletar.php  
// =========================================================

 
// nome do arquivo de conexão
include 'conexao.php'; 

$id_alimento = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$alimento_encontrado = false;
$nome_alimento = '';

// ----------------------------------------------------
// 1. Lógica de Busca do Nome
// ----------------------------------------------------
if ($id_alimento) {
    $sql_busca = "SELECT nome FROM alimentos WHERE id = ?";
    $stmt_busca = $conn->prepare($sql_busca);
    
    if ($stmt_busca) {
        $stmt_busca->bind_param("i", $id_alimento);
        $stmt_busca->execute();
        $resultado = $stmt_busca->get_result();
        
        if ($resultado->num_rows > 0) {
            $alimento = $resultado->fetch_assoc();
            $nome_alimento = htmlspecialchars($alimento['nome']);
            $alimento_encontrado = true;
        }
        $stmt_busca->close();
    }
}

// ----------------------------------------------------
// 2. Lógica de Deleção (quando o formulário é enviado)
// ----------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirmar_delecao'])) {
    
    $id_para_deletar = filter_input(INPUT_POST, 'id_alimento', FILTER_VALIDATE_INT);
    
    if ($id_para_deletar && $id_para_deletar === $id_alimento) {
        $sql_delete = "DELETE FROM alimentos WHERE id = ?";
        $stmt_delete = $conn->prepare($sql_delete);
        
        if ($stmt_delete) {
            $stmt_delete->bind_param("i", $id_para_deletar);
            
            if ($stmt_delete->execute()) {
                // Sucesso: Fecha a conexão e redireciona
                $stmt_delete->close();
                $conn->close();
                header("Location: alimentossalvos.php?status=sucesso_delecao"); 
                exit();
            } else {
                // Erro: Fecha a conexão e redireciona
                $stmt_delete->close();
                $conn->close();
                header("Location: alimentossalvos.php?status=erro_delecao");
                exit();
            }
        }
    }
}

// ----------------------------------------------------
// 3. Tratamento de Erro e Fechamento da Conexão
// ----------------------------------------------------
// Se ID for inválido ou alimento não encontrado, redireciona
if (!$alimento_encontrado) {
    if (isset($conn) && $conn->ping()) {
        $conn->close(); 
    }
    header("Location: alimentossalvos.php?status=erro_id");
    exit();
}

// Fecha a conexão antes de renderizar o HTML (se ainda estiver aberta)
if (isset($conn) && $conn->ping()) {
    $conn->close(); 
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmar Deleção de Alimento</title>
    <link rel="stylesheet" href="alimentossalvos.css"> 
    <link rel="stylesheet" href="ConfirmarDeletar.css"> 
</head>
<body>

    <div class="confirmation-box">
        <h2>⚠️ Confirmação de Exclusão</h2>
        
        <p>Você tem certeza que deseja **deletar permanentemente** o alimento:</p>
        
        <p class="food-name-to-delete">
            "<?php echo $nome_alimento; ?>"
        </p>

        <form action="ConfirmarDeletar.php?id=<?php echo $id_alimento; ?>" method="POST" class="confirmation-actions">
            
            <input type="hidden" name="id_alimento" value="<?php echo $id_alimento; ?>">
            
            <button type="submit" name="confirmar_delecao" class="btn-confirmar">SIM, Deletar</button>
            
            <a href="alimentossalvos.php" class="btn-cancelar">Não, Cancelar</a>
        </form>
    </div>

</body>
</html>