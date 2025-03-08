<?php
session_start();

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
$sql = "SELECT id, user_level FROM wblogin WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    // Se o usuário não existir, redireciona para a página de login
    header("Location: index.php");
    exit();
}

$user = $result->fetch_assoc();
$_SESSION['user_level'] = $user['user_level'];

// Verificar se o usuário tem permissão para acessar mensagens
$allowed_levels = ['admin', 'rh'];
if (!in_array($_SESSION['user_level'], $allowed_levels)) {
    header("Location: ../unauthorized.php");
    exit();
}

// Atualiza o tempo do último acesso
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
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link href='https://unpkg.com/boxicons@2.1.4/dist/boxicons.js' rel='stylesheet'>
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="msg.css">

    <title>WB - Admin</title>
</head>

<body>
<div id="loader">
    <div class="spinner"></div>
</div>

<section id="sidebar">
		<a href="./admin.php" class="brand">
        <i class='bx bxs-message-dots bx-sm' ></i>
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
			<li class="active">
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
        <i class='bx bx-menu bx-sm' ></i>
        <a href="#" class="nav-link">Categories</a>
        <form action="#">
            <div class="form-input">
                <input type="search" placeholder="Search...">
                <button type="submit" class="search-btn"><i class='bx bx-search' ></i></button>
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

    <main>
        <div class="head-title">
            <div class="left">
                <h1>Dashboard</h1>
                <ul class="breadcrumb">
                    <li>
                        <a href="#">Message</a>
                    </li>
                    <li>
                        <i class='bx bx-chevron-right'></i>
                    </li>
                    <li>
                        <a href="#" class="active">Home</a>
                    </li>
                </ul>
            </div>
        </div>
<br>
        <div class="msg-section">
    <h2 class="msg-title">Mensagens Recebidas</h2>
    <ul class="msg-list">
        <?php
        // Consulta para recuperar as mensagens do banco de dados
        $sql = "SELECT id, company_name, contact_name, email, phone, message FROM contact_messages ORDER BY id DESC";
        $result = $conn->query($sql);
        
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                // Definir valores padrão caso não existam
                $company_name = isset($row['company_name']) ? $row['company_name'] : 'Não informado';
                $contact_name = isset($row['contact_name']) ? $row['contact_name'] : 'Não informado';
                $email = isset($row['email']) ? $row['email'] : 'Não informado';
                $phone = isset($row['phone']) ? $row['phone'] : 'Não informado';
                $message = isset($row['message']) ? $row['message'] : 'Não informado';
                
                // Pegar a primeira letra da empresa para o ícone
                $company_initial = strtoupper(substr($company_name, 0, 1));
                
                echo "<li class='msg-item'>";
                echo "<div class='msg-header' onclick='toggleMessageDetails(this)'>";
                echo "<div class='msg-company-icon'>" . $company_initial . "</div>";
                echo "<div class='msg-company'>" . htmlspecialchars($company_name) . "</div>";
                echo "</div>";
                echo "<div class='msg-body'>";
                echo "<div class='msg-info'>";
                echo "<div class='msg-field'>";
                echo "<i class='bx bx-user'></i>";
                echo "<div class='msg-field-content'>";
                echo "<div class='msg-field-label'>Contato</div>";
                echo "<div class='msg-field-value'>" . htmlspecialchars($contact_name) . "</div>";
                echo "</div>";
                echo "</div>";
                
                echo "<div class='msg-field'>";
                echo "<i class='bx bx-envelope'></i>";
                echo "<div class='msg-field-content'>";
                echo "<div class='msg-field-label'>Email</div>";
                echo "<div class='msg-field-value'>" . htmlspecialchars($email) . "</div>";
                echo "</div>";
                echo "</div>";
                
                echo "<div class='msg-field'>";
                echo "<i class='bx bx-phone'></i>";
                echo "<div class='msg-field-content'>";
                echo "<div class='msg-field-label'>Telefone</div>";
                echo "<div class='msg-field-value'>" . htmlspecialchars($phone) . "</div>";
                echo "</div>";
                echo "</div>";
                
                echo "<div class='msg-field'>";
                echo "<i class='bx bx-message-detail'></i>";
                echo "<div class='msg-field-content'>";
                echo "<div class='msg-field-label'>Mensagem</div>";
                echo "<div class='msg-field-value'>" . nl2br(htmlspecialchars($message)) . "</div>";
                echo "</div>";
                echo "</div>";
                echo "</div>";
                
                echo "<div class='msg-actions'>";
                echo "<form class='msg-delete-form' action='delete_contact.php' method='POST' onsubmit='deleteMessage(event)'>";
                echo "<input type='hidden' name='message_id' value='" . $row['id'] . "'>";
                echo "<button type='submit' class='msg-btn msg-btn-delete'><i class='bx bx-trash'></i>Deletar</button>";
                echo "</form>";
                echo "<a href='mailto:" . htmlspecialchars($email) . "?subject=Re: Contato - " . htmlspecialchars($company_name) . "' class='msg-btn msg-btn-reply'><i class='bx bx-reply'></i>Responder</a>";
                echo "</div>";
                echo "</div>";
                echo "</li>";
            }
        } else {
            echo "<li class='msg-empty'>";
            echo "<i class='bx bx-envelope'></i>";
            echo "<p>Nenhuma mensagem encontrada.</p>";
            echo "</li>";
        }
        ?>
    </ul>
</div>
    </main>
    <script src="../script.js"></script>
    <script src="message.js"></script>
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

        // Configuração do tempo de expiração da sessão
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
    </script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Toggle para o menu de notificações
        const notificationIcon = document.getElementById('notificationIcon');
        const notificationMenu = document.getElementById('notificationMenu');
        const profileIcon = document.getElementById('profileIcon');
        const profileMenu = document.getElementById('profileMenu');
        
        // Função para fechar todos os menus
        function closeAllMenus() {
            if (notificationMenu) notificationMenu.style.display = 'none';
            if (profileMenu) profileMenu.style.display = 'none';
        }

        // Toggle para o menu de notificações
        if (notificationIcon && notificationMenu) {
            notificationIcon.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                closeAllMenus();
                notificationMenu.style.display = 'block';
            });
        }

        // Toggle para o menu de perfil
        if (profileIcon && profileMenu) {
            profileIcon.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                closeAllMenus();
                profileMenu.style.display = 'block';
            });
        }

        // Fechar menus ao clicar em qualquer lugar do documento
        document.addEventListener('click', function(e) {
            if (!notificationIcon?.contains(e.target) && 
                !notificationMenu?.contains(e.target) && 
                !profileIcon?.contains(e.target) && 
                !profileMenu?.contains(e.target)) {
                closeAllMenus();
            }
        });
    });
    </script>
</body>
</html>