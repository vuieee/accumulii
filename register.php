<?php
require 'config.php';
if (isLoggedIn()) { header('Location: index.php'); exit; }

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email']    ?? '');
    $password = password_hash($_POST['password'] ?? '', PASSWORD_DEFAULT);

    try {
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
        $stmt->execute([$username, $email, $password]);
        header('Location: login.php');
        exit;
    } catch (PDOException $e) {
        $error = 'ERR: Username or email already exists in registry.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>accumulii — register</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/theme.css">
</head>
<body class="theme-dark">

    <div class="register-terminal">

        <div class="login-logo">
█████╗  ██████╗  ██████╗ ██╗   ██╗███╗   ███╗██╗   ██╗██╗     ██╗██╗
██╔══██╗██╔════╝ ██╔════╝██║   ██║████╗ ████║██║   ██║██║     ██║██║
███████║██║      ██║     ██║   ██║██╔████╔██║██║   ██║██║     ██║██║
██╔══██║██║      ██║     ██║   ██║██║╚██╔╝██║██║   ██║██║     ██║██║
██║  ██║╚██████╗ ╚██████╗╚██████╔╝██║ ╚═╝ ██║╚██████╔╝███████╗██║██║
╚═╝  ╚═╝ ╚═════╝  ╚═════╝ ╚═════╝ ╚═╝     ╚═╝ ╚═════╝ ╚══════╝╚═╝╚═╝</div>

        <div class="register-section">
            <div class="register-title">// SYSTEM REGISTRATION PROTOCOL</div>

            <?php if ($error): ?>
            <div class="register-error">&gt; <?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="field-row">
                    <span class="field-label">username:</span>
                    <input class="field-input" type="text" name="username" required autofocus autocomplete="off">
                </div>
                <div class="field-row">
                    <span class="field-label">email:</span>
                    <input class="field-input" type="email" name="email" required autocomplete="off">
                </div>
                <div class="field-row">
                    <span class="field-label">password:</span>
                    <input class="field-input" type="password" name="password" required>
                </div>
                <button class="register-btn" type="submit">[ CREATE INSTANCE ]</button>
            </form>

            <div class="register-link">&gt; Already registered? <a href="login.php">Return to auth gate</a></div>
        </div>
    </div>

</body>
</html>
