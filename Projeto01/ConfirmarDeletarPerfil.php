<?php
// =========================================================
// ConfirmarDeletarPerfil.php
// Página para confirmar a exclusão do perfil do usuário
// =========================================================

// 1. INICIAR SESSÃO
session_start();

// 2. VERIFICAR SE O USUÁRIO ESTÁ LOGADO
if (!isset($_SESSION['user_id'])) {
    header("Location: Perfil.php");
    exit();
}

// 3. PEGAR O ID DO USUÁRIO (opcional, para exibir ou depurar)
$id_usuario = $_SESSION['user_id'];
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmar Exclusão de Perfil</title>
    <link rel="stylesheet" href="Perfil.css">
    <style>
        /* ============================
           ESTILOS BÁSICOS DA PÁGINA
        ============================ */
        body {
            font-family: Arial, Helvetica, sans-serif;
            background-color: #f9f9f9;
            margin: 0;
            padding: 0;
        }

        .container {
            max-width: 600px;
            background-color: #fff;
            margin: 80px auto;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            text-align: center;
        }

        h2 {
            color: #333;
            margin-bottom: 15px;
        }

        p {
            color: #555;
            margin-bottom: 30px;
        }

        .btn-confirmar, .btn-cancelar {
            display: inline-block;
            text-decoration: none;
            font-size: 16px;
            font-weight: bold;
            border-radius: 6px;
            padding: 10px 20px;
            cursor: pointer;
            transition: 0.3s;
        }

        .btn-confirmar {
            background-color: #e53935;
            color: white;
            border: none;
        }

        .btn-confirmar:hover {
            background-color: #c62828;
        }

        .btn-cancelar {
            background-color: #9e9e9e;
            color: white;
            margin-left: 10px;
        }

        .btn-cancelar:hover {
            background-color: #757575;
        }
    </style>
</head>
<body>

    <div class="container">
        <h2>Confirmar Exclusão de Perfil</h2>
        <p>Tem certeza de que deseja <strong>excluir seu perfil</strong>?<br>
        Esta ação é permanente e todos os seus dados serão apagados.</p>

        <form action="ProcessoDeletarPerfil.php" method="POST">
            <input type="hidden" name="confirmacao_exclusao" value="sim">
            <button type="submit" class="btn-confirmar">Sim, deletar meu perfil</button>
            <a href="Perfil.php" class="btn-cancelar">Cancelar</a>
        </form>
    </div>

</body>
</html>
