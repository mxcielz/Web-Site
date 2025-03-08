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

// Atualiza o tempo do último acesso
$_SESSION['ultimo_acesso'] = time();

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

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WB - Gerenciamento de Usuários</title>
    
    <!-- CSS -->
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="../style.css">
    
    <!-- Boxicons -->
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    
    <style>
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

.spinner {
    border: 4px solid #f3f3f3; /* Cor de fundo */
    border-top: 4px solid #3498db; /* Cor da animação */
    border-radius: 50%;
    width: 50px;
    height: 50px;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}
#content {
	display: none; /* Esconde o conteúdo do site até que o loader termine */
}


/* Modificação para modo escuro */
body.dark #loader {
    background: rgba(0, 0, 0, 0.9); /* Fundo mais escuro no modo escuro */
}

a {
        color: inherit; /* Herda a cor do elemento pai, que pode ser a cor padrão do texto */
        text-decoration: none; /* Remove o sublinhado padrão do link */
    }

	.hover-effect {
        display: inline-flex;  /* Usa flexbox para alinhar conteúdo */
        justify-content: center;  /* Centraliza horizontalmente */
        align-items: center;  /* Centraliza verticalmente */
        padding: 5px 10px;  /* Ajuste o padding para ter o tamanho certo */
        text-decoration: none;
        border-radius: 500px; /* Bordas arredondadas para o fundo */
        transition: background-color 0.3s ease, transform 0.3s ease;
        font-size: 14px;  /* Ajusta o tamanho do texto */
        color: #333; /* Cor padrão para o texto */
    }

    .hover-effect:hover {
        background-color: rgba(211, 211, 211, 0.5); /* Cor de fundo cinza mais visível ao passar o mouse */
        transform: scale(1.05); /* Aumenta o efeito de escala ao passar o mouse */
    }

    .icon {
        font-size: 20px; /* Tamanho do ícone */
        color: #555; /* Cor padrão do ícone */
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
}

.profile img:hover {
    border-color: #3498db; /* Borda azul ao passar o mouse */
}

