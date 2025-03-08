<?php
session_start();

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "wb";

$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

// Check if user exists and get their level
$user_id = $_SESSION['user_id'];
$sql = "SELECT id, user_level FROM wblogin WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    header("Location: ../index.php");
    exit();
}

$user = $result->fetch_assoc();
$_SESSION['user_level'] = $user['user_level'];

// Verificar se o usuário tem permissão para acessar currículos
$allowed_levels = ['admin', 'rh'];
if (!in_array($_SESSION['user_level'], $allowed_levels)) {
    header("Location: ../unauthorized.php");
    exit();
}

// Update last access time
$_SESSION['ultimo_acesso'] = time();

// Get curriculos with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

// Count total records for pagination
$count_sql = "SELECT COUNT(*) as total FROM curriculos";
$count_result = $conn->query($count_sql);
$total_records = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_records / $per_page);

// Get curriculos with pagination and search
$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
$sql_curriculos = "SELECT * FROM curriculos 
                   WHERE nome LIKE ? OR email LIKE ? OR cargo_interesse LIKE ?
                   ORDER BY data_envio DESC 
                   LIMIT ? OFFSET ?";
$stmt = $conn->prepare($sql_curriculos);
$search_param = "%$search%";
$stmt->bind_param("sssii", $search_param, $search_param, $search_param, $per_page, $offset);
$stmt->execute();
$result_curriculos = $stmt->get_result();

// Close statement
$stmt->close();

// Verificação de tarefas pendentes
if (!isset($pending_count)) {
    $sql_pending = "SELECT COUNT(*) as total_pending FROM tasks WHERE status = 'pendente'";
    $result_pending = $conn->query($sql_pending);
    $pending_count = $result_pending ? $result_pending->fetch_assoc()['total_pending'] : 0;

    // Consulta para obter as últimas 5 tarefas pendentes
    $sql_recent_pending = "SELECT t.*, w.username 
                          FROM tasks t 
                          LEFT JOIN wblogin w ON t.responsavel_id = w.id 
                          WHERE t.status = 'pendente' 
                          ORDER BY t.data_criacao DESC 
                          LIMIT 5";
    $result_recent_pending = $conn->query($sql_recent_pending);
}
?>


