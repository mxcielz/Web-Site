<?php
// Inicia a sessão
session_start();

// Destrói todas as variáveis de sessão
session_unset();

// Destrói a sessão
session_destroy();

// Redireciona para a página de login ou página inicial
header("Location: ../index.html"); // Altere para o caminho correto da sua página de login
exit();
?>
