<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
include "db.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION["user_id"];
$note_id = $_GET["id"] ?? null;

if (!$note_id) {
    die("Invalid note ID.");
}

// Check if user owns the note or it’s shared with them
$stmt = $conn->prepare("
    SELECT n.*, 
           (n.user_id = ?) AS is_owner
    FROM notes n
    LEFT JOIN shared_notes s ON n.id = s.note_id
    WHERE n.id = ? AND (n.user_id = ? OR s.shared_with_user_id = ?)
    LIMIT 1
");
$stmt->bind_param("iiii", $user_id, $note_id, $user_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
$note = $result->fetch_assoc();

if (!$note) {
    header("Location: notes.php?error=note_deleted");
    exit();}

$conflict = false;
$latestNote = $note; // Keep DB version for conflict UI

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $title = $_POST["title"];
    $content = $_POST["content"];
    $last_updated = $_POST["last_updated"];

    // Fetch the latest timestamp again
    $stmt = $conn->prepare("SELECT updated_at FROM notes WHERE id = ?");
    $stmt->bind_param("i", $note_id);
    $stmt->execute();
    $stmt->bind_result($current_updated_at);
    $stmt->fetch();
    $stmt->close();

    if ($last_updated !== $current_updated_at) {
        // Conflict detected
        $conflict = true;
    } else {
        // Safe to update
        $stmt = $conn->prepare("UPDATE notes SET title = ?, content = ?, updated_at = NOW() WHERE id = ?");
        $stmt->bind_param("ssi", $title, $content, $note_id);
        $stmt->execute();
        header("Location: notes.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Note</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<header>
    <h2>Edit Note</h2>
    <a href="notes.php">⬅ Back</a>
</header>

<div class="container">

<!-- <p id="save-status" style="color: gray; font-size: 14px;"></p> -->

    <?php if ($conflict): ?>
        <div class="conflict-warning">
            ⚠️ <strong>Conflict detected!</strong><br>
            Someone else updated this note since you started editing.

            <h4>🔹 Your Changes:</h4>
            <pre><?php echo htmlspecialchars($_POST["content"]); ?></pre>

            <h4>🔹 Latest Version in Database:</h4>
            <pre><?php echo htmlspecialchars($latestNote["content"]); ?></pre>

            <p>Please copy your changes and reapply them if needed.</p>
        </div>
    <?php endif; ?>

    <form method="POST">
        <input type="hidden" name="last_updated" value="<?php echo htmlspecialchars($note["updated_at"]); ?>">
        
        <input type="text" name="title" 
               value="<?php echo htmlspecialchars($note["title"]); ?>" 
               placeholder="Note Title" required>
        
        <textarea name="content" rows="10" required 
                  placeholder="Write your note here..."><?php echo htmlspecialchars($note["content"]); ?></textarea>
        
        <button type="submit">💾 Save Changes</button>
    </form>
</div>
/*
setInterval(function() {
    const form = document.querySelector("form");
    const formData = new FormData(form);

    fetch("autosave.php", {
        method: "POST",
        body: formData
    })
    .then(res => res.text())
    .then(msg => {
        document.getElementById("save-status").textContent = "💾 Saved!";
        setTimeout(() => document.getElementById("save-status").textContent = "", 2000);
    })
    .catch(err => console.error("Autosave failed", err));
}, 30000);
*/
</body>
</html>