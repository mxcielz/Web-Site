<?php
session_start([
    'cookie_lifetime' => 0, // Cookie de sessão expira quando o navegador é fechado
]);

// Conexão com o banco de dados
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "wb";

$conn = new mysqli($servername, $username, $password, $dbname);

// Verifica a conexão
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Verifica se o usuário está logado
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Verifica se o usuário existe no banco de dados e obtém o nível
$user_id = $_SESSION['user_id'];
$sql = "SELECT id, user_level FROM wblogin WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    header("Location: index.php");
    exit();
}

$user = $result->fetch_assoc();
$_SESSION['user_level'] = $user['user_level'];

// Atualiza o tempo do último acesso (se ainda estiver dentro do limite)
$_SESSION['ultimo_acesso'] = time();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Boxicons -->
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link href='https://unpkg.com/boxicons@2.1.4/dist/boxicons.js' rel='stylesheet'>

    <!-- My CSS -->
    <link rel="stylesheet" href="style.css">


    <title>WB - Admin</title>

</head>
<style>
/* Variáveis de cores *

/* Modo Escuro */
body.dark {
    --light: #0C0C1E;
    --grey: #060714;
    --dark: #FBFBFB;
}

/* Loader Styles */
#loader {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(255, 255, 255, 0.8); /* Fundo semitransparente */
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 9999; /* Garantir que o loader fique acima de outros elementos */
    transition: opacity 0.5s ease, visibility 0s 0.5s; /* Transição suave para desaparecer */
}

/* Estilo do spinner */
.spinner {
    border: 4px solid #f3f3f3; /* Cor de fundo */
    border-top: 4px solid #3498db; /* Cor da animação */
    border-radius: 50%;
    width: 50px;
    height: 50px;
    animation: spin 1s linear infinite;
}

/* Animação do spinner */
@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

#content {
    display: none; /* Esconde o conteúdo do site até que o loader termine */
}

.text {
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.money-container {
    display: flex;
    align-items: center;
    gap: 5px; /* Aumentei o espaço entre o valor e o olho */
}

/* Icone do toggle de visibilidade */
#toggle-icon {
    cursor: pointer;
    font-size: 16px; /* Reduzi de 20px para 16px */
    color: white;
    transition: color 0.3s ease-in-out;
}

#toggle-icon:hover {
    color: #1db954; /* Cor verde ao passar o mouse */
}

/* Controle da opacidade do valor */
#money-value {
    opacity: 0; /* Inicialmente escondido */
    transition: opacity 0.3s ease-in-out;
}

/* Modificação para modo escuro */
body.dark #loader {
    background: rgba(0, 0, 0, 0.9); /* Fundo mais escuro no modo escuro */
}

.profile {
    display: flex;
    align-items: center;
    cursor: pointer;
}

.profile img {
    width: 40px; /* Ajuste o tamanho da imagem */
    height: 40px;
    border-radius: 50%; /* Torna a imagem redonda */
    border: 2px solid #ddd; /* Borda sutil */
    transition: border-color 0.3s ease-in-out;
    border: 2px solidrgb(255, 0, 0); /* Borda azul por padrão */
}

.profile img:hover {
    border-color:rgb(219, 52, 52); /* Borda azul ao passar o mouse */
}

.profile-menu {
    display: none; /* Oculto por padrão */
    position: absolute;
    top: 50px; /* Ajuste conforme necessário */
    right: 0;
    background: white;
    border: 1px solid #ccc;
    padding: 10px;
    box-shadow: 2px 2px 5px rgba(0, 0, 0, 0.2);
    z-index: 1000;
}

.profile-menu.show {
    display: block; /* Torna visível quando a classe 'show' é adicionada */
}


.profile-menu ul {
    list-style: none;
    padding: 0;
    margin: 0;
}

.profile-menu ul li {
    padding: 10px;
    text-align: center;
}

.profile-menu ul li a {
    text-decoration: none;
    color: #333;
    display: block;
    transition: background 0.3s ease-in-out;
}

.profile-menu ul li a:hover {
    background: rgba(255, 255, 255, 0.1);
    border-radius: 8px;
}

/* Adicione estes estilos ao seu arquivo CSS existente */

.box-info {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    grid-gap: 24px;
    margin-top: 36px;
}

.box-info .box {
    padding: 24px;
    background: var(--light);
    border-radius: 20px;
    display: flex;
    align-items: center;
    grid-gap: 24px;
    transition: all 0.3s ease;
}

.box-info .box:hover {
    transform: translateY(-5px);
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
}

.box-info .box i {
    width: 80px;
    height: 80px;
    border-radius: 10px;
    font-size: 36px;
    display: flex;
    justify-content: center;
    align-items: center;
    background: var(--blue);
    color: var(--light);
}

.box-info .box:nth-child(2) i {
    background: var(--green);
}

.box-info .box:nth-child(3) i {
    background: var(--orange);
}

.box-info .box h3 {
    font-size: 24px;
    font-weight: 600;
    color: var(--dark);
}

.box-info .box p {
    color: var(--dark);
}

.status {
    padding: 6px 12px;
    border-radius: 20px;
    font-weight: 500;
}

.status.completed {
    background: var(--green);
    color: var(--light);
}

.status.process {
    background: var(--blue);
    color: var(--light);
}

.status.pending {
    background: var(--orange);
    color: var(--light);
}

.actions {
    display: flex;
    gap: 10px;
}

.actions i {
    cursor: pointer;
    font-size: 20px;
    transition: all 0.3s ease;
}

.actions i:hover {
    color: var(--blue);
}

/* Dark mode */
body.dark .box-info .box {
    background: var(--dark);
}

body.dark .box-info .box h3,
body.dark .box-info .box p {
    color: var(--light);
}

.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    overflow: auto;
    background-color: rgba(0,0,0,0.5);
    opacity: 0;
    transition: opacity 0.3s ease;
}

.modal.show {
    opacity: 1;
}

.modal-content {
    background-color: #fefefe;
    margin: 5% auto;
    padding: 25px;
    border: 1px solid #888;
    width: 50%;
    border-radius: 10px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.3);
    transform: translateY(-20px);
    opacity: 0;
    transition: all 0.3s ease;
}

