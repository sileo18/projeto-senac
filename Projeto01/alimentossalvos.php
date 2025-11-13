<?php
// =========================================================
// 1. CONFIGURAÇÃO DO BANCO DE DADOS E CONEXÃO
// =========================================================
$host = "localhost";
$usuario = "root";
$senha = "";
$banco = "contagem_calorias";

// Tenta conectar
  
$conn = @new mysqli($host, $usuario, $senha, $banco);

$conexao_falhou = $conn->connect_error;
$erro_conexao = $conexao_falhou ? $conn->connect_error : "";
$alimentos = []; // Array que armazenará os dados

// =========================================================
// 2. BUSCA DE DADOS (SE A CONEXÃO FOR BEM SUCEDIDA)
// =========================================================
if (!$conexao_falhou) {
    // Sua consulta SQL (selecionando todos os campos)
    $sql = "SELECT id, nome, calorias, carboidratos, proteina, gordura, acucar FROM alimentos ORDER BY data_cadastro DESC";
    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        // Armazena todos os resultados no array $alimentos
        while($row = $result->fetch_assoc()) {
            $alimentos[] = $row;
        }
    }
    // Fechamos a conexão aqui, antes de começar o HTML
    $conn->close();
}

// =========================================================
// 3. LÓGICA PARA EXIBIR MENSAGENS DE FEEDBACK (Alertas)
// =========================================================
$status = filter_input(INPUT_GET, 'status', FILTER_SANITIZE_SPECIAL_CHARS);
$mensagem = '';
$classe_alerta = '';

if ($status) {
    switch ($status) {
        case 'sucesso_edicao':
            $mensagem = "Ajustes salvos com sucesso!";
            $classe_alerta = 'alert-success';
            break;
        case 'sucesso_delecao':
            $mensagem = "Alimento deletado com sucesso!";
            $classe_alerta = 'alert-success';
            break;
        case 'erro_atualizar':
            $mensagem = "Erro ao tentar salvar os ajustes. Tente novamente.";
            $classe_alerta = 'alert-error';
            break;
        case 'erro_delecao':
        case 'erro_id':
            $mensagem = "Erro ao processar a deleção. ID do alimento inválido ou ausente.";
            $classe_alerta = 'alert-error';
            break;
        default:
            // Limpa o status se for desconhecido
            $status = null; 
            break;
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contagem de Calorias - Alimentos Cadastrados</title>
    
    <link rel="stylesheet" href="alimentossalvos.css">
</head>
<body>

    <div class="header">
        <img src="Images/alimentacao-saudavel (1).png" alt="Ícone de Alimentos Cadastrados" class="header-icon">
        <h1>Alimentos Cadastrados</h1>
    </div>

    <div class="container">
        <aside class="sidebar">
            <nav>
                <a href="Cadastrousuario.html" class="menu-item">Cadastro Usuário</a> 
                <a href="CadastroAlimento.html" class="menu-item">Cadastrar Alimentos</a>
                
                <a href="alimentossalvos.php" class="menu-item active">Alimentos Cadastrados</a> 
                
                <a href="meta.php" class="menu-item">Meta</a>
                
                <a href="Perfil.php" class="menu-item active">Perfil</a>
                <a href="Historicosemanal.php" class="menu-item">Histórico semanal</a>
            </nav>
        </aside>

        <main class="content">
            <?php if ($mensagem): ?>
                <div class="alerta-flutuante <?php echo $classe_alerta; ?>">
                    <?php echo htmlspecialchars($mensagem); ?>
                </div>
            <?php endif; ?>
            <div class="food-list-container">

                <?php if ($conexao_falhou): ?>
                    <div class="no-data-message" style="color: #F44336; font-weight: bold; background-color: #ffe0e0; padding: 20px; border-radius: 8px;">
                        <h2 style="margin-top: 0; color: #d32f2f;">Erro de Conexão com o Banco de Dados</h2>
                        <p>Não foi possível conectar ao MySQL. Por favor, verifique se os serviços Apache e MySQL estão **iniciados** no seu painel de controle do XAMPP.</p>
                        <p>Detalhes do Erro: **<?php echo htmlspecialchars($erro_conexao); ?>**</p>
                    </div>

                <?php elseif (empty($alimentos)): ?>
                    <div class="no-data-message">
                        Nenhum alimento cadastrado no momento. Utilize a opção "Cadastrar Alimentos" para começar.
                    </div>

                <?php else: ?>
                    <p class="form-info" style="text-align: right; margin-bottom: 20px;">
                        (Valores abaixo sempre referentes a 100 g)
                    </p>

                    <?php foreach ($alimentos as $alimento): ?>
                        <div class="food-card">
                            <div class="card-header"><?php echo htmlspecialchars($alimento['nome']); ?></div>
                            
                            <div class="food-macros">
                                <p><strong>Calorias (kcal):</strong> <?php echo number_format($alimento['calorias'], 0, ',', '.'); ?> Kcal</p>
                                <p><strong>Carboidratos (g):</strong> <?php echo number_format($alimento['carboidratos'], 1, ',', '.'); ?> g</p>
                                <p><strong>Proteína (g):</strong> <?php echo number_format($alimento['proteina'], 2, ',', '.'); ?> g</p>
                                <p><strong>Gordura (g):</strong> <?php echo number_format($alimento['gordura'], 2, ',', '.'); ?> g</p>
                                <p><strong>Açúcar (g):</strong> <?php echo number_format($alimento['acucar'], 1, ',', '.'); ?> g</p>
                            </div>
                            
                            <div class="card-actions">
                                <a href="AjustarAlimento.php?id=<?php echo $alimento['id']; ?>" class="btn-acao ajustar">Ajustar Alimento</a>
                                
                                <a href="ConfirmarDeletar.php?id=<?php echo $alimento['id']; ?>" class="btn-acao deletar">Deletar Alimento</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </main>
    </div>

</body>
</html>