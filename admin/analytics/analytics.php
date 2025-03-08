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

// Verifica se o usuário existe no banco de dados
$user_id = $_SESSION['user_id'];
$sql = "SELECT * FROM wblogin WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    // Se o usuário não existir, redireciona para a página de login
    header("Location: index.php");
    exit();
}

// Atualiza o tempo do último acesso (se ainda estiver dentro do limite)
$_SESSION['ultimo_acesso'] = time();

// Consulta para obter tarefas pendentes
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


    <title>WB - Admin</title>

</head>
<style>
/* Variáveis de cores */
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
    --dark-blue: #0C3E8B;
}

/* Modo Escuro */
body.dark {
    --light: #0C0C1E;
    --grey: #060714;
    --dark: #FBFBFB;
}

/* Sistema de Notificações */
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

.notification-count {
    background: var(--red);
    color: white;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
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

/* Adaptações para Modo Escuro */
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

.chart-legend {
    display: flex;
    flex-wrap: wrap;
    margin-top: 15px;
    justify-content: center;
}

.legend-item {
    display: flex;
    align-items: center;
    margin-right: 15px;
    margin-bottom: 10px;
}

.color-box {
    width: 15px;
    height: 15px;
    border-radius: 3px;
    margin-right: 5px;
}

.table-data {
    width: 100%;
    overflow-x: auto;
}

.table-data table {
    width: 100%;
    border-collapse: collapse;
}

.table-data table th {
    padding: 10px;
    text-align: left;
    background: #f8f9fa;
    color: var(--dark);
    font-weight: 600;
}

.table-data table td {
    padding: 10px;
    border-bottom: 1px solid #eee;
}

canvas {
    width: 100% !important;
    height: 250px !important;
}

/* Profile Menu Styles */
.profile {
    position: relative;
    display: flex;
    align-items: center;
    cursor: pointer;
    padding: 5px;
    transition: all 0.3s ease;
}

.profile img {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid var(--grey);
    transition: all 0.3s ease;
}

.profile:hover img {
    border-color: var(--blue);
    transform: scale(1.05);
}

.profile-menu {
    position: absolute;
    top: 50px;
    right: 10px;
    width: 180px;
    background: var(--light);
    border-radius: 10px;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
    display: none;
    z-index: 1000;
    overflow: hidden;
    animation: slideDown 0.3s ease-out;
}

.profile-menu ul {
    list-style: none;
    padding: 0;
    margin: 0;
}

.profile-menu ul li {
    padding: 0;
    border-bottom: 1px solid var(--grey);
}

.profile-menu ul li:last-child {
    border-bottom: none;
}

.profile-menu ul li a {
    padding: 12px 20px;
    display: flex;
    align-items: center;
    gap: 10px;
    color: var(--dark);
    text-decoration: none;
    font-size: 14px;
    transition: all 0.3s ease;
}

.profile-menu ul li a:hover {
    background: var(--grey);
    color: var(--blue);
    transform: translateX(5px);
}

.profile-menu ul li a i {
    font-size: 18px;
}

/* Dark Mode Adaptations */
body.dark .profile img {
    border-color: #444;
}

body.dark .profile:hover img {
    border-color: var(--blue);
}

body.dark .profile-menu {
    background: var(--dark);
    border: 1px solid #444;
}

body.dark .profile-menu ul li {
    border-bottom-color: #444;
}

body.dark .profile-menu ul li a {
    color: var(--light);
}

body.dark .profile-menu ul li a:hover {
    background: #333;
    color: var(--blue);
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
			<li class="active">
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
    <!-- NAVBAR -->

<!-- NAVBAR -->

<!-- Substitua o conteúdo dentro da tag <main> pelo seguinte código -->
<main>
    <div class="head-title">
        <div class="left">
            <h1>Analytics</h1>
            <ul class="breadcrumb">
                <li><a href="../admin.php">Dashboard</a></li>
                <li><i class='bx bx-chevron-right'></i></li>
                <li><a class="active" href="#">Analytics</a></li>
            </ul>
        </div>
        <a href="#" class="btn-download" id="downloadReport">
            <i class='bx bxs-cloud-download bx-fade-down-hover'></i>
            <span class="text">Download Relatório</span>
        </a>
    </div>

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

        <!-- Gráficos e Análises Detalhadas -->
        <div class="analytics-grid">
            <!-- Distribuição de Usuários por Nível -->
            <?php if (tabelaExiste($conn, 'wblogin')): ?>
            <div class="analytics-card">
                <div class="card-header">
                    <h3>Distribuição de Usuários por Nível</h3>
                </div>
                <div class="card-body">
                    <canvas id="userLevelChart"></canvas>
                    <div class="chart-legend">
                        <div class="legend-item">
                            <div class="color-box" style="background-color: #4CAF50;"></div>
                            <span>Admin: <?php echo $users_data['admin_count']; ?></span>
                        </div>
                        <div class="legend-item">
                            <div class="color-box" style="background-color: #2196F3;"></div>
                            <span>Funcionário: <?php echo $users_data['func_count']; ?></span>
                        </div>
                        <div class="legend-item">
                            <div class="color-box" style="background-color: #FF9800;"></div>
                            <span>RH: <?php echo $users_data['rh_count']; ?></span>
                        </div>
                        <div class="legend-item">
                            <div class="color-box" style="background-color: #9C27B0;"></div>
                            <span>Visitante: <?php echo $users_data['visit_count']; ?></span>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Status das Tarefas -->
            <?php if (tabelaExiste($conn, 'tasks')): ?>
            <div class="analytics-card">
                <div class="card-header">
                    <h3>Status das Tarefas</h3>
                </div>
                <div class="card-body">
                    <canvas id="taskStatusChart"></canvas>
                    <div class="chart-legend">
                        <div class="legend-item">
                            <div class="color-box" style="background-color: #F44336;"></div>
                            <span>Pendente: <?php echo $tasks_data['pending_count']; ?></span>
                        </div>
                        <div class="legend-item">
                            <div class="color-box" style="background-color: #FFC107;"></div>
                            <span>Em Andamento: <?php echo $tasks_data['progress_count']; ?></span>
                        </div>
                        <div class="legend-item">
                            <div class="color-box" style="background-color: #4CAF50;"></div>
                            <span>Concluído: <?php echo $tasks_data['completed_count']; ?></span>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Mensagens de Contato Recentes -->
            <?php if (tabelaExiste($conn, 'contact_messages')): ?>
            <div class="analytics-card">
                <div class="card-header">
                    <h3>Mensagens de Contato Recentes</h3>
                </div>
                <div class="card-body">
                    <div class="table-data">
                        <table>
                            <thead>
                                <tr>
                                    <th>Empresa</th>
                                    <th>Contato</th>
                                    <th>Email</th>
                                    <th>Data</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                try {
                                    $sql_recent_messages = "SELECT * FROM contact_messages ORDER BY created_at DESC LIMIT 5";
                                    $result_recent_messages = $conn->query($sql_recent_messages);
                                    
                                    if ($result_recent_messages && $result_recent_messages->num_rows > 0) {
                                        while($row = $result_recent_messages->fetch_assoc()) {
                                            echo "<tr>";
                                            echo "<td>" . htmlspecialchars($row['company_name']) . "</td>";
                                            echo "<td>" . htmlspecialchars($row['contact_name']) . "</td>";
                                            echo "<td>" . htmlspecialchars($row['email']) . "</td>";
                                            echo "<td>" . date('d/m/Y', strtotime($row['created_at'])) . "</td>";
                                            echo "</tr>";
                                        }
                                    } else {
                                        echo "<tr><td colspan='4'>Nenhuma mensagem encontrada</td></tr>";
                                    }
                                } catch (Exception $e) {
                                    echo "<tr><td colspan='4'>Erro ao carregar mensagens</td></tr>";
                                    error_log("Erro ao carregar mensagens: " . $e->getMessage());
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Currículos por Cargo de Interesse -->
            <?php if (tabelaExiste($conn, 'curriculos')): ?>
            <div class="analytics-card">
                <div class="card-header">
                    <h3>Currículos por Cargo de Interesse</h3>
                </div>
                <div class="card-body">
                    <canvas id="curriculoChart"></canvas>
                    <?php
                    $cargos = [];
                    $counts = [];
                    
                    try {
                        $sql_curriculos_by_cargo = "SELECT cargo_interesse, COUNT(*) as count FROM curriculos GROUP BY cargo_interesse ORDER BY count DESC LIMIT 5";
                        $result_curriculos_by_cargo = $conn->query($sql_curriculos_by_cargo);
                        
                        if ($result_curriculos_by_cargo && $result_curriculos_by_cargo->num_rows > 0) {
                            while($row = $result_curriculos_by_cargo->fetch_assoc()) {
                                $cargos[] = $row['cargo_interesse'];
                                $counts[] = $row['count'];
                            }
                        }
                    } catch (Exception $e) {
                        error_log("Erro ao carregar currículos por cargo: " . $e->getMessage());
                    }
                    ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Tarefas por Responsável -->
            <?php if (tabelaExiste($conn, 'tasks')): ?>
            <div class="analytics-card">
                <div class="card-header">
                    <h3>Tarefas por Responsável</h3>
                </div>
                <div class="card-body">
                    <canvas id="tasksByResponsibleChart"></canvas>
                    <?php
                    $responsaveis = [];
                    $task_counts = [];
                    
                    try {
                        $sql_tasks_by_responsible = "SELECT responsavel, COUNT(*) as count FROM tasks GROUP BY responsavel ORDER BY count DESC LIMIT 5";
                        $result_tasks_by_responsible = $conn->query($sql_tasks_by_responsible);
                        
                        if ($result_tasks_by_responsible && $result_tasks_by_responsible->num_rows > 0) {
                            while($row = $result_tasks_by_responsible->fetch_assoc()) {
                                $responsaveis[] = $row['responsavel'];
                                $task_counts[] = $row['count'];
                            }
                        }
                    } catch (Exception $e) {
                        error_log("Erro ao carregar tarefas por responsável: " . $e->getMessage());
                    }
                    ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</main>

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

    // Função para verificar se um elemento existe antes de criar um gráfico
    function criarGrafico(id, tipo, config) {
        const elemento = document.getElementById(id);
        if (elemento) {
            const ctx = elemento.getContext('2d');
            return new Chart(ctx, {
                type: tipo,
                data: config.data,
                options: config.options
            });
        }
        return null;
    }

    // Gráfico de Distribuição de Usuários por Nível
    criarGrafico('userLevelChart', 'pie', {
        data: {
            labels: ['Admin', 'Funcionário', 'RH', 'Visitante'],
            datasets: [{
                data: [
                    <?php echo isset($users_data['admin_count']) ? $users_data['admin_count'] : 0; ?>,
                    <?php echo isset($users_data['func_count']) ? $users_data['func_count'] : 0; ?>,
                    <?php echo isset($users_data['rh_count']) ? $users_data['rh_count'] : 0; ?>,
                    <?php echo isset($users_data['visit_count']) ? $users_data['visit_count'] : 0; ?>
                ],
                backgroundColor: [
                    '#4CAF50',
                    '#2196F3',
                    '#FF9800',
                    '#9C27B0'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            }
        }
    });

    // Gráfico de Status das Tarefas
    criarGrafico('taskStatusChart', 'doughnut', {
        data: {
            labels: ['Pendente', 'Em Andamento', 'Concluído'],
            datasets: [{
                data: [
                    <?php echo isset($tasks_data['pending_count']) ? $tasks_data['pending_count'] : 0; ?>,
                    <?php echo isset($tasks_data['progress_count']) ? $tasks_data['progress_count'] : 0; ?>,
                    <?php echo isset($tasks_data['completed_count']) ? $tasks_data['completed_count'] : 0; ?>
                ],
                backgroundColor: [
                    '#F44336',
                    '#FFC107',
                    '#4CAF50'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            }
        }
    });

    // Gráfico de Currículos por Cargo de Interesse
    criarGrafico('curriculoChart', 'bar', {
        data: {
            labels: <?php echo !empty($cargos) ? json_encode($cargos) : '[]'; ?>,
            datasets: [{
                label: 'Quantidade de Currículos',
                data: <?php echo !empty($counts) ? json_encode($counts) : '[]'; ?>,
                backgroundColor: '#3498db',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0
                    }
                }
            }
        }
    });

    // Gráfico de Tarefas por Responsável
    criarGrafico('tasksByResponsibleChart', 'bar', {
        data: {
            labels: <?php echo !empty($responsaveis) ? json_encode($responsaveis) : '[]'; ?>,
            datasets: [{
                label: 'Quantidade de Tarefas',
                data: <?php echo !empty($task_counts) ? json_encode($task_counts) : '[]'; ?>,
                backgroundColor: '#2ecc71',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0
                    }
                }
            }
        }
    });

    // Função para download do relatório
    const downloadBtn = document.getElementById('downloadReport');
    if (downloadBtn) {
        downloadBtn.addEventListener('click', function(e) {
            e.preventDefault();
            alert('Funcionalidade de download de relatório será implementada em breve!');
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
});
</script>
</body>
</html>
