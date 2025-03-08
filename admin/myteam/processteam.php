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

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $cnpj = $_POST['cnpj'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT); // Hash da senha
    $user_level = $_POST['user_level'];

    // Verifica se o CNPJ já existe
    $stmt = $conn->prepare("SELECT id FROM wblogin WHERE cnpj = ?");
    $stmt->bind_param("s", $cnpj);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        header("Location: index.php?error=cnpj");
        exit();
    }

    // Verifica se o username já existe
    $stmt = $conn->prepare("SELECT id FROM wblogin WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        header("Location: index.php?error=username");
        exit();
    }

    // Verifica se o email já existe
    $stmt = $conn->prepare("SELECT id FROM wblogin WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        header("Location: ");
        exit();
    }

    // Insere o novo usuário
    $stmt = $conn->prepare("INSERT INTO wblogin (username, email, cnpj, password, user_level) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $username, $email, $cnpj, $password, $user_level);

    if ($stmt->execute()) {
        header("Location: myteam.php");
    } else {
        header("Location: myteam.php");
    }
    exit();
}

$conn->close();
?> 