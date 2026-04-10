<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include("config.php");

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$user_id = (int) $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT name, points, badge FROM users WHERE id=? LIMIT 1");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

$name = $user['name'] ?? 'Student';
$points = $user['points'] ?? 0;

// completed task count
$stmt = $conn->prepare("SELECT COUNT(*) AS total_completed FROM tasks WHERE user_id=? AND status='completed'");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$totalCompleted = $stmt->get_result()->fetch_assoc()['total_completed'] ?? 0;
$stmt->close();

// Calculate badge dynamically
if ($totalCompleted >= 20) {
    $badge = "Gold";
} elseif ($totalCompleted >= 10) {
    $badge = "Silver";
} elseif ($totalCompleted >= 5) {
    $badge = "Bronze";
} else {
    $badge = "Beginner";
}

// XP / next badge target
if ($totalCompleted < 5) {
    $nextBadge = "Bronze";
    $target = 5;
    $current = $totalCompleted;
} elseif ($totalCompleted < 10) {
    $nextBadge = "Silver";
    $target = 10;
    $current = $totalCompleted;
} elseif ($totalCompleted < 20) {
    $nextBadge = "Gold";
    $target = 20;
    $current = $totalCompleted;
} else {
    $nextBadge = "Max Level";
    $target = 20;
    $current = 20;
}

$progressPercent = ($target > 0) ? min(100, round(($current / $target) * 100)) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Rewards | Study Planner</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css?v=4">
    <link rel="icon" type="image/png" sizes="32x32" href="images/favicon.png">
</head>
<body class="dashboard-page">

<?php include("sidebar.php"); ?>

<div class="main">
    <div class="header">
        <h1>Rewards & Achievements 🏆</h1>
        <p>Keep learning and unlock badges</p>
    </div>

    <div class="reward-wrapper">

        <div class="reward-top">
            <div class="reward-card">
                <h3>Student</h3>
                <p><?php echo htmlspecialchars($name); ?></p>
            </div>

            <div class="reward-card">
                <h3>Total Points</h3>
                <p><?php echo (int)$points; ?></p>
            </div>

            <div class="reward-card">
                <h3>Current Badge</h3>
                <p><?php echo htmlspecialchars($badge); ?></p>
            </div>

            <div class="reward-card">
                <h3>Completed Tasks</h3>
                <p><?php echo (int)$totalCompleted; ?></p>
            </div>
        </div>

        <div class="table-box">
            <h3 class="mb-15">Your Current Level</h3>

            <?php $badgeClass = strtolower($badge); ?>
            <span class="reward-badge <?php echo $badgeClass; ?>">
                <?php echo htmlspecialchars($badge); ?> Badge
            </span>
        </div>

        <!-- NEW XP / PROGRESS SECTION -->
        <div class="table-box">
            <h3 class="mb-15">Level Progress</h3>

            <p class="reward-progress-text">
                <?php if ($nextBadge !== "Max Level") { ?>
                    You have completed <strong><?php echo (int)$totalCompleted; ?></strong> tasks.
                    Complete <strong><?php echo $target; ?></strong> tasks to unlock
                    <strong><?php echo htmlspecialchars($nextBadge); ?></strong>.
                <?php } else { ?>
                    🎉 You have reached the highest badge level.
                <?php } ?>
            </p>

            <div class="xp-bar">
                <div class="xp-fill" style="width: <?php echo $progressPercent; ?>%;"></div>
            </div>

            <div class="xp-label">
                <?php echo $progressPercent; ?>% Progress
            </div>
        </div>

        <div class="table-box">
            <h3 class="mb-15">Available Achievements</h3>

            <div class="badge-grid">
                <div class="badge-box earned">
                    <h4>🌱 Beginner</h4>
                    <p>Start your journey.</p>
                    <p>Requirement: Join the platform</p>
                </div>

                <div class="badge-box <?php echo ($totalCompleted >= 5 ? 'earned' : 'locked'); ?>">
                    <h4>🥉 Bronze</h4>
                    <p>Completed 5 tasks.</p>
                    <p>Requirement: 5 completed tasks</p>
                </div>

                <div class="badge-box <?php echo ($totalCompleted >= 10 ? 'earned' : 'locked'); ?>">
                    <h4>🥈 Silver</h4>
                    <p>Completed 10 tasks.</p>
                    <p>Requirement: 10 completed tasks</p>
                </div>

                <div class="badge-box <?php echo ($totalCompleted >= 20 ? 'earned' : 'locked'); ?>">
                    <h4>🥇 Gold</h4>
                    <p>Completed 20 tasks.</p>
                    <p>Requirement: 20 completed tasks</p>
                </div>
            </div>
        </div>

    </div>
</div>

</body>
</html>