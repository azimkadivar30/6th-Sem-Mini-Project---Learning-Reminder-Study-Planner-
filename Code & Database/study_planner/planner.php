<?php
if (session_status() === PHP_SESSION_NONE) session_start();
include("config.php");

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$user_id = (int)$_SESSION['user_id'];
$msg = "";
$msgType = "";

/* =========================
   ACTIVE TIMER (only one)
========================= */
$activeSession = null;
$stmt = $conn->prepare("SELECT id, task_id, start_time FROM study_sessions WHERE user_id=? AND end_time IS NULL LIMIT 1");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$activeSession = $stmt->get_result()->fetch_assoc();

/* =========================
   START TIMER
========================= */
if (isset($_GET['start'])) {
    $task_id = (int)$_GET['start'];

    // check task status
    $stmt = $conn->prepare("SELECT status FROM tasks WHERE id=? AND user_id=? LIMIT 1");
    $stmt->bind_param("ii", $task_id, $user_id);
    $stmt->execute();
    $taskRow = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$taskRow) {
        $msg = "Task not found.";
        $msgType = "error";
    } elseif ($taskRow['status'] === 'completed') {
        $msg = "Completed tasks cannot start timer.";
        $msgType = "error";
    } else {
        // check any running session
        $stmt = $conn->prepare("SELECT id FROM study_sessions WHERE user_id=? AND end_time IS NULL LIMIT 1");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $running = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($running) {
            $msg = "A timer is already running. Stop it first ✅";
            $msgType = "error";
        } else {
            $stmt = $conn->prepare("INSERT INTO study_sessions (user_id, task_id, start_time) VALUES (?, ?, NOW())");
            $stmt->bind_param("ii", $user_id, $task_id);
            $stmt->execute();
            $stmt->close();

            header("Location: planner.php");
            exit();
        }
    }
}

/* =========================
   STOP TIMER
========================= */
if (isset($_GET['stop'])) {
    $session_id = (int)$_GET['stop'];

    $stmt = $conn->prepare("SELECT start_time FROM study_sessions WHERE id=? AND user_id=? AND end_time IS NULL");
    $stmt->bind_param("ii", $session_id, $user_id);
    $stmt->execute();
    $s = $stmt->get_result()->fetch_assoc();

    if ($s) {
        $start = strtotime($s['start_time']);
        $duration = max(0, time() - $start);

        $stmt = $conn->prepare("UPDATE study_sessions SET end_time=NOW(), duration_seconds=? WHERE id=? AND user_id=?");
        $stmt->bind_param("iii", $duration, $session_id, $user_id);
        $stmt->execute();

        header("Location: planner.php");
        exit();
    } else {
        $msg = "No running session found.";
        $msgType = "error";
    }
}

/* =========================
   ADD TASK
========================= */
if (isset($_POST['add_task'])) {

    $task_name  = trim($_POST['task_name']);
    $subject_id = (int)$_POST['subject_id'];
    $deadline   = $_POST['deadline'];

    if ($task_name == "" || $subject_id == 0 || $deadline == "") {
    $msg = "Please fill all fields.";
    $msgType = "error";
} elseif ($deadline < date("Y-m-d")) {
    $msg = "Past dates are not allowed.";
    $msgType = "error";
} else {
        $stmt = $conn->prepare("INSERT INTO tasks (task_name, subject_id, deadline, user_id, status) VALUES (?, ?, ?, ?, 'pending')");
        $stmt->bind_param("sisi", $task_name, $subject_id, $deadline, $user_id);
        $stmt->execute();

        $msg = "Task added successfully!";
        $msgType = "success";
    }
}

