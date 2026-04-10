<?php
include("config.php");

// If already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

$errors = [
    "name" => "",
    "email" => "",
    "phone" => "",
    "password" => "",
    "confirm" => "",
    "profile_pic" => "",
    "general" => ""
];

$name = "";
$email = "";
$phone = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $name = trim($_POST['name'] ?? "");
    $email = trim($_POST['email'] ?? "");
    $phone = trim($_POST['phone'] ?? "");
    $password = $_POST['password'] ?? "";
    $confirm = $_POST['confirm'] ?? "";

    $profile_pic = "";

    // NAME VALIDATION
    if ($name === "") {
        $errors['name'] = "Full name is required!";
    }

    // EMAIL VALIDATION
    if ($email === "") {
        $errors['email'] = "Email is required!";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = "Enter a valid email address!";
    }

    // PHONE VALIDATION
    if ($phone === "") {
        $errors['phone'] = "Phone number is required!";
    } elseif (!preg_match("/^[0-9]{10,15}$/", $phone)) {
        $errors['phone'] = "Phone number must be 10-15 digits only!";
    }

    // PASSWORD VALIDATION
    // Must contain uppercase, lowercase, number, special char, no spaces, minimum 8 chars
    if ($password === "") {
        $errors['password'] = "Password is required!";
    } elseif (
        !preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_])[^\s]{8,}$/', $password)
    ) {
        $errors['password'] = "Password must be 8+ chars, include uppercase, lowercase, number, special character, and no spaces!";
    }

    // CONFIRM PASSWORD
    if ($confirm === "") {
        $errors['confirm'] = "Confirm password is required!";
    } elseif ($password !== $confirm) {
        $errors['confirm'] = "Passwords do not match!";
    }

    // CHECK DUPLICATE EMAIL
    if ($errors['email'] === "") {
        $stmt = $conn->prepare("SELECT id FROM users WHERE email=? LIMIT 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $check = $stmt->get_result();

        if ($check && $check->num_rows > 0) {
            $errors['email'] = "Email already registered!";
        }
        $stmt->close();
    }

    // IMAGE UPLOAD
    if (!empty($_FILES['profile_pic']['name'])) {
        $fileName = $_FILES['profile_pic']['name'];
        $tmpName  = $_FILES['profile_pic']['tmp_name'];
        $fileSize = $_FILES['profile_pic']['size'];

        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $allowed = ["jpg", "jpeg", "png", "webp"];

        if (!in_array($ext, $allowed)) {
            $errors['profile_pic'] = "Only JPG, JPEG, PNG, WEBP allowed!";
        } elseif ($fileSize > 2 * 1024 * 1024) {
            $errors['profile_pic'] = "Image size must be less than 2MB!";
        } elseif (!getimagesize($tmpName)) {
            $errors['profile_pic'] = "Uploaded file is not a valid image!";
        } else {
            if (!is_dir("uploads")) {
                mkdir("uploads", 0755, true);
            }

            $newName = time() . "_" . preg_replace("/[^A-Za-z0-9_\-\.]/", "_", $fileName);

            if (move_uploaded_file($tmpName, "uploads/" . $newName)) {
                $profile_pic = $newName;
            } else {
                $errors['profile_pic'] = "Error uploading image!";
            }
        }
    }

    // CHECK IF ANY ERRORS
    $hasErrors = false;
    foreach ($errors as $err) {
        if (!empty($err)) {
            $hasErrors = true;
            break;
        }
    }

    // INSERT USER
    if (!$hasErrors) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $conn->prepare("INSERT INTO users (name, email, password, phone, profile_pic) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $name, $email, $hashed_password, $phone, $profile_pic);

        if ($stmt->execute()) {
            $new_user_id = $stmt->insert_id;

            $_SESSION['user_id'] = $new_user_id;
            $_SESSION['user_name'] = $name;
            $_SESSION['user_email'] = $email;

            header("Location: dashboard.php");
            exit();
        } else {
            $errors['general'] = "Something went wrong. Please try again.";
        }

        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Student Registration | Study Planner</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="css/style.css">
<link rel="icon" type="image/png" sizes="32x32" href="images/favicon.png">
<style>
.password-wrapper {
    position: relative;
    display: flex;
    align-items: center;
}
.password-wrapper input {
    width: 100%;
    padding-right: 45px;
}
.toggle-password {
    position: absolute;
    right: 12px;
    cursor: pointer;
    font-size: 18px;
    user-select: none;
}
.strength-text {
    margin-top: 6px;
    font-size: 13px;
    font-weight: 500;
}
.error {
    color: #e53935;
    font-size: 13px;
    margin-top: 5px;
    font-weight: 500;
}
.input-error {
    border: 1.5px solid #e53935 !important;
}
.input-success {
    border: 1.5px solid #28a745 !important;
}
</style>
</head>

<body class="login-page">

<div class="container">

    <div class="left">
        <h1>Create Account ✨</h1>
        <p>
            Join the Learning Reminder & Study Planner to manage your subjects,
            set goals, track progress, and stay consistent with your studies.
        </p>
        <div class="quote">
            “Success is the sum of small efforts repeated every day.”
        </div>
    </div>

    <div class="right">
        <h2>Student Registration</h2>

        <?php if ($errors['general'] != "") { ?>
            <div class="message"><?php echo htmlspecialchars($errors['general']); ?></div>
        <?php } ?>

        <form method="POST" enctype="multipart/form-data" novalidate>

            <div class="input-box">
                <label>Full Name</label>
                <input 
                    type="text" 
                    name="name" 
                    value="<?php echo htmlspecialchars($name); ?>"
                    class="<?php echo $errors['name'] ? 'input-error' : ($name !== '' ? 'input-success' : ''); ?>"
                    required
                >
                <?php if ($errors['name']) { ?>
                    <div class="error"><?php echo htmlspecialchars($errors['name']); ?></div>
                <?php } ?>
            </div>

            <div class="input-box">
                <label>Email</label>
                <input 
                    type="email" 
                    name="email" 
                    value="<?php echo htmlspecialchars($email); ?>"
                    class="<?php echo $errors['email'] ? 'input-error' : ($email !== '' ? 'input-success' : ''); ?>"
                    required
                >
                <?php if ($errors['email']) { ?>
                    <div class="error"><?php echo htmlspecialchars($errors['email']); ?></div>
                <?php } ?>
            </div>

            <div class="input-box">
                <label>Phone Number</label>
                <input 
                    type="tel" 
                    name="phone" 
                    id="phone"
                    value="<?php echo htmlspecialchars($phone); ?>"
                    class="<?php echo $errors['phone'] ? 'input-error' : ($phone !== '' ? 'input-success' : ''); ?>"
                    required
                    maxlength="15"
                >
                <?php if ($errors['phone']) { ?>
                    <div class="error"><?php echo htmlspecialchars($errors['phone']); ?></div>
                <?php } ?>
            </div>

            <div class="input-box">
                <label>Profile Photo</label>
                <input 
                    type="file" 
                    name="profile_pic" 
                    accept="image/*"
                    class="<?php echo $errors['profile_pic'] ? 'input-error' : ''; ?>"
                >
                <?php if ($errors['profile_pic']) { ?>
                    <div class="error"><?php echo htmlspecialchars($errors['profile_pic']); ?></div>
                <?php } ?>
            </div>

            <div class="input-box">
                <label>Password</label>
                <div class="password-wrapper">
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        required
                        class="<?php echo $errors['password'] ? 'input-error' : ''; ?>"
                    >
                    <span class="toggle-password" onclick="togglePassword('password', this)">👁</span>
                </div>
                <div id="strengthMessage" class="strength-text"></div>
                <?php if ($errors['password']) { ?>
                    <div class="error"><?php echo htmlspecialchars($errors['password']); ?></div>
                <?php } ?>
            </div>

            <div class="input-box">
                <label>Confirm Password</label>
                <div class="password-wrapper">
                    <input 
                        type="password" 
                        id="confirm" 
                        name="confirm" 
                        required
                        class="<?php echo $errors['confirm'] ? 'input-error' : ''; ?>"
                    >
                    <span class="toggle-password" onclick="togglePassword('confirm', this)">👁</span>
                </div>
                <?php if ($errors['confirm']) { ?>
                    <div class="error"><?php echo htmlspecialchars($errors['confirm']); ?></div>
                <?php } ?>
            </div>

            <button class="btn" type="submit">Register</button>
        </form>

        <div class="extra">
            Already have an account? <a href="index.php">Login</a>
        </div>
    </div>
</div>

<script>
function togglePassword(fieldId, icon) {
    const field = document.getElementById(fieldId);
    if (field.type === "password") {
        field.type = "text";
        icon.textContent = "🙈";
    } else {
        field.type = "password";
        icon.textContent = "👁";
    }
}

const passwordInput = document.getElementById("password");
const strengthMessage = document.getElementById("strengthMessage");

passwordInput.addEventListener("input", function () {
    const password = passwordInput.value;

    let hasLower = /[a-z]/.test(password);
    let hasUpper = /[A-Z]/.test(password);
    let hasNum = /[0-9]/.test(password);
    let hasSpecial = /[\W_]/.test(password);
    let hasSpace = /\s/.test(password);
    let hasMinLength = password.length >= 8;

    if (password.length === 0) {
        strengthMessage.textContent = "";
    } else if (hasLower && hasUpper && hasNum && hasSpecial && hasMinLength && !hasSpace) {
        strengthMessage.textContent = "Strong password ✅";
        strengthMessage.style.color = "green";
    } else {
        strengthMessage.textContent = "Need uppercase, lowercase, number, special character, no spaces, min 8 characters ❌";
        strengthMessage.style.color = "red";
    }
});

const phoneInput = document.getElementById("phone");
phoneInput.addEventListener("input", function () {
    this.value = this.value.replace(/[^0-9]/g, '');
});
</script>

</body>
</html>