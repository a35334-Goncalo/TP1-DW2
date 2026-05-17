<?php
session_start();
require 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];

    // ALTERADO: users -> contas
    $stmt = $pdo->prepare("SELECT * FROM contas WHERE EMAIL = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['PASSWORD'])) {

        $_SESSION['user_id'] = $user['ID'];
        $_SESSION['perfil'] = $user['PERFIL_ID'];

        // PERFIL_ID define o tipo de utilizador
        if ($user['PERFIL_ID'] == 1) {
            $_SESSION['role'] = 'gestor';
            header("Location: gestor.php");
        } elseif ($user['PERFIL_ID'] == 2) {
            $_SESSION['role'] = 'aluno';
            header("Location: aluno.php");
        } elseif ($user['PERFIL_ID'] == 3) {
            $_SESSION['role'] = 'funcionario';
            header("Location: funcionario.php");
        }

        exit();

    } else {
        $erro = "Login inválido";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Login - Gestão Académica</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<header>
    <h1>Sistema de Gestão Académica</h1>
</header>
<div class="container" style="max-width: 400px; margin: 50px auto; background: #fff; padding: 20px; border-radius: 5px; box-shadow: 0 0 10px rgba(0,0,0,0.1);">
    <h2>Login</h2>
    <?php if(isset($erro)) echo "<p style='color:red;'>".htmlspecialchars($erro)."</p>"; ?>
    <form method="POST">
        <label>Email:</label>
        <input type="email" name="email" required style="width: 100%; padding: 10px; margin-bottom: 10px; border: 1px solid #ccc; border-radius: 4px;">
        <label>Password:</label>
        <input type="password" name="password" required style="width: 100%; padding: 10px; margin-bottom: 20px; border: 1px solid #ccc; border-radius: 4px;">
        <button type="submit" style="width: 100%; padding: 10px; background: #007BFF; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 16px;">Entrar</button>
    </form>
</div>
</body>
</html>