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
$current_page = basename($_SERVER['PHP_SELF']);

$stmt = $conn->prepare("SELECT profile_pic, name FROM users WHERE id=? LIMIT 1");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

function activeLink($page, $current_page) {
    return $page === $current_page ? 'active' : '';
}
?>

<div class="sidebar">

    <div class="profile-box">
        <?php if (!empty($user['profile_pic']) && file_exists("uploads/" . $user['profile_pic'])) { ?>
            <img src="uploads/<?php echo htmlspecialchars($user['profile_pic']); ?>" alt="Profile">
        <?php } else { ?>
            <img src="default.png" alt="Profile">
        <?php } ?>
        <h3><?php echo htmlspecialchars($user['name'] ?? 'User'); ?></h3>
    </div>

    <button id="darkToggleBtn" class="theme-btn">🌙 Toggle Dark Mode</button>

    <a href="dashboard.php" class="<?php echo activeLink('dashboard.php', $current_page); ?>">🏠 Dashboard</a>
<a href="subject.php" class="<?php echo activeLink('subject.php', $current_page); ?>">📚 Subjects</a>
<a href="planner.php" class="<?php echo activeLink('planner.php', $current_page); ?>">🗓 Study Planner</a>
<a href="reminders.php" class="<?php echo activeLink('reminders.php', $current_page); ?>">⏰ Reminders</a>
<a href="progress.php" class="<?php echo activeLink('progress.php', $current_page); ?>">📊 Progress</a>
<a href="rewards.php" class="<?php echo activeLink('rewards.php', $current_page); ?>">🏆 Rewards</a>
<a href="profile.php" class="<?php echo activeLink('profile.php', $current_page); ?>">👤 Profile</a>
<a href="logout.php">🚪 Logout</a>

</div>

<script>
(function () {
    const btn = document.getElementById("darkToggleBtn");

    const saved = localStorage.getItem("darkMode");
    if (saved === "on") document.body.classList.add("dark");

    function setBtnText() {
        if (!btn) return;
        btn.innerText = document.body.classList.contains("dark")
            ? "☀ Light Mode"
            : "🌙 Toggle Dark Mode";
    }

    setBtnText();

    if (btn) {
        btn.addEventListener("click", function () {
            document.body.classList.toggle("dark");
            localStorage.setItem(
                "darkMode",
                document.body.classList.contains("dark") ? "on" : "off"
            );
            setBtnText();
        });
    }
})();
</script>