<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Boxicons -->
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>

    <!-- My CSS -->
    <link rel="stylesheet" href="../style.css">

    <title>WB - Currículos</title>

    <style>
        :root {
            --primary: #3C91E6;
            --light: #F9F9F9;
            --dark: #342E37;
            --grey: #eee;
            --red: #DB504A;
            --yellow: #FFCE26;
            --green: #38B000;
            --blue: #3C91E6;
            --purple: #8338EC;
            --orange: #FF9914;
            --azul: #004080;
            --shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        /* Loader Styles */
        #loader {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgb(255, 255, 255);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
            transition: opacity 0.2s ease, visibility 0s 0.2s;
        }

        .spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid var(--blue);
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
            display: none;
        }

        body.dark #loader {
            background: rgba(0, 0, 0, 0.9);
        }

        /* Links e botões */
        a {
            color: inherit;
            text-decoration: none;
        }

        .hover-effect {
            display: inline-flex;
            justify-content: center;
            align-items: center;
            padding: 5px 10px;
            border-radius: 500px;
            transition: all 0.3s ease;
            font-size: 14px;
            color: var(--dark);
            cursor: pointer;
            background-color: transparent;
            border: none;
            margin: 5px;
        }

        .hover-effect:hover {
            background-color: rgba(211, 211, 211, 0.5);
            transform: scale(1.05);
        }

        .hover-effect i {
            margin-right: 5px;
        }

        .icon {
            font-size: 20px;
            color: #555;
        }

        /* Profile menu */
        .profile {
            display: flex;
            align-items: center;
            cursor: pointer;
        }

        .profile img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            border: 2px solid #ddd;
            transition: border-color 0.3s ease-in-out;
        }

        .profile img:hover {
            border-color: var(--blue);
        }

        .profile-menu {
            display: none;
            position: absolute;
            top: 60px;
            right: 10px;
            background: #fff;
            border-radius: 8px;
            box-shadow: var(--shadow);
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
            color: var(--dark);
            display: block;
            transition: background 0.3s ease-in-out;
        }

        .profile-menu ul li a:hover {
            background: rgba(0, 0, 0, 0.1);
            border-radius: 8px;
        }

        /* Tabela de dados */
        .data-container {
            background: #fff;
            padding: 24px;
            border-radius: 12px;
            box-shadow: var(--shadow);
            margin-top: 20px;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        .data-table th {
            background-color: var(--azul);
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
            border-bottom: 1px solid var(--grey);
            vertical-align: middle;
        }

        .data-table tr:hover {
            background-color: var(--grey);
            transition: all 0.3s ease;
        }

        /* Botões de ação */
        .actions {
            display: flex;
            gap: 8px;
            align-items: center;
        }

        .delete-form {
            display: inline;
        }

        .btn {
            padding: 8px 12px;
            text-decoration: none;
            color: white;
            border-radius: 6px;
            display: inline-block;
            margin: 2px;
            font-weight: 500;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
        }
        
        .btn-view {
            background: linear-gradient(45deg, #11998e, #38ef7d);
        }
        
        .btn-download {
            background: linear-gradient(45deg, #4776E6, #8E54E9);
        }
        
        .btn-delete {
            background: linear-gradient(45deg, #FF416C, #FF4B2B);
        }

        .btn i {
            font-size: 1.2rem;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        /* Notificações */
        .notification-popup {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 20px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
            min-width: 300px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            animation: slideIn 0.5s ease-out;
            z-index: 1000;
        }

        .notification-popup.success {
            background: linear-gradient(45deg, #11998e, #38ef7d);
            color: white;
        }

        .notification-popup.error {
            background: linear-gradient(45deg, #FF416C, #FF4B2B);
            color: white;
        }

        .notification-content {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .notification-content i {
            font-size: 1.5rem;
        }

        .close-notification {
            background: none;
            border: none;
            color: white;
            cursor: pointer;
            padding: 5px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: transform 0.3s ease;
        }

        .close-notification:hover {
            transform: scale(1.1);
        }

        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        @keyframes slideOut {
            from {
                transform: translateX(0);
                opacity: 1;
            }
            to {
                transform: translateX(100%);
                opacity: 0;
            }
        }

        /* Paginação */
        .pagination {
            display: flex;
            justify-content: center;
            margin-top: 20px;
            gap: 5px;
        }

        .pagination a {
            padding: 8px 12px;
            background: var(--grey);
            color: var(--dark);
            border-radius: 4px;
            transition: all 0.3s ease;
        }

        .pagination a.active {
            background: var(--blue);
            color: white;
        }

        .pagination a:hover:not(.active) {
            background: #ddd;
        }

        /* Barra de pesquisa */
        .search-container {
            margin-bottom: 20px;
            display: flex;
            gap: 10px;
        }

        .search-container input {
            flex: 1;
            padding: 10px 15px;
            border: 1px solid var(--grey);
            border-radius: 8px;
            font-size: 14px;
        }

        .search-container button {
            padding: 10px 15px;
            background: var(--blue);
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .search-container button:hover {
            background: #2980b9;
        }

        /* Modo escuro */
        body.dark .data-container {
            background: #333;
            color: #eee;
        }

        body.dark .data-table th {
            background-color: #444;
        }

        body.dark .data-table td {
            border-bottom: 1px solid #444;
        }

        body.dark .data-table tr:hover {
            background-color: #444;
        }

        body.dark .search-container input {
            background: #444;
            color: #eee;
            border-color: #555;
        }

        body.dark .pagination a {
            background: #444;
            color: #eee;
        }

        body.dark .pagination a.active {
            background: var(--blue);
        }

        body.dark .pagination a:hover:not(.active) {
            background: #555;
        }

        /* Responsividade */
        @media screen and (max-width: 768px) {
            .data-container {
                padding: 15px;
                margin: 10px;
                overflow-x: auto;
            }

            .data-table {
                min-width: 800px;
            }

            .search-container {
                flex-direction: column;
            }

            .notification-popup {
                min-width: auto;
                width: 90%;
                right: 5%;
            }
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
                <li class="active">
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
            <li>
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
                            <li class="notification-item">
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
                            </li>
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
            <img src="../imagem/logo.png" alt="Profile">
        </a>
        <div class="profile-menu" id="profileMenu">
            <ul>
                <li>
                    <a href="../profile/profile.php">
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
                    <a href="../logout.php">
                        <i class='bx bx-log-out'></i>
                        Sair
                    </a>
                </li>
            </ul>
        </div>
    </nav>

        <!-- MAIN -->
        <main>
            <div class="head-title">
                <div class="left">
                    <h1>Currículos Recebidos</h1>
                    <ul class="breadcrumb">
                        <li><a href="../admin.php">Dashboard</a></li>
                        <li><i class='bx bx-chevron-right'></i></li>
                        <li><a class="active">Currículos</a></li>
                    </ul>
                </div>
            </div>

            <!-- Notification -->
            <?php if (isset($_SESSION['notification'])): ?>
                <div class="notification-popup <?php echo $_SESSION['notification']['type']; ?>">
                    <div class="notification-content">
                        <i class='bx <?php echo $_SESSION['notification']['type'] === 'success' ? 'bx-check' : 'bx-x'; ?>'></i>
                        <span><?php echo $_SESSION['notification']['message']; ?></span>
                    </div>
                    <button class="close-notification"><i class='bx bx-x'></i></button>
                </div>
                <?php unset($_SESSION['notification']); ?>
            <?php endif; ?>
<br>
            <!-- Search Form -->
            <div class="search-container">
                <form action="curriculum.php" method="GET" style="display: flex; width: 100%; gap: 10px;">
                    <input type="text" name="search" placeholder="Buscar por nome, email ou cargo..." value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                    <button type="submit"><i class='bx bx-search'></i> Buscar</button>
                </form>
            </div>

            <!-- Currículos Table -->
            <div class="data-container">
                <div class="order">
                    <div class="head">
                        <h3>Lista de Currículos</h3>
                        <?php if (!empty($search)): ?>
                            <p>Resultados para: "<?php echo htmlspecialchars($search); ?>"</p>
                        <?php endif; ?>
                    </div>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Data</th>
                                <th>Nome</th>
                                <th>Email</th>
                                <th>Telefone</th>
                                <th>Cargo</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if ($result_curriculos->num_rows > 0) {
                                while($row = $result_curriculos->fetch_assoc()) {
                                    echo "<tr>";
                                    echo "<td>" . date('d/m/Y H:i', strtotime($row['data_envio'])) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['nome']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['email']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['telefone']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['cargo_interesse']) . "</td>";
                                    echo "<td class='actions'>";
                                    echo "<a href='visucurriculum.php?id=" . $row['id'] . "' class='btn btn-view' title='Visualizar'><i class='bx bx-show'></i></a>";
                                    if (!empty($row['curriculo_pdf'])) {
                                        echo "<a href='../uploads/curriculos/" . htmlspecialchars($row['curriculo_pdf']) . "' class='btn btn-download' download title='Download PDF'><i class='bx bx-download'></i></a>";
                                    }
                                    echo "<form class='delete-form' action='deletecurriculum.php' method='POST' onsubmit='return confirmDelete()'>";
                                    echo "<input type='hidden' name='curriculum_id' value='" . $row['id'] . "'>";
                                    echo "<button type='submit' class='btn btn-delete' title='Excluir'><i class='bx bx-trash'></i></button>";
                                    echo "</form>";
                                    echo "</td>";
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td colspan='6' style='text-align: center;'>Nenhum currículo encontrado.</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>

                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <div class="pagination">
                            <?php if ($page > 1): ?>
                                <a href="?page=<?php echo $page-1; ?><?php echo !empty($search) ? '&search='.urlencode($search) : ''; ?>">&laquo; Anterior</a>
                            <?php endif; ?>
                            
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <a href="?page=<?php echo $i; ?><?php echo !empty($search) ? '&search='.urlencode($search) : ''; ?>" <?php echo ($page == $i) ? 'class="active"' : ''; ?>><?php echo $i; ?></a>
                            <?php endfor; ?>
                            
                            <?php if ($page < $total_pages): ?>
                                <a href="?page=<?php echo $page+1; ?><?php echo !empty($search) ? '&search='.urlencode($search) : ''; ?>">Próximo &raquo;</a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </section>
    <!-- CONTENT -->

    <script src="../script.js"></script>
    <script>
        // Função para ocultar o loader após 1,3 segundos e mostrar o conteúdo
        window.addEventListener('load', () => {
            setTimeout(() => {
                const loader = document.getElementById('loader');
                const content = document.getElementById('content');
                loader.style.opacity = '0';
                loader.style.visibility = 'hidden';
                content.style.display = 'block';
            }, 1300);
        });

        // Gerenciamento de notificações
        document.addEventListener('DOMContentLoaded', function() {
            // Handle notification auto-close
            const notification = document.querySelector('.notification-popup');
            if (notification) {
                setTimeout(() => {
                    notification.style.animation = 'slideOut 0.5s ease-out forwards';
                    setTimeout(() => {
                        notification.remove();
                    }, 500);
                }, 5000);

                // Handle manual close
                const closeBtn = notification.querySelector('.close-notification');
                closeBtn.addEventListener('click', () => {
                    notification.style.animation = 'slideOut 0.5s ease-out forwards';
                    setTimeout(() => {
                        notification.remove();
                    }, 500);
                });
            }

            // Toggle profile menu
            const profileIcon = document.getElementById('profileIcon');
            const profileMenu = document.getElementById('profileMenu');
            
            if (profileIcon && profileMenu) {
                profileIcon.addEventListener('click', function(e) {
                    e.preventDefault();
                    profileMenu.style.display = profileMenu.style.display === 'block' ? 'none' : 'block';
                });
                
                document.addEventListener('click', function(e) {
                    if (!profileIcon.contains(e.target) && !profileMenu.contains(e.target)) {
                        profileMenu.style.display = 'none';
                    }
                });
            }

            // Toggle notification menu
            const notificationIcon = document.getElementById('notificationIcon');
            const notificationMenu = document.getElementById('notificationMenu');
            
            if (notificationIcon && notificationMenu) {
                notificationIcon.addEventListener('click', function(e) {
                    e.preventDefault();
                    notificationMenu.style.display = notificationMenu.style.display === 'block' ? 'none' : 'block';
                });
                
                document.addEventListener('click', function(e) {
                    if (!notificationIcon.contains(e.target) && !notificationMenu.contains(e.target)) {
                        notificationMenu.style.display = 'none';
                    }
                });
            }
        });

        // Confirmação de exclusão
        function confirmDelete() {
            return confirm('Tem certeza que deseja excluir este currículo? Esta ação não pode ser desfeita.');
        }

        // Controle de sessão
        let tempoExpiracao = 15 * 60 * 1000; // 15 minutos em milissegundos
        let timeout;

        function resetTimer() {
            clearTimeout(timeout);
            timeout = setTimeout(() => {
                window.location.href = "../index.php"; // Redireciona para a tela de login
            }, tempoExpiracao);
        }

        // Reinicia o tempo sempre que o usuário interagir com a página
        window.onload = resetTimer;
        document.onmousemove = resetTimer;
        document.onkeydown = resetTimer;
        document.onclick = resetTimer;
    </script>


<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Esconde o loader e mostra o conteúdo
    setTimeout(() => {
        const loader = document.getElementById('loader');
        const content = document.getElementById('content');
        
        if (loader && content) {
            loader.style.opacity = '0';
            loader.style.visibility = 'hidden';
            content.style.display = 'block';
        }
    }, 1300);

    // Gráfico de Desempenho
    const performanceCtx = document.getElementById('performanceChart');
    if (performanceCtx) {
        new Chart(performanceCtx, {
            type: 'doughnut',
            data: {
                labels: ['Concluídas', 'Em Andamento', 'Pendentes'],
                datasets: [{
                    data: [
                        <?php echo $completed_tasks; ?>,
                        <?php echo $in_progress_tasks; ?>,
                        <?php echo $pending_tasks; ?>
                    ],
                    backgroundColor: [
                        '#4CAF50',
                        '#2196F3',
                        '#FFC107'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    }

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

    // Toggle para o menu lateral em dispositivos móveis
    const toggle = document.querySelector('.bx-menu');
    const sidebar = document.getElementById('sidebar');
    const content = document.getElementById('content');
    
    if (toggle && sidebar && content) {
        toggle.addEventListener('click', function() {
            sidebar.classList.toggle('hide');
            content.classList.toggle('expand');
        });
    }

    // Toggle para o modo escuro/claro
    const switchMode = document.getElementById('switch-mode');
    
    if (switchMode) {
        switchMode.addEventListener('change', function() {
            document.body.classList.toggle('dark');
        });
    }
});
</script>
</body>
</html>