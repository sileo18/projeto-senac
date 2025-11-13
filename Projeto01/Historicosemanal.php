<?php
// =========================================================
// 1. CONFIGURAÇÃO DE BANCO DE DADOS E VARIÁVEIS 
// =========================================================
$host = "localhost";
$usuario = "root";
$senha = "";
$banco_alimentos = "contagem_calorias"; 
$banco_usuarios = "cadastrarusuario"; 
$imc_meta_desejado = 22.0;

// Inicializa variáveis de resultado
$dados_usuario = null;
$conexao_falhou = false;
$erro_conexao = "";

// Variáveis de Cálculo (Peso)  
$peso_atual = 0.0;
$altura_metros = 0.0;
$peso_meta = 0.0; 
$acao_necessaria = "Verificar"; 

// Variáveis Diárias (Nutrição)
$calorias_ingeridas_total = 0;
$carboidratos_total = 0.0;
$proteina_total = 0.0;
$gordura_total = 0.0;
$acucar_total = 0.0;

// Variáveis de Meta (Nutrição)
$gasto_energetico_total = 0; // GET
$deficit_calorico_alvo = 500; // Meta padrão de déficit para perda de peso
$meta_calorica_diaria = 0;
$status_calorico = "Manter";
$classe_status_nutricao = "status-neutro";
$mensagem_status_nutricao = "";
$nota_referencia_calorica = ""; // Variável para a nota


// Variáveis de Média Diária  
$dias_para_dividir = 7; // O número de dias que você quer considerar
$calorias_media_diaria = 0;
$carboidratos_media_diaria = 0.0;
$proteina_media_diaria = 0.0;
$gordura_media_diaria = 0.0;
$acucar_media_diaria = 0.0;


// =========================================================
// 2. CONEXÃO E BUSCA DE DADOS DO ÚLTIMO USUÁRIO CADASTRADO 
// =========================================================
$conn_user = @new mysqli($host, $usuario, $senha, $banco_usuarios);
$conn_alimentos = @new mysqli($host, $usuario, $senha, $banco_alimentos);

if ($conn_user->connect_error || $conn_alimentos->connect_error) {
    $conexao_falhou = true;
    $erro_conexao = $conn_user->connect_error ?: $conn_alimentos->connect_error;
} else {
    // 2.1 Busca do último usuário (para cálculo de meta de peso e GET)
    $sql_usuario = "SELECT id, peso, altura, idade, sexo, frequencia_exercicios 
                    FROM usuarios 
                    ORDER BY id DESC 
                    LIMIT 1";
    $result_usuario = $conn_user->query($sql_usuario);

    if ($result_usuario && $result_usuario->num_rows > 0) {
        $dados_usuario = $result_usuario->fetch_assoc();
        $id_usuario = (int)$dados_usuario['id'];
        
        // 2.2 SOMA DOS ALIMENTOS CADASTRADOS  
        $sql_soma_alimentos = "SELECT 
                                SUM(calorias) AS total_calorias,
                                SUM(carboidratos) AS total_carboidratos,
                                SUM(proteina) AS total_proteina,
                                SUM(gordura) AS total_gordura,
                                SUM(acucar) AS total_acucar
                                FROM alimentos"; 
        
        $result_soma = $conn_alimentos->query($sql_soma_alimentos);
        if ($result_soma && $result_soma->num_rows > 0) {
            $soma_macros = $result_soma->fetch_assoc();
            $calorias_ingeridas_total = (int)$soma_macros['total_calorias'];
            $carboidratos_total = (float)$soma_macros['total_carboidratos'];
            $proteina_total = (float)$soma_macros['total_proteina'];
            $gordura_total = (float)$soma_macros['total_gordura'];
            $acucar_total = (float)$soma_macros['total_acucar'];
        }
    }
}


