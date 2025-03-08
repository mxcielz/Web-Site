<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Verificação de autenticação
if (!isset($_SESSION['user_id']) || $_SESSION['user_level'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

// Configuração da conexão com o banco de dados
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "wb";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Falha na conexão: " . $conn->connect_error);
}

// Processamento de ações (aprovar/rejeitar)
if (isset($_POST['action']) && isset($_POST['request_id'])) {
    $request_id = intval($_POST['request_id']);
    $action = $_POST['action'];

    // Buscar informações da solicitação
    $stmt = $conn->prepare("SELECT * FROM solicitacoes_acesso WHERE id = ?");
    $stmt->bind_param("i", $request_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    try {
        $conn->begin_transaction();

        if ($action === 'aprovar') {
            // Inserir no histórico
            $stmt = $conn->prepare("INSERT INTO historico_solicitacoes (nome, email, cpf, cargo, status, data_solicitacao, data_processamento) VALUES (?, ?, ?, ?, 'aprovado', ?, NOW())");
            $stmt->bind_param("sssss", 
                $row['nome'], 
                $row['email'], 
                $row['cpf'], 
                $row['cargo'], 
                $row['data_solicitacao']
            );
            
            if ($stmt->execute()) {
                // Gerar uma senha temporária (pode ser alterada depois)
                $senha_temporaria = password_hash($row['cpf'], PASSWORD_DEFAULT);
                
                // Inserir na tabela de usuários
                $stmt = $conn->prepare("INSERT INTO wblogin (username, cpf, password, email, user_level, created_at) VALUES (?, ?, ?, ?, 'funcionario', NOW())");
                $stmt->bind_param("ssss", 
                    $row['nome'], // usando o nome como username
                    $row['cpf'],
                    $senha_temporaria,
                    $row['email']
                );
                
                if ($stmt->execute()) {
                    // Deletar a solicitação
                    $stmt = $conn->prepare("DELETE FROM solicitacoes_acesso WHERE id = ?");
                    $stmt->bind_param("i", $request_id);
                    
                    if ($stmt->execute()) {
                        $conn->commit();
                        $_SESSION['success_msg'] = "Solicitação aprovada com sucesso. Usuário cadastrado no sistema. A senha inicial é o CPF.";
                    } else {
                        throw new Exception("Erro ao deletar solicitação.");
                    }
                } else {
                    throw new Exception("Erro ao cadastrar usuário: " . $conn->error);
                }
            } else {
                throw new Exception("Erro ao registrar no histórico.");
            }
        } elseif ($action === 'rejeitar') {
            // Inserir no histórico
            $stmt = $conn->prepare("INSERT INTO historico_solicitacoes (nome, email, cpf, cargo, status, data_solicitacao, data_processamento) VALUES (?, ?, ?, ?, 'rejeitado', ?, NOW())");
            $stmt->bind_param("sssss", 
                $row['nome'], 
                $row['email'], 
                $row['cpf'], 
                $row['cargo'], 
                $row['data_solicitacao']
            );
            
            if ($stmt->execute()) {
                // Deletar a solicitação
                $stmt = $conn->prepare("DELETE FROM solicitacoes_acesso WHERE id = ?");
                $stmt->bind_param("i", $request_id);
                
                if ($stmt->execute()) {
                    $conn->commit();
                    $_SESSION['success_msg'] = "Solicitação rejeitada com sucesso.";
                } else {
                    throw new Exception("Erro ao deletar solicitação.");
                }
            } else {
                throw new Exception("Erro ao registrar no histórico.");
            }
        }
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error_msg'] = "Erro ao processar solicitação: " . $e->getMessage();
        error_log("Erro no processamento: " . $e->getMessage());
    }
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Buscar solicitações pendentes
$sql = "SELECT * FROM solicitacoes_acesso WHERE status = 'pendente' ORDER BY data_solicitacao DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciador de Acessos - WB Admin</title>
    
    <!-- CSS -->
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="style.css">
    
    <style>
        /* Estilos para o gerenciador de acesso */
        :root {
            --primary: #3C91E6;
            --light: #F9F9F9;
            --dark: #342E37;
            --grey: #eee;
            --red: #DB504A;
            --green: #38B000;
        }

        /* Container principal */
        .validation-container {
            padding: 20px;
            max-width: 1200px;
            margin: 0 auto;
        }

        h1 {
            color: var(--dark);
            margin-bottom: 30px;
            font-size: 24px;
            font-weight: 600;
        }

        /* Tabela de solicitações */
        .requests-table {
            width: 100%;
            background: var(--light);
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            margin-top: 20px;
        }

        .requests-table thead {
            background: var(--primary);
            color: white;
        }

        .requests-table th {
            padding: 15px;
            text-align: left;
            font-weight: 500;
        }

        .requests-table td {
            padding: 15px;
            border-bottom: 1px solid var(--grey);
        }

        .requests-table tbody tr:hover {
            background: rgba(60, 145, 230, 0.05);
        }

        /* Botões de ação */
        .action-btn {
            padding: 8px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: all 0.3s ease;
            margin-right: 5px;
        }

        .approve-btn {
            background: var(--green);
            color: white;
        }

        .approve-btn:hover {
            background: #2d8c00;
            transform: translateY(-2px);
        }

        .reject-btn {
            background: var(--red);
            color: white;
        }

        .reject-btn:hover {
            background: #b54641;
            transform: translateY(-2px);
        }

        /* Mensagens de alerta */
        .alert {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            animation: slideIn 0.3s ease-out;
        }

        .alert-success {
            background: rgba(56, 176, 0, 0.1);
            border: 1px solid var(--green);
            color: var(--green);
        }

        .alert-error {
            background: rgba(219, 80, 74, 0.1);
            border: 1px solid var(--red);
            color: var(--red);
        }

        @keyframes slideIn {
            from {
                transform: translateY(-10px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        /* Mensagem quando não há solicitações */
        .no-requests {
            text-align: center;
            padding: 30px;
            color: var(--dark);
            font-style: italic;
        }

        /* Responsividade */
        @media (max-width: 768px) {
            .requests-table {
                display: block;
                overflow-x: auto;
            }

            .action-btn {
                padding: 6px 12px;
                font-size: 12px;
            }

            h1 {
                font-size: 20px;
            }
        }

        /* Modo escuro */
        body.dark {
            --light: #0C0C1E;
            --dark: #FBFBFB;
            --grey: #060714;
        }

        body.dark .requests-table {
            background: var(--light);
            color: var(--dark);
        }

        body.dark .requests-table tbody tr:hover {
            background: rgba(60, 145, 230, 0.1);
        }
    </style>
</head>
<body>
    <!-- Loader -->
    <div id="loader">
        <div class="spinner"></div>
    </div>

    <!-- Sidebar -->
    <section id="sidebar">
        <a href="./admin.php" class="brand">
            <i class='bx bxs-dashboard bx-sm'></i>
            <span class="text">WB Manutenção</span>
        </a>
        <ul class="side-menu top">
            <li>
                <a href="./admin.php">
                    <i class='bx bxs-dashboard bx-sm'></i>
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
                    <i class='bx bxs-doughnut-chart bx-sm'></i>
                    <span class="text">Analytics</span>
                </a>
            </li>
            <?php if ($_SESSION['user_level'] === 'admin' || $_SESSION['user_level'] === 'rh'): ?>         
            <li>
                <a href="./contact/message.php">
                    <i class='bx bxs-message-dots bx-sm'></i>
                    <span class="text">Message</span>
                </a>
            </li>
            <?php endif; ?>
            <li>
                <a href="./myteam/myteam.php">
                    <i class='bx bxs-group bx-sm'></i>
                    <span class="text">Team</span>
                </a>
            </li>
            <?php if ($_SESSION['user_level'] === 'admin'): ?>         
            <li class="active">
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
                    <i class='bx bxs-cog bx-sm bx-spin-hover'></i>
                    <span class="text">Settings</span>
                </a>
            </li>
            <li>
                <a href="logout.php" class="logout">
                    <i class='bx bx-power-off bx-sm bx-burst-hover'></i>
                    <span class="text">Logout</span>
                </a>
            </li>
        </ul>
    </section>

    <!-- Conteúdo principal -->
    <section id="content">
        <!-- Navbar -->
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
            $sql_pending = "SELECT COUNT(*) as total_pending FROM tasks WHERE status = 'pendente'";
            $result_pending = $conn->query($sql_pending);
            $pending_count = $result_pending->fetch_assoc()['total_pending'];

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

        <!-- Conteúdo da validação -->
        <div class="validation-container">
            <h1>Gerenciador de Solicitações de Acesso</h1>

            <?php if (isset($_SESSION['success_msg'])): ?>
                <div class="alert alert-success">
                    <?php 
                    echo $_SESSION['success_msg'];
                    unset($_SESSION['success_msg']);
                    ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['error_msg'])): ?>
                <div class="alert alert-error">
                    <?php 
                    echo $_SESSION['error_msg'];
                    unset($_SESSION['error_msg']);
                    ?>
                </div>
            <?php endif; ?>

            <table class="requests-table">
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>Email</th>
                        <th>Cargo</th>
                        <th>CPF</th>
                        <th>Data da Solicitação</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['nome']); ?></td>
                                <td><?php echo htmlspecialchars($row['email']); ?></td>
                                <td><?php echo htmlspecialchars($row['cargo']); ?></td>
                                <td><?php echo htmlspecialchars($row['cpf']); ?></td>
                                <td><?php echo date('d/m/Y H:i', strtotime($row['data_solicitacao'])); ?></td>
                                <td>
                                    <form method="post" style="display: inline;">
                                        <input type="hidden" name="request_id" value="<?php echo $row['id']; ?>">
                                        <button type="submit" name="action" value="aprovar" class="action-btn approve-btn">
                                            <i class='bx bx-check'></i> Aprovar
                                        </button>
                                        <button type="submit" name="action" value="rejeitar" class="action-btn reject-btn">
                                            <i class='bx bx-x'></i> Rejeitar
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="no-requests">Não há solicitações pendentes.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

    <script>
        // Loader
        window.addEventListener('load', () => {
            setTimeout(() => {
                const loader = document.getElementById('loader');
                loader.style.opacity = '0';
                loader.style.visibility = 'hidden';
            }, 1000);
        });

        // Auto-hide das mensagens de alerta
        document.addEventListener('DOMContentLoaded', () => {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                setTimeout(() => {
                    alert.style.opacity = '0';
                    setTimeout(() => alert.remove(), 300);
                }, 5000);
            });
        });
    </script>
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
<?php $conn->close(); ?>
