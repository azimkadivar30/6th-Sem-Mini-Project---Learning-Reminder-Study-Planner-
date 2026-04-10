<?php
include("config.php");

// If already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

$error = "";
$email = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $email = trim($_POST['email'] ?? "");
    $password = $_POST['password'] ?? "";

    if ($email === "" || $password === "") {
        $error = "Please fill all fields!";
    } else {

        $stmt = $conn->prepare("SELECT id, name, email, password FROM users WHERE email=? LIMIT 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows === 1) {

            $user = $result->fetch_assoc();

            if (password_verify($password, $user['password'])) {

                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_email'] = $user['email'];

                header("Location: dashboard.php");
                exit();

            } else {
                $error = "Invalid email or password!";
            }

        } else {
            $error = "Invalid email or password!";
        }

        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Login | Study Planner</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="css/style.css">
<link rel="icon" type="image/png" sizes="32x32" href="images/favicon.png">

<style>
.error {
    color: #e53935;
    font-size: 14px;
    margin-bottom: 10px;
    text-align: center;
    font-weight: 500;
}
</style>
</head>

<body class="login-page">

<div class="container">

    <div class="left">
        <h1>Study Smart 📚</h1>
        <p>
            Learning Reminder & Study Planner helps students organize their study schedule,
            set reminders, track progress, and stay productive.
        </p>
    </div>

    <div class="right">
        <h2>Student Login</h2>

        <?php if ($error != "") { ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php } ?>

        <form method="POST">

            <div class="input-box">
                <label>Email</label>
                <input type="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
            </div>

            <div class="input-box">
                <label>Password</label>
                <input type="password" name="password" required>
            </div>

            <button class="btn" type="submit">Login</button>
        </form>

        <div class="extra">
            Don’t have an account? <a href="register.php">Register</a>
        </div>
    </div>

</div>

</body>
</html>