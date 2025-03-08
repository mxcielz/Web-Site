<?php
session_start();

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "wb";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['curriculum_id'])) {
    $curriculum_id = $_POST['curriculum_id'];
    
    // First, get the curriculum details for the PDF file
    $sql_select = "SELECT curriculo_pdf FROM curriculos WHERE id = ?";
    $stmt_select = $conn->prepare($sql_select);
    $stmt_select->bind_param("i", $curriculum_id);
    $stmt_select->execute();
    $result = $stmt_select->get_result();
    $curriculum = $result->fetch_assoc();
    
    // Delete the curriculum from database
    $sql_delete = "DELETE FROM curriculos WHERE id = ?";
    $stmt_delete = $conn->prepare($sql_delete);
    $stmt_delete->bind_param("i", $curriculum_id);
    
    if ($stmt_delete->execute()) {
        // If there's a PDF file, delete it from the server
        if (!empty($curriculum['curriculo_pdf'])) {
            $pdf_path = 'curriculos/' . $curriculum['curriculo_pdf'];
            if (file_exists($pdf_path)) {
                unlink($pdf_path);
            }
        }
        
        $_SESSION['notification'] = [
            'type' => 'success',
            'message' => 'Currículo excluído com sucesso!'
        ];
    } else {
        $_SESSION['notification'] = [
            'type' => 'error',
            'message' => 'Erro ao excluir o currículo.'
        ];
    }
    
    $stmt_delete->close();
}

header("Location: curriculum.php");
exit();
?>