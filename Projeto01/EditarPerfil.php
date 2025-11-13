<?php
// =========================================================
// 1. CONFIGURAÇÃO DO BANCO DE DADOS
// =========================================================
$host = "localhost";
$usuario = "root";
$senha = "";
$banco = "cadastrarusuario";  

// Inicializa variáveis
$id_usuario = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$dados_usuario = null;
$conexao_falhou = false;
$mensagem_feedback = '';
$classe_feedback = '';

// =========================================================
// 2. CONEXÃO E VERIFICAÇÃO INICIAL
// =========================================================
$conn = @new mysqli($host, $usuario, $senha, $banco);

if ($conn->connect_error) {
    $conexao_falhou = true;
    $mensagem_feedback = "Erro de conexão: " . $conn->connect_error;
    $classe_feedback = 'alert-error';
} elseif (!$id_usuario) {
    $mensagem_feedback = "ID do usuário inválido ou ausente. Não é possível editar.";
    $classe_feedback = 'alert-error';
    $conn->close();
}

// =========================================================
// 3. PROCESSAMENTO DA ATUALIZAÇÃO (POST)
// =========================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $id_usuario && !$conexao_falhou) {
    
    // 3.1 Captura e Saneamento dos novos dados do formulário
    $nome = $conn->real_escape_string($_POST['nome']);
    $idade = filter_input(INPUT_POST, 'idade', FILTER_VALIDATE_INT);
    $sexo = $conn->real_escape_string($_POST['sexo']);
    $frequencia = $conn->real_escape_string($_POST['frequencia_exercicios']);
    
    // =========================================================
    // CORREÇÃO CRÍTICA: TRATAR PESO E ALTURA (VÍRGULA PARA PONTO)
    // =========================================================
    $peso_raw = $_POST['peso'] ?? '0';
    $altura_raw = $_POST['altura'] ?? '0';
    
    $peso_para_db = str_replace(',', '.', $peso_raw);
    $altura_para_db = str_replace(',', '.', $altura_raw);
    
    // Garante que o PHP interprete como float para a SQL (se necessário)
    $peso = (float)$peso_para_db; 
    $altura = (float)$altura_para_db;

    // 3.2 Montagem e Execução do SQL de atualização (Usando as variáveis corrigidas)
   
    $sql_update = "UPDATE usuarios SET 
                    nome = '{$nome}', 
                    idade = {$idade}, 
                    sexo = '{$sexo}', 
                    peso = {$peso}, 
                    altura = {$altura}, 
                    frequencia_exercicios = '{$frequencia}'
                    WHERE id = {$id_usuario}";

    if ($conn->query($sql_update) === TRUE) {
        header("Location: Perfil.php?status=sucesso_edicao");
        exit;
    } else {
        $mensagem_feedback = "Erro ao atualizar o perfil: " . $conn->error;
        $classe_feedback = 'alert-error';
    }
}

// =========================================================
// 4. BUSCA DOS DADOS ATUAIS PARA PRÉ-PREENCHIMENTO
// =========================================================
if ($id_usuario && !$conexao_falhou) {
    $sql_select = "SELECT nome, idade, sexo, peso, altura, frequencia_exercicios FROM usuarios WHERE id = {$id_usuario}";
    $result = $conn->query($sql_select);

    if ($result && $result->num_rows > 0) {
        $dados_usuario = $result->fetch_assoc();
        
        // CORREÇÃO PARA EXIBIÇÃO: Formata peso e altura com VÍRGULA para o formulário
        $dados_usuario['peso_display'] = number_format((float)$dados_usuario['peso'], 2, ',', '');
        $dados_usuario['altura_display'] = number_format((float)$dados_usuario['altura'], 2, ',', '');
        
    } else {
        $mensagem_feedback = "Usuário não encontrado.";
        $classe_feedback = 'alert-error';
    }
}

if (!$conexao_falhou && isset($conn)) {
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Perfil</title>
    <link rel="stylesheet" href="EditarPerfil.css"> 
</head>
<body>

<div class="header">
    <img src="Images/alimentacao-saudavel (1).png" alt="Ícone de Edição" class="header-icon">
    <h1>Editar Perfil</h1>
</div>

<div class="container">
    <main class="content">

        <?php if ($mensagem_feedback): ?>
            <div class="alerta-flutuante <?php echo $classe_feedback; ?>">
                <?php echo htmlspecialchars($mensagem_feedback); ?>
            </div>
        <?php endif; ?>

        <?php if ($dados_usuario): ?>
            <div class="form-card">
                <h2>Ajuste Seus Dados</h2>
                
                <form action="EditarPerfil.php?id=<?php echo $id_usuario; ?>" method="POST" class="cadastro-form">
                    
                    <div class="form-group">
                        <label for="nome">Nome Completo:</label>
                        <input type="text" id="nome" name="nome" required 
                            value="<?php echo htmlspecialchars($dados_usuario['nome']); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="idade">Idade:</label>
                        <input type="number" id="idade" name="idade" required min="1"
                            value="<?php echo htmlspecialchars($dados_usuario['idade']); ?>">
                    </div>

                    <div class="form-group">
                        <label for="sexo">Sexo:</label>
                        <select id="sexo" name="sexo" required>
                            <?php $sexo_atual = strtolower($dados_usuario['sexo']); ?>
                            <option value="masculino" <?php echo ($sexo_atual === 'masculino' ? 'selected' : ''); ?>>Masculino</option>
                            <option value="feminino" <?php echo ($sexo_atual === 'feminino' ? 'selected' : ''); ?>>Feminino</option>
                            <option value="outro" <?php echo ($sexo_atual === 'outro' ? 'selected' : ''); ?>>Outro</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="peso">Peso (kg - use VÍRGULA, ex: 80,5):</label>
                        <input type="text" id="peso" name="peso" required min="10"
                            value="<?php echo htmlspecialchars($dados_usuario['peso_display']); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="altura">Altura (m - use VÍRGULA, ex: 1,90):</label>
                        <input type="text" id="altura" name="altura" required min="0.50"
                            value="<?php echo htmlspecialchars($dados_usuario['altura_display']); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="frequencia_exercicios">Frequência de Exercícios:</label>
                        <select id="frequencia_exercicios" name="frequencia_exercicios" required>
                            <?php $frequencia_atual = strtolower($dados_usuario['frequencia_exercicios']); ?>
                            <option value="sedentario" <?php echo ($frequencia_atual === 'sedentario' ? 'selected' : ''); ?>>Sedentário (pouco ou nenhum exercício)</option>
                            <option value="leve" <?php echo ($frequencia_atual === 'leve' ? 'selected' : ''); ?>>Leve (exercício 1-3 dias/semana)</option>
                            <option value="moderado" <?php echo ($frequencia_atual === 'moderado' ? 'selected' : ''); ?>>Moderado (exercício 3-5 dias/semana)</option>
                            <option value="ativo" <?php echo ($frequencia_atual === 'ativo' ? 'selected' : ''); ?>>Ativo (exercício 6-7 dias/semana)</option>
                            <option value="muito ativo" <?php echo ($frequencia_atual === 'muito ativo' ? 'selected' : ''); ?>>Muito Ativo (trabalho físico pesado/2x ao dia)</option>
                        </select>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn-primary">Salvar Alterações</button>
                        <a href="Perfil.php" class="btn-secondary">Cancelar</a>
                    </div>

                </form>
            </div>
        <?php endif; ?>

    </main>
</div>

</body>
</html>