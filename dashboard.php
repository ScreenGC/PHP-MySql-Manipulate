<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

$host = '';
$user = '';
$password = ''; // sua senha do MySQL
$db = ''; // nome do banco de dados

// Conexão com o banco de dados
$conn = new mysqli($host, $user, $password, $db);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

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
// Verifica se a chave do usuário expirou
$sql_check_expiration = "SELECT key_expiration_date FROM users WHERE username = '" . $_SESSION['username'] . "'";
$result_check_expiration = $conn->query($sql_check_expiration);

if ($result_check_expiration === false) {
    die("Erro na consulta para verificar expiração da chave: " . $conn->error);
}

$row_check_expiration = $result_check_expiration->fetch_assoc();
$key_expiration_date = $row_check_expiration['key_expiration_date'];

if ($key_expiration_date && strtotime($key_expiration_date) < time()) {
    // A chave expirou
    // Bloquear o usuário
    $sql_update_status = "UPDATE users SET blocked = 1 WHERE username = '" . $_SESSION['username'] . "'";
    if ($conn->query($sql_update_status) === TRUE) {
        echo "<p>Seu acesso foi bloqueado devido ao término do tempo de ativação da chave.</p>";
        // Enviar notificação ao Discord
        $message = "A chave do usuário expirou!\n\nUsuário: " . $_SESSION['username'];
        sendToDiscord($message);
        // Redireciona para a página de login
        header("Location: login.php");
        exit();
    } else {
        echo "<p>Erro ao bloquear o usuário: " . $conn->error . "</p>";
    }
}


// Verifica se o usuário tem uma chave
$sql_check_key = "SELECT user_key FROM users WHERE username = '" . $_SESSION['username'] . "'";
$result_check_key = $conn->query($sql_check_key);

// Verificar se a consulta foi bem-sucedida
if ($result_check_key === false) {
    die("Erro na consulta para verificar chave do usuário: " . $conn->error); // Exibe o erro da consulta, se houver
}

$row_check_key = $result_check_key->fetch_assoc();
$user_key = $row_check_key['user_key'];

// Ativação de chave
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['activate_key'])) {
    $key_value = mysqli_real_escape_string($conn, $_POST['key_value']);

    // Verificar se a chave existe e se já foi usada
    $sql_check_key_value = "SELECT * FROM `user_keys` WHERE key_value = '$key_value'"; // Usando a tabela correta

    $result_check_key_value = $conn->query($sql_check_key_value);

    // Verificar se a consulta foi bem-sucedida
    if ($result_check_key_value === false) {
        die("Erro na consulta para verificar chave: " . $conn->error); // Exibe o erro da consulta, se houver
    }

    if ($result_check_key_value->num_rows > 0) {
        $row_key = $result_check_key_value->fetch_assoc();
        if ($row_key['used'] == 1) {
            $error_message = "Esta chave já foi utilizada.";
        } else {
            // Ativar a chave para o usuário
            $key_value = mysqli_real_escape_string($conn, $_POST['key_value']);
            $sql_update_key = "UPDATE users SET user_key = '$key_value' WHERE username = '" . $_SESSION['username'] . "'";
            $sql_update_key_status = "UPDATE `user_keys` SET used = 1 WHERE key_value = '$key_value'"; // Usando a tabela correta
            

            if ($conn->query($sql_update_key) === TRUE && $conn->query($sql_update_key_status) === TRUE) {
                // Calcular a data de expiração
                $tempo_key = $row_key['tempo_key']; // Obtém o tempo de validade da chave
                $expiration_date = date('Y-m-d H:i:s', strtotime("+$tempo_key days")); // Define a data de expiração
            
                // Atualiza a data de ativação e a data de expiração no usuário
                $sql_update_dates = "UPDATE users SET key_activation_date = NOW(), key_expiration_date = '$expiration_date' WHERE username = '" . $_SESSION['username'] . "'";
            
                if ($conn->query($sql_update_dates) === TRUE) {
                    $success_message = "Chave ativada com sucesso!";
                    
                    // Enviar notificação ao Discord
                    $message = "A chave foi ativada com sucesso!\n\nUsuário: " . $_SESSION['username'] . "\nChave: $key_value";
                    sendToDiscord($message); // Envia a mensagem para o Discord
            
                    // Destrói todas as variáveis de sessão
                    session_unset();
                    session_destroy();
            
                    // Redireciona para a página de login
                    header("Location: login.php");
                    exit();
                } else {
                    $error_message = "Erro ao atualizar a data de ativação e expiração.";
                }
            } else {
                $error_message = "Erro ao ativar chave.";
            }
            
        }
    } else {
        $error_message = "A chave inserida não existe.";
    }
}


// Reset HWID
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['reset_hwid'])) {
    $username = $_SESSION['username'];

    // Verificar a data do último reset
    $sql_check = "SELECT last_reset FROM users WHERE username = '$username'";
    $result = $conn->query($sql_check);

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $last_reset = $row['last_reset'];

        // Verifica se o último reset foi hoje
        if ($last_reset && date('Y-m-d', strtotime($last_reset)) == date('Y-m-d')) {
            echo "<p>Você já resetou o HWID hoje. Tente novamente amanhã.</p>";
        } else {
            // Atualizar HWID e data do último reset
            $sql = "UPDATE users SET first_access = 1, ip_address = 11, last_reset = NOW() WHERE username = '$username'";
            if ($conn->query($sql) === TRUE) {
                echo "<p>HWID resetado com sucesso!</p>";
                // Enviar notificação ao Discord
                $message = "Reset hwid!\n\nUsuário: " . $_SESSION['username'] . "\nChave: $key_value";
                sendToDiscord($message); // Envia a mensagem para o Discord
            } else {
                echo "<p>Erro ao resetar HWID: " . $conn->error . "</p>";
            }
        }
    } else {
        echo "<p>Usuário não encontrado.</p>";
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel de Controle</title>
    <link rel="stylesheet" href="dash.css">
</head>
<body>
    <div class="container">
        <h1>Bem-vindo, <?php echo $_SESSION['username']; ?>!</h1>

        <?php if ($user_key): ?>
            <!-- Exibe o botão de Reset HWID se o usuário tem uma chave -->
            <form action="dashboard.php" method="post">
                <button type="submit" name="reset_hwid">Reset HWID</button>
            </form>
        <?php else: ?>
            <!-- Exibe o botão de Ativar Key se o usuário não tem chave -->
            <button id="activateKeyBtn">Ativar Key</button>

            <div id="activateKeyForm" style="display: none;">
                <form action="dashboard.php" method="post">
                    <div class="input-group">
                        <label for="key_value">Insira a chave:</label>
                        <input type="text" name="key_value" id="key_value" required>
                    </div>
                    <button type="submit" name="activate_key">Ativar</button>
                </form>
                <?php if (isset($error_message)): ?>
                    <div class="error"><?php echo $error_message; ?></div>
                <?php endif; ?>
                <?php if (isset($success_message)): ?>
                    <div class="success"><?php echo $success_message; ?></div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <br><br>
        <a href="logout.php">Logout</a>
    </div>

    <script>
        // Função para mostrar o formulário de ativação de chave
        var activateKeyBtn = document.getElementById('activateKeyBtn');
        var activateKeyForm = document.getElementById('activateKeyForm');

        activateKeyBtn.onclick = function() {
            activateKeyForm.style.display = 'block';
            activateKeyBtn.style.display = 'none'; // Esconde o botão de ativar key
        }
    </script>
</body>
</html>
