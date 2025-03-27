<?php
session_start();
$host = '';
$user = '';
$password = ''; // sua senha do MySQL
$db = ''; // nome do banco de dados

// Conexão com o banco de dados
$conn = new mysqli($host, $user, $password, $db);
// Função para enviar a mensagem para o Discord
function sendToDiscord($message) {
    $webhook_url = ""; // Substitua com seu URL do webhook do Discord

    $data = json_encode([
        "content" => $message,
        "username" => "Ativação de Key", // Nome que aparecerá como o autor da mensagem
    ]);

    // Inicializa a sessão cURL
    $ch = curl_init($webhook_url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    // Executa a requisição e fecha a sessão cURL
    curl_exec($ch);
    curl_close($ch);
}


if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Login logic
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['login'])) {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);

    // Verifica se o usuário existe e as credenciais estão corretas
    $sql = "SELECT * FROM users WHERE username = '$username' AND password = '$password'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        // Login bem-sucedido
        $row = $result->fetch_assoc();  // Obtém os dados do usuário
        $_SESSION['username'] = $username;

        // Verifica se o usuário tem privilégios de administrador
        if ($row['admin_grant'] == 1) {
            // Redireciona para a página de administração
            header("Location: admin.php");
                            // Enviar notificação ao Discord
                            $message = "Admin Logado!\n\nUsuário: " . $_SESSION['username'] . "\nChave: $key_value";
                            sendToDiscord($message); // Envia a mensagem para o Discord
        } else {
            // Redireciona para o dashboard do usuário
            header("Location: dashboard.php");
        }
        exit; // Encerra o script após o redirecionamento
    } else {
        // Erro no login
        $error_message = "Nome de usuário ou senha incorretos!";
    }
}

// Register logic
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['register'])) {
    $username = mysqli_real_escape_string($conn, $_POST['reg_username']);
    $password = mysqli_real_escape_string($conn, $_POST['reg_password']);
    $confirm_password = mysqli_real_escape_string($conn, $_POST['reg_confirm_password']);
    $email = mysqli_real_escape_string($conn, $_POST['reg_email']);

    if ($password === $confirm_password) {
        // Verifica se o nome de usuário ou email já existe
        $sql_check = "SELECT * FROM users WHERE username = '$username' OR email = '$email'";
        $result_check = $conn->query($sql_check);
        
        if ($result_check->num_rows == 0) {
// Insere o novo usuário com email, user_key, blocked, first_access e ip_address como '00'
$sql_insert = "INSERT INTO users (username, password, email, user_key, blocked, first_access, ip_address) 
               VALUES ('$username', '$password', '$email', 0, 0, 1, '00')";
            if ($conn->query($sql_insert) === TRUE) {
                $success_message = "Usuário criado com sucesso!";
            } else {
                $error_message = "Erro ao criar usuário: " . $conn->error;
            }
        } else {
            $error_message = "Nome de usuário ou email já existe!";
        }
    } else {
        $error_message = "As senhas não coincidem!";
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="style.css">
    <style>
        /* Estilos gerais */
body {
    font-family: Arial, sans-serif;
    background-color: #f0f0f0;
    display: flex;
    justify-content: center;
    align-items: center;
    height: 100vh;
    margin: 0;
}

/* Container de login */
.login-container {
    background-color: #ffffff;
    padding: 30px;
    border-radius: 8px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    width: 300px;
}

h2 {
    text-align: center;
    margin-bottom: 20px;
}

.input-group {
    margin-bottom: 15px;
}

label {
    font-size: 14px;
    color: #333;
    margin-bottom: 5px;
    display: block;
}

input[type="text"], input[type="password"], input[type="email"] {
    width: 100%;
    padding: 10px;
    border-radius: 5px;
    border: 1px solid #ccc;
    font-size: 14px;
    margin-top: 5px;
    box-sizing: border-box;
}

button {
    width: 100%;
    padding: 12px;
    background-color: #007BFF;
    color: #fff;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    font-size: 16px;
}

button:hover {
    background-color: #0056b3;
}

/* Mensagens de erro e sucesso */
.error, .success {
    color: #ff4d4d;
    background-color: #ffe6e6;
    padding: 10px;
    border-radius: 5px;
    margin-bottom: 20px;
    text-align: center;
}

.success {
    color: #28a745;
    background-color: #d4edda;
}

/* Estilos para o modal de registro */
.modal {
    display: none;
    position: fixed;
    z-index: 1;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    overflow: auto;
    background-color: rgba(0, 0, 0, 0.4);
    padding-top: 50px;
}

.modal-content {
    background-color: white;
    margin: 5% auto;
    padding: 20px;
    border: 1px solid #888;
    width: 400px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    border-radius: 8px;
}

.close {
    color: #aaa;
    float: right;
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
}

.close:hover,
.close:focus {
    color: black;
    text-decoration: none;
    cursor: pointer;
}

/* Estilos adicionais para o formulário de registro */
input[type="email"], input[type="text"], input[type="password"] {
    margin-top: 8px;
}

#registerModal h2 {
    margin-top: 0;
}

/* Botão de registro */
#registerBtn {
    margin-top: 15px;
    background-color: #28a745;
    color: #fff;
    padding: 10px 20px;
    border-radius: 5px;
    font-size: 16px;
    cursor: pointer;
    border: none;
}

#registerBtn:hover {
    background-color: #218838;
}

    </style>
</head>
<body>
    <div class="login-container">
        <h2>Login</h2>
        <?php if (isset($error_message)): ?>
            <div class="error"><?php echo $error_message; ?></div>
        <?php endif; ?>
        <?php if (isset($success_message)): ?>
            <div class="success"><?php echo $success_message; ?></div>
        <?php endif; ?>
        <form action="login.php" method="post">
            <div class="input-group">
                <label for="username">Username:</label>
                <input type="text" name="username" id="username" required>
            </div>
            <div class="input-group">
                <label for="password">Password:</label>
                <input type="password" name="password" id="password" required>
            </div>
            <button type="submit" name="login">Login</button>
        </form>
        <button id="registerBtn">Register</button>
    </div>

    <!-- Modal for registration -->
    <div id="registerModal" class="modal">
        <div class="modal-content">
            <span class="close" id="closeModal">&times;</span>
            <h2>Register</h2>
            <form action="login.php" method="post">
                <div class="input-group">
                    <label for="reg_username">Username:</label>
                    <input type="text" name="reg_username" id="reg_username" required>
                </div>
                <div class="input-group">
                    <label for="reg_password">Password:</label>
                    <input type="password" name="reg_password" id="reg_password" required>
                </div>
                <div class="input-group">
                    <label for="reg_confirm_password">Confirm Password:</label>
                    <input type="password" name="reg_confirm_password" id="reg_confirm_password" required>
                </div>
                <div class="input-group">
                    <label for="reg_email">Email:</label>
                    <input type="email" name="reg_email" id="reg_email" required>
                </div>
                <button type="submit" name="register">Register</button>
            </form>
        </div>
    </div>

    <script>
        // Modal functionality
        var modal = document.getElementById("registerModal");
        var btn = document.getElementById("registerBtn");
        var span = document.getElementById("closeModal");

        btn.onclick = function() {
            modal.style.display = "block";
        }

        span.onclick = function() {
            modal.style.display = "none";
        }

        window.onclick = function(event) {
            if (event.target == modal) {
                modal.style.display = "none";
            }
        }
    </script>
</body>
</html>
