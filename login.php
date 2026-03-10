<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Admin - Kamus Tunjung</title>
    <link rel="icon" type="image/png" href="logo-upr.png" />
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <div class="login-container">
        <h1>Login Admin</h1>
        <?php
        if (isset($_GET['error'])) {
            echo '<p class="error-message">' . htmlspecialchars($_GET['error']) . '</p>';
        }
        ?>
        <form action="proses_login.php" method="POST" class="login-form">
            <input type="text" name="username" placeholder="Username" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit">Login</button>
        </form>

        <a href="index" class="btn-back">Kembali ke Kamus</a>
    </div>
</body>

</html>