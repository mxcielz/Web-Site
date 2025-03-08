<?php
session_start();

// Verifica se o usuário está logado
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit(json_encode(['error' => 'Não autorizado']));
}

// Conexão com o banco de dados
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "wb";

$conn = new mysqli($servername, $username, $password, $dbname);

// Verifica a conexão
if ($conn->connect_error) {
    http_response_code(500);
    exit(json_encode(['error' => 'Erro de conexão com o banco de dados']));
}

// Consulta para obter o número de tarefas pendentes
$sql_pending = "SELECT COUNT(*) as total_pending FROM tasks WHERE status = 'pendente'";
$result_pending = $conn->query($sql_pending);
$pending_count = $result_pending->fetch_assoc()['total_pending'];

// Consulta para obter as últimas 5 tarefas pendentes
$sql_recent = "SELECT t.*, w.username 
               FROM tasks t 
               LEFT JOIN wblogin w ON t.responsavel_id = w.id 
               WHERE t.status = 'pendente' 
               ORDER BY t.data_criacao DESC 
               LIMIT 5";
$result_recent = $conn->query($sql_recent);

$recent_tasks = [];
if ($result_recent->num_rows > 0) {
    while ($row = $result_recent->fetch_assoc()) {
        $recent_tasks[] = [
            'id' => $row['id'],
            'tarefa' => $row['tarefa'],
            'responsavel' => $row['username'],
            'data_criacao' => date('d/m/Y H:i', strtotime($row['data_criacao']))
        ];
    }
}

// Prepara a resposta
$response = [
    'pending_count' => $pending_count,
    'recent_tasks' => $recent_tasks
];

// Envia a resposta como JSON
header('Content-Type: application/json');
echo json_encode($response);

$conn->close();
?> 