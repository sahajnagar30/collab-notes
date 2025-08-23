<?php
session_start();
include "db.php";

if (!isset($_SESSION["user_id"])) exit;

$note_id = $_POST["id"] ?? null;
$title = $_POST["title"] ?? "";
$content = $_POST["content"] ?? "";

if ($note_id) {
    $stmt = $conn->prepare("UPDATE notes SET title = ?, content = ?, updated_at = NOW() WHERE id = ?");
    $stmt->bind_param("ssi", $title, $content, $note_id);
    $stmt->execute();
    echo "ok";
}
?>