.modal.show .modal-content {
    transform: translateY(0);
    opacity: 1;
}

.close {
    color: #aaa;
    float: right;
    font-size: 28px;
    font-weight: bold;
    transition: color 0.3s ease;
}

.close:hover,
.close:focus {
    color: #333;
    text-decoration: none;
    cursor: pointer;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 500;
}

.form-control {
    width: 100%;
    padding: 12px;
    border: 1px solid #ddd;
    border-radius: 5px;
    font-size: 14px;
    transition: border-color 0.3s ease, box-shadow 0.3s ease;
}

.form-control:focus {
    border-color: #3498db;
    box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.2);
    outline: none;
}

/* Estilos para os ícones de ação */
.actions i {
    cursor: pointer;
    font-size: 20px;
    transition: all 0.3s ease;
    padding: 5px;
    border-radius: 5px;
}

.actions i.bx-edit {
    color: #3498db;
}

.actions i.bx-trash {
    color: #e74c3c;
}

.actions i.bx-edit:hover {
    background-color: rgba(52, 152, 219, 0.1);
    transform: translateY(-2px);
}

.actions i.bx-trash:hover {
    background-color: rgba(231, 76, 60, 0.1);
    transform: translateY(-2px);
}

/* Animação para remoção de linha da tabela */
.row-delete {
    animation: fadeOutRow 0.5s ease forwards;
}

@keyframes fadeOutRow {
    0% {
        opacity: 1;
        transform: translateX(0);
    }
    100% {
        opacity: 0;
        transform: translateX(50px);
        height: 0;
        padding: 0;
        margin: 0;
    }
}

/* Estilos para o modo escuro */
body.dark .modal-content {
    background-color: var(--dark);
    color: var(--light);
    border-color: #444;
}

body.dark .close {
    color: #ddd;
}

body.dark .close:hover,
body.dark .close:focus {
    color: #fff;
}

body.dark .form-control {
    background-color: #30363d;
    color: var(--light);
    border-color: #555;
}

body.dark .form-control:focus {
    border-color: #3498db;
    box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.3);
}

body.dark .btn-primary {
    background-color: #3498db;
}

body.dark .btn-primary:hover {
    background-color: #2980b9;
}

/* Estilos para alertas */
.alert {
    padding: 15px;
    margin-bottom: 20px;
    border: 1px solid transparent;
    border-radius: 4px;
    position: relative;
    opacity: 1;
    transition: opacity 1s ease-in-out;
    animation: slideDown 0.5s ease-in-out;
}

@keyframes slideDown {
    0% {
        transform: translateY(-20px);
        opacity: 0;
    }
    100% {
        transform: translateY(0);
        opacity: 1;
    }
}

.alert-success {
    color: #155724;
    background-color: #d4edda;
    border-color: #c3e6cb;
}

.alert-danger {
    color: #721c24;
    background-color: #f8d7da;
    border-color: #f5c6cb;
}

/* Estilos para alertas no modo escuro */
body.dark .alert-success {
    color: #d4edda;
    background-color: #155724;
    border-color: #c3e6cb;
}

body.dark .alert-danger {
    color: #f8d7da;
    background-color: #721c24;
    border-color: #f5c6cb;
}

.table-data .head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    grid-gap: 16px;
    margin-bottom: 24px;
}

.table-data .head h3 {
    margin-right: auto;
    font-size: 24px;
    font-weight: 600;
}

.table-data .head .bx {
    cursor: pointer;
}

#addTaskBtn {
    display: flex;
    align-items: center;
    gap: 8px;
    background-color: #3498db;
    color: white;
    border: none;
    border-radius: 5px;
    padding: 8px 16px;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
}

#addTaskBtn:hover {
    background-color: #2980b9;
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
}

#addTaskBtn:active {
    transform: translateY(0);
    box-shadow: 0 2px 3px rgba(0, 0, 0, 0.1);
}

#addTaskBtn i {
    font-size: 18px;
}

.table-data .order {
    flex-grow: 1;
    flex-basis: 500px;
}

/* Estilos para o modo escuro */
body.dark .alert-success {
    color: #d4edda;
    background-color: #155724;
    border-color: #c3e6cb;
}

body.dark .alert-danger {
    color: #f8d7da;
    background-color: #721c24;
    border-color: #f5c6cb;
}

/* Estilos melhorados para botões */
.task-btn-primary {
    background-color: #3498db;
    color: white;
    padding: 10px 20px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 8px;
    font-weight: 500;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
}

.task-btn-primary:hover {
    background-color: #2980b9;
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
}

.task-btn-primary:active {
    transform: translateY(0);
    box-shadow: 0 2px 3px rgba(0, 0, 0, 0.1);
}

/* Estilos para a tabela de tarefas */
.task-table-data {
    display: flex;
    flex-wrap: wrap;
    grid-gap: 24px;
    margin-top: 24px;
    width: 100%;
    color: var(--dark);
}

.task-table-data > div {
    border-radius: 20px;
    background: var(--light);
    padding: 24px;
    overflow-x: auto;
}

.task-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    grid-gap: 16px;
    margin-bottom: 24px;
}

.task-head h3 {
    margin-right: auto;
    font-size: 24px;
    font-weight: 600;
}

.task-head .bx {
    cursor: pointer;
}

#addTaskBtn {
    display: flex;
    align-items: center;
    gap: 8px;
    background-color: #3498db;
    color: white;
    border: none;
    border-radius: 5px;
    padding: 8px 16px;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
}

#addTaskBtn:hover {
    background-color: #2980b9;
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
}

#addTaskBtn:active {
    transform: translateY(0);
    box-shadow: 0 2px 3px rgba(0, 0, 0, 0.1);
}

#addTaskBtn i {
    font-size: 18px;
}

.task-order {
    flex-grow: 1;
    flex-basis: 500px;
}

.task-table {
    width: 100%;
    border-collapse: collapse;
}

.task-table th {
    padding-bottom: 12px;
    font-size: 13px;
    text-align: left;
    border-bottom: 1px solid var(--grey);
}