// =========================================================
// 3. LÓGICA DE CÁLCULO NUTRICIONAL E PESO
// =========================================================
if ($dados_usuario) {

     
    function calcularTMB($peso, $altura_cm, $idade, $sexo) {
        // Fórmula de Mifflin-St Jeor 
        $tmb = (10 * $peso) + (6.25 * $altura_cm) - (5 * $idade);
        if (strtolower($sexo) === 'masculino') {
            $tmb += 5;
        } else { // Feminino
            $tmb -= 161;
        }
        return $tmb;
    }

    function calcularGET($tmb, $frequencia) {
        $fator_atividade = 1.2; // Sedentário 

        if (stripos($frequencia, 'leve') !== false) {
            $fator_atividade = 1.375;
        } elseif (stripos($frequencia, 'moderado') !== false) {
            $fator_atividade = 1.55;
        } elseif (stripos($frequencia, 'ativo') !== false || stripos($frequencia, 'intenso') !== false) {
            $fator_atividade = 1.725;
        }
        return $tmb * $fator_atividade;
    }

    // 3.2 Extração e Conversão de Dados  
    $peso_raw = str_replace(',', '.', $dados_usuario['peso']);
    $peso_kg = (float)$peso_raw;
    
    $altura_raw = str_replace(',', '.', $dados_usuario['altura']);
    $altura_cm = (float)$altura_raw; 

    $idade = (int)$dados_usuario['idade'];
    $sexo = $dados_usuario['sexo'];
    $frequencia = $dados_usuario['frequencia_exercicios'];
    
    if ($altura_cm > 3) { 
        $altura_metros = $altura_cm / 100.0;
    } else { 
        $altura_metros = $altura_cm;
    }
    
    // 3.3 CÁLCULO DA META DE PESO  
    $peso_meta = $imc_meta_desejado * ($altura_metros * $altura_metros); 
    $peso_atual = $peso_kg; 
    
    // 3.4 Lógica da Ação Necessária  
    if ($peso_atual > $peso_meta) {
        $acao_necessaria = "Perder";
    } elseif ($peso_atual < $peso_meta) {
        $acao_necessaria = "Ganhar";
    } else {
        $acao_necessaria = "Manter";
    }
    
    // 3.5 Execução do Cálculo de GET 
    $tmb_altura_cm = ($altura_metros < 3) ? ($altura_metros * 100) : $altura_metros;
    $tmb = calcularTMB($peso_kg, $tmb_altura_cm, $idade, $sexo);
    $gasto_energetico_total = calcularGET($tmb, $frequencia);
    
    // 3.6 Lógica de Meta Calórica  
    if ($acao_necessaria == "Perder") {
        $meta_calorica_diaria = $gasto_energetico_total - $deficit_calorico_alvo;
    } elseif ($acao_necessaria == "Ganhar") {
        $meta_calorica_diaria = $gasto_energetico_total + 300; 
    } else {
        $meta_calorica_diaria = $gasto_energetico_total;
    }
    
    $nota_referencia_calorica = "Nota: O valor de 2.000 kcal é a referência diária média padrão utilizada pelos órgãos de saúde e rotulagem, mas sua meta é calculada individualmente.";
    
    // 3.7 Análise Nutricional  
    $diferenca_calorica = $calorias_ingeridas_total - $meta_calorica_diaria;

    if ($diferenca_calorica < -100) { 
        $status_calorico = "Baixa Ingestão";
        $classe_status_nutricao = "status-alerta";
        $mensagem_status_nutricao = "Você está comendo **" . number_format(abs($diferenca_calorica), 0) . " kcal a menos** que a meta. Ideal para déficit, mas cuidado para não ser muito restritivo!";
    } elseif ($diferenca_calorica > 100) { 
        $status_calorico = "Ingestão Alta";
        $classe_status_nutricao = "status-mau"; 
        $mensagem_status_nutricao = "Você consumiu **" . number_format(abs($diferenca_calorica), 0) . " kcal a mais** que a meta diária. Cuidado, isso pode dificultar a meta de peso.";
    } else { 
        $status_calorico = "Meta Atingida";
        $classe_status_nutricao = "status-sucesso";
        $mensagem_status_nutricao = "Parabéns! Sua ingestão calórica de **" . number_format($calorias_ingeridas_total, 0, ',', '.') . " kcal** está dentro da margem da sua meta.";
    }
    
    // =========================================================
    // 4. CÁLCULO DE MÉDIA DIÁRIA (DIVIDIDO POR 7)  
    // =========================================================
    // Garante que não haja divisão por zero
    if ($dias_para_dividir > 0) {
        $calorias_media_diaria = $calorias_ingeridas_total / $dias_para_dividir;
        $carboidratos_media_diaria = $carboidratos_total / $dias_para_dividir;
        $proteina_media_diaria = $proteina_total / $dias_para_dividir;
        $gordura_media_diaria = $gordura_total / $dias_para_dividir;
        $acucar_media_diaria = $acucar_total / $dias_para_dividir;
    }

} // Fim do if ($dados_usuario)

