<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
?>

<h1>Bem-vindo!</h1>
<link rel="stylesheet" href="css/style.css">
<p>Perfil: <?php echo $_SESSION['role']; ?></p>

<a href="logout.php">Logout</a>