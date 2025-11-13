<?php
session_start();

$host = "localhost";
$usuario = "root";
$senha = "";
$banco = "cadastrarusuario";

$dados_usuario = null;
$id_usuario = null;
$conexao_falhou = false;
$erro_conexao = "";

$conn = @new mysqli($host, $usuario, $senha, $banco);

if ($conn->connect_error) {
    $conexao_falhou = true;
    $erro_conexao = $conn->connect_error;
} else {
    $sql = "SELECT id, nome, idade, sexo, peso, altura, frequencia_exercicios FROM usuarios ORDER BY id DESC LIMIT 1";
    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        $dados_usuario = $result->fetch_assoc();
        $id_usuario = $dados_usuario['id'];

        //  GUARDA O ID NA SESSÃO
        $_SESSION['user_id'] = $id_usuario;
    }

    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contagem de Calorias - Meu Perfil</title>
    <link rel="stylesheet" href="Perfil.css">
</head>
<body>

    <div class="header">
        <img src="Images/alimentacao-saudavel (1).png" alt="Ícone de Perfil" class="header-icon">
        <h1>Meu Perfil</h1>
    </div>

    <div class="container">
        <aside class="sidebar">
            <nav>
                <a href="Cadastrousuario.html" class="menu-item">Cadastro Usuário</a>
                <a href="CadastroAlimento.html" class="menu-item">Cadastrar Alimentos</a>
                <a href="alimentossalvos.php" class="menu-item">Alimentos Cadastrados</a>
                <a href="meta.php" class="menu-item">Meta</a>
                <a href="Perfil.php" class="menu-item active">Perfil</a>
                <a href="Historicosemanal.php" class="menu-item">Histórico semanal</a>
            </nav>
        </aside>

        <main class="content">
            <div class="perfil-container">
                <h2>Detalhes do Usuário e Opções</h2>

                <?php if ($conexao_falhou): ?>
                    <div class="no-data-message" style="color: #F44336; font-weight: bold; background-color: #ffe0e0; padding: 20px; border-radius: 8px;">
                        <h3 style="margin-top: 0; color: #d32f2f;">Erro de Conexão com o Banco de Dados</h3>
                        <p>Não foi possível conectar ao banco <strong><?php echo $banco; ?></strong>. Detalhes: <strong><?php echo htmlspecialchars($erro_conexao); ?></strong></p>
                    </div>

                <?php elseif (!$dados_usuario): ?>
                    <div class="no-data-message" style="padding: 20px; text-align: center;">
                        <p class="form-info">Não encontramos seus dados cadastrados.</p>
                        <p>Por favor, realize o <strong><a href='Cadastrousuario.html'>Cadastro de Usuário</a></strong>.</p>
                    </div>

                <?php else:
                    // Se os dados foram encontrados, exibe o perfil
                    $altura_cm = htmlspecialchars($dados_usuario['altura'] ?? '0');
                    $peso_kg = htmlspecialchars($dados_usuario['peso'] ?? '0');
                    $frequencia = htmlspecialchars($dados_usuario['frequencia_exercicios'] ?? 'Não informado');
                ?>
                    <div class="dados-pessoais">
                        <p><strong>Nome:</strong> <?php echo htmlspecialchars($dados_usuario['nome'] ?? 'N/A'); ?></p>
                        <p><strong>Idade:</strong> <?php echo htmlspecialchars($dados_usuario['idade'] ?? 'N/A'); ?> anos</p>
                        <p><strong>Sexo:</strong> <?php echo htmlspecialchars(ucfirst($dados_usuario['sexo'] ?? 'N/A')); ?></p>
                        <p><strong>Peso Atual:</strong> <?php echo number_format((float)$peso_kg, 1, ',', '.'); ?> kg</p>
                        <p><strong>Altura:</strong> <?php echo number_format((float)$altura_cm, 2, ',', '.'); ?> m</p>
                        <p><strong>Frequência de Exercícios:</strong> <?php echo ucfirst($frequencia); ?></p>

                        <a href="EditarPerfil.php?id=<?php echo $id_usuario; ?>" class="btn-editar">Editar Perfil</a>
                        <a href="ConfirmarDeletarPerfil.php?id=<?php echo $id_usuario; ?>" class="btn-deletar" style="background-color: #f44336;">Deletar Perfil</a>
                    </div>
                <?php endif; ?>

            </div>
        </main>
    </div>

</body>
</html>