// Fechar conexões
if (isset($conn_user)) $conn_user->close();
if (isset($conn_alimentos)) $conn_alimentos->close();

// Variáveis de peso de acompanhamento semanal 
$peso_inicial_semana = 0.0; 
$meta_peso_semanal_absoluto = 0.0; 
$peso_perdido_ganho_real = 0.0; 
$progresso_semanal_mensagem = "Acompanhamento de peso futuro."; 
$classe_status_progresso = "status-neutro"; 
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Histórico Semanal</title>
    <link rel="stylesheet" href="Historicosemanal.css">
    <style>
        /* CSS Básico */
        .resultado-nutricional {
            margin-top: 40px;
            padding: 20px;
            border-radius: 8px;
            background-color: #e8f5e9; 
        }
        .macros-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        .macros-table th, .macros-table td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
        }
        .macros-table th {
            background-color: #4CAF50;
            color: white;
        }
        .total-kcal-box {
            font-size: 1.5em;
            font-weight: bold;
            padding: 15px;
            margin-top: 20px;
            border-radius: 6px;
            text-align: center;
        }
        .status-mau {
            background-color: #ffcdd2;
            color: #c62828;
            border: 2px solid #e57373;
        }
        .status-sucesso {
            background-color: #c8e6c9;
            color: #2e7d32;
            border: 2px solid #81c784;
        }
        .status-alerta {
             background-color: #fff9c4;
            color: #fbc02d;
            border: 2px solid #ffeb3b;
        }
        .status-neutro {
            background-color: #e3f2fd;
            color: #1976d2;
            border: 2px solid #90caf9;
        }
        .media-estimada {
            padding: 20px;
            border: 1px dashed #4CAF50;
            border-radius: 8px;
            margin-top: 30px;
        }
    </style>
