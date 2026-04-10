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

// ✅ Fetch user safely (prepared)
function getUserData($conn, $user_id)
{
    $stmt = $conn->prepare("SELECT id, name, email, phone, profile_pic, password FROM users WHERE id=? LIMIT 1");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res && $res->num_rows > 0) {
        return $res->fetch_assoc();
    }
    return null;
}

// ✅ UPDATE PROFILE INFO
if (isset($_POST['update_profile'])) {
    $name = trim($_POST['name'] ?? "");
    $email = trim($_POST['email'] ?? "");
    $phone = trim($_POST['phone'] ?? "");

    if ($name === "" || $email === "") {
        $msg = "Name and Email are required.";
        $msgType = "error";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $msg = "Please enter a valid email address.";
        $msgType = "error";
    } else {
        // optional: check duplicate email (except own)
        $stmt = $conn->prepare("SELECT id FROM users WHERE email=? AND id<>? LIMIT 1");
        $stmt->bind_param("si", $email, $user_id);
        $stmt->execute();
        $check = $stmt->get_result();
        if ($check && $check->num_rows > 0) {
            $msg = "This email is already used by another account.";
            $msgType = "error";
        } else {
            $stmt = $conn->prepare("UPDATE users SET name=?, email=?, phone=? WHERE id=?");
            $stmt->bind_param("sssi", $name, $email, $phone, $user_id);
            $stmt->execute();

            $msg = "Profile updated successfully!";
            $msgType = "success";
        }
    }
}

// ✅ UPLOAD PROFILE PHOTO
if (isset($_POST['upload_photo'])) {
    if (!empty($_FILES['profile_pic']['name'])) {

        $fileName = $_FILES['profile_pic']['name'];
        $tmpName = $_FILES['profile_pic']['tmp_name'];

        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $allowed = ["jpg", "jpeg", "png", "webp"];

        if (!in_array($ext, $allowed)) {
            $msg = "Only JPG, JPEG, PNG, WEBP images are allowed.";
            $msgType = "error";
        } else {
            if (!is_dir("uploads"))
                mkdir("uploads", 0777, true);

            $newName = time() . "_" . preg_replace("/[^A-Za-z0-9_\-\.]/", "_", $fileName);
            move_uploaded_file($tmpName, "uploads/" . $newName);

            $stmt = $conn->prepare("UPDATE users SET profile_pic=? WHERE id=?");
            $stmt->bind_param("si", $newName, $user_id);
            $stmt->execute();

            $msg = "Profile photo updated!";
            $msgType = "success";
        }
    } else {
        $msg = "Please choose an image first.";
        $msgType = "error";
    }
}