.profile-menu {
    display: none;
    position: absolute;
    top: 50px; /* Ajuste conforme necessário */
    right: 10px;
    background: #fff;
    border-radius: 8px;
    box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.2);
    width: 150px;
    z-index: 1000;
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
    background: rgba(0, 0, 0, 0.1);
    border-radius: 8px;
}
:root {
            --primary-color: #004080;
            --secondary-color: #005bb5;
            --bg-light: #ffffff;
            --bg-dark: #f8f8f8;
            --border: #e0e0e0;
            --box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            --anim-duration: all 0.3s ease-in-out;
        }

        .user-form-container {
            background-color: var(--bg-light);
            padding: 30px;
            border-radius: 12px;
            box-shadow: var(--box-shadow);
            margin: 30px 0;
            animation: slideDown 0.5s ease-out;
        }

        @keyframes slideDown {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .input-wrapper {
            margin-bottom: 20px;
        }

        .input-wrapper .input-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--primary-color);
        }

        .input-wrapper .input-field,
        .input-wrapper .select-field {
            width: 100%;
            padding: 12px;
            border: 2px solid var(--border);
            border-radius: 8px;
            font-size: 15px;
            transition: var(--anim-duration);
        }

        .input-wrapper .input-field:focus,
        .input-wrapper .select-field:focus {
            border-color: var(--primary-color);
            outline: none;
            box-shadow: 0 0 0 3px rgba(0, 64, 128, 0.1);
        }

        .button-group {
            display: flex;
            gap: 15px;
            margin-top: 30px;
        }

        .btn-primary,
        .btn-danger {
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--anim-duration);
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .btn-primary {
            background-color: var(--primary-color);
            color: var(--bg-light);
        }

        .btn-danger {
            background-color: #dc3545;
            color: var(--bg-light);
        }

        .btn-primary:hover,
        .btn-danger:hover {
            transform: translateY(-2px);
            box-shadow: var(--box-shadow);
        }

        .data-container {
            background: var(--bg-light);
            padding: 24px;
            border-radius: 12px;
            box-shadow: var(--box-shadow);
            margin-top: 20px;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        .data-table th {
            background-color: var(--primary-color);
            color: white;
            padding: 15px;
            text-align: left;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 14px;
            letter-spacing: 1px;
        }

        .data-table td {
            padding: 15px;
            border-bottom: 1px solid var(--border);
            vertical-align: middle;
        }

        .data-table td:first-child {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .data-table td img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #eee;
        }

        .data-table tr:hover {
            background-color: var(--bg-dark);
            transition: all 0.3s ease;
        }

        .user-role {
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            display: inline-block;
            text-align: center;
            min-width: 100px;
        }

        .user-role.role-admin {
            background: linear-gradient(45deg, #FF416C, #FF4B2B);
            color: white;
        }

        .user-role.role-funcionario {
            background: linear-gradient(45deg, #11998e, #38ef7d);
            color: white;
        }

        .user-role.role-rh {
            background: linear-gradient(45deg, #4776E6, #8E54E9);
            color: white;
        }

        .user-role.role-visitante {
            background: linear-gradient(45deg, #ACB6E5, #86FDE8);
            color: #333;
        }

        .order .head {
            display: flex;
            align-items: center;
            gap: 16px;
            margin-bottom: 16px;
        }

        .order .head h3 {
            font-weight: 600;
            color: var(--text-dark);
        }

        .order .head i {
            font-size: 20px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .order .head i:hover {
            color: var(--primary-color);
        }

        @media screen and (max-width: 768px) {
            .data-container {
                padding: 15px;
                margin: 10px;
                overflow-x: auto;
            }

            .data-table {
                min-width: 800px;
            }

            .user-role {
                padding: 6px 12px;
                font-size: 12px;
                min-width: 80px;
            }
        }

        @keyframes pulseEffect {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }

        .btn-primary:active,
        .btn-danger:active {
            animation: pulseEffect 0.3s ease-in-out;
        }

        [data-theme="dark"] {
            --bg-dark: #1a1a1a;
            --bg-light: #2d2d2d;
            --border: #404040;
            color: #e0e0e0;
        }

        [data-theme="dark"] .input-wrapper .input-label {
            color: #e0e0e0;
        }

        [data-theme="dark"] .input-wrapper .input-field,
        [data-theme="dark"] .input-wrapper .select-field {
            background-color: #333;
            color: #e0e0e0;
            border-color: #404040;
        }

        [data-theme="dark"] .data-table tr:hover {
            background-color: #333;
        }



        .btn-edit,
        .btn-delete {
            background: none;
            border: none;
            font-size: 20px;
            cursor: pointer;
            transition: var(--anim-duration);
            padding: 8px;
            border-radius: 50%;
        }

        .btn-edit {
            color: var(--primary-color);
        }

        .btn-delete {
            color: #dc3545;
        }

        .btn-edit:hover,
        .btn-delete:hover {
            transform: scale(1.2);
            background-color: rgba(0, 0, 0, 0.05);
        }

        .message {
            padding: 15px 20px;
            border-radius: 8px;
            margin: 20px 0;
            font-weight: 500;
            animation: slideIn 0.3s ease-out;
            position: relative;
            padding-right: 40px;
        }

        @keyframes slideIn {
            from { transform: translateX(-20px); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }

        .message-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .message-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        @media (max-width: 768px) {
            .button-group {
                flex-direction: column;
            }

            .btn-primary,
            .btn-danger {
                width: 100%;
            }

            .data-container {
                overflow-x: auto;
            }

            .data-table {
                min-width: 800px;
            }

            .user-role {
                font-size: 12px;
                padding: 6px 10px;
            }
        }

        @keyframes pulseEffect {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }

        .btn-primary:active,
        .btn-danger:active {
            animation: pulseEffect 0.3s ease-in-out;
        }

        [data-theme="dark"] {
            --bg-dark: #1a1a1a;
            --bg-light: #2d2d2d;
            --border: #404040;
            color: #e0e0e0;
        }

        [data-theme="dark"] .input-wrapper .input-label {
            color: #e0e0e0;
        }

        [data-theme="dark"] .input-wrapper .input-field,
        [data-theme="dark"] .input-wrapper .select-field {
            background-color: #333;
            color: #e0e0e0;
            border-color: #404040;
        }

        [data-theme="dark"] .data-table tr:hover {
            background-color: #333;
        }
        .head-title {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
}

.head-title .left {
    flex: 1;
}

.head-title .right {
    display: flex;
    gap: 12px;
}

.breadcrumb {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-top: 8px;
}

.breadcrumb li a {
    color: var(--text-color);
    text-decoration: none;
    transition: color 0.3s ease;
}

.breadcrumb li a:hover {
    color: var(--primary-color);
}

.breadcrumb li a.active {
    color: var(--primary-color);
    font-weight: 600;
}

.hover-effect {
    padding: 8px 16px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    gap: 8px;
    background: var(--bg-light);
    border: 1px solid var(--border-color);
    cursor: pointer;
    transition: all 0.3s ease;
}

.hover-effect:hover {
    background: var(--primary-color);
    color: white;
    transform: translateY(-2px);
}

.hover-effect .icon {
    font-size: 20px;
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
    border-bottom: 1px solid #eee;
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
    background: #ff4444;
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
    border-bottom: 1px solid #eee;
    transition: all 0.3s ease;
    cursor: pointer;
    text-decoration: none;
    color: inherit;
}

.notification-item:hover {
    background-color: #f8f9fa;
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
    transition: transform 0.3s ease;
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
    border-top: 1px solid #eee;
    background: #f8f9fa;
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
    color: #4caf50;
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
    background-color: #333;
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
    </style>
</head>

<body>
<div id="loader">
        <div class="spinner"></div>
    </div>

  
	<section id="sidebar">
		<a href="../admin.php" class="brand">
        <i class='bx bxs-dashboard bx-sm' ></i>
		<span class="text">WB Manutenção</span>
		</a>
		<ul class="side-menu top">
            <li>
        <a href="../admin.php">
					<i class='bx bxs-dashboard bx-sm' ></i>
					<span class="text">Dashboard</span>
				</a>
			</li>
            <?php if ($_SESSION['user_level'] === 'admin' || $_SESSION['user_level'] === 'rh'): ?>              
			<li>
				<a href="../curriculum/curriculum.php">
					<i class='bx bxs-file-doc bx-sm' ></i>
					<span class="text">Curriculum</span>
				</a>
			</li>
            <?php endif; ?>
			<li>
				<a href="../analytics/analytics.php">
					<i class='bx bxs-doughnut-chart bx-sm' ></i>
					<span class="text">Analytics</span>
				</a>
			</li>
            <?php if ($_SESSION['user_level'] === 'admin' || $_SESSION['user_level'] === 'rh'): ?>         
			<li>
				<a href="../contact/message.php">
					<i class='bx bxs-message-dots bx-sm' ></i>
					<span class="text">Message</span>
				</a>
			</li>
            <?php endif; ?>    
            <li class="active">
				<a href="../myteam/myteam.php">
					<i class='bx bxs-group bx-sm' ></i>
					<span class="text">Team</span>
				</a>
			</li>
            <?php if ($_SESSION['user_level'] === 'admin'): ?>         
            <li>
                <a href="../validationlogin.php">
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
				<a href="../logout.php" class="logout">
					<i class='bx bx-power-off bx-sm bx-burst-hover' ></i>
					<span class="text">Logout</span>
				</a>
			</li>
		</ul>
	</section>
<!-- CONTENT -->
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
                            <a href="../profile/profile.php" class="notification-item">
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
                            <a href="../profile/profile.php">Ver todas as <?php echo $pending_count; ?> tarefas pendentes</a>
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
            <img src="../imagem/logo.png" alt="Profile">
        </a>
        <div class="profile-menu" id="profileMenu">
            <ul>
                <li><a href="../profile/profile.php">Meu Perfil</a></li>
                <li><a href="#">Configurações</a></li>
                <li><a href="../logout.php">Sair</a></li>
            </ul>
        </div>
    </nav>

        <!-- Main Content -->
        <main>
        <div class="head-title">
    <div class="left">
        <h1>Gerenciamento de Usuários</h1>
        <ul class="breadcrumb">
            <li>
                <a href="../admin.php">Dashboard</a>
            </li>
            <li><i class='bx bx-chevron-right'></i></li>
            <li>
                <a class="active"href="myteam.php">Team</a>
            </li>
            <li><i class='bx bx-chevron-right'></i></li>
            <li>
                <a class="active" href="#">Adicionar Usuário</a>
            </li>
        </ul>
    </div>
    <div class="right">
        <button onclick="toggleAddForm()" class="hover-effect">
            <i class='bx bx-plus icon'></i>
            <span>Adicionar Usuário</span>
        </button>
        <a href="myteam.php" class="hover-effect">
            <i class='bx bx-arrow-back icon'></i>
            <span>Voltar</span>
        </a>
    </div>
</div>

            <?php if (isset($_GET['success'])): ?>
                <div class="message message-success">
                    Operação realizada com sucesso!
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['error'])): ?>
                <div class="message message-error">
                    <?php
                    switch($_GET['error']) {
                        case 'cpf':
                            echo "CPF já cadastrado.";
                            break;
                        case 'username':
                            echo "Nome de usuário já existe.";
                            break;
                        case 'email':
                            echo "Email já cadastrado.";
                            break;
                        default:
                            echo "Erro ao processar a solicitação.";
                    }
                    ?>
                </div>
            <?php endif; ?>
        
            <!-- Add User Form -->
            <div id="addUserForm" class="user-form-container" style="display: none;">
                <h2>Adicionar Novo Usuário</h2>
                <form action="processteam.php" method="post">
                    <div class="input-wrapper">
                        <label for="username" class="input-label">Nome de Usuário</label>
                        <input type="text" id="username" name="username" class="input-field" required>
                    </div>
                    
                    <div class="input-wrapper">
                        <label for="email" class="input-label">Email</label>
                        <input type="email" id="email" name="email" class="input-field" required>
                    </div>
                    
                    <div class="input-wrapper">
                        <label for="cpf" class="input-label">CPF</label>
                        <input type="text" id="cpf" name="cpf" class="input-field" required>
                    </div>
                    
                    <div class="input-wrapper">
                        <label for="password" class="input-label">Senha</label>
                        <input type="password" id="password" name="password" class="input-field" required>
                    </div>
                    
                    <div class="input-wrapper">
                        <label for="user_level" class="input-label">Nível de Acesso</label>
                        <select id="user_level" name="user_level" class="select-field" required>
                            <option value="admin">Admin</option>
                            <option value="funcionario">Funcionário</option>
                            <option value="rh">RH</option>
                            <option value="visitante">Visitante</option>
                        </select>
                    </div>
                    
                    <div class="button-group">
                        <button type="submit" class="btn-primary">Adicionar</button>
                        <button type="button" onclick="toggleAddForm()" class="btn-danger">Cancelar</button>
                    </div>
                </form>
            </div>

            <!-- Users Table -->
            <div class="data-container">
                <div class="order">
                    <div class="head">
                        <h3>Usuários Cadastrados</h3>
                        <i class='bx bx-search'></i>
                        <i class='bx bx-filter'></i>
                    </div>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Usuário</th>
                                <th>Email</th>
                                <th>CPF</th>
                                <th>Data de Cadastro</th>
                                <th>Nível</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $sql = "SELECT id, username, email, cpf, created_at, user_level FROM wblogin ORDER BY created_at DESC";
                            $result = $conn->query($sql);

                            if ($result->num_rows > 0) {
                                while($row = $result->fetch_assoc()) {
                                    echo "<tr>
                                            <td>
                                                <img src='../imagem/logo.png' alt='User' style='width: 36px; height: 36px; border-radius: 50%; object-fit: cover;'>
                                                <p>" . htmlspecialchars($row['username']) . "</p>
                                            </td>
                                            <td>" . htmlspecialchars($row['email']) . "</td>
                                            <td>" . htmlspecialchars($row['cpf']) . "</td>
                                            <td>" . date('d/m/Y H:i', strtotime($row['created_at'])) . "</td>
                                            <td><span class='user-role role-" . htmlspecialchars($row['user_level']) . "'>" . 
                                                htmlspecialchars($row['user_level']) . "</span></td>
                                            <td>
                                                <div class='action-container'>
                                                    <button onclick='editUser(" . $row['id'] . ")' class='btn-edit'>
                                                        <i class='bx bx-edit'></i>
                                                    </button>
                                                    <form class='delete-form' action='deleteteam.php' method='POST' 
                                                          style='display: inline;' onsubmit='return confirmDelete()'>
                                                        <input type='hidden' name='user_id' value='" . $row['id'] . "'>
                                                        <button type='submit' class='btn-delete'>
                                                            <i class='bx bx-trash'></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                          </tr>";
                                }
                            }
                            $conn->close();
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main
        </section>

    <!-- Scripts -->
     <script src="team.js"></script>
    <script src="../script.js"></script>
    <script>
        // Toggle Add User Form
        function toggleAddForm() {
            const form = document.getElementById('addUserForm');
            form.style.display = form.style.display === 'none' ? 'block' : 'none';
        }

        // Confirm Delete
        function confirmDelete() {
            return confirm('Tem certeza que deseja excluir este usuário?');
        }

        // Edit User
        function editUser(userId) {
            // Implementar a edição do usuário
            alert('Editar usuário ' + userId);
        }

        // Loader
        window.addEventListener('load', () => {
            setTimeout(() => {
                const loader = document.getElementById('loader');
                const content = document.getElementById('content');
                loader.style.opacity = '0';
                loader.style.visibility = 'hidden';
                content.style.display = 'block';
            }, 1300);
        });

        document.addEventListener('DOMContentLoaded', function() {
            // Toggle para o menu de notificações
            const notificationIcon = document.getElementById('notificationIcon');
            const notificationMenu = document.getElementById('notificationMenu');
            
            if (notificationIcon && notificationMenu) {
                notificationIcon.addEventListener('click', function(e) {
                    e.preventDefault();
                    notificationMenu.style.display = notificationMenu.style.display === 'block' ? 'none' : 'block';
                });
            }

            // Toggle para o menu de perfil
            const profileIcon = document.getElementById('profileIcon');
            const profileMenu = document.getElementById('profileMenu');
            
            if (profileIcon && profileMenu) {
                profileIcon.addEventListener('click', function(e) {
                    e.preventDefault();
                    profileMenu.style.display = profileMenu.style.display === 'block' ? 'none' : 'block';
                });
            }

            // Fechar menus ao clicar fora deles
            document.addEventListener('click', function(e) {
                if (notificationIcon && notificationMenu && !notificationIcon.contains(e.target) && !notificationMenu.contains(e.target)) {
                    notificationMenu.style.display = 'none';
                }
                
                if (profileIcon && profileMenu && !profileIcon.contains(e.target) && !profileMenu.contains(e.target)) {
                    profileMenu.style.display = 'none';
                }
            });
        });
    </script>
</body>
</html>