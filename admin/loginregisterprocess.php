<?php
session_start();
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "wb";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nome = trim($_POST['nome']);
    $email = trim(filter_var($_POST['email'], FILTER_SANITIZE_EMAIL));
    $senha = trim($_POST['senha']);
    $cargo = trim($_POST['cargo']);
    $cpf = preg_replace("/[^0-9]/", "", $_POST['cpf']);

    if (empty($nome) || empty($email) || empty($senha) || empty($cargo) || empty($cpf)) {
        $_SESSION['error'] = "Todos os campos são obrigatórios.";
        header("Location: loginregister.php");
        exit();
    }

    // Verifica se o CPF já existe no banco
    $stmt = $conn->prepare("SELECT id FROM solicitacoes_acesso WHERE cpf = ?");
    $stmt->bind_param("s", $cpf);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $_SESSION['error'] = "Este CPF já está cadastrado.";
        $stmt->close();
        $conn->close();
        header("Location: loginregister.php");
        exit();
    }
    $stmt->close();

    // Verifica se o email já existe no banco
    $stmt = $conn->prepare("SELECT id FROM solicitacoes_acesso WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $_SESSION['error'] = "Este e-mail já está cadastrado.";
        $stmt->close();
        $conn->close();
        header("Location: loginregister.php");
        exit();
    }
    $stmt->close();

    // Insere os dados no banco
    $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("INSERT INTO solicitacoes_acesso (nome, email, cpf, cargo, senha, status) VALUES (?, ?, ?, ?, ?, 'pendente')");
    $stmt->bind_param("sssss", $nome, $email, $cpf, $cargo, $senha_hash);

    if ($stmt->execute()) {
        $_SESSION['success'] = "Solicitação enviada com sucesso! Aguarde a aprovação do administrador.";
    } else {
        $_SESSION['error'] = "Erro ao enviar solicitação.";
    }

    $stmt->close();
    $conn->close();
    header("Location: loginregister.php");
    exit();
}
?>
