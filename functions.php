<?php
require_once 'db.php';

function registerUser($phone, $name, $pin, $role, $class = null) {
    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO users (name, pin, role, class, phone) VALUES (?, ?, ?, ?, ?)");
    return $stmt->execute([$name, $pin, $role, $class, $phone]);
}

function loginUser($username, $pin, $role) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM users WHERE name = ? AND pin = ? AND role = ?");
    $stmt->execute([$username, $pin, $role]);
    return $stmt->fetch();
}

function saveAttendance($student_ids, $class, $date) {
    global $pdo;
    $ids = explode(",", $student_ids);
    foreach ($ids as $sid) {
        $stmt = $pdo->prepare("INSERT INTO attendance (student_id, class, date, status) VALUES (?, ?, ?, 'present')");
        $stmt->execute([$sid, $class, $date]);
    }
    return true;
}

function saveGrade($student_id, $subject, $grade) {
    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO grades (student_id, subject, grade) VALUES (?, ?, ?)");
    return $stmt->execute([$student_id, $subject, $grade]);
}

function getAttendanceSummary($student_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT status, COUNT(*) as total FROM attendance WHERE student_id = ? GROUP BY status");
    $stmt->execute([$student_id]);
    return $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
}

function getStudentGrades($student_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT subject, grade FROM grades WHERE student_id = ?");
    $stmt->execute([$student_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

?>
