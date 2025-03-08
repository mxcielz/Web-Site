// Gerenciamento de Menus (Perfil e Notificações)
document.addEventListener('DOMContentLoaded', () => {
    initializeMenus();
    initializeButtons();
    initializeMessageInteractions();
});

function initializeMenus() {
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
            if (notificationMenu.style.display === 'block') {
                notificationMenu.style.display = 'none';
            } else {
                closeAllMenus();
                notificationMenu.style.display = 'block';
            }
        });
    }

    // Toggle para o menu de perfil
    if (profileIcon && profileMenu) {
        profileIcon.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            if (profileMenu.style.display === 'block') {
                profileMenu.style.display = 'none';
            } else {
                closeAllMenus();
                profileMenu.style.display = 'block';
            }
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
}

function initializeButtons() {
    const buttons = document.querySelectorAll('.msg-delete-btn, .msg-reply-btn');
    buttons.forEach(button => {
        button.classList.add('ripple');
        button.addEventListener('click', createRipple);
    });
}

function initializeMessageInteractions() {
    // Adicionar listeners para interações com mensagens aqui, se necessário
}

// Função para alternar detalhes da mensagem com animação
function toggleMessageDetails(header) {
    header.classList.toggle('active');
    const messageBody = header.nextElementSibling;
    
    // Remover classes de animação existentes
    messageBody.classList.remove('show', 'hide');

    if (messageBody.style.display === 'none' || messageBody.style.display === '') {
        messageBody.style.display = 'block';
        setTimeout(() => {
            messageBody.classList.add('show');
        }, 10);
    } else {
        messageBody.classList.add('hide');
        setTimeout(() => {
            messageBody.style.display = 'none';
            messageBody.classList.remove('hide');
        }, 300);
    }
}

// Função para deletar mensagem com animação
function deleteMessage(event) {
    event.preventDefault();
    
    if (!confirm('Tem certeza que deseja deletar esta mensagem?')) {
        return;
    }

    const form = event.target;
    const messageItem = form.closest('.msg-item');
    const messageList = messageItem.parentElement;
    
    // Adiciona classe para iniciar a animação de fade out
    messageItem.style.opacity = '0';
    messageItem.style.transform = 'translateX(-100px)';
    messageItem.style.transition = 'all 0.5s ease';

    // Espera a animação terminar antes de enviar o request
    setTimeout(() => {
        const formData = new FormData(form);

        fetch(form.action, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Remove o elemento com animação
                messageItem.style.height = messageItem.offsetHeight + 'px';
                messageItem.style.marginTop = '0';
                messageItem.style.marginBottom = '0';
                messageItem.style.padding = '0';
                
                setTimeout(() => {
                    messageItem.style.height = '0';
                    messageItem.style.overflow = 'hidden';
                    
                    setTimeout(() => {
                        messageItem.remove();
                        
                        // Verifica se há mais mensagens
                        if (messageList.children.length === 0) {
                            messageList.innerHTML = '<li class="msg-empty">Nenhuma mensagem encontrada.</li>';
                        }
                    }, 300);
                }, 100);

                // Mostra notificação de sucesso
                showNotification('Mensagem deletada com sucesso!', 'success');
            } else {
                // Reverte a animação se houver erro
                messageItem.style.opacity = '1';
                messageItem.style.transform = 'translateX(0)';
                showNotification(data.message || 'Erro ao deletar mensagem.', 'error');
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            messageItem.style.opacity = '1';
            messageItem.style.transform = 'translateX(0)';
            showNotification('Erro ao deletar mensagem. Tente novamente.', 'error');
        });
    }, 500);
}

// Função para mostrar notificações
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `notification-toast ${type}`;
    notification.textContent = message;
    
    // Estiliza a notificação
    Object.assign(notification.style, {
        position: 'fixed',
        top: '20px',
        right: '20px',
        padding: '15px 25px',
        borderRadius: '8px',
        color: 'white',
        zIndex: '1000',
        opacity: '0',
        transform: 'translateY(-20px)',
        transition: 'all 0.3s ease'
    });

    // Define cores baseadas no tipo
    const colors = {
        success: '#2ed573',
        error: '#ff4757',
        info: '#1e90ff'
    };
    notification.style.backgroundColor = colors[type] || colors.info;
    
    document.body.appendChild(notification);
    
    // Anima a entrada
    setTimeout(() => {
        notification.style.opacity = '1';
        notification.style.transform = 'translateY(0)';
    }, 10);
    
    // Remove após 3 segundos
    setTimeout(() => {
        notification.style.opacity = '0';
        notification.style.transform = 'translateY(-20px)';
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

// Adiciona efeito ripple aos botões quando a página carrega
document.addEventListener('DOMContentLoaded', () => {
    const buttons = document.querySelectorAll('.msg-delete-btn, .msg-reply-btn');
    buttons.forEach(button => {
        button.classList.add('ripple');
        button.addEventListener('click', createRipple);
    });
});

// Função para criar efeito ripple
function createRipple(event) {
    const button = event.currentTarget;
    const circle = document.createElement('span');
    const diameter = Math.max(button.clientWidth, button.clientHeight);
    const radius = diameter / 2;

    circle.style.width = circle.style.height = `${diameter}px`;
    circle.style.left = `${event.clientX - button.offsetLeft - radius}px`;
    circle.style.top = `${event.clientY - button.offsetTop - radius}px`;
    circle.classList.add('ripple-effect');

    const ripple = button.getElementsByClassName('ripple-effect')[0];
    if (ripple) {
        ripple.remove();
    }

    button.appendChild(circle);
} 