<?php
session_start();
require 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['role'];

        if ($user['role'] == 'gestor') {
    header("Location: gestor.php");
} elseif ($user['role'] == 'aluno') {
    header("Location: aluno.php");
} elseif ($user['role'] == 'funcionario') {
    header("Location: funcionario.php");
}
exit();
        exit();
    } else {
        $erro = "Login inválido";
    }
}
?>

<!DOCTYPE html>
<html>
<body>

<h2>Login</h2>
    <link rel="stylesheet" href="css/style.css">

<form method="POST">
    Email: <input type="email" name="email" required><br><br>
    Password: <input type="password" name="password" required><br><br>
    <button type="submit">Entrar</button>
</form>

<?php if(isset($erro)) echo "<p>$erro</p>"; ?>

</body>
</html>