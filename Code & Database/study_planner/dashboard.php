<?php
session_start();
include("config.php");

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}


$user_name = $_SESSION['user_name'];
$user_id = (int) $_SESSION['user_id'];

/* ================= TOTAL STUDY HOURS ================= */
$stmt = $conn->prepare("
    SELECT IFNULL(SUM(duration_seconds),0) AS secs
    FROM study_sessions
    WHERE user_id=? AND end_time IS NOT NULL
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$totalSecs = $stmt->get_result()->fetch_assoc()['secs'] ?? 0;
$totalHours = round($totalSecs / 3600, 2);
$stmt->close();

/* ================= STUDY STREAK ================= */
$stmt = $conn->prepare("
  SELECT DISTINCT DATE(completed_at) as d
  FROM tasks
  WHERE user_id=? AND status='completed' AND completed_at IS NOT NULL
  ORDER BY d DESC
  LIMIT 60
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();

$days = [];
while ($r = $res->fetch_assoc()) {
    $days[] = $r['d'];
}

$streak = 0;
$today = new DateTime(date("Y-m-d"));

for ($i = 0; $i < count($days); $i++) {
    $d = new DateTime($days[$i]);
    $expected = clone $today;
    $expected->modify("-" . $streak . " day");
    if ($d->format("Y-m-d") === $expected->format("Y-m-d")) {
        $streak++;
    } else {
        break;
    }
}
$stmt->close();

/* ================= BASIC COUNTS ================= */
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM subjects WHERE user_id=?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$totalSubjects = $stmt->get_result()->fetch_assoc()['total'] ?? 0;
$stmt->close();

$stmt = $conn->prepare("SELECT COUNT(*) as total FROM tasks WHERE user_id=?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$totalTasks = $stmt->get_result()->fetch_assoc()['total'] ?? 0;
$stmt->close();

$stmt = $conn->prepare("SELECT COUNT(*) as total FROM tasks WHERE user_id=? AND status='completed'");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$totalCompleted = $stmt->get_result()->fetch_assoc()['total'] ?? 0;
$stmt->close();

$stmt = $conn->prepare("SELECT COUNT(*) as total FROM tasks WHERE user_id=? AND status='pending'");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$totalPending = $stmt->get_result()->fetch_assoc()['total'] ?? 0;
$stmt->close();

/* ================= OVERDUE ================= */
$todayDate = date("Y-m-d");
$stmt = $conn->prepare("
    SELECT COUNT(*) as total
    FROM tasks
    WHERE user_id=? AND status='pending' AND deadline < ?
");
$stmt->bind_param("is", $user_id, $todayDate);
$stmt->execute();
$overdueTasks = $stmt->get_result()->fetch_assoc()['total'] ?? 0;
$stmt->close();

/* ================= TODAY TASKS ================= */
$stmt = $conn->prepare("
    SELECT tasks.task_name, subjects.name AS subject_name, tasks.deadline
    FROM tasks
    JOIN subjects ON tasks.subject_id = subjects.id
    WHERE tasks.user_id=? AND tasks.deadline=? AND tasks.status='pending'
    ORDER BY tasks.id DESC
    LIMIT 5
");
$stmt->bind_param("is", $user_id, $todayDate);
$stmt->execute();
$todayTasks = $stmt->get_result();
$stmt->close();

/* ================= UPCOMING REMINDERS ================= */
$now = date("Y-m-d H:i:s");
$stmt = $conn->prepare("
    SELECT title, reminder_time
    FROM reminders
    WHERE user_id=? AND status='pending' AND reminder_time >= ?
    ORDER BY reminder_time ASC
    LIMIT 3
");
$stmt->bind_param("is", $user_id, $now);
$stmt->execute();
$upcomingReminders = $stmt->get_result();
$stmt->close();

/* ================= RECENT ================= */
$stmt = $conn->prepare("
    SELECT tasks.*, subjects.name as subject_name
    FROM tasks
    JOIN subjects ON tasks.subject_id = subjects.id
    WHERE tasks.user_id=?
    ORDER BY tasks.id DESC
    LIMIT 5
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$recent = $stmt->get_result();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard</title>
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

    <div class="header">
        <h1>Welcome, <span><?php echo htmlspecialchars($user_name); ?></span> 👋</h1>
        <p id="date"></p>
    </div>

    <div class="cards">
        <div class="card">
            <h3>Total Subjects</h3>
            <p><?php echo $totalSubjects; ?></p>
        </div>
        <div class="card">
            <h3>Total Tasks</h3>
            <p><?php echo $totalTasks; ?></p>
        </div>
        <div class="card">
            <h3>Completed</h3>
            <p><?php echo $totalCompleted; ?></p>
        </div>
        <div class="card">
            <h3>Pending</h3>
            <p><?php echo $totalPending; ?></p>
        </div>
        <div class="card">
            <h3>Study Streak 🔥</h3>
            <p><?php echo $streak; ?> days</p>
        </div>
        <div class="card">
            <h3>Total Study Hours ⏱</h3>
            <p><?php echo $totalHours; ?></p>
        </div>
        <div class="card overdue-card">
            <h3>Overdue Tasks ⚠</h3>
            <p><?php echo $overdueTasks; ?></p>
        </div>
    </div>

    <!-- TODAY -->
    <div class="table-box">
        <h3 style="margin-bottom:15px;">Today's Tasks</h3>
        <table>
            <tr>
                <th>Subject</th>
                <th>Task</th>
                <th>Deadline</th>
            </tr>
            <?php if ($todayTasks->num_rows > 0) {
                while ($t = $todayTasks->fetch_assoc()) { ?>
                    <tr>
                        <td><?php echo htmlspecialchars($t['subject_name']); ?></td>
                        <td><?php echo htmlspecialchars($t['task_name']); ?></td>
                        <td><?php echo date("d M Y", strtotime($t['deadline'])); ?></td>
                    </tr>
                <?php }
            } else { ?>
                <tr>
                    <td colspan="3">No tasks due today 🎉</td>
                </tr>
            <?php } ?>
        </table>
    </div>

    <!-- UPCOMING -->
    <div class="table-box">
        <h3 style="margin-bottom:15px;">Upcoming Reminders</h3>
        <table>
            <tr>
                <th>Title</th>
                <th>Time</th>
            </tr>
            <?php if ($upcomingReminders->num_rows > 0) {
                while ($r = $upcomingReminders->fetch_assoc()) { ?>
                    <tr>
                        <td><?php echo htmlspecialchars($r['title']); ?></td>
                        <td><?php echo date("d M Y, h:i A", strtotime($r['reminder_time'])); ?></td>
                    </tr>
                <?php }
            } else { ?>
                <tr>
                    <td colspan="2">No upcoming reminders</td>
                </tr>
            <?php } ?>
        </table>
    </div>

    <!-- RECENT -->
    <div class="table-box">
        <h3 style="margin-bottom:15px;">Recent Study Tasks</h3>
        <table>
            <tr>
                <th>Subject</th>
                <th>Topic</th>
                <th>Deadline</th>
                <th>Status</th>
            </tr>
            <?php if ($recent->num_rows > 0) { ?>
    <?php while ($row = $recent->fetch_assoc()) { ?>
        <tr>
            <td><?php echo htmlspecialchars($row['subject_name']); ?></td>
            <td><?php echo htmlspecialchars($row['task_name']); ?></td>
            <td><?php echo date("d M Y", strtotime($row['deadline'])); ?></td>
            <td>
                <span class="status <?php echo $row['status']; ?>">
                    <?php echo ucfirst($row['status']); ?>
                </span>
            </td>
        </tr>
    <?php } ?>
<?php } else { ?>
    <tr>
        <td colspan="4">No recent tasks found.</td>
    </tr>
<?php } ?>
        </table>
    </div>

</div>

<script>
    document.getElementById("date").innerText =
        new Date().toLocaleDateString('en-IN', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
</script>

</body>
</html>