<?php
$host = '';
$user = '';
$password = '';
$db = '';

$conn = new mysqli($host, $user, $password, $db);

if ($conn->connect_error) {
    die(json_encode(["error" => "Database connection failed."]));
}

if (isset($_GET['username'])) {
    $username = mysqli_real_escape_string($conn, $_GET['username']);
    $sql = "SELECT username, password, user_key, blocked, last_reset FROM users WHERE username = '$username'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        echo json_encode($result->fetch_assoc());
    } else {
        echo json_encode(["error" => "User not found."]);
    }
}

$conn->close();
?>
