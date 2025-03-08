// Menu Lateral (Side Menu)
const allSideMenu = document.querySelectorAll('#sidebar .side-menu.top li a');

allSideMenu.forEach(item => {
    const li = item.parentElement;

    item.addEventListener('click', function () {
        allSideMenu.forEach(i => {
            i.parentElement.classList.remove('active');
        })
        li.classList.add('active');
    })
});

// TOGGLE SIDEBAR
const menuBar = document.querySelector('#content nav .bx.bx-menu');
const sidebar = document.getElementById('sidebar');

// Sidebar toggle işlemi
menuBar.addEventListener('click', function () {
    sidebar.classList.toggle('hide');
});

// Sayfa yüklendiğinde ve boyut değişimlerinde sidebar durumunu ayarlama
function adjustSidebar() {
    if (window.innerWidth <= 576) {
        sidebar.classList.add('hide');  // 576px ve altı için sidebar gizli
        sidebar.classList.remove('show');
    } else {
        sidebar.classList.remove('hide');  // 576px'den büyükse sidebar görünür
        sidebar.classList.add('show');
    }
}

// Sayfa yüklendiğinde ve pencere boyutu değiştiğinde sidebar durumunu ayarlama
window.addEventListener('load', adjustSidebar);
window.addEventListener('resize', adjustSidebar);

// Arama butonunu toggle etme
const searchButton = document.querySelector('#content nav form .form-input button');
const searchButtonIcon = document.querySelector('#content nav form .form-input button .bx');
const searchForm = document.querySelector('#content nav form');

searchButton.addEventListener('click', function (e) {
    if (window.innerWidth < 768) {
        e.preventDefault();
        searchForm.classList.toggle('show');
        if (searchForm.classList.contains('show')) {
            searchButtonIcon.classList.replace('bx-search', 'bx-x');
        } else {
            searchButtonIcon.classList.replace('bx-x', 'bx-search');
        }
    }
});

// Dark Mode Switch - Para persistir a escolha do tema entre páginas
const switchMode = document.getElementById('switch-mode');

// Verifica e aplica a configuração de tema salva no localStorage
document.addEventListener('DOMContentLoaded', () => {
    const savedTheme = localStorage.getItem('theme');
    if (savedTheme === 'dark') {
        document.body.classList.add('dark');
        switchMode.checked = true;  // Marca o checkbox de modo noturno
    } else {
        document.body.classList.remove('dark');
        switchMode.checked = false;  // Desmarca o checkbox de modo noturno
    }

    // Alterna entre o modo claro e escuro
    switchMode.addEventListener('change', function () {
        if (this.checked) {
            document.body.classList.add('dark');
            localStorage.setItem('theme', 'dark'); // Salva a escolha
        } else {
            document.body.classList.remove('dark');
            localStorage.setItem('theme', 'light'); // Salva a escolha
        }
    });
});

// Notification Menu Toggle
document.querySelector('.notification').addEventListener('click', function () {
    document.querySelector('.notification-menu').classList.toggle('show');
    document.querySelector('.profile-menu').classList.remove('show'); // Close profile menu if open
});

// Profile Menu Toggle
document.querySelector('.profile').addEventListener('click', function () {
    document.querySelector('.profile-menu').classList.toggle('show');
    document.querySelector('.notification-menu').classList.remove('show'); // Close notification menu if open
});

// Fecha os menus se clicar fora
window.addEventListener('click', function (e) {
    if (!e.target.closest('.notification') && !e.target.closest('.profile')) {
        document.querySelector('.notification-menu').classList.remove('show');
        document.querySelector('.profile-menu').classList.remove('show');
    }
});

// Função para alternar menus de forma personalizada
function toggleMenu(menuId) {
    var menu = document.getElementById(menuId);
    var allMenus = document.querySelectorAll('.menu');

    // Fecha todos os menus
    allMenus.forEach(function(m) {
        if (m !== menu) {
            m.style.display = 'none';
        }
    });

    // Abre ou fecha o menu clicado
    if (menu.style.display === 'none' || menu.style.display === '') {
        menu.style.display = 'block';
    } else {
        menu.style.display = 'none';
    }
}
document.addEventListener("DOMContentLoaded", function () {
    const profileIcon = document.getElementById("profileIcon");
    const profileMenu = document.getElementById("profileMenu");

    profileIcon.addEventListener("click", function (event) {
        event.preventDefault(); // Impede comportamento padrão do link
        profileMenu.classList.toggle("show"); // Alterna a visibilidade do menu
    });

    // Fecha o menu ao clicar fora dele
    document.addEventListener("click", function (event) {
        if (!profileMenu.contains(event.target) && !profileIcon.contains(event.target)) {
            profileMenu.classList.remove("show");
        }
    });
});

function toggleMessageDetails(header) {
    header.classList.toggle('active');
    const messageBody = header.nextElementSibling;
    
    if (messageBody.style.display === 'none' || messageBody.style.display === '') {
        messageBody.style.display = 'block';
        messageBody.classList.add('show');
    } else {
        messageBody.style.display = 'none';
        messageBody.classList.remove('show');
    }
}