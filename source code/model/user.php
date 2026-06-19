
<?php
require_once __DIR__ . "/dp.php";

class User {
    public static function login($username, $password) {
        global $conn;
        $password = md5($password);
        $sql = "SELECT * FROM users WHERE username = ? AND password = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $username, $password);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public static function addEmployee($username, $password) {
        global $conn;
        $password = md5($password);
        $sql = "INSERT INTO users (username, password, role) VALUES (?, ?, 'employee')";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $username, $password);
        return $stmt->execute();
    }
}
?>
