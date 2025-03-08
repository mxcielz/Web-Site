

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

