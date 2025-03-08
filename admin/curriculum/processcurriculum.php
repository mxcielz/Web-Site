<?php
// Iniciar a sessão para mensagens de feedback
session_start();

// Definir constantes para configuração
define('UPLOAD_DIR', 'uploads/curriculos/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB

// Função para validar e sanitizar dados
function validar_dados($dados) {
    $erros = [];
    
    // Validar nome (não vazio e apenas letras, espaços e acentos)
    if (empty($dados['nome'])) {
        $erros[] = "O nome é obrigatório.";
    } elseif (!preg_match("/^[a-zA-ZÀ-ÿ\s]+$/u", $dados['nome'])) {
        $erros[] = "O nome deve conter apenas letras e espaços.";
    }
    
    // Validar email
    if (empty($dados['email'])) {
        $erros[] = "O email é obrigatório.";
    } elseif (!filter_var($dados['email'], FILTER_VALIDATE_EMAIL)) {
        $erros[] = "O email informado é inválido.";
    }
    
    // Validar telefone (apenas números, parênteses, traços e espaços)
    if (empty($dados['telefone'])) {
        $erros[] = "O telefone é obrigatório.";
    } elseif (!preg_match("/^[0-9()\-\s]+$/", $dados['telefone'])) {
        $erros[] = "O telefone deve conter apenas números, parênteses, traços e espaços.";
    }
    
    // Validar cargo de interesse
    if (empty($dados['cargo_interesse'])) {
        $erros[] = "O cargo de interesse é obrigatório.";
    }
    
    // Validar LinkedIn (se fornecido)
    if (!empty($dados['linkedin']) && !filter_var($dados['linkedin'], FILTER_VALIDATE_URL)) {
        $erros[] = "O link do LinkedIn informado é inválido.";
    }
    
    // Validar campos de texto obrigatórios
    if (empty($dados['resumo_profissional'])) {
        $erros[] = "O resumo profissional é obrigatório.";
    }
    
    if (empty($dados['experiencia'])) {
        $erros[] = "A experiência profissional é obrigatória.";
    }
    
    if (empty($dados['formacao'])) {
        $erros[] = "A formação acadêmica é obrigatória.";
    }
    
    return $erros;
}


// Função para processar upload de arquivo
function processar_upload($arquivo) {
    // Verificar se houve erro no upload
    if ($arquivo['error'] !== UPLOAD_ERR_OK) {
        switch ($arquivo['error']) {
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                return ['status' => false, 'mensagem' => 'O arquivo excede o tamanho máximo permitido.'];
            case UPLOAD_ERR_PARTIAL:
                return ['status' => false, 'mensagem' => 'O upload do arquivo foi feito parcialmente.'];
            case UPLOAD_ERR_NO_FILE:
                // Não é erro, apenas não enviou arquivo
                return ['status' => true, 'caminho' => ''];
            default:
                return ['status' => false, 'mensagem' => 'Erro desconhecido no upload.'];
        }
    }
    
    // Verificar tamanho do arquivo
    if ($arquivo['size'] > MAX_FILE_SIZE) {
        return ['status' => false, 'mensagem' => 'O arquivo excede o tamanho máximo de 5MB.'];
    }
    
    // Verificar tipo do arquivo
    $file_type = strtolower(pathinfo($arquivo['name'], PATHINFO_EXTENSION));
    if ($file_type != "pdf") {
        return ['status' => false, 'mensagem' => 'Apenas arquivos PDF são permitidos.'];
    }
    
    // Criar diretório se não existir
    if (!file_exists(UPLOAD_DIR)) {
        if (!mkdir(UPLOAD_DIR, 0777, true)) {
            return ['status' => false, 'mensagem' => 'Erro ao criar diretório para upload.'];
        }
    }
    
    // Gerar nome único para o arquivo
    $file_name = time() . '_' . uniqid() . '_' . basename($arquivo['name']);
    $file_name = preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', $file_name); // Remover caracteres especiais
    $target_file = UPLOAD_DIR . $file_name;
    
    // Tentar fazer o upload
    if (move_uploaded_file($arquivo['tmp_name'], $target_file)) {
        return ['status' => true, 'caminho' => $target_file];
    } else {
        return ['status' => false, 'mensagem' => 'Erro ao fazer upload do arquivo.'];
    }
}

// Função para registrar log de erros
function registrar_log($mensagem) {
    $log_file = 'logs/curriculos_' . date('Y-m-d') . '.log';
    $log_dir = dirname($log_file);
    
    if (!file_exists($log_dir)) {
        mkdir($log_dir, 0777, true);
    }
    
    $timestamp = date('Y-m-d H:i:s');
    $log_entry = "[$timestamp] $mensagem" . PHP_EOL;
    
    file_put_contents($log_file, $log_entry, FILE_APPEND);
}

// Conexão com o banco de dados
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "wb";

try {
    $conn = new mysqli($servername, $username, $password, $dbname);
    
    // Verificar conexão
    if ($conn->connect_error) {
        throw new Exception("Falha na conexão com o banco de dados: " . $conn->connect_error);
    }
    
    // Verificar se a tabela existe, se não, criá-la
    $check_table = "SHOW TABLES LIKE 'curriculos'";
    $result = $conn->query($check_table);
    
    if ($result->num_rows == 0) {
        // Criar a tabela conforme especificado
        $create_table = "CREATE TABLE curriculos (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nome VARCHAR(100) NOT NULL,
            email VARCHAR(100) NOT NULL,
            telefone VARCHAR(20) NOT NULL,
            cargo_interesse VARCHAR(100) NOT NULL,
            linkedin VARCHAR(255),
            mensagem TEXT,
            resumo_profissional TEXT NOT NULL,
            experiencia TEXT NOT NULL,
            formacao TEXT NOT NULL,
            curriculo_pdf VARCHAR(255),
            data_envio TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        
        if (!$conn->query($create_table)) {
            throw new Exception("Erro ao criar tabela: " . $conn->error);
        }
    }
    
    // Verificar se o formulário foi enviado
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        // Coletar e sanitizar dados do formulário
        $dados = [
            'nome' => trim(strip_tags($_POST['nome'])),
            'email' => trim(strip_tags($_POST['email'])),
            'telefone' => trim(strip_tags($_POST['telefone'])),
            'cargo_interesse' => trim(strip_tags($_POST['cargo_interesse'])),
            'linkedin' => isset($_POST['linkedin']) ? trim(strip_tags($_POST['linkedin'])) : '',
            'mensagem' => isset($_POST['mensagem']) ? trim(strip_tags($_POST['mensagem'])) : '',
            'resumo_profissional' => trim(strip_tags($_POST['resumo_profissional'])),
            'experiencia' => trim(strip_tags($_POST['experiencia'])),
            'formacao' => trim(strip_tags($_POST['formacao']))
        ];
        
        // Validar dados
        $erros = validar_dados($dados);
        
        if (!empty($erros)) {
            // Se houver erros, armazenar na sessão e redirecionar de volta
            $_SESSION['error'] = implode('<br>', $erros);
            $_SESSION['form_data'] = $dados; // Manter os dados para preencher o formulário
            header("Location: ../..trabalhe.html");
            exit();
        }
        
        // Processar upload de arquivo PDF, se existir
        $curriculo_pdf = '';
        if (isset($_FILES['curriculo_pdf']) && $_FILES['curriculo_pdf']['error'] != UPLOAD_ERR_NO_FILE) {
            $upload_result = processar_upload($_FILES['curriculo_pdf']);
            
            if (!$upload_result['status']) {
                $_SESSION['error'] = $upload_result['mensagem'];
                $_SESSION['form_data'] = $dados;
                header("Location: ../..trabalhe.html");
                exit();
            }
            
            $curriculo_pdf = $upload_result['caminho'];
        }
        
        // Preparar e executar a query SQL
        $sql = "INSERT INTO curriculos (nome, email, telefone, cargo_interesse, linkedin, mensagem, 
                                       resumo_profissional, experiencia, formacao, curriculo_pdf) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($sql);
        
        if (!$stmt) {
            throw new Exception("Erro na preparação da consulta: " . $conn->error);
        }
        
        $stmt->bind_param("ssssssssss", $dados['nome'], $dados['email'], $dados['telefone'], 
                          $dados['cargo_interesse'], $dados['linkedin'], $dados['mensagem'], 
                          $dados['resumo_profissional'], $dados['experiencia'], $dados['formacao'], 
                          $curriculo_pdf);
        
        if ($stmt->execute()) {
            // Sucesso
            $_SESSION['success'] = "Currículo enviado com sucesso! Agradecemos o seu interesse em fazer parte da nossa equipe.";
            
            // Enviar email de confirmação (opcional)
            // enviar_email_confirmacao($dados['email'], $dados['nome']);
            
            header("Location: ../../index.html");
            exit();
        } else {
            throw new Exception("Erro ao inserir dados no banco: " . $stmt->error);
        }
        
        $stmt->close();
    } else {
        // Se alguém tentar acessar este arquivo diretamente
        header("Location: trabalhe.html");
        exit();
    }
} catch (Exception $e) {
    // Registrar erro no log
    registrar_log("ERRO: " . $e->getMessage());
    
    // Informar ao usuário
    $_SESSION['error'] = "Ocorreu um erro ao processar seu currículo. Por favor, tente novamente mais tarde ou entre em contato conosco.";
    
    if (isset($dados)) {
        $_SESSION['form_data'] = $dados;
    }
    
    header("Location: trabalhe.html");
    exit();
} finally {
    // Fechar conexão com o banco de dados
    if (isset($conn)) {
        $conn->close();
    }
}
?> 