.task-table td {
    padding: 16px 0;
}

.task-table tr td:first-child {
    display: flex;
    align-items: center;
    grid-gap: 12px;
    padding-left: 6px;
}

.task-table tbody tr:hover {
    background: var(--grey);
}

/* Estilos para status de tarefas */
.task-status {
    padding: 6px 12px;
    border-radius: 20px;
    font-weight: 500;
}

.task-completed {
    background: #4CAF50;
    color: white;
    padding: 5px 10px;
    border-radius: 15px;
    font-size: 0.9em;
}

.task-process {
    background: var(--blue);
    color: var(--light);
}

.task-pending {
    background: var(--orange);
    color: var(--light);
}

/* Estilos para os ícones de ação */
.task-actions {
    display: flex;
    gap: 10px;
}

.task-actions i {
    cursor: pointer;
    font-size: 20px;
    transition: all 0.3s ease;
    padding: 5px;
    border-radius: 5px;
}

.task-actions i.bx-edit {
    color: #3498db;
}

.task-actions i.bx-trash {
    color: #e74c3c;
}

.task-actions i.bx-edit:hover {
    background-color: rgba(52, 152, 219, 0.1);
    transform: translateY(-2px);
}

.task-actions i.bx-trash:hover {
    background-color: rgba(231, 76, 60, 0.1);
    transform: translateY(-2px);
}

/* Animação para remoção de linha da tabela */
.row-delete {
    animation: fadeOutRow 0.5s ease forwards;
}

@keyframes fadeOutRow {
    0% {
        opacity: 1;
        transform: translateX(0);
    }
    100% {
        opacity: 0;
        transform: translateX(50px);
        height: 0;
        padding: 0;
        margin: 0;
    }
}

/* Estilos para o modo escuro */
body.dark .task-table-data > div {
    background: var(--dark);
}

body.dark .task-table th,
body.dark .task-table td {
    color: var(--light);
}

body.dark .task-table tbody tr:hover {
    background: #30363d;
}

body.dark .modal-content {
    background-color: var(--dark);
    color: var(--light);
    border-color: #444;
}

body.dark .close {
    color: #ddd;
}

body.dark .close:hover,
body.dark .close:focus {
    color: #fff;
}

body.dark .form-control {
    background-color: #30363d;
    color: var(--light);
    border-color: #555;
}

body.dark .form-control:focus {
    border-color: #3498db;
    box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.3);
}

body.dark .task-btn-primary {
    background-color: #3498db;
}

body.dark .task-btn-primary:hover {
    background-color: #2980b9;
}

/* Estilos melhorados para o modal */
.task-modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    overflow: auto;
    background-color: rgba(0,0,0,0.5);
    opacity: 0;
    transition: opacity 0.3s ease;
}

.task-modal.show {
    opacity: 1;
}

.task-modal-content {
    background-color: #fefefe;
    margin: 5% auto;
    padding: 25px;
    border: 1px solid #888;
    width: 50%;
    border-radius: 10px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.3);
    transform: translateY(-20px);
    opacity: 0;
    transition: all 0.3s ease;
}

.task-modal.show .task-modal-content {
    transform: translateY(0);
    opacity: 1;
}

.task-close {
    color: #aaa;
    float: right;
    font-size: 28px;
    font-weight: bold;
    transition: color 0.3s ease;
}

.task-close:hover,
.task-close:focus {
    color: #333;
    text-decoration: none;
    cursor: pointer;
}

.task-form-group {
    margin-bottom: 20px;
}

.task-form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 500;
}

.task-form-control {
    width: 100%;
    padding: 12px;
    border: 1px solid #ddd;
    border-radius: 5px;
    font-size: 14px;
    transition: border-color 0.3s ease, box-shadow 0.3s ease;
}

.task-form-control:focus {
    border-color: #3498db;
    box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.2);
    outline: none;
}

/* Estilos para o modo escuro */
body.dark .task-modal-content {
    background-color: var(--dark);
    color: var(--light);
    border-color: #444;
}

body.dark .task-close {
    color: #ddd;
}

body.dark .task-close:hover,
body.dark .task-close:focus {
    color: #fff;
}

body.dark .task-form-control {
    background-color: #30363d;
    color: var(--light);
    border-color: #555;
}

body.dark .task-form-control:focus {
    border-color: #3498db;
    box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.3);
}

/* Estilos para o sistema de notificações */
.notification-menu {
    position: absolute;
    top: 60px;
    right: 60px;
    width: 350px;
    background: var(--light);
    border-radius: 10px;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
    display: none;
    z-index: 1000;
    max-height: 500px;
    overflow-y: auto;
    animation: slideDown 0.3s ease-out;
}

@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.notification-header {
    padding: 15px 20px;
    border-bottom: 1px solid var(--grey);
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: var(--blue);
    color: var(--light);
    border-radius: 10px 10px 0 0;
}

.notification-header h3 {
    margin: 0;
    font-size: 16px;
    font-weight: 600;
}

.notification-count {
    background: var(--red);
    color: white;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
}

.notification-content {
    padding: 0;
}

.notification-item {
    padding: 15px 20px;
    display: flex;
    align-items: flex-start;
    gap: 15px;
    border-bottom: 1px solid var(--grey);
    transition: all 0.3s ease;
    cursor: pointer;
    text-decoration: none;
    color: inherit;
}

.notification-item:hover {
    background: var(--grey);
    transform: translateX(5px);
}

