<?php
session_start();
include("config.php");

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$user_id = (int)$_SESSION['user_id'];


// ================= ADD SUBJECT =================
if (isset($_POST['add_subject'])) {

    $name = trim($_POST['subject_name']);
    $priority = $_POST['priority'];

    $allowed_priorities = ['high', 'medium', 'low'];

    if ($name !== "" && in_array($priority, $allowed_priorities)) {
        $stmt = $conn->prepare("INSERT INTO subjects (name, priority, user_id) VALUES (?, ?, ?)");
        $stmt->bind_param("ssi", $name, $priority, $user_id);
        $stmt->execute();
        $stmt->close();
    }

    header("Location: subject.php");
    exit();
}


// ================= DELETE SUBJECT =================
if (isset($_GET['delete'])) {

    $id = intval($_GET['delete']);

    $stmt = $conn->prepare("DELETE FROM subjects WHERE id=? AND user_id=?");
    $stmt->bind_param("ii", $id, $user_id);
    $stmt->execute();
    $stmt->close();

    header("Location: subject.php");
    exit();
}


// ================= FETCH SUBJECTS =================
$stmt = $conn->prepare("SELECT * FROM subjects WHERE user_id=? ORDER BY id DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Subjects | Study Planner</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Google Font -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">

    <!-- External CSS -->
    <link rel="stylesheet" href="css/style.css?v=2">
    <link rel="icon" type="image/png" sizes="32x32" href="images/favicon.png">
</head>

<body class="dashboard-page">

<?php include("sidebar.php"); ?>

<div class="main">
    <h1>Manage Subjects 📚</h1>

    <div class="form-card">
        <form method="POST">
            <input type="text" name="subject_name" placeholder="Enter Subject Name" required>

            <select name="priority" required>
                <option value="high">High Priority</option>
                <option value="medium">Medium Priority</option>
                <option value="low">Low Priority</option>
            </select>

            <button class="btn" name="add_subject">Add Subject</button>
        </form>
    </div>

    <div class="subject-list">
        <?php if ($result->num_rows > 0) { ?>
            <?php while ($row = $result->fetch_assoc()) { ?>
                <div class="subject-card">
                    <a class="delete-btn" href="?delete=<?php echo $row['id']; ?>">×</a>
                    <h3><?php echo htmlspecialchars($row['name']); ?></h3>
                    <span class="priority <?php echo $row['priority']; ?>">
                        <?php echo strtoupper($row['priority']); ?> Priority
                    </span>
                </div>
            <?php } ?>
        <?php } else { ?>
            <p>No subjects added yet.</p>
        <?php } ?>
    </div>
</div>

</body>
</html>