<?php
session_start();
require 'db.php';

// Verifica se o utilizador é aluno
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'aluno') {
    header("Location: login.php");
    exit();
}

// Inicializa variáveis para evitar warnings
$erro = "";
$sucesso = "";
$ficha = [];
$pedidos = [];
$cursos = [];

// Pasta para uploads
$upload_dir = __DIR__ . '/uploads';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// ----------------- PROCESSA FICHA -----------------
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['acao_ficha'])) {
    $nome = trim($_POST['nome']);
    $idade = intval($_POST['idade']);
    $curso = trim($_POST['curso']);
    $acao = $_POST['acao_ficha']; // salvar ou submeter

    $foto = null;
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] == 0) {
        $ext = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
        $tipos_permitidos = ['jpg','jpeg','png','gif'];
        $max_size = 2 * 1024 * 1024; // 2MB

        if (!in_array($ext, $tipos_permitidos)) {
            $erro = "Tipo de arquivo não permitido. Apenas JPG, JPEG, PNG ou GIF.";
        } elseif ($_FILES['foto']['size'] > $max_size) {
            $erro = "Arquivo muito grande. Máx: 2MB.";
        } else {
            $novo_nome = 'foto_' . $_SESSION['user_id'] . '_' . time() . '.' . $ext;
            $destino = $upload_dir . '/' . $novo_nome;

            if (move_uploaded_file($_FILES['foto']['tmp_name'], $destino)) {
                $foto = $novo_nome;
            } else {
                $erro = "Erro ao fazer upload da foto.";
            }
        }
    }

    if (!$erro) {
        $stmt = $pdo->prepare("SELECT * FROM fichas_aluno WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $ficha_existente = $stmt->fetch();

        $estado = ($acao === 'submeter') ? 'submetida' : 'rascunho';

        if ($ficha_existente) {
            if (!$foto) $foto = $ficha_existente['foto'];
            $stmt = $pdo->prepare("UPDATE fichas_aluno SET dados = ?, foto = ?, estado = ? WHERE user_id = ?");
            $stmt->execute([
                "Nome: $nome, Idade: $idade, Curso: $curso",
                $foto,
                $estado,
                $_SESSION['user_id']
            ]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO fichas_aluno (user_id, dados, foto, estado) VALUES (?, ?, ?, ?)");
            $stmt->execute([
                $_SESSION['user_id'],
                "Nome: $nome, Idade: $idade, Curso: $curso",
                $foto,
                $estado
            ]);
        }

        $sucesso = ($estado === 'submetida') ? "Ficha submetida com sucesso!" : "Ficha salva com sucesso!";
    }
}

// ----------------- PROCESSA PEDIDO DE MATRÍCULA -----------------
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['acao_pedido'])) {
    $curso_id = intval($_POST['curso_id']);

    if ($curso_id <= 0) {
        $erro = "Indique um curso válido.";
    } else {
        $stmt = $pdo->prepare("INSERT INTO matriculas (user_id, curso_id) VALUES (?, ?)");
        $stmt->execute([$_SESSION['user_id'], $curso_id]);
        $sucesso = "Pedido de matrícula criado com sucesso!";
    }
}

// ----------------- BUSCA DADOS -----------------
$stmt = $pdo->prepare("SELECT * FROM fichas_aluno WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$ficha = $stmt->fetch() ?: []; // transforma null em array vazio

// Lista cursos para o drop-down
$stmt = $pdo->query("SELECT id, nome FROM cursos ORDER BY nome ASC");
$cursos = $stmt->fetchAll() ?: [];

// Lista pedidos do aluno
$stmt = $pdo->prepare("
    SELECT m.*, c.nome AS curso_nome 
    FROM matriculas m
    JOIN cursos c ON m.curso_id = c.id
    WHERE m.user_id = ?
    ORDER BY m.data_pedido DESC
");
$stmt->execute([$_SESSION['user_id']]);
$pedidos = $stmt->fetchAll() ?: []; // garante que é array
?>

<!DOCTYPE html>
<html>
<head>
    <title>Área do Aluno</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<header>
    <h1>Sistema de Gestão Académica</h1>
</header>

<nav>
    <div class="links">
        <a href="aluno.php">Área do Aluno</a>
    </div>
    <div class="links">
        <a href="logout.php">Logout</a>
    </div>
</nav>

<div class="container">
    <?php if($erro) echo "<p style='color:red;'>".htmlspecialchars($erro)."</p>"; ?>
    <?php if($sucesso) echo "<p style='color:green;'>".htmlspecialchars($sucesso)."</p>"; ?>

    <!-- ================== FICHA DE ALUNO ================== -->
    <h2>Ficha de Aluno</h2>

    <?php if($ficha): ?>
        <p><strong>Estado da Ficha:</strong> <?php echo htmlspecialchars(ucfirst($ficha['estado'])); ?></p>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
        Nome: <input type="text" name="nome" required value="<?php 
            echo isset($ficha['dados']) ? htmlspecialchars(preg_replace('/.*Nome: ([^,]*),.*/', '$1', $ficha['dados'])) : ''; 
        ?>"><br><br>

        Idade: <input type="number" name="idade" required value="<?php 
            echo isset($ficha['dados']) ? htmlspecialchars(preg_replace('/.*Idade: (\d+),.*/', '$1', $ficha['dados'])) : ''; 
        ?>"><br><br>

        Curso: <select name="curso_id" required>
            <option value="">--Selecione--</option>
            <?php foreach($cursos as $c): ?>
                <option value="<?php echo htmlspecialchars($c['id']); ?>"><?php echo htmlspecialchars($c['nome']); ?></option>
            <?php endforeach; ?>
        </select><br><br>

        Foto: <input type="file" name="foto"><br><br>

        <?php if(isset($ficha['foto']) && $ficha['foto']): ?>
            <img src="uploads/<?php echo htmlspecialchars($ficha['foto']); ?>" width="100"><br><br>
        <?php endif; ?>

        <button type="submit" name="acao_ficha" value="salvar">Salvar Ficha</button>
        <button type="submit" name="acao_ficha" value="submeter">Submeter Ficha</button>
    </form>

    <!-- ================== PEDIDOS DE MATRÍCULA ================== -->
    <h2>Pedidos de Matrícula/Inscrição</h2>

    <form method="POST">
        Curso:
        <select name="curso_id" required>
            <option value="">--Selecione--</option>
            <?php foreach($cursos as $c): ?>
                <option value="<?php echo htmlspecialchars($c['id']); ?>"><?php echo htmlspecialchars($c['nome']); ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" name="acao_pedido" value="criar">Criar Pedido</button>
    </form>

    <h3>Pedidos Anteriores</h3>
    <?php if(count($pedidos) == 0): ?>
        <p>Nenhum pedido realizado ainda.</p>
    <?php else: ?>
        <table>
            <tr>
                <th>Curso</th>
                <th>Data do Pedido</th>
                <th>Estado</th>
                <th>Observações</th>
            </tr>
            <?php foreach($pedidos as $p): ?>
            <tr>
                <td><?php echo htmlspecialchars($p['curso_nome']); ?></td>
                <td><?php echo htmlspecialchars($p['data_pedido']); ?></td>
                <td><?php echo htmlspecialchars($p['estado']); ?></td>
                <td><?php echo htmlspecialchars($p['observacoes']); ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>
</div>
</body>
</html>