</head>
<body>

    <div class="header">
        <img src="Images/alimentacao-saudavel (1).png" alt="Ícone de Histórico" class="header-icon">
        <h1>Seu Histórico e Progresso Semanal</h1>
    </div>

    <div class="container">
        <aside class="sidebar">
            <nav>
                <a href="Cadastrousuario.html" class="menu-item">Cadastro Usuário</a>
                <a href="CadastroAlimento.html" class="menu-item">Cadastrar Alimentos</a>
                <a href="alimentossalvos.php" class="menu-item" style="white-space: nowrap;">Alimentos Cadastrados</a>
                <a href="meta.php" class="menu-item">Meta</a>
                <a href="Perfil.php" class="menu-item">Perfil</a>
                <a href="HistoricoSemanal.php" class="menu-item active">Histórico semanal</a>
            </nav>
        </aside>

        <main class="content">
            <div class="resultado-container historico-container">
                <h2>Acompanhamento Semanal de Peso</h2>

                <?php if ($conexao_falhou): ?>
                    <div class="status-erro">
                        <h3 class="titulo-erro">Erro de Conexão com o Banco de Dados</h3>
                        <p>Não foi possível conectar ao banco de dados.</p>
                    </div>

                <?php elseif (!$dados_usuario): ?>
                    <div class="no-data-message form-info">
                        <p>Não encontramos seus dados cadastrados.</p>
                        <p>Por favor, realize o **<a href='Cadastrousuario.html'>Cadastro de Usuário</a>**.</p>
                    </div>

                <?php else: ?>
                    <p class="form-info">
                        Meta de longo prazo: **<?php echo $acao_necessaria; ?>** peso.
                        Seu peso atual: **<?php echo number_format($peso_atual, 1); ?> kg** |
                        Meta de peso (IMC 22.0): **<?php echo number_format($peso_meta, 1); ?> kg**
                    </p>
                    
                    <div class="meta-status <?php echo $classe_status_progresso; ?>" style="margin-top: 10px;">
                        <?php echo $progresso_semanal_mensagem; ?>
                    </div>
                
                    <hr>

                    <div class="resultado-nutricional">
                        <h2>Resumo Nutricional (Total Cadastrado)</h2>

                        <p>Meta Calculada para **<?php echo $acao_necessaria; ?>** peso:</p>
                        <p class="total-kcal-box status-neutro">
                            <?php echo number_format($meta_calorica_diaria, 0, ',', '.'); ?> Kcal por dia (Meta)
                        </p>
                        
                        <p class="instrucao-importante" style="text-align: center; font-size: 0.9em; margin-top: 10px;">
                            *<?php echo $nota_referencia_calorica; ?>*
                        </p>

                        <div class="total-kcal-box <?php echo $classe_status_nutricao; ?>">
                            Total de Calorias Ingeridas: 
                            <span style="font-size: 1.2em;"><?php echo number_format($calorias_ingeridas_total, 0, ',', '.'); ?> kcal</span>
                        </div>
                        
                        <p style="text-align: center; margin-top: 15px;">
                            **Status:** *<?php echo $mensagem_status_nutricao; ?>*
                        </p>

                        <h3 style="margin-top: 25px;">Distribuição Total de Macronutrientes</h3>
                        <table class="macros-table">
                            <thead>
                                <tr>
                                    <th>Nutriente</th>
                                    <th>Total Consumido (g)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Carboidratos</td>
                                    <td><?php echo number_format($carboidratos_total, 1, ',', '.'); ?> g</td>
                                </tr>
                                <tr>
                                    <td>Proteína</td>
                                    <td><?php echo number_format($proteina_total, 1, ',', '.'); ?> g</td>
                                </tr>
                                <tr>
                                    <td>Gordura</td>
                                    <td><?php echo number_format($gordura_total, 1, ',', '.'); ?> g</td>
                                </tr>
                                <tr>
                                    <td>Açúcar</td>
                                    <td><?php echo number_format($acucar_total, 1, ',', '.'); ?> g</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="resultado-container media-estimada">
                        <h2>📈 Média Diária Estimada (Total dividido por <?php echo $dias_para_dividir; ?> dias)</h2>
                        
                        <div class="total-kcal-box status-neutro" style="font-size: 1.3em;">
                            Média de Calorias por Dia:
                            <span style="font-size: 1.1em;"><?php echo number_format($calorias_media_diaria, 0, ',', '.'); ?> kcal</span>
                        </div>

                        <p class="instrucao-importante" style="margin-top: 15px;">
                            **Atenção:** Este é um valor de **média simples**. 
                            Todos os alimentos cadastrados foram divididos por **<?php echo $dias_para_dividir; ?>** para estimar sua ingestão diária.
                        </p>

                        <h3 style="margin-top: 25px;">Média de Macronutrientes por Dia</h3>
                        <table class="macros-table">
                            <thead>
                                <tr>
                                    <th>Nutriente</th>
                                    <th>Média Diária (g)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Carboidratos</td>
                                    <td><?php echo number_format($carboidratos_media_diaria, 1, ',', '.'); ?> g</td>
                                </tr>
                                <tr>
                                    <td>Proteína</td>
                                    <td><?php echo number_format($proteina_media_diaria, 1, ',', '.'); ?> g</td>
                                </tr>
                                <tr>
                                    <td>Gordura</td>
                                    <td><?php echo number_format($gordura_media_diaria, 1, ',', '.'); ?> g</td>
                                </tr>
                                <tr>
                                    <td>Açúcar</td>
                                    <td><?php echo number_format($acucar_media_diaria, 1, ',', '.'); ?> g</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
            </div>
            
        </main>
    </div>

</body>
</html>