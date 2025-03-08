<?php
// Conexão com o banco de dados
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "wb";

// Cria a conexão
$conn = new mysqli($servername, $username, $password, $dbname);

// Verifica a conexão
if ($conn->connect_error) {
    die("Falha na conexão: " . $conn->connect_error);
}

// Obtém os dados do formulário
$company_name = $_POST['company_name'];
$contact_name = $_POST['contact_name'];
$email = $_POST['email'];
$phone = $_POST['phone'];
$message = $_POST['message'];

// Prepara e executa a consulta SQL
$sql = "INSERT INTO contact_messages (company_name, contact_name, email, phone, message) VALUES (?, ?, ?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("sssss", $company_name, $contact_name, $email, $phone, $message);

if ($stmt->execute()) {
    echo "Mensagem enviada com sucesso!";
    header("Location: ../../index.html");
} else {
    echo "Erro: " . $sql . "<br>" . $conn->error;
}

$stmt->close();
$conn->close();
?>
