<?php
session_start();

// Configurações do banco de dados
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "wb";

// Conexão com o banco de dados
$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['user_id'])) {
    $user_id = $_POST['user_id'];
    
    // Verifica se não está tentando excluir o próprio usuário logado
    if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $user_id) {
        header("Location: index.php?error=self_delete");
        exit();
    }
    
    // Verifica se é o último usuário admin
    $stmt = $conn->prepare("SELECT user_level FROM wblogin WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    
    if ($user['user_level'] == 'admin') {
        $stmt = $conn->prepare("SELECT COUNT(*) as admin_count FROM wblogin WHERE user_level = 'admin'");
        $stmt->execute();
        $result = $stmt->get_result();
        $count = $result->fetch_assoc();
        
        if ($count['admin_count'] <= 1) {
            header("Location: addteam.php");
            exit();
        }
    }
    
    // Exclui o usuário
    $stmt = $conn->prepare("DELETE FROM wblogin WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    
    if ($stmt->execute()) {
        header("Location: addteam.php");
    } else {
        header("Location: addteam.php");
    }
} else {
    header("Location: index.php");
}

$conn->close();
?> 