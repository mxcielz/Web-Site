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
    header("Location: ../../index.php");
    exit();
}

// Verifica se o usuário existe no banco de dados
$user_id = $_SESSION['user_id'];
$sql = "SELECT * FROM wblogin WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user_data = $result->fetch_assoc();

if ($result->num_rows == 0) {
    // Se o usuário não existir, redireciona para a página de login
    header("Location: ../../index.php");
    exit();
}

// Atualiza o tempo do último acesso
$_SESSION['ultimo_acesso'] = time();

// Função para obter as tarefas do usuário
function getUserTasks($conn, $user_id) {
    $tasks = [];
    
    // Verifica se a tabela tasks existe
    $result = $conn->query("SHOW TABLES LIKE 'tasks'");
    if ($result->num_rows == 0) {
        return $tasks;
    }
    
    // Verifica se a coluna responsavel_id existe
    $result = $conn->query("SHOW COLUMNS FROM tasks LIKE 'responsavel_id'");
    if ($result->num_rows > 0) {
        // Se existir a coluna responsavel_id, busca por ela
        $sql = "SELECT * FROM tasks WHERE responsavel_id = ? ORDER BY 
                CASE WHEN status = 'concluido' THEN 1 ELSE 0 END, 
                status, data_criacao DESC";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $user_id);
    } else {
        // Se não existir, busca pelo nome do responsável
        $sql = "SELECT * FROM tasks WHERE responsavel = ? ORDER BY 
                CASE WHEN status = 'concluido' THEN 1 ELSE 0 END, 
                status, data_criacao DESC";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $user_data['username']);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $tasks[] = $row;
        }
    }
    
    return $tasks;
}

// Função para calcular o desempenho do usuário
function calculatePerformance($tasks) {
    $total = count($tasks);
    if ($total == 0) return 0;
    
    $completed = 0;
    foreach ($tasks as $task) {
        if ($task['status'] == 'concluido') {
            $completed++;
        }
    }
    
    return ($completed / $total) * 100;
}

// Função para calcular o tempo médio de conclusão das tarefas
function calculateAverageCompletionTime($tasks) {
    $completed_tasks = 0;
    $total_days = 0;
    
    foreach ($tasks as $task) {
        if ($task['status'] == 'concluido') {
            $completed_tasks++;
            $created = new DateTime($task['data_criacao']);
            $updated = new DateTime($task['data_atualizacao']);
            $interval = $created->diff($updated);
            $total_days += $interval->days;
        }
    }
    
    if ($completed_tasks == 0) return 0;
    return $total_days / $completed_tasks;
}

// Obter as tarefas do usuário
$user_tasks = getUserTasks($conn, $user_id);

// Calcular estatísticas
$performance = calculatePerformance($user_tasks);
$avg_completion_time = calculateAverageCompletionTime($user_tasks);

// Contar tarefas por status
$pending_tasks = 0;
$in_progress_tasks = 0;
$completed_tasks = 0;

foreach ($user_tasks as $task) {
    if ($task['status'] == 'pendente') {
        $pending_tasks++;
    } elseif ($task['status'] == 'em_andamento') {
        $in_progress_tasks++;
    } elseif ($task['status'] == 'concluido') {
        $completed_tasks++;
    }
}

// Verificar se a coluna data_conclusao existe
$result = $conn->query("SHOW COLUMNS FROM tasks LIKE 'data_conclusao'");
$has_completion_date = $result->num_rows > 0;

// Se não existir, tenta criar a coluna
if (!$has_completion_date) {
    $conn->query("ALTER TABLE tasks ADD COLUMN data_conclusao DATETIME NULL AFTER data_atualizacao");
    // Atualiza as tarefas já concluídas para terem a data de conclusão igual à data de atualização
    $conn->query("UPDATE tasks SET data_conclusao = data_atualizacao WHERE status = 'concluido'");
}

