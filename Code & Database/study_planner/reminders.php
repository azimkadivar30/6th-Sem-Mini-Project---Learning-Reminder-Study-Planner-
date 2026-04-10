<?php
if (session_status() === PHP_SESSION_NONE)
    session_start();
include("config.php");

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$user_id = (int) $_SESSION['user_id'];
$msg = "";
$msgType = ""; // success | error

// ✅ ADD REMINDER
if (isset($_POST['add_reminder'])) {
    $title = trim($_POST['title'] ?? "");
    $time = trim($_POST['reminder_time'] ?? "");

    if ($title === "" || $time === "") {
        $msg = "Please fill all fields.";
        $msgType = "error";
    } else {
        // datetime-local -> "YYYY-mm-dd HH:ii:ss"
        $time = str_replace("T", " ", $time) . ":00";

        if (strtotime($time) === false) {
    $msg = "Invalid date and time.";
    $msgType = "error";
} elseif (strtotime($time) < time()) {
    $msg = "Past date/time is not allowed.";
    $msgType = "error";
} else {

            // check if task_id column exists
            $hasTaskId = false;
            $check = $conn->query("SHOW COLUMNS FROM reminders LIKE 'task_id'");
            if ($check && $check->num_rows > 0)
                $hasTaskId = true;

            if ($hasTaskId) {
                $task_id = NULL;
                $stmt = $conn->prepare("INSERT INTO reminders (user_id, title, task_id, reminder_time, status) VALUES (?, ?, ?, ?, 'pending')");
                $stmt->bind_param("isis", $user_id, $title, $task_id, $time);
            } else {
                $stmt = $conn->prepare("INSERT INTO reminders (user_id, title, reminder_time, status) VALUES (?, ?, ?, 'pending')");
                $stmt->bind_param("iss", $user_id, $title, $time);
            }

            $stmt->execute();
            $stmt->close();

            $_SESSION['reminder_msg'] = "Reminder set successfully ✅";
            $_SESSION['reminder_msg_type'] = "success";
            header("Location: reminders.php");
            exit();
        }
    }
}

// ✅ MARK DONE
if (isset($_GET['done'])) {
    $id = (int) $_GET['done'];
    $stmt = $conn->prepare("UPDATE reminders SET status='done' WHERE id=? AND user_id=?");
    $stmt->bind_param("ii", $id, $user_id);
    $stmt->execute();
    header("Location: reminders.php");
    exit();
}

// ✅ DELETE
if (isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM reminders WHERE id=? AND user_id=?");
    $stmt->bind_param("ii", $id, $user_id);
    $stmt->execute();
    header("Location: reminders.php");
    exit();
}

// ✅ FETCH REMINDERS
$stmt = $conn->prepare("SELECT * FROM reminders WHERE user_id=? ORDER BY reminder_time ASC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$reminders = $stmt->get_result();

$now = date("Y-m-d H:i:s");
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Reminders | Study Planner</title>
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
    <h1>Reminders ⏰</h1>

    <?php if ($msg !== ""): ?>
        <div class="msg <?php echo $msgType; ?>"><?php echo htmlspecialchars($msg); ?></div>
    <?php endif; ?>

    <div class="rem-card">
        <form method="POST">
            <label>Reminder Title</label>
            <input type="text" name="title" placeholder="Eg: Complete OS Assignment" required>

            <label class="rem-label">Reminder Date & Time</label>
            <input type="datetime-local" name="reminder_time" min="<?php echo date('Y-m-d\TH:i'); ?>" required>

            <div class="rem-actions">
                <button class="btn" name="add_reminder">Set Reminder</button>
            </div>
        </form>
    </div>

    <div class="rem-list" id="reminderList">
        <?php if ($reminders && $reminders->num_rows > 0): ?>

            <?php while ($r = $reminders->fetch_assoc()):
                $status = $r['status'] ?? 'pending';
                $isOverdue = ($status === 'pending' && $r['reminder_time'] < $now);
                ?>
                <div class="rem-item"
                    data-title="<?php echo htmlspecialchars($r['title'] ?? 'Reminder'); ?>"
                    data-time="<?php echo htmlspecialchars($r['reminder_time']); ?>"
                    data-status="<?php echo htmlspecialchars($status); ?>">

                    <div class="rem-left">
                        <strong><?php echo htmlspecialchars($r['title'] ?? 'Reminder'); ?></strong>
                        <small><?php echo date("d M Y, h:i A", strtotime($r['reminder_time'])); ?></small>
                    </div>

                    <div class="rem-right">
                        <?php
                        if ($status === 'done') {
                            echo '<span class="badge done">Done</span>';
                        } elseif ($isOverdue) {
                            echo '<span class="badge overdue">Overdue</span>';
                        } else {
                            echo '<span class="badge pending">Pending</span>';
                        }
                        ?>

                        <span class="rem-btns">
                            <?php if ($status !== 'done'): ?>
                                <a class="doneBtn" href="?done=<?php echo (int) $r['id']; ?>"
                                    onclick="return confirm('Mark as done?')">✔ Done</a>
                            <?php endif; ?>

                            <a class="delBtn" href="?delete=<?php echo (int) $r['id']; ?>"
                                onclick="return confirm('Delete this reminder?')">🗑 Delete</a>
                        </span>
                    </div>
                </div>

            <?php endwhile; ?>

        <?php else: ?>
            <div class="rem-card">No reminders added yet.</div>
        <?php endif; ?>
    </div>
</div>

<script>
    if ("Notification" in window) Notification.requestPermission();

    let fired = new Set();

    function checkReminders() {
        const items = document.querySelectorAll(".rem-item");
        const now = Date.now();

        items.forEach(item => {
            const status = item.dataset.status;
            if (status !== "pending") return;

            const title = item.dataset.title || "Study Reminder";
            const timeStr = (item.dataset.time || "").replace(" ", "T");
            const t = new Date(timeStr).getTime();

            if (!isNaN(t) && t <= now) {
                const key = title + "|" + timeStr;
                if (fired.has(key)) return;
                fired.add(key);

                if (Notification.permission === "granted") {
                    new Notification("Study Reminder 🔔", { body: title });
                }
            }
        });
    }

    setInterval(checkReminders, 10000);
    checkReminders();
</script>

</body>
</html>