.notification-icon {
    background: #e3f2fd;
    width: 40px;
    height: 40px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.notification-item:hover .notification-icon {
    transform: scale(1.1);
}

.notification-icon i {
    font-size: 20px;
    color: var(--blue);
}

.notification-info {
    flex: 1;
}

.notification-title {
    margin: 0 0 5px 0;
    font-weight: 600;
    font-size: 14px;
    color: var(--dark);
}

.notification-desc {
    margin: 0;
    font-size: 12px;
    color: #666;
    line-height: 1.4;
}

.notification-footer {
    padding: 15px 20px;
    text-align: center;
    border-top: 1px solid var(--grey);
    background: var(--light);
    border-radius: 0 0 10px 10px;
}

.notification-footer a {
    color: var(--blue);
    text-decoration: none;
    font-size: 14px;
    font-weight: 500;
    transition: all 0.3s ease;
}

.notification-footer a:hover {
    color: var(--dark-blue);
    text-decoration: underline;
}

.notification-empty {
    padding: 30px 20px;
    text-align: center;
    color: #666;
}

.notification-empty i {
    font-size: 40px;
    color: var(--green);
    margin-bottom: 10px;
    animation: bounce 2s infinite;
}

@keyframes bounce {
    0%, 20%, 50%, 80%, 100% {
        transform: translateY(0);
    }
    40% {
        transform: translateY(-10px);
    }
    60% {
        transform: translateY(-5px);
    }
}

/* Dark mode */
body.dark .notification-menu {
    background: var(--dark);
    border: 1px solid #444;
}

body.dark .notification-header {
    border-bottom-color: #444;
}

body.dark .notification-item {
    border-bottom-color: #444;
}

body.dark .notification-item:hover {
    background: #333;
}

body.dark .notification-title {
    color: var(--light);
}

body.dark .notification-desc {
    color: #999;
}

body.dark .notification-footer {
    background: #333;
    border-top-color: #444;
}

body.dark .notification-empty {
    color: #999;
}

/* Responsividade */
@media screen and (max-width: 768px) {
    .notification-menu {
        width: 90%;
        right: 5%;
    }
}

.analytics-container {
    padding: 20px;
}

.box-info {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    grid-gap: 20px;
    margin-bottom: 30px;
}

.box-info li {
    padding: 20px;
    background: var(--light);
    border-radius: 10px;
    display: flex;
    align-items: center;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

.box-info li i {
    font-size: 28px;
    width: 60px;
    height: 60px;
    border-radius: 10px;
    display: flex;
    justify-content: center;
    align-items: center;
    margin-right: 15px;
}

.box-info li:nth-child(1) i {
    background: #CFE8FF;
    color: #1976D2;
}

.box-info li:nth-child(2) i {
    background: #FFF2C6;
    color: #FFA000;
}

.box-info li:nth-child(3) i {
    background: #BBF7D0;
    color: #388E3C;
}

.box-info li:nth-child(4) i {
    background: #E7D9FF;
    color: #7B1FA2;
}

.box-info li:nth-child(5) i {
    background: #FFD6D6;
    color: #D32F2F;
}

.box-info li .text h3 {
    font-size: 24px;
    font-weight: 600;
    margin-bottom: 5px;
}

.box-info li .text p {
    font-size: 14px;
    color: var(--dark-grey);
}

.analytics-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
    grid-gap: 20px;
}

.analytics-card {
    background: var(--light);
    border-radius: 10px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    overflow: hidden;
}

.card-header {
    padding: 15px 20px;
    border-bottom: 1px solid #eee;
}

.card-header h3 {
    font-size: 18px;
    font-weight: 600;
}

.card-body {
    padding: 20px;
    position: relative;
}

</style>
<body>
<div id="loader">
        <div class="spinner"></div>
    </div>

  
	<section id="sidebar">
		<a href="./admin.php" class="brand">
        <i class='bx bxs-dashboard bx-sm' ></i>
		<span class="text">WB Manutenção</span>
		</a>
        <ul class="side-menu top">
            <li class="active">
                <a href="./admin.php">
                    <i class='bx bxs-dashboard bx-sm' ></i>
                    <span class="text">Dashboard</span>
                </a>
            </li>
            <?php if ($_SESSION['user_level'] === 'admin' || $_SESSION['user_level'] === 'rh'): ?>         
            <li>
                <a href="./curriculum/curriculum.php">
                    <i class='bx bxs-file-doc bx-sm'></i>
                    <span class="text">Curriculum</span>
                </a>
            </li>
            <?php endif; ?>
            <li>
                <a href="./analytics/analytics.php">
                    <i class='bx bxs-doughnut-chart bx-sm' ></i>
                    <span class="text">Analytics</span>
                </a>
            </li>
            <?php if ($_SESSION['user_level'] === 'admin' || $_SESSION['user_level'] === 'rh'): ?>         
            <li>
                <a href="./contact/message.php">
                    <i class='bx bxs-message-dots bx-sm' ></i>
                    <span class="text">Message</span>
                </a>
            </li>
            <?php endif; ?>
            <li>
                <a href="./myteam/myteam.php">
                    <i class='bx bxs-group bx-sm' ></i>
                    <span class="text">Team</span>
                </a>
            </li>
            <?php if ($_SESSION['user_level'] === 'admin'): ?>         
            <li>
                <a href="validationlogin.php">
                <i class='bx bxs-user-check bx-sm'></i>
                    <span class="text">Validation</span>
                </a>
            </li>
            <?php endif; ?>
        </ul>
        <ul class="side-menu bottom">
            <li>
                <a href="#">
                    <i class='bx bxs-cog bx-sm bx-spin-hover' ></i>
                    <span class="text">Settings</span>
                </a>
            </li>
            <li>
                <a href="logout.php" class="logout">
                    <i class='bx bx-power-off bx-sm bx-burst-hover' ></i>
                    <span class="text">Logout</span>
                </a>
            </li>
        </ul>
	</section>


<section id="content">
    <!-- NAVBAR -->
    <nav>
        <i class='bx bx-menu bx-sm'></i>
        <a href="#" class="nav-link">Categories</a>
        <form action="#">
            <div class="form-input">
                <input type="search" placeholder="Search...">
                <button type="submit" class="search-btn"><i class='bx bx-search'></i></button>
            </div>
        </form>
        <input type="checkbox" class="checkbox" id="switch-mode" hidden />
        <label class="swith-lm" for="switch-mode">
            <i class="bx bxs-moon"></i>
            <i class="bx bx-sun"></i>
            <div class="ball"></div>
        </label>

        <!-- Notification Bell -->
        <?php
        // Consulta para obter tarefas pendentes
        $sql_pending = "SELECT COUNT(*) as total_pending FROM tasks WHERE status = 'pendente'";
        $result_pending = $conn->query($sql_pending);
        $pending_count = $result_pending->fetch_assoc()['total_pending'];

        // Consulta para obter as últimas 5 tarefas pendentes
        $sql_recent_pending = "SELECT t.*, w.username 
                              FROM tasks t 
                              LEFT JOIN wblogin w ON t.responsavel_id = w.id 
                              WHERE t.status = 'pendente' 
                              ORDER BY t.data_criacao DESC 
                              LIMIT 5";
        $result_recent_pending = $conn->query($sql_recent_pending);
        ?>
        
        <a href="#" class="notification" id="notificationIcon">
            <i class='bx bxs-bell bx-tada-hover'></i>
            <?php if ($pending_count > 0): ?>
                <span class="num"><?php echo $pending_count; ?></span>
            <?php endif; ?>
        </a>
        <div class="notification-menu" id="notificationMenu">
            <div class="notification-header">
                <h3>Notificações</h3>
                <?php if ($pending_count > 0): ?>
                    <span class="notification-count"><?php echo $pending_count; ?> tarefas pendentes</span>
                <?php endif; ?>
            </div>
            <div class="notification-content">
                <?php if ($result_recent_pending && $result_recent_pending->num_rows > 0): ?>
                    <ul>
                        <?php while($task = $result_recent_pending->fetch_assoc()): ?>
                            <a href="./profile/profile.php" class="notification-item">
                                <div class="notification-icon">
                                    <i class='bx bx-time-five'></i>
                                </div>
                                <div class="notification-info">
                                    <p class="notification-title"><?php echo htmlspecialchars($task['tarefa']); ?></p>
                                    <p class="notification-desc">
                                        Responsável: <?php echo htmlspecialchars($task['username']); ?><br>
                                        Criada em: <?php echo date('d/m/Y H:i', strtotime($task['data_criacao'])); ?>
                                    </p>
                                </div>
                            </a>
                        <?php endwhile; ?>
                    </ul>
                    <?php if ($pending_count > 5): ?>
                        <div class="notification-footer">
                            <a href="#" onclick="scrollToTasks()">Ver todas as <?php echo $pending_count; ?> tarefas pendentes</a>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="notification-empty">
                        <i class='bx bx-check-circle'></i>
                        <p>Não há tarefas pendentes</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Profile Menu -->
        <a href="#" class="profile" id="profileIcon">
            <img src="imagem/logo.png" alt="Profile">
        </a>
        <div class="profile-menu" id="profileMenu">
            <ul>
                <li>
                    <a href="./profile/profile.php">
                        <i class='bx bx-user'></i>
                        Meu Perfil
                    </a>
                </li>
                <li>
                    <a href="#">
                        <i class='bx bx-cog'></i>
                        Configurações
                    </a>
                </li>
                <li>
                    <a href="logout.php">
                        <i class='bx bx-log-out'></i>
                        Sair
                    </a>
                </li>
            </ul>
        </div>
    </nav>

    <!-- NAVBAR -->

<!-- NAVBAR -->

<!-- Substitua o conteúdo dentro da tag <main> pelo seguinte código -->
<main>
    <div class="head-title">
        <div class="left">
            <h1>Dashboard</h1>
            <ul class="breadcrumb">
                <li><a href="#">Dashboard</a></li>
                <li><i class='bx bx-chevron-right'></i></li>
                <li><a class="active" href="#">Home</a></li>
            </ul>
        </div>
        <a href="#" class="btn-download">
            <i class='bx bxs-cloud-download bx-fade-down-hover'></i>
            <span class="text">Download Relatório</span>
        </a>
    </div>


    <!-- Cards do Dashboard -->
    <div class="analytics-container">
        <!-- Resumo Geral -->
        <div class="box-info">
            <?php
            // Função para executar consultas com tratamento de erro
            function executarConsulta($conn, $sql, $default = []) {
                try {
                    $result = $conn->query($sql);
                    if ($result && $result->num_rows > 0) {
                        return $result->fetch_assoc();
                    }
                    return $default;
                } catch (Exception $e) {
                    error_log("Erro na consulta: " . $e->getMessage());
                    return $default;
                }
            }
            
            // Verificar se a tabela existe antes de consultar
            function tabelaExiste($conn, $tabela) {
                $result = $conn->query("SHOW TABLES LIKE '$tabela'");
                return $result->num_rows > 0;
            }
            
            // Valores padrão
            $users_data = ['total' => 0, 'admin_count' => 0, 'func_count' => 0, 'rh_count' => 0, 'visit_count' => 0];
            $messages_data = ['total' => 0];
            $curriculos_data = ['total' => 0];
            $tasks_data = ['total' => 0, 'pending_count' => 0, 'progress_count' => 0, 'completed_count' => 0];
            
            // Contagem de usuários (se a tabela existir)
            if (tabelaExiste($conn, 'wblogin')) {
                $sql_users = "SELECT COUNT(*) as total, 
                              SUM(CASE WHEN user_level = 'admin' THEN 1 ELSE 0 END) as admin_count,
                              SUM(CASE WHEN user_level = 'funcionario' THEN 1 ELSE 0 END) as func_count,
                              SUM(CASE WHEN user_level = 'rh' THEN 1 ELSE 0 END) as rh_count,
                              SUM(CASE WHEN user_level = 'visitante' THEN 1 ELSE 0 END) as visit_count
                              FROM wblogin";
                $users_data = executarConsulta($conn, $sql_users, $users_data);
            }
            
            // Contagem de mensagens de contato (se a tabela existir)
            if (tabelaExiste($conn, 'contact_messages')) {
                $sql_messages = "SELECT COUNT(*) as total FROM contact_messages";
                $messages_data = executarConsulta($conn, $sql_messages, $messages_data);
            }
            
            // Contagem de currículos (se a tabela existir)
            if (tabelaExiste($conn, 'curriculos')) {
                $sql_curriculos = "SELECT COUNT(*) as total FROM curriculos";
                $curriculos_data = executarConsulta($conn, $sql_curriculos, $curriculos_data);
            }
            
            // Contagem de tarefas (se a tabela existir)
            if (tabelaExiste($conn, 'tasks')) {
                $sql_tasks = "SELECT COUNT(*) as total,
                              SUM(CASE WHEN status = 'pendente' THEN 1 ELSE 0 END) as pending_count,
                              SUM(CASE WHEN status = 'em_andamento' THEN 1 ELSE 0 END) as progress_count,
                              SUM(CASE WHEN status = 'concluido' THEN 1 ELSE 0 END) as completed_count
                              FROM tasks";
                $tasks_data = executarConsulta($conn, $sql_tasks, $tasks_data);
            }
            ?>
            
            <li>
                <i class='bx bxs-user bx-sm'></i>
                <span class="text">
                    <h3><?php echo $users_data['total']; ?></h3>
                    <p>Usuários</p>
                </span>
            </li>
            <li>
                <i class='bx bxs-message-dots bx-sm'></i>
                <span class="text">
                    <h3><?php echo $messages_data['total']; ?></h3>
                    <p>Mensagens</p>
                </span>
            </li>
            <li>
                <i class='bx bxs-file-doc bx-sm'></i>
                <span class="text">
                    <h3><?php echo $curriculos_data['total']; ?></h3>
                    <p>Currículos</p>
                </span>
            </li>
            <li>
            <i class='bx bx-task'></i>
                <span class="text">
                    <h3><?php echo $tasks_data['total']; ?></h3>
                    <p>Tarefas</p>
                </span>
            </li>
        </div>

    <!-- Tabela de Tarefas -->
    <div class="task-table-data">
        <div class="task-order">
            <div class="task-head">
                <h3>Tarefas Recentes</h3>
                <?php if ($_SESSION['user_level'] === 'admin'): ?>                         

                <button id="addTaskBtn" class="task-btn-primary">
                    <i class='bx bx-plus'></i>
                    <span>Adicionar Tarefa</span>
                </button>
                <?php endif; ?>
            </div>
            <table class="task-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Tarefa</th>
                        <th>Responsável</th>
                        <th>Status</th>
                        <th>Data de Criação</th>
                        <?php if ($_SESSION['user_level'] === 'admin'): ?>                         
                        <th>Ações</th>
                        <?php endif; ?>

                    </tr>
                </thead>
                <tbody>
    <?php
    // Consulta para obter as tarefas
    $sql = "SELECT t.*, w.username FROM tasks t 
            LEFT JOIN wblogin w ON t.responsavel_id = w.id 
            ORDER BY t.data_criacao DESC";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $status_class = '';
            $status_text = '';
            
            switch($row['status']) {
                case 'pendente':
                    $status_class = 'task-pending';
                    $status_text = 'Pendente';
                    break;
                case 'em_andamento':
                    $status_class = 'task-process';
                    $status_text = 'Em Andamento';
                    break;
                case 'concluido':
                    $status_class = 'task-completed';
                    $status_text = 'Concluído';
                    break;
            }
            
            echo "<tr data-id='" . $row['id'] . "'>";
            echo "<td>" . $row['id'] . "</td>";
            echo "<td>" . $row['tarefa'] . "</td>";
            echo "<td>" . $row['username'] . "</td>";
            echo "<td><span class='task-status " . $status_class . "'>" . $status_text . "</span></td>";
            echo "<td>" . date('d/m/Y H:i', strtotime($row['data_criacao'])) . "</td>";
            echo "<td class='task-actions'>";
            
            if ($_SESSION['user_level'] === 'admin') {
                echo "<i class='bx bx-edit' onclick='editTask(" . $row['id'] . ")'></i>";
                echo "<i class='bx bx-trash' onclick='deleteTask(" . $row['id'] . ")'></i>";
            }
            
            echo "</td>";
            echo "</tr>";
        }
    } else {
        echo "<tr><td colspan='6' style='text-align: center;'>Nenhuma tarefa encontrada</td></tr>";
    }
    ?>
