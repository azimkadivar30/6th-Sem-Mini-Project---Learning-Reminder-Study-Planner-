<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include("config.php");

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$user_id = (int)$_SESSION['user_id'];

/* ===== TOTAL TASKS ===== */
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM tasks WHERE user_id=?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$total = $stmt->get_result()->fetch_assoc()['total'] ?? 0;
$stmt->close();

/* ===== COMPLETED TASKS ===== */
$stmt = $conn->prepare("SELECT COUNT(*) as completed FROM tasks WHERE status='completed' AND user_id=?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$completed = $stmt->get_result()->fetch_assoc()['completed'] ?? 0;
$stmt->close();

/* ===== CALCULATIONS ===== */
$pending = $total - $completed;
$percent = ($total > 0) ? round(($completed / $total) * 100) : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Progress & Analytics | Study Planner</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Google Font -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">

    <!-- External CSS -->
    <link rel="stylesheet" href="css/style.css?v=2">
    <link rel="icon" type="image/png" sizes="32x32" href="images/favicon.png">

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body class="dashboard-page">

<?php include("sidebar.php"); ?>

<div class="main">
    <h1>Progress & Analytics 📊</h1>

    <a href="export_progress.php" class="btn" style="display:inline-block; width:auto; margin:10px 0; text-decoration:none;">
        ⬇ Download PDF Report
    </a>

    <div class="cards">
        <div class="card">
            <h3>Total Tasks</h3>
            <p><?php echo $total; ?></p>
        </div>

        <div class="card">
            <h3>Completed</h3>
            <p><?php echo $completed; ?></p>
        </div>

        <div class="card">
            <h3>Pending</h3>
            <p><?php echo $pending; ?></p>
        </div>

        <div class="card">
            <h3>Progress %</h3>
            <p><?php echo $percent; ?>%</p>
        </div>
    </div>

    <div class="chart-box">
    <?php if ($total > 0) { ?>
        <canvas id="progressChart"></canvas>
    <?php } else { ?>
        <p>No task data available yet.</p>
    <?php } ?>
</div>
</div>

<?php if ($total > 0) { ?>
<script>
const ctx = document.getElementById('progressChart');

new Chart(ctx, {
    type: 'doughnut',
    data: {
        labels: ['Completed', 'Pending'],
        datasets: [{
            data: [<?php echo (int)$completed; ?>, <?php echo (int)$pending; ?>],
            backgroundColor: ['#2ecc71', '#e74c3c']
        }]
    },
    options: {
        responsive: true,
        cutout: '70%',
        plugins: {
            legend: { position: 'bottom' }
        }
    }
});
</script>
<?php } ?>

</body>
</html>