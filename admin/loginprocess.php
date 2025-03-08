<?php
// Iniciar a sessão
session_start();

// Verificar se os campos foram preenchidos
if (!isset($_POST['username']) || !isset($_POST['password'])) {
    header("Location: login.php?erro=campos");
    exit;
}

// Obter os valores do formulário
$username = $_POST['username'];
$password = $_POST['password'];

// Conectar ao banco de dados
$conexao = new mysqli("localhost", "root", "", "wb");

// Verificar conexão
if ($conexao->connect_error) {
    die("Erro de conexão: " . $conexao->connect_error);
}

// Usar prepared statements para evitar SQL Injection
$sql = "SELECT id, username, password FROM wblogin WHERE username = ?";
$stmt = $conexao->prepare($sql);
$stmt->bind_param("s", $username);
$stmt->execute();
$resultado = $stmt->get_result();

// Verificar se o usuário foi encontrado
if ($resultado->num_rows > 0) {
    $dados_tratados = $resultado->fetch_assoc();
    $hashArmazenado = $dados_tratados['password']; // Senha criptografada salva no banco

    // Verificar se a senha digitada corresponde ao hash armazenado
    if (password_verify($password, $hashArmazenado)) {
        // Login bem-sucedido: armazenar dados do usuário na sessão
        $_SESSION['user_id'] = $dados_tratados['id'];
        $_SESSION['username'] = $dados_tratados['username'];

        // Redirecionar para a página de administração
        header("Location: admin.php");
        exit;
    } else {
        // Senha incorreta
        header("Location: index.php?erro=senha_incorreta");
        exit;
    }
} else {
    // Usuário não encontrado
    header("Location: index.php?erro=senha_incorreta");
    exit;
}

// Fechar conexão
$stmt->close();
$conexao->close();
?>
