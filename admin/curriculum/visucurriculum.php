<?php
session_start();

// Verificar se o usuário está logado
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

// Verificar se o ID do currículo foi fornecido
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: curriculum.php");
    exit();
}

$id = $_GET['id'];

// Conexão com o banco de dados
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "wb";

$conn = new mysqli($servername, $username, $password, $dbname);

// Verificar conexão
if ($conn->connect_error) {
    die("Falha na conexão: " . $conn->connect_error);
}

// Buscar dados do currículo
$sql = "SELECT * FROM curriculos WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    // Currículo não encontrado
    header("Location: curriculum.php");
    exit();
}
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


$curriculo = $result->fetch_assoc();
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
        .curriculo-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 30px;
            background-color: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .curriculo-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #004080;
            padding-bottom: 15px;
        }
        
        .curriculo-header h1 {
            color: #004080;
            margin: 0;
        }
        
        .curriculo-header .data {
            font-size: 14px;
            color: #666;
        }
        
        .curriculo-section {
            margin-bottom: 25px;
        }
        
        .curriculo-section h2 {
            color: #004080;
            font-size: 18px;
            margin-bottom: 10px;
            border-bottom: 1px solid #eee;
            padding-bottom: 5px;
        }
        
        .curriculo-section p {
            line-height: 1.6;
            color: #333;
        }
        
        .contato-info {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .contato-item {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .contato-item i {
            color: #004080;
            font-size: 18px;
        }
        
        .btn-container {
            display: flex;
            justify-content: space-between;
            margin-top: 30px;
        }
        
        .btn {
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            cursor: pointer;
            border: none;
        }
        
        .btn-primary {
            background: linear-gradient(45deg, #004080, #005bb5);
            color: white;
        }
        
        .btn-secondary {
            background: linear-gradient(45deg, #4776E6, #8E54E9);
            color: white;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }
        
        /* Modo escuro */
        body.dark .curriculo-container {
            background-color: #2d2d2d;
            color: #e0e0e0;
        }
        
        body.dark .curriculo-header h1,
        body.dark .curriculo-section h2 {
            color: #e0e0e0;
        }
        
        body.dark .curriculo-section p {
            color: #ccc;
        }
        
        body.dark .curriculo-header {
            border-bottom-color: #555;
        }
        
        body.dark .curriculo-section h2 {
            border-bottom-color: #555;
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

        <!-- MAIN -->
        <main>
            <div class="head-title">
                <div class="left">
                    <h1>Visualizar Currículo</h1>
                    <ul class="breadcrumb">
                        <li><a href="../admin.php">Dashboard</a></li>
                        <li><i class='bx bx-chevron-right'></i></li>
                        <li><a href="curriculum.php">Currículos</a></li>
                        <li><i class='bx bx-chevron-right'></i></li>
                        <li><a class="active">Visualizar</a></li>
                    </ul>
                </div>
       
            </div>

            <div class="curriculo-container">
                <div class="curriculo-header">
                    <h1><?php echo htmlspecialchars($curriculo['nome']); ?></h1>
                    <div class="data">
                        Recebido em: <?php echo date('d/m/Y H:i', strtotime($curriculo['data_envio'])); ?>
                    </div>
                </div>

                <div class="contato-info">
                    <div class="contato-item">
                        <i class='bx bx-envelope'></i>
                        <span><?php echo htmlspecialchars($curriculo['email']); ?></span>
                    </div>
                    <div class="contato-item">
                        <i class='bx bx-phone'></i>
                        <span><?php echo htmlspecialchars($curriculo['telefone']); ?></span>
                    </div>
                    <?php if (!empty($curriculo['linkedin'])): ?>
                    <div class="contato-item">
                        <i class='bx bxl-linkedin-square'></i>
                        <a href="<?php echo htmlspecialchars($curriculo['linkedin']); ?>" target="_blank">LinkedIn</a>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="curriculo-section">
                    <h2>Cargo de Interesse</h2>
                    <p><?php echo htmlspecialchars($curriculo['cargo_interesse']); ?></p>
                </div>

                <div class="curriculo-section">
                    <h2>Resumo Profissional</h2>
                    <p><?php echo nl2br(htmlspecialchars($curriculo['resumo_profissional'])); ?></p>
                </div>

                <div class="curriculo-section">
                    <h2>Experiência Profissional</h2>
                    <p><?php echo nl2br(htmlspecialchars($curriculo['experiencia'])); ?></p>
                </div>

                <div class="curriculo-section">
                    <h2>Formação Acadêmica</h2>
                    <p><?php echo nl2br(htmlspecialchars($curriculo['formacao'])); ?></p>
                </div>

                <?php if (!empty($curriculo['mensagem'])): ?>
                <div class="curriculo-section">
                    <h2>Mensagem Adicional</h2>
                    <p><?php echo nl2br(htmlspecialchars($curriculo['mensagem'])); ?></p>
                </div>
                <?php endif; ?>

                <div class="btn-container">
                    <a href="curriculum.php" class="btn btn-primary">Voltar para Lista</a>
                    <?php if (!empty($curriculo['curriculo_pdf'])): ?>
                    <a href="<?php echo htmlspecialchars($curriculo['curriculo_pdf']); ?>" class="btn btn-secondary" download>Download PDF</a>
                    <?php endif; ?>
                </div>
            </div>
        </main>
        <!-- MAIN -->
    </section>

    <script src="../script.js"></script>
    <script>
        // Função para ocultar o loader após carregar a página
        window.addEventListener('load', () => {
            setTimeout(() => {
                const loader = document.getElementById('loader');
                const content = document.getElementById('content');

                loader.style.opacity = '0';
                loader.style.visibility = 'hidden';
                content.style.display = 'block';
            }, 1300);
        });
    </script>
</body>
</html>
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
<?php
$conn->close();
?> 