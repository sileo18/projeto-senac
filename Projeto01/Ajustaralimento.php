<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

include 'conexao.php';

// --- Se for POST (Salvar Ajustes)  ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = filter_input(INPUT_POST, 'id', FILTER_SANITIZE_NUMBER_INT);
    $nome = filter_input(INPUT_POST, 'nome', FILTER_SANITIZE_SPECIAL_CHARS);
    
    // Converte vírgula para ponto para o banco de dados
    $calorias = str_replace(',', '.', $_POST['calorias']);
    $carboidratos = str_replace(',', '.', $_POST['carboidratos']);
    $proteina = str_replace(',', '.', $_POST['proteina']);
    $gordura = str_replace(',', '.', $_POST['gordura']);
    $acucar = str_replace(',', '.', $_POST['acucar']);

    if (!$id || !$nome) {
        header("Location: alimentossalvos.php?status=erro");
        exit;
    }

    $sql = "UPDATE alimentos 
            SET nome=?, calorias=?, carboidratos=?, proteina=?, gordura=?, acucar=? 
            WHERE id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sdddddi", $nome, $calorias, $carboidratos, $proteina, $gordura, $acucar, $id);

    if ($stmt->execute()) {
        header("Location: alimentossalvos.php?status=sucesso_edicao"); // Status específico para edição
    } else {
        header("Location: alimentossalvos.php?status=erro_atualizar");
    }

    exit;
}

// --- Se for GET (Abrir formulário para edição) ---
$id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);
if (!$id) {
    header("Location: alimentossalvos.php?status=erro_id");
    exit;
}

$sql = "SELECT * FROM alimentos WHERE id=?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Alimento não encontrado.");
}

$dado = $result->fetch_assoc();

// Carregar HTML e substituir
$html = file_get_contents("AjustarAlimento.html");

$html = str_replace("VALOR_ID_ATUAL", $dado['id'], $html);
$html = str_replace("VALOR_NOME_ATUAL", htmlspecialchars($dado['nome']), $html);
$html = str_replace("VALOR_CALORIAS_ATUAL", $dado['calorias'], $html);
$html = str_replace("VALOR_CARBO_ATUAL", $dado['carboidratos'], $html);
$html = str_replace("VALOR_PROTEINA_ATUAL", $dado['proteina'], $html);
$html = str_replace("VALOR_GORDURA_ATUAL", $dado['gordura'], $html);
$html = str_replace("VALOR_ACUCAR_ATUAL", $dado['acucar'], $html);

echo $html;
?>