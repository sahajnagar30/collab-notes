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


// Add note
if (isset($_POST["add_note"])) {
    $title = $_POST["title"] ?? "";
    $content = $_POST["content"] ?? "";
    $stmt = $conn->prepare("INSERT INTO notes (user_id, title, content) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $user_id, $title, $content);
    $stmt->execute();
    // (Optional) redirect to clear POST refresh
    header("Location: notes.php");
    exit();
}

// Delete own note
if (isset($_GET["delete"])) {
    $note_id = (int)$_GET["delete"];
    $stmt = $conn->prepare("DELETE FROM notes WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $note_id, $user_id);
    $stmt->execute();
    header("Location: notes.php");
    exit();
}

// Remove myself from a shared note
if (isset($_GET["remove_shared"])) {
    $note_id = (int)$_GET["remove_shared"];
    $stmt = $conn->prepare("DELETE FROM shared_notes WHERE note_id = ? AND shared_with_user_id = ?");
    $stmt->bind_param("ii", $note_id, $user_id);
    if ($stmt->execute()) {
        header("Location: notes.php?removed=success");
    } else {
        header("Location: notes.php?error=remove_failed");
    }
    exit();
}

/* -------------------- Search / Filter -------------------- */

$search    = trim($_GET["search"] ?? "");
$from_date = $_GET["from_date"] ?? "";
$to_date   = $_GET["to_date"] ?? "";

$filter = "";
$filterParams = [];
$filterTypes  = "";

if ($search !== "") {
    $filter       .= " AND n.title LIKE ?";
    $filterParams[] = "%{$search}%";
    $filterTypes  .= "s";
}

// Date range (on created_at)
if ($from_date !== "") {
    $filter       .= " AND DATE(n.created_at) >= ?";
    $filterParams[] = $from_date;
    $filterTypes  .= "s";
}
if ($to_date !== "") {
    $filter       .= " AND DATE(n.created_at) <= ?";
    $filterParams[] = $to_date;
    $filterTypes  .= "s";
}

// My Notes
$sqlMy     = "SELECT n.* FROM notes n WHERE n.user_id = ? $filter ORDER BY n.updated_at DESC";
$paramsMy  = array_merge([$user_id], $filterParams);
$typesMy   = "i" . $filterTypes;

$stmtMy = $conn->prepare($sqlMy);
$stmtMy->bind_param($typesMy, ...$paramsMy);
$stmtMy->execute();
$notes = $stmtMy->get_result();

// Shared Notes (owner’s username as shared_by)
$sqlShared = "
    SELECT n.*, u.username AS shared_by
    FROM shared_notes s
    JOIN notes n ON s.note_id = n.id
    JOIN users u ON n.user_id = u.id
    WHERE s.shared_with_user_id = ? $filter
    ORDER BY n.updated_at DESC
";
$paramsSh = array_merge([$user_id], $filterParams);
$typesSh  = "i" . $filterTypes;

$stmtSh = $conn->prepare($sqlShared);
$stmtSh->bind_param($typesSh, ...$paramsSh);
$stmtSh->execute();
$sharedNotes = $stmtSh->get_result();
?>

<!DOCTYPE html>
<html>
<head>
    <title>My Notes</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<header>
    <h2>Welcome, <?php echo $_SESSION["username"]; ?> 👋</h2>
    <a href="logout.php">Logout</a>
</header>

<div class="container">

    <?php if (isset($_GET["error"])): ?>
        <p class="error">
            <?php
                if ($_GET["error"] === "user_not_found")   echo "❌ User not found!";
                elseif ($_GET["error"] === "cannot_share_self") echo "⚠️ You cannot share a note with yourself.";
                elseif ($_GET["error"] === "already_shared")   echo "⚠️ This note is already shared with that user.";
                elseif ($_GET["error"] === "share_failed")     echo "❌ Failed to share note. Try again.";
                elseif ($_GET["error"] === "remove_failed")    echo "❌ Failed to remove shared note.";
                elseif ($_GET["error"] === "note_deleted")     echo "❌ This note was deleted by the owner.";
            ?>
        </p>
    <?php elseif (isset($_GET["shared"]) && $_GET["shared"] === "success"): ?>
        <p class="success-msg">✅ Note shared successfully!</p>
    <?php elseif (isset($_GET["removed"]) && $_GET["removed"] === "success"): ?>
        <p class="success-msg">✅ Removed from shared note!</p>
    <?php endif; ?>

    <!-- Search & Filter -->
    <form method="GET" action="notes.php" class="search-bar">
        <input type="text" name="search" placeholder="Keyword"
               value="<?php echo htmlspecialchars($search); ?>">
        <input type="date" name="from_date" value="<?php echo htmlspecialchars($from_date); ?>">
        <input type="date" name="to_date"   value="<?php echo htmlspecialchars($to_date); ?>">
        <button type="submit" class="btn-blue">Go</button>
        <a href="notes.php" class="reset-link">Reset</a>
    </form>

    <!-- Create Note -->
    <form method="POST">
        <h3>📝 Create a New Note</h3>
        <input type="text" name="title" placeholder="Note Title" required>
        <textarea name="content" rows="5" placeholder="Write your note here..." required></textarea>
        <button type="submit" name="add_note">Add Note</button>
    </form>

    <!-- Shared With Me -->
    <h3>🤝 Notes Shared With Me</h3>
    <div class="notes">
        <?php while ($srow = $sharedNotes->fetch_assoc()): ?>
            <div class="note shared">
                <h4>
                    <?php echo htmlspecialchars($srow["title"]); ?>
                    <small class="shared-by">(from <?php echo htmlspecialchars($srow["shared_by"]); ?>)</small>
                </h4>
                <p><?php echo nl2br(htmlspecialchars($srow["content"])); ?></p>
                <small>Last updated: <?php echo $srow["updated_at"]; ?></small>
                <a href="edit_note.php?id=<?php echo $srow['id']; ?>" style="font-size:12px;">✏️ Edit</a>
                <a href="notes.php?remove_shared=<?php echo $srow['id']; ?>"
                   onclick="return confirm('Remove this shared note from your list?')"
                   style="font-size:12px;">❌ Remove</a>
            </div>
        <?php endwhile; ?>
    </div>

    <!-- My Notes -->
    <h3>📒 Your Notes</h3>
    <div class="notes">
        <?php while ($row = $notes->fetch_assoc()): ?>
            <div class="note">
                <h4><?php echo htmlspecialchars($row["title"]); ?></h4>
                <p><?php echo nl2br(htmlspecialchars($row["content"])); ?></p>
                <small>Last updated: <?php echo $row["updated_at"]; ?></small><br>

                <a href="edit_note.php?id=<?php echo $row['id']; ?>" style="font-size:12px;">✏️ Edit</a>
                <a href="notes.php?delete=<?php echo $row['id']; ?>"
                   onclick="return confirm('Delete this note?')"
                   style="font-size:12px;">🗑 Delete</a>

                <form method="POST" action="share_note.php" class="share-form">
                    <input type="hidden" name="note_id" value="<?php echo $row['id']; ?>">
                    <input type="text" name="username" placeholder="Share with (username)" required>
                    <button type="submit" class="btn-blue">Share</button>
                </form>
            </div>
        <?php endwhile; ?>
    </div>

</div>
</body>
</html>
