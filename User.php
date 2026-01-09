<?php
class User {

    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    // Fetch user by username
    public function getUserByUsername($username) {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        return $stmt->fetch();
    }

    // Insert Organizer (Admin creates organizer)
    public function addOrganizer($full_name, $username, $password) {
        $stmt = $this->pdo->prepare("
            INSERT INTO users (full_name, username, password, user_role)
            VALUES (?, ?, ?, 'Organizer')
        ");
        return $stmt->execute([$full_name, $username, $password]);
    }

    // Insert student created later (optional)
    public function addStudent($full_name, $username, $password) {
        $stmt = $this->pdo->prepare("
            INSERT INTO users (full_name, username, password, user_role)
            VALUES (?, ?, ?, 'Student')
        ");
        return $stmt->execute([$full_name, $username, $password]);
    }
}
?>
