<?php
session_start();

// Verificar se o usuário está logado como administrador
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}

$host = '';
$user = '';
$password = '';
$db = '';

$conn = new mysqli($host, $user, $password, $db);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Obter a lista de usuários
$sql = "SELECT username FROM users";
$result = $conn->query($sql);
$users = [];
while ($row = $result->fetch_assoc()) {
    $users[] = $row['username'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel</title>
    <link rel="stylesheet" href="style.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f0f0f0;
            margin: 0;
            padding: 0;
            text-align: center;
        }

        .admin-container {
            width: 50%;
            margin: 50px auto;
            background-color: #ffffff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        h2 {
            margin-bottom: 20px;
        }

        .manage-btn {
            background-color: #007BFF;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
        }

        .manage-btn:hover {
            background-color: #0056b3;
        }

        .dropdown {
            display: none;
            background-color: white;
            border: 1px solid #ddd;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            position: absolute;
            left: 50%;
            transform: translateX(-50%);
            margin-top: 10px;
            padding: 15px;
            border-radius: 5px;
        }

        .dropdown select, .dropdown button {
            width: 100%;
            padding: 10px;
            margin: 5px 0;
            border-radius: 5px;
            border: 1px solid #ddd;
            cursor: pointer;
        }

        .dropdown button {
            background-color: #28a745;
            color: white;
            border: none;
        }

        .dropdown button:hover {
            background-color: #218838;
        }

        /* Modal */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
        }

        .modal-content {
            background-color: white;
            padding: 20px;
            border-radius: 5px;
            width: 300px;
            margin: 15% auto;
            text-align: center;
        }

        .close {
            float: right;
            cursor: pointer;
            font-size: 20px;
        }
    </style>
</head>
<body>

    <div class="admin-container">
        <h2>Admin Panel</h2>
        <button class="manage-btn" onclick="toggleDropdown()">Manage</button>

        <!-- Dropdown Menu -->
        <div class="dropdown" id="manageDropdown">
            <select id="selectedUser">
                <option value="">Select a user</option>
                <?php foreach ($users as $user): ?>
                    <option value="<?php echo $user; ?>"><?php echo $user; ?></option>
                <?php endforeach; ?>
            </select>
            <button onclick="manageUser('block')">Block</button>
            <button onclick="manageUser('delete')">Delete</button>
            <button onclick="manageUser('reset_hwid')">Reset HWID</button>
            <button onclick="manageUser('info')">Info</button>
        </div>
    </div>

    <!-- Modal para exibir informações do usuário -->
    <div id="infoModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <h3>User Info</h3>
            <p id="userInfo"></p>
        </div>
    </div>

    <script>
        function toggleDropdown() {
            var dropdown = document.getElementById('manageDropdown');
            dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
        }

        function manageUser(action) {
            var username = document.getElementById("selectedUser").value;
            if (!username) {
                alert("Please select a user first!");
                return;
            }

            if (action === "info") {
                fetch('get_user_info.php?username=' + username)
                    .then(response => response.json())
                    .then(data => {
                        if (data.error) {
                            alert(data.error);
                        } else {
                            document.getElementById('userInfo').innerHTML =
                                "Username: " + data.username + "<br>" +
                                "Password: " + data.password + "<br>" +
                                "User Key: " + data.user_key + "<br>" +
                                "Blocked: " + (data.blocked ? "Yes" : "No") + "<br>" +
                                "Last Reset: " + data.last_reset;
                            document.getElementById('infoModal').style.display = 'block';
                        }
                    });
            } else {
                window.location.href = 'admin.php?action=' + action + '&username=' + username;
            }
        }

        function closeModal() {
            document.getElementById('infoModal').style.display = 'none';
        }
    </script>

</body>
</html>

<?php
$conn->close();
?>