</tbody>
            </table>
        </div>
    </div>

</main>

<!-- Modal para Adicionar Tarefa -->
<div id="addTaskModal" class="task-modal">
    <div class="task-modal-content">
        <span class="task-close">&times;</span>
        <h2>Adicionar Nova Tarefa</h2>
        <form id="taskForm" method="post" action="processtask.php">
            <div class="task-form-group">
                <label for="tarefa">Tarefa:</label>
                <input type="text" id="tarefa" name="tarefa" class="task-form-control" required>
            </div>
            <div class="task-form-group">
                <label for="responsavel">Responsável:</label>
                <select id="responsavel" name="responsavel_id" class="task-form-control" required>
                    <option value="">Selecione um responsável</option>
                    <?php
                    $sql = "SELECT id, username FROM wblogin";
                    $result = $conn->query($sql);
                    
                    if ($result->num_rows > 0) {
                        while($row = $result->fetch_assoc()) {
                            echo "<option value='" . $row['id'] . "'>" . $row['username'] . "</option>";
                        }
                    }
                    ?>
                </select>
            </div>
            <div class="task-form-group">
                <label for="status">Status:</label>
                <select id="status" name="status" class="task-form-control" required>
                    <option value="pendente">Pendente</option>
                    <option value="em_andamento">Em Andamento</option>
                    <option value="concluido">Concluído</option>
                </select>
            </div>
            <button type="submit" class="task-btn-primary">Salvar</button>
        </form>
    </div>
