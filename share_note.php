<?php
session_start();
include "db.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $note_id = $_POST["note_id"];
    $share_with_username = trim($_POST["username"]);
    $owner_id = $_SESSION["user_id"];

    // ✅ Check if user exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->bind_param("s", $share_with_username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        header("Location: notes.php?error=user_not_found");
        exit();
    }

    $row = $result->fetch_assoc();
    $shared_with_user_id = $row["id"];

    // ✅ Prevent sharing with self
    if ($shared_with_user_id == $owner_id) {
        header("Location: notes.php?error=cannot_share_self");
        exit();
    }

    // ✅ Prevent duplicate share
    $check = $conn->prepare("SELECT id FROM shared_notes WHERE note_id = ? AND shared_with_user_id = ?");
    $check->bind_param("ii", $note_id, $shared_with_user_id);
    $check->execute();
    $res = $check->get_result();

    if ($res->num_rows > 0) {
        header("Location: notes.php?error=already_shared");
        exit();
    }

    // ✅ Insert share
    $stmt = $conn->prepare("INSERT INTO shared_notes (note_id, shared_with_user_id, shared_by_user_id) VALUES (?, ?, ?)");
    $stmt->bind_param("iii", $note_id, $shared_with_user_id, $owner_id);

    if ($stmt->execute()) {
        header("Location: notes.php?shared=success");
        exit();
    } else {
        header("Location: notes.php?error=share_failed");
        exit();
    }
}
?>