<?php
session_start();

if (!isset($_POST['username']) || !isset($_POST['password'])) {
    header("Location: login.php?erro=campos");
    exit;
}

$username = $_POST['username'];
$password = $_POST['password'];

$conexao = new mysqli("localhost", "root", "", "wb");

if ($conexao->connect_error) {
    die("Erro de conexão: " . $conexao->connect_error);
}

$sql = "SELECT id, username, password FROM wblogin WHERE username = ?";
$stmt = $conexao->prepare($sql);
$stmt->bind_param("s", $username);
$stmt->execute();
$resultado = $stmt->get_result();

if ($resultado->num_rows > 0) {
    $dados_tratados = $resultado->fetch_assoc();
    $hashArmazenado = $dados_tratados['password']; 

    if (password_verify($password, $hashArmazenado)) {
        $_SESSION['user_id'] = $dados_tratados['id'];
        $_SESSION['username'] = $dados_tratados['username'];

        header("Location: admin.php");
        exit;
    } else {
        header("Location: index.php?erro=senha_incorreta");
        exit;
    }
} else {
    header("Location: index.php?erro=senha_incorreta");
    exit;
}

$stmt->close();
$conexao->close();
?>