</div>

<!-- Modal para Editar Tarefa -->
<div id="editTaskModal" class="task-modal">
    <div class="task-modal-content">
        <span class="task-close" id="closeEditModal">&times;</span>
        <h2>Editar Tarefa</h2>
        <form id="editTaskForm" method="post" action="edittask.php">
            <input type="hidden" id="edit_task_id" name="id">
            <div class="task-form-group">
                <label for="edit_tarefa">Tarefa:</label>
                <input type="text" id="edit_tarefa" name="tarefa" class="task-form-control" required>
            </div>
            <div class="task-form-group">
                <label for="edit_responsavel">Responsável:</label>
                <select id="edit_responsavel" name="responsavel_id" class="task-form-control" required>
                    <option value="">Selecione um responsável</option>
                    <?php
                    $sql = "SELECT id, username FROM wblogin";
                    $result = $conn->query($sql);
                    
                    if ($result->num_rows > 0) {
                        while($row = $result->fetch_assoc()) {
                            echo "<option value='" . $row['id'] . "'>" . $row['username'] . "</option>";
                        }
                    }
                    ?>
                </select>
            </div>
            <div class="task-form-group">
                <label for="edit_status">Status:</label>
                <select id="edit_status" name="status" class="task-form-control" required>
                    <option value="pendente">Pendente</option>
                    <option value="em_andamento">Em Andamento</option>
                    <option value="concluido">Concluído</option>
                </select>
            </div>
            <button type="submit" class="task-btn-primary">Atualizar</button>
        </form>
    </div>
