<?php
session_start();

// Verifica se o usuário está logado
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Usuário não autenticado']);
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
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Falha na conexão com o banco de dados']);
    exit();
}

// Verifica se o ID da tarefa foi enviado
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $id = $_GET['id'];
    
    // Prepara e executa a consulta SQL para buscar a tarefa
    $sql = "SELECT * FROM tasks WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Retorna os dados da tarefa em formato JSON
        $task = $result->fetch_assoc();
        header('Content-Type: application/json');
        echo json_encode($task);
    } else {
        // Tarefa não encontrada
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Tarefa não encontrada']);
    }
    
    $stmt->close();
} else {
    // ID da tarefa não fornecido
    header('Content-Type: application/json');
    echo json_encode(['error' => 'ID da tarefa não fornecido']);
}

$conn->close();
?> 