// ✅ CHANGE PASSWORD (HASHED)
if (isset($_POST['change_password'])) {
    $current = $_POST['current'] ?? "";
    $new = $_POST['new'] ?? "";
    $confirm = $_POST['confirm'] ?? "";

    $user = getUserData($conn, $user_id);

    if (!$user) {
        header("Location: index.php");
        exit();
    }

    if (!password_verify($current, $user['password'])) {
        $msg = "Current password is incorrect.";
        $msgType = "error";
    } elseif ($new !== $confirm) {
        $msg = "New password and confirm password do not match.";
        $msgType = "error";
    } elseif (strlen($new) < 8) {
        $msg = "New password must be at least 8 characters.";
        $msgType = "error";
    } else {
        $hashed = password_hash($new, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET password=? WHERE id=?");
        $stmt->bind_param("si", $hashed, $user_id);
        $stmt->execute();

        $msg = "Password changed successfully!";
        $msgType = "success";
    }
}

// ✅ Always load latest data
$user = getUserData($conn, $user_id);
if (!$user) {
    header("Location: index.php");
    exit();
}

// ✅ Safe values
$name = $user['name'] ?? "";
$email = $user['email'] ?? "";
$phone = $user['phone'] ?? "";
$pic = $user['profile_pic'] ?? "";
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Profile | Study Planner</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css?v=2">
    <link rel="icon" type="image/png" sizes="32x32" href="images/favicon.png">
</head>

<body class="dashboard-page">

<?php include("sidebar.php"); ?>

<div class="main profile-page">

    <?php if ($msg !== "") { ?>
        <div class="msg <?php echo $msgType; ?>">
            <?php echo htmlspecialchars($msg); ?>
        </div>
    <?php } ?>

    <div class="profile-grid">

        <!-- LEFT -->
        <div class="profile-card">

            <h2>👤 Profile</h2>

            <div class="profileTop">
                <?php if (!empty($pic) && file_exists("uploads/" . $pic)) { ?>
                    <img id="previewImg" class="avatar" src="uploads/<?php echo htmlspecialchars($pic); ?>">
                <?php } else { ?>
                    <img id="previewImg" class="avatar" src="default.png">
                <?php } ?>

                <div class="nameEmail">
                    <h3><?php echo htmlspecialchars($name); ?></h3>
                    <p><?php echo htmlspecialchars($email); ?></p>
                </div>
            </div>

            <!-- Update Profile Info -->
            <form method="POST">
                <div class="profile-row">
                    <div>
                        <label>Name</label>
                        <input type="text" name="name" value="<?php echo htmlspecialchars($name); ?>" required>
                    </div>
                    <div>
                        <label>Phone</label>
                        <input type="text" name="phone" value="<?php echo htmlspecialchars($phone); ?>" placeholder="Enter phone">
                    </div>
                </div>

                <div class="profile-mt">
                    <label>Email</label>
                    <input type="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
                </div>

                <div class="profile-actions">
                    <button class="btn" name="update_profile">Update Profile</button>
                    <button class="btn btnGhost" type="reset">Reset</button>
                </div>
            </form>

            <hr>

            <!-- Upload Photo -->
            <form method="POST" enctype="multipart/form-data">
                <h2>🖼 Update Photo</h2>
                <input type="file" name="profile_pic" id="profilePicInput" accept="image/*" required>
                <div class="help">Preview will appear above immediately.</div>
                <div class="profile-mt">
                    <button class="btn" name="upload_photo">Upload Photo</button>
                </div>
            </form>

        </div>

        <!-- RIGHT -->
        <div class="profile-card">
            <h2>🔐 Change Password</h2>

            <form method="POST" onsubmit="return validatePassword();">
                <label>Current Password</label>
                <input type="password" name="current" required>

                <label class="profile-mt-label">New Password</label>
                <input type="password" name="new" id="newPass" required oninput="updateStrength();">

                <div class="strength">
                    <div id="strengthBar"></div>
                </div>
                <div class="help" id="strengthText">Use 8+ chars, uppercase, lowercase, number & symbol.</div>

                <label class="profile-mt-label">Confirm Password</label>
                <input type="password" name="confirm" id="confirmPass" required>

                <div class="profile-mt">
                    <button class="btn" name="change_password">Change Password</button>
                </div>
            </form>
        </div>

    </div>
</div>

<script>
    // ✅ Image preview
    const input = document.getElementById("profilePicInput");
    const preview = document.getElementById("previewImg");

    if (input) {
        input.addEventListener("change", function () {
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = (e) => preview.src = e.target.result;
                reader.readAsDataURL(file);
            }
        });
    }

    // ✅ Password strength
    function scorePassword(p) {
        let score = 0;
        if (!p) return score;

        const hasLower = /[a-z]/.test(p);
        const hasUpper = /[A-Z]/.test(p);
        const hasNum = /[0-9]/.test(p);
        const hasSym = /[^A-Za-z0-9]/.test(p);

        if (p.length >= 8) score += 25;
        if (hasLower) score += 15;
        if (hasUpper) score += 20;
        if (hasNum) score += 20;
        if (hasSym) score += 20;

        return Math.min(score, 100);
    }

    function updateStrength() {
        const p = document.getElementById("newPass").value;
        const bar = document.getElementById("strengthBar");
        const text = document.getElementById("strengthText");

        const s = scorePassword(p);
        bar.style.width = s + "%";

        if (s < 40) { bar.style.background = "#e74c3c"; text.innerText = "Weak password"; }
        else if (s < 70) { bar.style.background = "#f39c12"; text.innerText = "Medium password"; }
        else { bar.style.background = "#2ecc71"; text.innerText = "Strong password"; }
    }

    function validatePassword() {
        const p = document.getElementById("newPass").value;
        const c = document.getElementById("confirmPass").value;

        if (p.length < 8) {
            alert("Password must be at least 8 characters.");
            return false;
        }
        if (p !== c) {
            alert("Confirm password does not match.");
            return false;
        }
        return true;
    }
</script>

</body>
</html>