// Atualizar status da tarefa se solicitado
if (isset($_POST['update_task']) && isset($_POST['task_id']) && isset($_POST['new_status'])) {
    $task_id = $_POST['task_id'];
    $new_status = $_POST['new_status'];
    
    // Verifica se existe a coluna data_conclusao
    $result = $conn->query("SHOW COLUMNS FROM tasks LIKE 'data_conclusao'");
    $has_completion_date = $result->num_rows > 0;
    
    if ($new_status == 'concluido' && $has_completion_date) {
        // Se a tarefa está sendo concluída e existe a coluna data_conclusao
        $sql = "UPDATE tasks SET status = ?, data_atualizacao = NOW(), data_conclusao = NOW() WHERE id = ?";
    } else {
        // Para outros status ou se não existir a coluna data_conclusao
        $sql = "UPDATE tasks SET status = ?, data_atualizacao = NOW() WHERE id = ?";
        
        // Se a tarefa está sendo reaberta e existe a coluna data_conclusao
        if ($new_status != 'concluido' && $has_completion_date) {
            // Limpa a data de conclusão
            $conn->query("UPDATE tasks SET data_conclusao = NULL WHERE id = $task_id");
        }
    }
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $new_status, $task_id);
    
    if ($stmt->execute()) {
        // Redirecionar para evitar reenvio do formulário
        header("Location: profile.php?success=1&status=" . $new_status);
        exit();
    }
}

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
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Boxicons -->
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link href='https://unpkg.com/boxicons@2.1.4/dist/boxicons.js' rel='stylesheet'>

    <!-- My CSS -->
    <link rel="stylesheet" href="../style.css">

    <title>WB - Perfil</title>
</head>
<style>
/* Loader Styles */
#loader {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(255, 255, 255, 0.8);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 9999;
    transition: opacity 0.5s ease, visibility 0s 0.5s;
}

