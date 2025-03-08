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

// Verifica se o ID da tarefa foi enviado
if (isset($_POST['id']) && !empty($_POST['id'])) {
    $id = $_POST['id'];
    
    // Prepara e executa a consulta SQL para excluir a tarefa
    $sql = "DELETE FROM tasks WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        echo "Tarefa excluída com sucesso!";
    } else {
        echo "Erro ao excluir tarefa: " . $conn->error;
    }
    
    $stmt->close();
} else {
    echo "ID da tarefa não fornecido!";
}

$conn->close();
?> 