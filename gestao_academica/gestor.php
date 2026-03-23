<?php
session_start();
require 'db.php';

// Verifica se o utilizador é gestor pedagógico
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'gestor') {
    header("Location: login.php");
    exit();
}

$erro = "";
$sucesso = "";

// ----------------- PROCESSA APROVAÇÃO/REJEIÇÃO DE FICHAS -----------------
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['acao_ficha'])) {
    $ficha_id = intval($_POST['ficha_id']);
    $novo_estado = $_POST['estado'] ?? '';
    $observacoes = trim($_POST['observacoes'] ?? '');

    if ($ficha_id > 0 && in_array($novo_estado, ['Aprovada', 'Rejeitada'])) {
        $stmt = $pdo->prepare("
            UPDATE fichas_aluno
            SET estado = ?, observacoes = ?, validado_por = ?, data_validacao = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$novo_estado, $observacoes, $_SESSION['user_id'], $ficha_id]);
        $sucesso = "Ficha atualizada com sucesso!";
    } else {
        $erro = "Dados inválidos.";
    }
}

// ----------------- CRUD DE CURSOS -----------------
$curso_id_selecionado = '';
$curso_nome_selecionado = '';
$curso_ativo_selecionado = 1;

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['acao_curso'])) {
    $acao = $_POST['acao_curso'];

    if ($acao === 'carregar_edicao') {
        $curso_id_selecionado = intval($_POST['curso_id']);
        $curso_nome_selecionado = $_POST['curso_nome'];
        $curso_ativo_selecionado = intval($_POST['desativar_val']);
    } elseif ($acao === 'excluir') {
        $curso_id = intval($_POST['curso_id']);
        $stmt = $pdo->prepare("DELETE FROM cursos WHERE id = ?");
        $stmt->execute([$curso_id]);
        $sucesso = "Curso eliminado com sucesso!";
    } else { // salvar/ativar/desativar
        $curso_id = intval($_POST['curso_id'] ?? 0);
        $curso_nome = trim($_POST['curso_nome'] ?? '');
        $desativar = isset($_POST['desativar']) ? 0 : 1;

        if ($curso_nome === '') {
            $erro = "Nome do curso não pode ficar vazio.";
        } else {
            if ($curso_id > 0) {
                $stmt = $pdo->prepare("UPDATE cursos SET nome = ?, ativo = ? WHERE id = ?");
                $stmt->execute([$curso_nome, $desativar, $curso_id]);
                $sucesso = "Curso atualizado com sucesso!";
            } else {
                $stmt = $pdo->prepare("INSERT INTO cursos (nome, ativo) VALUES (?, 1)");
                $stmt->execute([$curso_nome]);
                $sucesso = "Curso criado com sucesso!";
            }
        }
    }
}

// ----------------- CRUD DE UCs -----------------
$uc_id_selecionado = '';
$uc_nome_selecionado = '';
$uc_ativo_selecionado = 1;

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['acao_uc'])) {
    $acao = $_POST['acao_uc'];

    if ($acao === 'carregar_edicao') {
        $uc_id_selecionado = intval($_POST['uc_id']);
        $uc_nome_selecionado = $_POST['uc_nome'];
        $uc_ativo_selecionado = intval($_POST['desativar_val']);
    } elseif ($acao === 'excluir') {
        $uc_id = intval($_POST['uc_id']);
        $stmt = $pdo->prepare("DELETE FROM ucs WHERE id = ?");
        $stmt->execute([$uc_id]);
        $sucesso = "UC eliminada com sucesso!";
    } else { // salvar/ativar/desativar
        $uc_id = intval($_POST['uc_id'] ?? 0);
        $uc_nome = trim($_POST['uc_nome'] ?? '');
        $desativar = isset($_POST['desativar']) ? 0 : 1;

        if ($uc_nome === '') {
            $erro = "Nome da UC não pode ficar vazio.";
        } else {
            if ($uc_id > 0) {
                $stmt = $pdo->prepare("UPDATE ucs SET nome = ?, ativo = ? WHERE id = ?");
                $stmt->execute([$uc_nome, $desativar, $uc_id]);
                $sucesso = "UC atualizada com sucesso!";
            } else {
                $stmt = $pdo->prepare("INSERT INTO ucs (nome, ativo) VALUES (?, 1)");
                $stmt->execute([$uc_nome]);
                $sucesso = "UC criada com sucesso!";
            }
        }
    }
}

