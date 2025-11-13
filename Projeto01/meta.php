<?php
// =========================================================
// 1. CONFIGURAÇÃO DE BANCO DE DADOS E VARIÁVEIS
// =========================================================
$host = "localhost";
$usuario = "root";
$senha = "";
$banco = "cadastrarusuario"; 
$imc_meta_desejado = 22.0; // IMC  

// Inicializa variáveis de meta e resultado
$dados_usuario = null;
$conexao_falhou = false;
$erro_conexao = "";

// Variáveis de Cálculo
$peso_atual = 0.0;
$altura_metros = 0.0;
$imc_atual = 0.0; 
$peso_meta = 0.0;

// Variáveis de Saída da Meta  
$acao_necessaria = "Verificar";
$diferenca_peso_abs = 0.0; // A quantidade de quilos a perder ou ganhar
$mensagem_meta_peso = "Aguardando cálculo.";

// Variáveis ESPECÍFICAS do Diagnóstico de IMC
$faixa_imc = "N/A";
$mensagem_imc = "Não calculado.";


// =========================================================
// 2. CONEXÃO E BUSCA DE DADOS DO ÚLTIMO USUÁRIO CADASTRADO
// =========================================================
$conn = @new mysqli($host, $usuario, $senha, $banco);

if ($conn->connect_error) {
    $conexao_falhou = true;
    $erro_conexao = $conn->connect_error;
} else {
    // Busca os dados e o ID do último usuário cadastrado
    $sql = "SELECT id, peso, altura, idade, sexo, frequencia_exercicios FROM usuarios ORDER BY id DESC LIMIT 1";
    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        $dados_usuario = $result->fetch_assoc();
        $id_usuario = (int)$dados_usuario['id'];
    }
 
}

