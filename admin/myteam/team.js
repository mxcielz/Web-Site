// Gerenciamento do menu do perfil
document.addEventListener('DOMContentLoaded', function() {
    const profileIcon = document.getElementById('profileIcon');
    const profileMenu = document.getElementById('profileMenu');

    // Toggle do menu do perfil
    profileIcon.addEventListener('click', function(e) {
        e.stopPropagation();
        profileMenu.classList.toggle('active');
    });

    // Fechar menu ao clicar fora
    document.addEventListener('click', function(e) {
        if (!profileMenu.contains(e.target) && !profileIcon.contains(e.target)) {
            profileMenu.classList.remove('active');
        }
    });

    // Animação suave ao passar o mouse sobre itens do menu
    const menuItems = profileMenu.querySelectorAll('li');
    menuItems.forEach(item => {
        item.addEventListener('mouseenter', function() {
            this.style.transform = 'translateX(5px)';
        });
        item.addEventListener('mouseleave', function() {
            this.style.transform = 'translateX(0)';
        });
    });
});

// Melhorar a experiência do upload de arquivo
document.addEventListener('DOMContentLoaded', function() {
    const fileInput = document.getElementById('image');
    if (fileInput) {
        fileInput.addEventListener('change', function() {
            const fileName = this.files[0]?.name;
            if (fileName) {
                this.nextElementSibling = fileName;
            }
        });
    }
});

// Validação do formulário
document.querySelector('form').addEventListener('submit', function(e) {
    const requiredFields = this.querySelectorAll('[required]');
    let isValid = true;

    requiredFields.forEach(field => {
        if (!field.value) {
            isValid = false;
            field.classList.add('error');
            
            // Remover classe de erro quando o campo for preenchido
            field.addEventListener('input', function() {
                if (this.value) {
                    this.classList.remove('error');
                }
            });
        }
    });

    if (!isValid) {
        e.preventDefault();
        alert('Por favor, preencha todos os campos obrigatórios.');
    }
}); 

function confirmDelete() {
    return confirm("Tem certeza que deseja excluir este usuário?");
}

function editUser(userId) {
    // Redirect to edit page or show modal
    window.location.href = `edit_user.php?id=${userId}`;
}