// ----------------- CONFIGURAÇÃO PLANO DE ESTUDOS -----------------
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['acao_plano'])) {
    $curso_id = intval($_POST['curso_id'] ?? 0);
    $uc_id = intval($_POST['uc_id'] ?? 0);
    $ano = intval($_POST['ano'] ?? 0);
    $semestre = intval($_POST['semestre'] ?? 0);

    if ($curso_id <= 0 || $uc_id <= 0 || $ano <= 0 || $semestre <= 0) {
        $erro = "Todos os campos do plano de estudos são obrigatórios.";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM plano_estudos WHERE curso_id = ? AND uc_id = ? AND ano = ? AND semestre = ?");
        $stmt->execute([$curso_id, $uc_id, $ano, $semestre]);
        if ($stmt->fetch()) {
            $erro = "Essa UC já está associada a este curso/ano/semestre.";
        } else {
            $stmt = $pdo->prepare("INSERT INTO plano_estudos (curso_id, uc_id, ano, semestre) VALUES (?, ?, ?, ?)");
            $stmt->execute([$curso_id, $uc_id, $ano, $semestre]);
            $sucesso = "UC associada ao curso com sucesso!";
        }
    }
}

// ----------------- BUSCA DADOS -----------------
$fichas = $pdo->query("
    SELECT f.*, u.nome AS aluno_nome
    FROM fichas_aluno f
    JOIN users u ON f.user_id = u.id
    ORDER BY f.id DESC
")->fetchAll();

$cursos = $pdo->query("SELECT * FROM cursos ORDER BY nome ASC")->fetchAll();
$ucs = $pdo->query("SELECT * FROM ucs ORDER BY nome ASC")->fetchAll();
$plano = $pdo->query("
    SELECT pe.*, c.nome AS curso_nome, u.nome AS uc_nome
    FROM plano_estudos pe
    JOIN cursos c ON pe.curso_id = c.id
    JOIN ucs u ON pe.uc_id = u.id
    ORDER BY c.nome, pe.ano, pe.semestre
")->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Gestor Pedagógico</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<header>
    <h1>Sistema de Gestão Académica - Gestor Pedagógico</h1>
</header>

<nav>
    <div class="links">
        <a href="gestor.php">Área do Gestor</a>
    </div>
    <div class="links" style="margin-left:auto;">
        <a href="logout.php">Logout</a>
    </div>
</nav>

<div class="container">
    <?php if(!empty($erro)) echo "<p class='erro'>".htmlspecialchars($erro)."</p>"; ?>
    <?php if(!empty($sucesso)) echo "<p class='sucesso'>".htmlspecialchars($sucesso)."</p>"; ?>

    <!-- ================== FICHAS DE ALUNO ================== -->
    <h2>Fichas de Aluno</h2>
    <table>
        <tr>
            <th>Aluno</th>
            <th>Dados</th>
            <th>Foto</th>
            <th>Estado</th>
            <th>Observações</th>
            <th>Ação / Auditoria</th>
        </tr>
        <?php foreach($fichas as $f): ?>
        <tr>
            <td><?php echo htmlspecialchars($f['aluno_nome']); ?></td>
            <td><?php echo htmlspecialchars($f['dados']); ?></td>
            <td><?php if($f['foto']): ?><img src="uploads/<?php echo htmlspecialchars($f['foto']); ?>" width="100"><?php endif; ?></td>
            <td><?php echo htmlspecialchars($f['estado']); ?></td>
            <td><?php echo htmlspecialchars($f['observacoes']); ?></td>
            <td>
                <?php if($f['estado'] == 'submetida'): ?>
                    <form method="POST">
                        <input type="hidden" name="ficha_id" value="<?php echo $f['id']; ?>">
                        <select name="estado" required>
                            <option value="">--Selecione--</option>
                            <option value="Aprovada">Aprovar</option>
                            <option value="Rejeitada">Rejeitar</option>
                        </select>
                        <input type="text" name="observacoes" placeholder="Observações">
                        <button type="submit" name="acao_ficha" value="atualizar">Atualizar</button>
                    </form>
                <?php elseif($f['estado'] == 'rascunho'): ?>
                    Rascunho — ainda não submetida
                <?php else: ?>
                    Validado por ID <?php echo htmlspecialchars($f['validado_por']); ?> em <?php echo htmlspecialchars($f['data_validacao']); ?>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>

    <!-- ================== CURSOS ================== -->
    <h2>Gestão de Cursos</h2>
    <form method="POST">
        <input type="hidden" name="curso_id" value="<?php echo $curso_id_selecionado ?? ''; ?>">
        Nome do curso: <input type="text" name="curso_nome" value="<?php echo $curso_nome_selecionado ?? ''; ?>" required>
        <label><input type="checkbox" name="desativar" <?php echo isset($curso_ativo_selecionado) && !$curso_ativo_selecionado ? 'checked' : ''; ?>> Desativar</label>
        <button type="submit" name="acao_curso" value="salvar">Salvar Curso</button>
    </form>

    <table>
        <tr><th>ID</th><th>Nome</th><th>Ativo</th><th>Ações</th></tr>
        <?php foreach($cursos as $c): ?>
            <tr>
                <td><?php echo $c['id']; ?></td>
                <td><?php echo htmlspecialchars($c['nome']); ?></td>
                <td><?php echo isset($c['ativo']) && $c['ativo'] ? 'Sim' : 'Não'; ?></td>
                <td>
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="curso_id" value="<?php echo $c['id']; ?>">
                        <input type="hidden" name="curso_nome" value="<?php echo htmlspecialchars($c['nome']); ?>">
                        <input type="hidden" name="desativar_val" value="<?php echo $c['ativo'] ? 1 : 0; ?>">
                        <button type="submit" name="acao_curso" value="carregar_edicao">Editar</button>
                        <button type="submit" name="acao_curso" value="excluir" onclick="return confirm('Tem a certeza que deseja eliminar este curso?')">Eliminar</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>

    <!-- ================== UCs ================== -->
    <h2>Gestão de UCs</h2>
    <form method="POST">
        <input type="hidden" name="uc_id" value="<?php echo $uc_id_selecionado ?? ''; ?>">
        Nome da UC: <input type="text" name="uc_nome" value="<?php echo $uc_nome_selecionado ?? ''; ?>" required>
        <label><input type="checkbox" name="desativar" <?php echo isset($uc_ativo_selecionado) && !$uc_ativo_selecionado ? 'checked' : ''; ?>> Desativar</label>
        <button type="submit" name="acao_uc" value="salvar">Salvar UC</button>
    </form>

    <table>
        <tr><th>ID</th><th>Nome</th><th>Ativo</th><th>Ações</th></tr>
        <?php foreach($ucs as $u): ?>
            <tr>
                <td><?php echo $u['id']; ?></td>
                <td><?php echo htmlspecialchars($u['nome']); ?></td>
                <td><?php echo isset($u['ativo']) && $u['ativo'] ? 'Sim' : 'Não'; ?></td>
                <td>
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="uc_id" value="<?php echo $u['id']; ?>">
                        <input type="hidden" name="uc_nome" value="<?php echo htmlspecialchars($u['nome']); ?>">
                        <input type="hidden" name="desativar_val" value="<?php echo $u['ativo'] ? 1 : 0; ?>">
                        <button type="submit" name="acao_uc" value="carregar_edicao">Editar</button>
                        <button type="submit" name="acao_uc" value="excluir" onclick="return confirm('Tem a certeza que deseja eliminar esta UC?')">Eliminar</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>

    <!-- ================== PLANO DE ESTUDOS ================== -->
    <h2>Plano de Estudos</h2>
    <form method="POST">
        Curso:
        <select name="curso_id" required>
            <option value="">--Selecione--</option>
            <?php foreach($cursos as $c): ?>
                <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['nome']); ?></option>
            <?php endforeach; ?>
        </select>
        UC:
        <select name="uc_id" required>
            <option value="">--Selecione--</option>
            <?php foreach($ucs as $u): ?>
                <option value="<?php echo $u['id']; ?>"><?php echo htmlspecialchars($u['nome']); ?></option>
            <?php endforeach; ?>
        </select>
        Ano: <input type="number" name="ano" min="1" required>
        Semestre: <input type="number" name="semestre" min="1" max="2" required>
        <button type="submit" name="acao_plano" value="salvar">Adicionar ao Plano</button>
    </form>

    <table>
        <tr><th>Curso</th><th>UC</th><th>Ano</th><th>Semestre</th></tr>
        <?php foreach($plano as $p): ?>
            <tr>
                <td><?php echo htmlspecialchars($p['curso_nome']); ?></td>
                <td><?php echo htmlspecialchars($p['uc_nome']); ?></td>
                <td><?php echo $p['ano']; ?></td>
                <td><?php echo $p['semestre']; ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
</div>
</body>
</html>