</div>

<!-- MAIN -->
    </section>
    <!-- CONTENT -->
    

    <script src="script.js"></script>
    <script>


        // Função para ocultar o loader após 3 segundos e mostrar o conteúdo
        window.addEventListener('load', () => {
            setTimeout(() => {
                const loader = document.getElementById('loader');
                const content = document.getElementById('content');

                // Esconde o loader e mostra o conteúdo do site
                loader.style.opacity = '0'; // Fade out
                loader.style.visibility = 'hidden'; // Esconde completamente após o fade
                content.style.display = 'block'; // Torna o conteúdo visível
            }, 1300); // 1300 milissegundos = 1,3 segundos
        });

        function toggleMoney() {
    let moneyValue = document.getElementById('money-value');
    let toggleIcon = document.getElementById('toggle-icon');

    if (moneyValue.style.opacity == "0" || moneyValue.style.opacity === '') {
        moneyValue.style.opacity = "1"; // Mostra o dinheiro
        toggleIcon.classList.replace('bx-show', 'bx-hide'); // Troca para ícone de olho fechado
    } else {
        moneyValue.style.opacity = "0"; // Oculta o dinheiro
        toggleIcon.classList.replace('bx-hide', 'bx-show'); // Troca para ícone de olho aberto
    }
}

document.addEventListener("DOMContentLoaded", function () {
    const profileIcon = document.getElementById("profileIcon");
    const profileMenu = document.getElementById("profileMenu");

    profileIcon.addEventListener("click", function (event) {
        event.preventDefault();
        profileMenu.style.display = (profileMenu.style.display === "block") ? "none" : "block";
    });

    // Fechar menu ao clicar fora dele
    document.addEventListener("click", function (event) {
        if (!profileIcon.contains(event.target) && !profileMenu.contains(event.target)) {
            profileMenu.style.display = "none";
        }
    });
});

    </script>
<script>
    let tempoExpiracao = 15 * 60 * 1000; // 15 minutos em milissegundos
    let timeout;

    function resetTimer() {
        clearTimeout(timeout);
        timeout = setTimeout(() => {
            window.location.href = "index.php"; // Redireciona para a tela de login
        }, tempoExpiracao);
    }

    // Reinicia o tempo sempre que o usuário interagir com a página
    window.onload = resetTimer;
    document.onmousemove = resetTimer;
    document.onkeydown = resetTimer;
    document.onclick = resetTimer;

    // Código para o modal de tarefas
    document.addEventListener("DOMContentLoaded", function() {
        // Elementos do modal de adição
        const addModal = document.getElementById("addTaskModal");
        const addBtn = document.getElementById("addTaskBtn");
        const addSpan = addModal.getElementsByClassName("task-close")[0];

        // Elementos do modal de edição
        const editModal = document.getElementById("editTaskModal");
        const editSpan = document.getElementById("closeEditModal");

        // Abre o modal quando o botão é clicado
        addBtn.onclick = function() {
            addModal.style.display = "block";
            // Adiciona a classe show após um pequeno delay para ativar a animação
            setTimeout(() => {
                addModal.classList.add("show");
            }, 10);
        }

        // Fecha o modal quando o X é clicado
        addSpan.onclick = function() {
            closeModal(addModal);
        }

        // Fecha o modal de edição quando o X é clicado
        editSpan.onclick = function() {
            closeModal(editModal);
        }

        // Fecha os modais quando o usuário clica fora deles
        window.onclick = function(event) {
            if (event.target == addModal) {
                closeModal(addModal);
            }
            if (event.target == editModal) {
                closeModal(editModal);
            }
        }

        // Função para fechar modal com animação
        function closeModal(modal) {
            modal.classList.remove("show");
            // Aguarda a animação terminar antes de esconder o modal
            setTimeout(() => {
                modal.style.display = "none";
            }, 300);
        }

        // Faz as mensagens de alerta desaparecerem após 5 segundos
        const tempAlert = document.getElementById("tempAlert");
        if (tempAlert) {
            // Adiciona animação de fade out
            setTimeout(function() {
                tempAlert.style.transition = "opacity 1s";
                tempAlert.style.opacity = "0";
            }, 4000); // 4 segundos antes de começar a desaparecer
            
            // Remove o elemento após a animação
            setTimeout(function() {
                tempAlert.remove();
            }, 5000); // 5 segundos no total (4s + 1s de animação)
        }
    });

    // Função para editar uma tarefa
    function editTask(id) {
        // Abre o modal de edição
        const modal = document.getElementById("editTaskModal");
        modal.style.display = "block";
        
        // Adiciona a classe show após um pequeno delay para ativar a animação
        setTimeout(() => {
            modal.classList.add("show");
        }, 10);
        
        // Busca os dados da tarefa via AJAX
        const xhr = new XMLHttpRequest();
        xhr.open("GET", "get_task.php?id=" + id, true);
        xhr.onreadystatechange = function() {
            if (this.readyState === XMLHttpRequest.DONE && this.status === 200) {
                console.log("Resposta do servidor:", this.responseText);
                try {
                    const task = JSON.parse(this.responseText);
                    
                    // Preenche o formulário com os dados da tarefa
                    document.getElementById("edit_task_id").value = task.id;
                    document.getElementById("edit_tarefa").value = task.tarefa;
                    document.getElementById("edit_responsavel").value = task.responsavel_id;
                    document.getElementById("edit_status").value = task.status;
                } catch (e) {
                    console.error("Erro ao processar resposta do servidor:", e);
                    alert("Erro ao carregar dados da tarefa. Verifique o console para mais detalhes.");
                }
            }
        }
        xhr.send();
    }

    // Função para excluir uma tarefa
    function deleteTask(id) {
        if (confirm("Tem certeza que deseja excluir esta tarefa?")) {
            // Adiciona animação à linha da tabela antes de excluir
            const row = document.querySelector(`tr[data-id="${id}"]`);
            if (row) {
                row.classList.add("row-delete");
                
                // Aguarda a animação terminar antes de enviar a requisição AJAX
                setTimeout(() => {
                    // Envia uma requisição AJAX para excluir a tarefa
                    const xhr = new XMLHttpRequest();
                    xhr.open("POST", "deletetask.php", true);
                    xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                    xhr.onreadystatechange = function() {
                        if (this.readyState === XMLHttpRequest.DONE && this.status === 200) {
                            console.log("Resposta do servidor:", this.responseText);
                            // Remove a linha da tabela após a exclusão
                            row.remove();
                            
                            // Exibe mensagem de sucesso
                            const tableContainer = document.querySelector(".task-table-data");
                            const successMsg = document.createElement("div");
                            successMsg.id = "tempAlert";
                            successMsg.className = "alert alert-success";
                            successMsg.textContent = "Tarefa excluída com sucesso!";
                            
                            // Insere a mensagem antes da tabela
                            tableContainer.parentNode.insertBefore(successMsg, tableContainer);
                            
                            // Faz a mensagem desaparecer após alguns segundos
                            setTimeout(() => {
                                successMsg.style.transition = "opacity 1s";
                                successMsg.style.opacity = "0";
                                
                                setTimeout(() => {
                                    successMsg.remove();
                                }, 1000);
                            }, 4000);
                        }
                    }
                    xhr.send("id=" + id);
                }, 500); // Aguarda 500ms para a animação terminar
            } else {
                // Se não encontrar a linha, apenas envia a requisição AJAX
                const xhr = new XMLHttpRequest();
                xhr.open("POST", "deletetask.php", true);
                xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                xhr.onreadystatechange = function() {
                    if (this.readyState === XMLHttpRequest.DONE && this.status === 200) {
                        console.log("Resposta do servidor:", this.responseText);
                        location.reload();
                    }
                }
                xhr.send("id=" + id);
            }
        }
    }

