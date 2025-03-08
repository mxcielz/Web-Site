<style>
        @import url('https://fonts.googleapis.com/css?family=Poppins');

        /* BASIC */
        html {
            background: linear-gradient(120deg, #56baed, #39ace7);
            min-height: 100vh;
        }

        body {
            font-family: "Poppins", sans-serif;
            height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            margin: 0;
            background: transparent;
        }

        a {
            color: #56baed;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        a:hover {
            color: #39ace7;
        }

        h2 {
            text-align: center;
            font-size: 24px;
            font-weight: 600;
            text-transform: uppercase;
            margin: 20px 0;
            color: #333;
            letter-spacing: 1px;
        }

        /* STRUCTURE */
        .wrapper {
            width: 100%;
            padding: 20px;
            display: flex;
            justify-content: center;
        }

        #formContent {
            background: rgba(255, 255, 255, 0.95);
            padding: 40px 30px;
            width: 100%;
            max-width: 400px;
            position: relative;
            border-radius: 15px;
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
            text-align: center;
            backdrop-filter: blur(10px);
        }

        #formFooter {
            margin-top: 25px;
            padding: 20px;
            text-align: center;
            border-top: 1px solid rgba(0, 0, 0, 0.1);
        }

        /* FORM TYPOGRAPHY */
        input[type=text], input[type=password] {
            width: 100%;
            padding: 15px;
            margin: 10px 0;
            border: 2px solid #f0f0f0;
            border-radius: 8px;
            font-size: 16px;
            color: #333;
            transition: all 0.3s ease;
            background: rgba(255, 255, 255, 0.9);
        }

        input[type=text]:focus, input[type=password]:focus {
            border-color: #56baed;
            box-shadow: 0 0 8px rgba(86, 186, 237, 0.2);
            outline: none;
        }

        input[type=submit] {
            background: linear-gradient(to right, #56baed, #39ace7);
            border: none;
            color: white;
            padding: 15px 40px;
            border-radius: 30px;
            font-size: 16px;
            font-weight: 500;
            letter-spacing: 1px;
            cursor: pointer;
            margin: 20px 0;
            transition: all 0.3s ease;
            width: auto;
            min-width: 200px;
            box-shadow: 0 5px 15px rgba(86, 186, 237, 0.3);
        }

        input[type=submit]:hover {
            transform: translateY(-2px);
            box-shadow: 0 7px 20px rgba(86, 186, 237, 0.4);
        }

        /* Logo */
        .logo {
            width: 120px;
            height: auto;
            margin-bottom: 20px;
            transition: transform 0.3s ease;
        }

        .logo:hover {
            transform: scale(1.05);
        }

        /* Error Box */
        .error-box {
            background-color: rgba(248, 215, 218, 0.9);
            color: #721c24;
            padding: 12px 20px;
            border-radius: 8px;
            margin: 15px 0;
            font-size: 14px;
            border: 1px solid #f5c6cb;
            animation: fadeIn 0.5s ease;
        }

        /* Animations */
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .fadeIn {
            animation: fadeIn 0.5s ease;
        }

        /* Loader */
        #loader {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.95);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
            transition: all 0.5s ease;
        }

        .spinner {
            width: 40px;
            height: 40px;
            border: 4px solid #f3f3f3;
            border-top: 4px solid #56baed;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Responsive */
        @media (max-width: 480px) {
            #formContent {
                padding: 30px 20px;
            }

            input[type=submit] {
                width: 100%;
            }

            h2 {
                font-size: 20px;
            }
        }
    </style>
</head>
<body>
    <div id="loader">
        <div class="spinner"></div>
    </div>
    <div class="wrapper fadeInDown">
        <div id="formContent">
            <!-- Adicionando a logo -->
            <img src="./imagem/logo.png" alt="Logo" class="logo">
            
            <h2 class="active">Logar Usuário</h2>
            
            <!-- Formulário de login -->
            <form action="loginprocess.php" method="POST">
                <input type="text" id="username" name="username" class="fadeIn second" placeholder="Usuário" required>
                <input type="password" id="password" name="password" class="fadeIn third" placeholder="Senha" required>
                <input type="submit" class="fadeIn fourth" value="Entrar">
                
                <?php
                if (isset($_GET['erro']) && $_GET['erro'] == 'senha_incorreta') {
                    echo '<div class="error-box">Senha ou Usuário incorreta! Tente novamente.</div>';
                }
                ?>
            </form>
            <form action="loginregister.php" method="POST">
                <input type="submit" class="fadeIn fourth" value="Validar Acesso">
            </form>
        </div>
    </div>

    <script>
        window.addEventListener('load', () => {
            setTimeout(() => {
                const loader = document.getElementById('loader');
                loader.style.opacity = '0';
                loader.style.visibility = 'hidden';
            }, 1500); 
        });
    </script>
</body>
</html> 