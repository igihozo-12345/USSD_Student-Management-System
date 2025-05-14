<?php
require_once 'functions.php';
require_once 'vendor/autoload.php';
use AfricasTalking\SDK\AfricasTalking;

// === SMS Class ===
class Sms {
    protected $phone;
    protected $AT;

    function __construct($phone){
        $this->phone = $phone;
        $this->AT = new AfricasTalking("sandbox", "atsk_fa70e8e44d04637f2753547c36e3cfc7009c7ad018f246a93eedaf2e06f8eecb3d23217b");
    }

    public function sendSMS($message, $recipient){
        $sms = $this->AT->sms();
        $result = $sms->send([
            'to' => $recipient,
            'message' => $message,
            'from' => "Myussd"
        ]);
        return $result;
    }
}

// === USSD LOGIC ===
$sessionId = $_POST["sessionId"] ?? "";
$serviceCode = $_POST["serviceCode"] ?? "";
$phone = $_POST["phoneNumber"] ?? "";
$text = $_POST["text"] ?? "";

$parts = explode("*", $text);
$level = count($parts);
$response = "";

// Handle 'Go Back'
if ($level > 0 && end($parts) === "3") {
    array_pop($parts);
    if (count($parts) > 0) {
        array_pop($parts);
    }
    $text = implode("*", $parts);
    $parts = explode("*", $text);
    $level = count($parts);
}

// Handle 'Main Menu'
if ($level > 0 && end($parts) === "4") {
    $text = "";
    $parts = [];
    $level = 0;
}

// Main Menu Function
function mainMenu() {
    return "CON Welcome to SmartSchool\n1. Register\n2. Login";
}

if ($text == "") {
    echo mainMenu();
    exit;
}

// === REGISTRATION FLOW ===
if ($parts[0] == "1") {
    if ($level == 1) {
        $response = "CON Choose Role:\n1. Teacher\n2. Student\n3. Go Back\n4. Main Menu";
    } else if ($level == 2) {
        $response = "CON Enter Username:\n3. Go Back\n4. Main Menu";
    } else if ($level == 3) {
        $response = "CON Set 4-digit PIN:\n3. Go Back\n4. Main Menu";
    } else if ($level == 4 && $parts[1] == "1") {
        registerUser($phone, $parts[2], $parts[3], "teacher");
        $sms = new Sms($phone);
        $sms->sendSMS("You have been successfully registered as a Teacher in SmartSchool.", $phone);
        $response = "END Teacher registered successfully.";
    } else if ($level == 4 && $parts[1] == "2") {
        $response = "CON Enter Class:\n3. Go Back\n4. Main Menu";
    } else if ($level == 5 && $parts[1] == "2") {
        registerUser($phone, $parts[2], $parts[3], "student", $parts[4]);
        $sms = new Sms($phone);
        $sms->sendSMS("You have been successfully registered as a Student in SmartSchool.", $phone);
        $response = "END Student registered successfully.";
    }

// === LOGIN FLOW ===
} else if ($parts[0] == "2") {
    if ($level == 1) {
        $response = "CON Choose Role:\n1. Teacher\n2. Student\n3. Go Back\n4. Main Menu";
    } else if ($level == 2) {
        $response = "CON Enter Username:\n3. Go Back\n4. Main Menu";
    } else if ($level == 3) {
        $response = "CON Enter 4-digit PIN:\n3. Go Back\n4. Main Menu";
    } else {
        $role = $parts[1] == "1" ? "teacher" : "student";
        $username = $parts[2];
        $pin = $parts[3];
        $user = loginUser($username, $pin, $role);

        if (!$user) {
            $response = "END Login failed. Check username or PIN.";
        } else if ($role == "teacher" && $level == 4) {
            $response = "CON Welcome Teacher\n1. Take Attendance\n2. Enter Grades\n3. Go Back\n4. Main Menu";

        // === ATTENDANCE ===
        } else if ($role == "teacher" && $parts[4] == "1" && $level == 5) {
            $response = "CON Enter Class Name:\n3. Go Back\n4. Main Menu";
        } else if ($role == "teacher" && $parts[4] == "1" && $level == 6) {
            $response = "CON Enter Date (YYYY-MM-DD):\n3. Go Back\n4. Main Menu";
        } else if ($role == "teacher" && $parts[4] == "1" && $level == 7) {
            $response = "CON Enter Student IDs (comma-separated):\n3. Go Back\n4. Main Menu";
        } else if ($role == "teacher" && $parts[4] == "1" && $level == 8) {
            saveAttendance($parts[7], $parts[5], $parts[6]);
            $sms = new Sms($phone);
            $sms->sendSMS("Attendance for class {$parts[5]} on {$parts[6]} has been saved successfully.", $phone);
            $response = "END Attendance saved successfully.";

        // === GRADING ===
        } else if ($role == "teacher" && $parts[4] == "2" && $level == 5) {
            $response = "CON Enter Student ID:\n3. Go Back\n4. Main Menu";
        } else if ($role == "teacher" && $parts[4] == "2" && $level == 6) {
            $response = "CON Enter Subject:\n3. Go Back\n4. Main Menu";
        } else if ($role == "teacher" && $parts[4] == "2" && $level == 7) {
            $response = "CON Enter Grade:\n3. Go Back\n4. Main Menu";
        } else if ($role == "teacher" && $parts[4] == "2" && $level == 8) {
            $response = "CON Confirm save?\n1. Confirm\n2. Cancel\n3. Go Back\n4. Main Menu";
        } else if ($role == "teacher" && $parts[4] == "2" && $level == 9) {
            if ($parts[8] == "1") {
                saveGrade($parts[5], $parts[6], $parts[7]);
                $sms = new Sms($phone);
                $sms->sendSMS("Grade for student {$parts[5]} in {$parts[6]} has been saved successfully.", $phone);
                $response = "END Grade saved.";
            } else if ($parts[8] == "2") {
                $response = "END Cancelled.";
            } else {
                $response = "END Action aborted.";
            }

        // === STUDENT VIEW ===
        } else if ($role == "student" && $level == 4) {
            $response = "CON Welcome Student\n1. View Attendance\n2. View Grades\n3. Go Back\n4. Main Menu";
        } else if ($role == "student" && $parts[4] == "1") {
            $summary = getAttendanceSummary($user['id']);
            $present = $summary['present'] ?? 0;
            $absent = $summary['absent'] ?? 0;
            $response = "END Attendance Summary:\nPresent: $present\nAbsent: $absent";
        } else if ($role == "student" && $parts[4] == "2") {
            $grades = getStudentGrades($user['id']);
            if (empty($grades)) {
                $response = "END No grades found.";
            } else {
                $msg = "Grades:\n";
                foreach ($grades as $g) {
                    $msg .= $g['subject'] . ": " . $g['grade'] . "\n";
                }
                $response = "END " . trim($msg);
            }
        }
    }
}

header('Content-type: text/plain');
echo $response;