</script>



<script>

const notificationIcon = document.getElementById('notificationIcon');
const notificationMenu = document.getElementById('notificationMenu');
const notificationItems = document.querySelectorAll('.notification-item');

if (notificationIcon && notificationMenu) {
    // Abrir/fechar menu de notificações com animação
    notificationIcon.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        if (notificationMenu.style.display === 'block') {
            closeNotificationMenu();
        } else {
            openNotificationMenu();
        }
    });

    // Fechar ao clicar fora
    document.addEventListener('click', function(e) {
        if (!notificationMenu.contains(e.target) && !notificationIcon.contains(e.target)) {
            closeNotificationMenu();
        }
    });

    // Fechar ao pressionar ESC
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeNotificationMenu();
        }
    });
}

// Funções para abrir/fechar menu de notificações
function openNotificationMenu() {
    notificationMenu.style.display = 'block';
    notificationMenu.style.opacity = '0';
    setTimeout(() => {
        notificationMenu.style.opacity = '1';
    }, 10);
}

function closeNotificationMenu() {
    notificationMenu.style.opacity = '0';
    setTimeout(() => {
        notificationMenu.style.display = 'none';
    }, 300);
}

// Atualizar notificações automaticamente
function updateNotifications() {
    const xhr = new XMLHttpRequest();
    xhr.open('GET', 'get_notifications.php', true);
    xhr.onload = function() {
        if (xhr.status === 200) {
            try {
                const data = JSON.parse(xhr.responseText);
                updateNotificationCount(data.pending_count);
                updateNotificationContent(data.recent_tasks);
            } catch (e) {
                console.error('Erro ao processar notificações:', e);
            }
        }
    };
    xhr.send();
}

// Atualizar contador de notificações
function updateNotificationCount(count) {
    const notificationCount = document.querySelector('.notification .num');
    if (notificationCount) {
        if (count > 0) {
            notificationCount.textContent = count;
            notificationCount.style.display = 'block';
            // Adicionar animação de pulso
            notificationCount.classList.add('bx-tada');
            setTimeout(() => {
                notificationCount.classList.remove('bx-tada');
            }, 1000);
        } else {
            notificationCount.style.display = 'none';
        }
    }
}

// Atualizar conteúdo das notificações
function updateNotificationContent(tasks) {
    const notificationContent = document.querySelector('.notification-content');
    if (notificationContent) {
        if (tasks && tasks.length > 0) {
            let html = '<ul>';
            tasks.forEach(task => {
                html += `
                    <a href="./profile/profile.php" class="notification-item">
                        <div class="notification-icon">
                            <i class='bx bx-time-five'></i>
                        </div>
                        <div class="notification-info">
                            <p class="notification-title">${escapeHtml(task.tarefa)}</p>
                            <p class="notification-desc">
                                Responsável: ${escapeHtml(task.responsavel)}<br>
                                Criada em: ${task.data_criacao}
                            </p>
                        </div>
                    </a>
                `;
            });
            html += '</ul>';
            notificationContent.innerHTML = html;
        } else {
            notificationContent.innerHTML = `
                <div class="notification-empty">
                    <i class='bx bx-check-circle'></i>
                    <p>Não há tarefas pendentes</p>
                </div>
            `;
        }
    }
}

// Função auxiliar para escapar HTML
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Atualizar notificações a cada 5 minutos
setInterval(updateNotifications, 300000);

// Atualizar notificações ao carregar a página
document.addEventListener('DOMContentLoaded', updateNotifications);

</script>
</body>
</html>