// =========================================================
// 3. LÓGICA DE CÁLCULO (Se os dados foram encontrados)
// =========================================================
if ($dados_usuario) {
    
    // 3.1 Extração e Conversão de Dados (CORREÇÃO CRÍTICA DO FORMATO NUMÉRICO)
    $peso_raw = str_replace(',', '.', $dados_usuario['peso']);
    $peso_atual = (float)$peso_raw;
    
    $altura_raw = str_replace(',', '.', $dados_usuario['altura']);
    $altura_cm = (float)$altura_raw; 

    // Lógica para definir a altura em METROS
    if ($altura_cm > 3) { 
        $altura_metros = $altura_cm / 100.0;
    } else { 
        $altura_metros = $altura_cm;
    }
    
    // 3.2 CÁLCULO E CLASSIFICAÇÃO DO IMC ATUAL
    if ($altura_metros > 0) {
        $imc_atual = $peso_atual / ($altura_metros * $altura_metros);
    }
    
    // 3.3 CLASSIFICAÇÃO DO IMC
    if ($imc_atual < 18.5) {
        $faixa_imc = "Abaixo do peso";
        $mensagem_imc = "Você está abaixo do peso ideal. A meta é **Ganhar** peso.";
    } elseif ($imc_atual >= 18.5 && $imc_atual < 24.9) {
        $faixa_imc = "Peso normal";
        $mensagem_imc = "Seu peso está normal. Mantenha o foco!";
    } elseif ($imc_atual >= 25.0 && $imc_atual < 29.9) {
        $faixa_imc = "Sobrepeso";
        $mensagem_imc = "Você está com sobrepeso. A meta é **Perder** peso.";
    } elseif ($imc_atual >= 30.0 && $imc_atual < 34.9) {
        $faixa_imc = "Obesidade Grau I";
        $mensagem_imc = "Você está com Obesidade Grau I. A meta é **Perder** peso com acompanhamento.";
    } elseif ($imc_atual >= 35.0 && $imc_atual < 39.9) {
        $faixa_imc = "Obesidade Grau II (Severa)";
        $mensagem_imc = "Você está com Obesidade Grau II. Priorize a perda de peso com acompanhamento profissional.";
    } else {
        $faixa_imc = "Obesidade Grau III (Mórbida)";
        $mensagem_imc = "Você está com Obesidade Grau III. Acompanhamento médico e nutricional é fundamental.";
    }
    
    
    // 3.4 Cálculo da Meta de Peso (Peso ideal para o IMC desejado)
    if ($altura_metros > 0) {
        $peso_meta = $imc_meta_desejado * ($altura_metros * $altura_metros);
    }
    
    // 3.5 Definição da Ação Necessária e Cálculo da Diferença ABSOLUTA (Revisado)
    $diferenca_peso = $peso_atual - $peso_meta;  

    if ($diferenca_peso > 0.5) { // Precisa perder mais de 0.5kg
        $acao_necessaria = "Perder";
        $diferenca_peso_abs = $diferenca_peso;
        $mensagem_meta_peso = "Você precisa **perder** cerca de " . number_format($diferenca_peso_abs, 2, ',', '.') . " kg para atingir o peso ideal ($imc_meta_desejado IMC).";
    } elseif ($diferenca_peso < -0.5) { // Precisa ganhar mais de 0.5kg
        $acao_necessaria = "Ganhar";
        $diferenca_peso_abs = abs($diferenca_peso);
        $mensagem_meta_peso = "Você precisa **ganhar** cerca de " . number_format($diferenca_peso_abs, 2, ',', '.') . " kg para atingir o peso ideal ($imc_meta_desejado IMC).";
    } else {
        $acao_necessaria = "Manter";
        $diferenca_peso_abs = 0;
        $mensagem_meta_peso = "Parabéns! Seu peso está dentro da margem de segurança do IMC ideal.";
    }
    
   

    if (!$conexao_falhou) {
        $conn->close();
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sua Meta de Peso</title>
    <link rel="stylesheet" href="meta.css"> 
    <style>
        
        .resultado-valor { font-weight: bold; color: #007bff; }
        .meta-status { 
            padding: 20px; 
            border-radius: 8px; 
            margin-top: 20px;
            font-size: 1.1em;
            line-height: 1.5;
            background-color: #f0fff0; 
            border: 2px solid #00a000;
            color: #006600;
            text-align: center;
        }
        .meta-message {
            font-size: 1.3em;
            font-weight: bold;
            color: #007bff;
        }
    </style>
</head>
<body>

    <div class="header">
        <img src="Images/alimentacao-saudavel (1).png" alt="Ícone de Meta" class="header-icon"> 
        <h1>Sua Meta de Longo Prazo</h1>
    </div>

    <div class="container">
        <aside class="sidebar">
            <nav>
                <a href="Cadastrousuario.html" class="menu-item">Cadastro Usuário</a>
                <a href="CadastroAlimento.html" class="menu-item">Cadastrar Alimentos</a> 
                <a href="alimentossalvos.php" class="menu-item">Alimentos Cadastrados</a>
                
                <a href="meta.php" class="menu-item active">Meta</a> 
                
                <a href="Perfil.php" class="menu-item">Perfil</a>
                
                <a href="HistoricoSemanal.php" class="menu-item">Histórico semanal</a>
            </nav>
        </aside>

        <main class="content">
            <div class="resultado-container">
                <h2>Seu Objetivo: Peso Ideal</h2>
                
                <?php if ($conexao_falhou): ?>
                    <div class="no-data-message" style="color: #F44336; font-weight: bold; background-color: #ffe0e0; padding: 20px; border-radius: 8px;">
                        <h3 style="margin-top: 0; color: #d32f2f;">Erro de Conexão</h3>
                        <p>Não foi possível conectar ao banco de dados.</p>
                    </div>
                
                <?php elseif (!$dados_usuario): ?>
                    <div class="no-data-message" style="padding: 20px; text-align: center;">
                        <p class="form-info">Não encontramos seus dados. Por favor, realize o **<a href='Cadastrousuario.html'>Cadastro de Usuário</a>**.</p>
                    </div>
                
                <?php else: ?>
                    
                    <div style="background-color: #f0f8ff; border: 1px solid #cceeff; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                        <h3>Diagnóstico Atual</h3>
                        <p>
                            **Seu Peso Atual:** <span class="resultado-valor"><?php echo number_format($peso_atual, 2, ',', '.'); ?> kg</span>
                        </p>
                        <p>
                            **Seu IMC Atual:** <span class="resultado-valor" style="color: 
                            <?php 
                                if ($imc_atual >= 30) echo '#cc0000';
                                elseif ($imc_atual >= 25) echo '#ff9900';
                                elseif ($imc_atual < 18.5) echo '#3399ff'; 
                                else echo '#008000'; 
                            ?>
                            ; font-size: 1.2em;"><?php echo number_format($imc_atual, 2, ',', '.'); ?></span>
                        </p>
                        <p>
                            **Classificação:** <span style="font-weight: bold; color: 
                            <?php 
                                if ($imc_atual >= 30) echo '#cc0000';
                                elseif ($imc_atual >= 25) echo '#ff9900';
                                elseif ($imc_atual < 18.5) echo '#3399ff';
                                else echo '#008000';
                            ?>
                            ;"><?php echo $faixa_imc; ?></span>
                        </p>
                    </div>
                    
                    <hr>

                    <h3>Meta de Longo Prazo (IMC Ideal: <?php echo number_format($imc_meta_desejado, 1, ',', '.'); ?>)</h3>

                    <p>
                        **Peso Ideal Estimado:** <span class="resultado-valor"><?php echo number_format($peso_meta, 2, ',', '.'); ?> kg</span>
                    </p>
                    
                    <div class="meta-status">
                        <?php if ($diferenca_peso_abs > 0): ?>
                            <p class="meta-message" style="color: <?php echo ($acao_necessaria == 'Perder') ? '#cc0000' : '#006600'; ?>;">
                                **<?php echo $mensagem_meta_peso; ?>**
                            </p>
                            <p style="font-style: italic; margin-top: 15px;">
                                Lembre-se: metas de peso devem ser discutidas com um profissional de saúde.
                            </p>
                        <?php else: ?>
                            <p class="meta-message" style="color: #008000;">
                                **<?php echo $mensagem_meta_peso; ?>**
                            </p>
                        <?php endif; ?>
                    </div>
                    
                <?php endif; ?>

            </div>
        </main>
    </div>
    
</body>
</html>