.spinner {
    border: 4px solid #f3f3f3;
    border-top: 4px solid #3498db;
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

.profile-container {
    padding: 20px;
}

.profile-header {
    display: flex;
    align-items: center;
    margin-bottom: 30px;
    background: var(--light);
    border-radius: 10px;
    padding: 20px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

.profile-avatar {
    width: 100px;
    height: 100px;
    border-radius: 50%;
    background-color: #3498db;
    display: flex;
    justify-content: center;
    align-items: center;
    margin-right: 20px;
    color: white;
    font-size: 40px;
    font-weight: bold;
}

.profile-info {
    flex: 1;
}

.profile-info h2 {
    margin: 0 0 5px 0;
    font-size: 24px;
}

.profile-info p {
    margin: 0 0 5px 0;
    color: var(--dark-grey);
}

.profile-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    grid-gap: 20px;
    margin-bottom: 30px;
}

.stat-card {
    background: var(--light);
    border-radius: 10px;
    padding: 20px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    text-align: center;
}

.stat-card i {
    font-size: 28px;
    margin-bottom: 10px;
}

.stat-card h3 {
    font-size: 24px;
    margin: 0 0 5px 0;
}

.stat-card p {
    margin: 0;
    color: var(--dark-grey);
}

.performance-bar {
    height: 10px;
    background-color: #f1f1f1;
    border-radius: 5px;
    margin-top: 10px;
    overflow: hidden;
}

.performance-fill {
    height: 100%;
    background-color: #4CAF50;
    border-radius: 5px;
}

.tasks-container {
    background: var(--light);
    border-radius: 10px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    overflow: hidden;
    margin-bottom: 30px;
}

.tasks-header {
    padding: 15px 20px;
    border-bottom: 1px solid #eee;
}

.tasks-header h3 {
    font-size: 18px;
    font-weight: 600;
    margin: 0;
}

.tasks-body {
    padding: 0;
}

.tasks-table {
    width: 100%;
    border-collapse: collapse;
}

.tasks-table th {
    padding: 15px;
    text-align: left;
    background: #f8f9fa;
    color: var(--dark);
    font-weight: 600;
}

.tasks-table td {
    padding: 15px;
    border-bottom: 1px solid #eee;
}

.status-badge {
    padding: 5px 10px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
}

.status-pendente {
    background-color: #ffecb3;
    color: #ff8f00;
}

.status-em_andamento {
    background-color: #bbdefb;
    color: #1976d2;
}

.status-concluido {
    background-color: #c8e6c9;
    color: #388e3c;
}

.task-actions {
    display: flex;
    gap: 5px;
}

.task-actions button {
    border: none;
    background: none;
    cursor: pointer;
    padding: 5px;
    border-radius: 5px;
    transition: background-color 0.3s;
}

.task-actions button:hover {
    background-color: #f1f1f1;
}

.task-actions button i {
    font-size: 18px;
}

.btn-pendente {
    color: #ff8f00;
}

.btn-em_andamento {
    color: #1976d2;
}

.btn-concluido {
    color: #388e3c;
}

.performance-chart-container {
    background: var(--light);
    border-radius: 10px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    overflow: hidden;
    margin-bottom: 30px;
}

.chart-header {
    padding: 15px 20px;
    border-bottom: 1px solid #eee;
}

.chart-header h3 {
    font-size: 18px;
    font-weight: 600;
    margin: 0;
}

.chart-body {
    padding: 20px;
    height: 300px;
}

.alert {
    padding: 15px;
    margin-bottom: 20px;
    border-radius: 5px;
}

.alert-success {
    background-color: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.no-tasks {
    text-align: center;
    padding: 30px;
    color: var(--dark-grey);
}

.no-tasks i {
    font-size: 50px;
    margin-bottom: 10px;
    color: #ccc;
}

.task-completed {
    background-color: rgba(200, 230, 201, 0.2);
}

.task-completed td {
    color: #666;
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
            <li class="active">
				<a href="../profile/profile.php">
                <i class='bx bx-user'></i>
                <span class="text">My Profile</span>
				</a>
			</li>
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
                    <a href="./profile.php">
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
    <!-- NAVBAR -->

    <main>
        <div class="head-title">
            <div class="left">
                <h1>Perfil do Usuário</h1>
                <ul class="breadcrumb">
                    <li><a href="../admin.php">Dashboard</a></li>
                    <li><i class='bx bx-chevron-right'></i></li>
                    <li><a class="active" href="#">Perfil</a></li>
                </ul>
            </div>
        </div>

        <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success">
            <?php 
            $status_text = '';
            if (isset($_GET['status'])) {
                switch($_GET['status']) {
                    case 'pendente':
                        $status_text = 'pendente';
                        break;
                    case 'em_andamento':
                        $status_text = 'em andamento';
                        break;
                    case 'concluido':
                        $status_text = 'concluída';
                        break;
                }
                echo "Tarefa marcada como $status_text com sucesso!";
            } else {
                echo "Status da tarefa atualizado com sucesso!";
            }
            ?>
        </div>
        <?php endif; ?>

        <div class="profile-container">
            <!-- Informações do Perfil -->
            <div class="profile-header">
                <div class="profile-avatar">
                    <?php echo strtoupper(substr($user_data['username'], 0, 1)); ?>
                </div>
                <div class="profile-info">
                    <h2><?php echo htmlspecialchars($user_data['username']); ?></h2>
                    <p>CPF: <?php echo htmlspecialchars($user_data['CPF']); ?></p>
                    <p>Email: <?php echo htmlspecialchars($user_data['email']); ?></p>
                    <p>Nível de Acesso: <?php echo htmlspecialchars($user_data['user_level']); ?></p>
                    <p>Membro desde: <?php echo date('d/m/Y', strtotime($user_data['created_at'])); ?></p>
                </div>
            </div>

            <!-- Estatísticas -->
            <div class="profile-stats">
                <div class="stat-card">
                    <i class='bx bxs-hourglass-top' style="color: #ff8f00;"></i>
                    <h3><?php echo $pending_tasks; ?></h3>
                    <p>Tarefas Pendentes</p>
                </div>
                <div class="stat-card">
                    <i class='bx bx-loader-circle' style="color: #1976d2;"></i>
                    <h3><?php echo $in_progress_tasks; ?></h3>
                    <p>Tarefas em Andamento</p>
                </div>
                <div class="stat-card">
                    <i class='bx bx-check-circle' style="color: #388e3c;"></i>
                    <h3><?php echo $completed_tasks; ?></h3>
                    <p>Tarefas Concluídas</p>
                </div>
                <div class="stat-card">
                    <i class='bx bx-line-chart' style="color: #3498db;"></i>
                    <h3><?php echo number_format($performance, 1); ?>%</h3>
                    <p>Taxa de Conclusão</p>
                    <div class="performance-bar">
                        <div class="performance-fill" style="width: <?php echo $performance; ?>%"></div>
                    </div>
                </div>
                <div class="stat-card">
                    <i class='bx bx-time' style="color: #9c27b0;"></i>
                    <h3><?php echo number_format($avg_completion_time, 1); ?></h3>
                    <p>Dias Médios p/ Conclusão</p>
                </div>
            </div>

            <!-- Gráfico de Desempenho -->
            <div class="performance-chart-container">
                <div class="chart-header">
                    <h3>Desempenho de Tarefas</h3>
                </div>
                <div class="chart-body">
                    <canvas id="performanceChart"></canvas>
                </div>
            </div>

            <!-- Lista de Tarefas -->
            <div class="tasks-container">
                <div class="tasks-header">
                    <h3>Minhas Tarefas</h3>
                </div>
                <div class="tasks-body">
                    <?php if (count($user_tasks) > 0): ?>
                    <table class="tasks-table">
                        <thead>
                            <tr>
                                <th>Tarefa</th>
                                <th>Status</th>
                                <th>Data de Criação</th>
                                <th>Última Atualização</th>
                                <?php if ($has_completion_date): ?>
                                <th>Data de Conclusão</th>
                                <?php endif; ?>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($user_tasks as $task): ?>
                            <tr class="<?php echo $task['status'] == 'concluido' ? 'task-completed' : ''; ?>">
                                <td><?php echo htmlspecialchars($task['tarefa']); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo $task['status']; ?>">
                                        <?php 
                                        $status_text = '';
                                        switch($task['status']) {
                                            case 'pendente':
                                                $status_text = 'Pendente';
                                                break;
                                            case 'em_andamento':
                                                $status_text = 'Em Andamento';
                                                break;
                                            case 'concluido':
                                                $status_text = 'Concluído';
                                                break;
                                            default:
                                                $status_text = $task['status'];
                                        }
                                        echo $status_text;
                                        ?>
                                    </span>
                                </td>
                                <td><?php echo date('d/m/Y H:i', strtotime($task['data_criacao'])); ?></td>
                                <td><?php echo date('d/m/Y H:i', strtotime($task['data_atualizacao'])); ?></td>
                                <?php if ($has_completion_date): ?>
                                <td>
                                    <?php 
                                    if ($task['status'] == 'concluido' && isset($task['data_conclusao']) && $task['data_conclusao']) {
                                        echo date('d/m/Y H:i', strtotime($task['data_conclusao']));
                                    } else {
                                        echo '-';
                                    }
                                    ?>
                                </td>
                                <?php endif; ?>
                                <td>
                                    <div class="task-actions">
                                        <form method="post" action="">
                                            <input type="hidden" name="task_id" value="<?php echo $task['id']; ?>">
                                            <input type="hidden" name="update_task" value="1">
                                            
                                            <?php if ($task['status'] != 'pendente'): ?>
                                            <button type="submit" name="new_status" value="pendente" title="Marcar como Pendente">
                                                <i class='bx bx-time btn-pendente'></i>
                                            </button>
                                            <?php endif; ?>
                                            
                                            <?php if ($task['status'] != 'em_andamento'): ?>
                                            <button type="submit" name="new_status" value="em_andamento" title="Marcar como Em Andamento">
                                                <i class='bx bx-loader-circle btn-em_andamento'></i>
                                            </button>
                                            <?php endif; ?>
                                            
                                            <?php if ($task['status'] != 'concluido'): ?>
                                            <button type="submit" name="new_status" value="concluido" title="Marcar como Concluído">
                                                <i class='bx bx-check-circle btn-concluido'></i>
                                            </button>
                                            <?php endif; ?>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                    <div class="no-tasks">
                        <i class='bx bx-task-x'></i>
                        <p>Nenhuma tarefa encontrada</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>
</section>

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