/* =========================
   DELETE TASK
========================= */
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];

    // stop running timer if this task is running
    $stmt = $conn->prepare("SELECT id, start_time FROM study_sessions WHERE user_id=? AND task_id=? AND end_time IS NULL LIMIT 1");
    $stmt->bind_param("ii", $user_id, $id);
    $stmt->execute();
    $run = $stmt->get_result()->fetch_assoc();

    if ($run) {
        $duration = max(0, time() - strtotime($run['start_time']));
        $stmt2 = $conn->prepare("UPDATE study_sessions SET end_time=NOW(), duration_seconds=? WHERE id=? AND user_id=?");
        $stmt2->bind_param("iii", $duration, $run['id'], $user_id);
        $stmt2->execute();
    }

    $stmt = $conn->prepare("DELETE FROM tasks WHERE id=? AND user_id=?");
    $stmt->bind_param("ii", $id, $user_id);
    $stmt->execute();

    header("Location: planner.php");
    exit();
}

/* =========================
   COMPLETE TASK + REWARDS
========================= */
if (isset($_GET['complete'])) {
    $id = (int)$_GET['complete'];

    // Check current task status first
    $stmt = $conn->prepare("SELECT status FROM tasks WHERE id=? AND user_id=? LIMIT 1");
    $stmt->bind_param("ii", $id, $user_id);
    $stmt->execute();
    $taskRow = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($taskRow && $taskRow['status'] === 'pending') {

        // stop timer if running on that task
        $stmt = $conn->prepare("SELECT id, start_time FROM study_sessions WHERE user_id=? AND task_id=? AND end_time IS NULL LIMIT 1");
        $stmt->bind_param("ii", $user_id, $id);
        $stmt->execute();
        $run = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($run) {
            $duration = max(0, time() - strtotime($run['start_time']));
            $stmt2 = $conn->prepare("UPDATE study_sessions SET end_time=NOW(), duration_seconds=? WHERE id=? AND user_id=?");
            $stmt2->bind_param("iii", $duration, $run['id'], $user_id);
            $stmt2->execute();
            $stmt2->close();
        }

        // mark task as completed
        $stmt = $conn->prepare("UPDATE tasks SET status='completed', completed_at=NOW() WHERE id=? AND user_id=?");
        $stmt->bind_param("ii", $id, $user_id);
        $stmt->execute();
        $stmt->close();

        // add reward points only once
        $stmt = $conn->prepare("UPDATE users SET points = points + 10 WHERE id=?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->close();

        // count completed tasks
        $stmt = $conn->prepare("SELECT COUNT(*) AS total_completed FROM tasks WHERE user_id=? AND status='completed'");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $rewardRow = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        $totalCompleted = (int)($rewardRow['total_completed'] ?? 0);

        // decide badge
        if ($totalCompleted >= 20) {
            $badge = "Gold";
        } elseif ($totalCompleted >= 10) {
            $badge = "Silver";
        } elseif ($totalCompleted >= 5) {
            $badge = "Bronze";
        } else {
            $badge = "Beginner";
        }

        // update badge
        $stmt = $conn->prepare("UPDATE users SET badge=? WHERE id=?");
        $stmt->bind_param("si", $badge, $user_id);
        $stmt->execute();
        $stmt->close();
    }

    header("Location: planner.php");
    exit();
}

/* =========================
   FETCH SUBJECTS
========================= */
$stmt = $conn->prepare("SELECT * FROM subjects WHERE user_id=? ORDER BY id DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$subjects = $stmt->get_result();

/* =========================
   FETCH TASKS + TIME SPENT
========================= */
$stmt = $conn->prepare("
    SELECT 
      tasks.*,
      subjects.name AS subject_name,
      IFNULL(SUM(study_sessions.duration_seconds),0) AS total_seconds
    FROM tasks
    JOIN subjects ON tasks.subject_id = subjects.id
    LEFT JOIN study_sessions 
      ON study_sessions.task_id = tasks.id 
     AND study_sessions.user_id = tasks.user_id
     AND study_sessions.end_time IS NOT NULL
    WHERE tasks.user_id=?
    GROUP BY tasks.id
    ORDER BY tasks.id DESC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$tasks = $stmt->get_result();

function formatTime($seconds){
    $h = floor($seconds/3600);
    $m = floor(($seconds%3600)/60);
    $s = $seconds%60;
    return sprintf("%02dh %02dm %02ds", $h,$m,$s);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Study Planner</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="css/style.css?v=2">
<link rel="icon" type="image/png" sizes="32x32" href="images/favicon.png">
</head>

<body class="dashboard-page">

<?php include("sidebar.php"); ?>

<div class="main">
  <h1>Study Planner 🗓</h1>

  <?php if($msg !== ""){ ?>
    <div class="msg <?php echo $msgType; ?>"><?php echo htmlspecialchars($msg); ?></div>
  <?php } ?>

  <div class="form-card">
    <form method="POST">
      <input type="text" name="task_name" placeholder="Enter Topic / Task" required>

      <select name="subject_id" required>
        <option value="">Select Subject</option>
        <?php while($s = $subjects->fetch_assoc()){ ?>
          <option value="<?php echo $s['id']; ?>"><?php echo htmlspecialchars($s['name']); ?></option>
        <?php } ?>
      </select>

      <input type="date" name="deadline" min="<?php echo date('Y-m-d'); ?>" required>
      <button class="btn" name="add_task">Add Task</button>
    </form>
  </div>

  <div class="task-list">
    <?php while($row = $tasks->fetch_assoc()){
      $isRunningThisTask = ($activeSession && (int)$activeSession['task_id'] === (int)$row['id']);
      $runningOtherTask  = ($activeSession && !$isRunningThisTask);
      $taskSeconds = (int)$row['total_seconds'];
    ?>
      <div class="task-card">
        <div class="task-info">
          <h3><?php echo htmlspecialchars($row['task_name']); ?></h3>
          <p><?php echo htmlspecialchars($row['subject_name']); ?> | Deadline: <?php echo htmlspecialchars($row['deadline']); ?></p>

          <div class="timer-chip">
            ⏱ Time Spent: <?php echo formatTime($taskSeconds); ?>
          </div>

          <?php if($isRunningThisTask){ ?>
            <div class="live">
              🔴 Running: <span id="liveTimer"
                data-start="<?php echo htmlspecialchars($activeSession['start_time']); ?>">00:00:00</span>
              (since <?php echo date("h:i A", strtotime($activeSession['start_time'])); ?>)
            </div>
          <?php } ?>
        </div>

        <div class="task-actions">
  <?php if($row['status'] == 'completed'){ ?>
  <span class="action-btn disabled">✔ Completed</span>
<?php } else { ?>
  <?php if($isRunningThisTask){ ?>
    <a class="action-btn stop-btn" href="?stop=<?php echo (int)$activeSession['id']; ?>">⏹ Stop</a>
  <?php } else { ?>
    <a class="action-btn start-btn <?php echo $runningOtherTask ? 'disabled' : ''; ?>"
       href="?start=<?php echo (int)$row['id']; ?>">▶ Start</a>
  <?php } ?>

  <a class="action-btn complete-btn" href="?complete=<?php echo (int)$row['id']; ?>">✔ Complete</a>
<?php } ?>

  <a class="action-btn task-delete-btn" href="?delete=<?php echo (int)$row['id']; ?>" onclick="return confirm('Delete this task?')">✖ Delete</a>
</div>
      </div>
    <?php } ?>
  </div>

</div>

<script>
// ✅ LIVE TIMER (no refresh needed)
(function(){
  const el = document.getElementById("liveTimer");
  if(!el) return;

  const startStr = el.getAttribute("data-start");
  if(!startStr) return;

  const start = new Date(startStr.replace(" ", "T")).getTime();
  if(isNaN(start)) return;

  function pad(n){ return String(n).padStart(2,'0'); }

  function tick(){
    const now = Date.now();
    let diff = Math.max(0, Math.floor((now - start)/1000));

    const h = Math.floor(diff/3600);
    const m = Math.floor((diff%3600)/60);
    const s = diff%60;

    el.textContent = `${pad(h)}:${pad(m)}:${pad(s)}`;
  }

  tick();
  setInterval(tick, 1000);
})();
</script>

</body>
</html>