<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acesso Não Autorizado</title>
    <style>
        .unauthorized-message {
            text-align: center;
            padding: 50px;
            color: #721c24;
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            border-radius: 5px;
            margin: 50px auto;
            max-width: 500px;
        }
    </style>
</head>
<body>
    <div class="unauthorized-message">
        <h1>Acesso Não Autorizado</h1>
        <p>Você não tem permissão para acessar esta página.</p>
        <p><a href="index.php">Voltar para a página inicial</a></p>
    </div>
</body>
</html>