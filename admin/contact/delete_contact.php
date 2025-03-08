<?php
session_start();
// Conexão com o banco de dados
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "wb";

$conn = new mysqli($servername, $username, $password, $dbname);

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Usuário não autorizado']);
    exit;
}

// Verifica se o ID foi fornecido
if (!isset($_POST['message_id'])) {
    echo json_encode(['success' => false, 'message' => 'ID da mensagem não fornecido']);
    exit;
}


if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Erro na conexão com o banco de dados']);
    exit;
}

// Prepara e executa a query de deleção
$stmt = $conn->prepare("DELETE FROM contact_messages WHERE id = ?");
$stmt->bind_param("i", $_POST['message_id']);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Mensagem deletada com sucesso']);
} else {
    echo json_encode(['success' => false, 'message' => 'Erro ao deletar mensagem']);
}

$stmt->close();
$conn->close();