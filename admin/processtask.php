<?php
session_start();

// Verifica se o usuário está logado
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Conexão com o banco de dados
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "wb";

$conn = new mysqli($servername, $username, $password, $dbname);

// Verifica a conexão
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Verifica se o formulário foi enviado
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Obtém os dados do formulário
    $tarefa = $_POST['tarefa'];
    $responsavel_id = $_POST['responsavel_id'];
    $status = $_POST['status'];
    
    // Prepara e executa a consulta SQL para inserir a tarefa
    $sql = "INSERT INTO tasks (tarefa, responsavel_id, status) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sis", $tarefa, $responsavel_id, $status);
    
    if ($stmt->execute()) {
        // Redireciona de volta para a página admin com mensagem de sucesso
        header("Location: admin.php?success=1");
    } else {
        // Redireciona de volta para a página admin com mensagem de erro
        header("Location: admin.php?error=1");
    }
    
    $stmt->close();
} else {
    // Se o método não for POST, redireciona para a página admin
    header("Location: admin.php");
}

$conn->close();
?> 