<?php
session_start();
require 'db.php';

// Verifica se o utilizador é funcionário
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'funcionario') {
    header("Location: login.php");
    exit();
}

$erro = "";
$sucesso = "";

// ----------------- PROCESSA APROVAÇÃO/REJEIÇÃO -----------------
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['acao_pedido'])) {
    $pedido_id = intval($_POST['pedido_id']);
    $novo_estado = $_POST['estado'] ?? '';
    $observacoes = trim($_POST['observacoes'] ?? '');

    if ($pedido_id > 0 && in_array($novo_estado, ['Aprovado', 'Rejeitado'])) {
        $stmt = $pdo->prepare("
            UPDATE matriculas
            SET estado = ?, observacoes = ?, validado_por = ?, data_validacao = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$novo_estado, $observacoes, $_SESSION['user_id'], $pedido_id]);
        $sucesso = "Pedido atualizado com sucesso!";
    } else {
        $erro = "Dados inválidos.";
    }
}

// ----------------- CRIAR PAUTA -----------------
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['criar_pauta'])) {
    $uc_id = intval($_POST['uc_id']);
    $ano_letivo = trim($_POST['ano_letivo']);
    $epoca = trim($_POST['epoca']);

    if ($uc_id > 0 && $ano_letivo && $epoca) {
        // Cria a pauta
        $stmt = $pdo->prepare("
            INSERT INTO pautas (uc_id, ano_letivo, epoca, criado_por, data_criacao)
            VALUES (?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$uc_id, $ano_letivo, $epoca, $_SESSION['user_id']]);
        $pauta_id = $pdo->lastInsertId();

        // Obter alunos elegíveis
        $stmt = $pdo->prepare("
            SELECT m.user_id, u.nome
            FROM matriculas m
            JOIN users u ON m.user_id = u.id
            WHERE m.estado = 'Aprovado' AND m.curso_id IN (
                SELECT curso_id FROM plano_estudos WHERE uc_id = ?
            )
        ");
        $stmt->execute([$uc_id]);
        $alunos = $stmt->fetchAll();

        // Inserir registos de avaliação
        $stmt = $pdo->prepare("
            INSERT INTO avaliacoes (pauta_id, aluno_id, nota)
            VALUES (?, ?, NULL)
        ");
        foreach ($alunos as $aluno) {
            $stmt->execute([$pauta_id, $aluno['user_id']]);
        }

        $sucesso = "Pauta criada com sucesso com " . count($alunos) . " alunos!";
    } else {
        $erro = "Preencha todos os campos da pauta.";
    }
}

// ----------------- REGISTAR NOTAS -----------------
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['registar_notas'])) {
    foreach ($_POST['notas'] as $avaliacao_id => $nota) {
        $nota = trim($nota);
        if ($nota !== '') {
            $stmt = $pdo->prepare("
                UPDATE avaliacoes
                SET nota = ?
                WHERE id = ?
            ");
            $stmt->execute([$nota, intval($avaliacao_id)]);
        }
    }
    $sucesso = "Notas atualizadas com sucesso!";
}

// ----------------- BUSCA PEDIDOS -----------------
$stmt = $pdo->prepare("
    SELECT m.*, c.nome AS curso_nome, u.nome AS aluno_nome, f.nome AS funcionario_nome
    FROM matriculas m
    JOIN cursos c ON m.curso_id = c.id
    JOIN users u ON m.user_id = u.id
    LEFT JOIN users f ON m.validado_por = f.id
    ORDER BY m.data_pedido DESC
");
$stmt->execute();
$pedidos = $stmt->fetchAll() ?: [];

// ----------------- BUSCA UCs -----------------
$stmt = $pdo->query("SELECT * FROM ucs ORDER BY nome ASC");
$ucs = $stmt->fetchAll();

// ----------------- BUSCA PAUTAS -----------------
$stmt = $pdo->prepare("
    SELECT p.*, u.nome AS uc_nome, f.nome AS funcionario_nome
    FROM pautas p
    JOIN ucs u ON p.uc_id = u.id
    LEFT JOIN users f ON p.criado_por = f.id
    ORDER BY p.data_criacao DESC
");
$stmt->execute();
$pautas = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Área do Funcionário</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<header>
    <h1>Sistema de Gestão Académica</h1>
</header>

<nav>
    <div class="links">
        <a href="funcionario.php">Área do Funcionário</a>
    </div>
    <div class="links" style="margin-left:auto;">
        <a href="logout.php">Logout</a>
    </div>
</nav>

<div class="container">
    <?php if($erro) echo "<p style='color:red;'>".htmlspecialchars($erro)."</p>"; ?>
    <?php if($sucesso) echo "<p style='color:green;'>".htmlspecialchars($sucesso)."</p>"; ?>

    <h2>Pedidos de Matrícula/Inscrição</h2>
    <?php if(count($pedidos) == 0): ?>
        <p>Nenhum pedido disponível.</p>
    <?php else: ?>
        <table>
            <tr>
                <th>Aluno</th>
                <th>Curso</th>
                <th>Data do Pedido</th>
                <th>Estado</th>
                <th>Observações</th>
                <th>Ações / Auditoria</th>
            </tr>
            <?php foreach($pedidos as $p): ?>
            <tr>
                <td><?php echo htmlspecialchars($p['aluno_nome']); ?></td>
                <td><?php echo htmlspecialchars($p['curso_nome']); ?></td>
                <td><?php echo htmlspecialchars($p['data_pedido']); ?></td>
                <td><?php echo htmlspecialchars($p['estado']); ?></td>
                <td><?php echo htmlspecialchars($p['observacoes']); ?></td>
                <td>
                    <?php if(strtolower($p['estado']) === 'pendente'): ?>
                        <form method="POST">
                            <input type="hidden" name="pedido_id" value="<?php echo $p['id']; ?>">
                            <select name="estado" required>
                                <option value="">--Selecione--</option>
                                <option value="Aprovado">Aprovar</option>
                                <option value="Rejeitado">Rejeitar</option>
                            </select>
                            <input type="text" name="observacoes" placeholder="Observações">
                            <button type="submit" name="acao_pedido" value="atualizar">Atualizar</button>
                        </form>
                    <?php else: ?>
                        Validado por <?php echo htmlspecialchars($p['funcionario_nome']); ?> em <?php echo htmlspecialchars($p['data_validacao']); ?>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>

    <h2>Criar Pauta</h2>
    <form method="POST">
        <label>UC:</label>
        <select name="uc_id" required>
            <option value="">--Selecione--</option>
            <?php foreach($ucs as $uc): ?>
                <option value="<?php echo $uc['id']; ?>"><?php echo htmlspecialchars($uc['nome']); ?></option>
            <?php endforeach; ?>
        </select>
        <label>Ano Letivo:</label>
        <input type="text" name="ano_letivo" placeholder="Ex: 2025/2026" required>
        <label>Época:</label>
        <select name="epoca" required>
            <option value="">--Selecione--</option>
            <option value="Normal">Normal</option>
            <option value="Recurso">Recurso</option>
            <option value="Especial">Especial</option>
        </select>
        <button type="submit" name="criar_pauta">Criar Pauta</button>
    </form>

    <h2>Pautas Criadas</h2>
    <?php if(count($pautas) == 0): ?>
        <p>Nenhuma pauta disponível.</p>
    <?php else: ?>
        <?php foreach($pautas as $pauta): ?>
            <h3><?php echo htmlspecialchars($pauta['uc_nome']); ?> - <?php echo htmlspecialchars($pauta['ano_letivo']); ?> (<?php echo htmlspecialchars($pauta['epoca']); ?>)</h3>
            <p>Criado por <?php echo htmlspecialchars($pauta['funcionario_nome']); ?> em <?php echo htmlspecialchars($pauta['data_criacao']); ?></p>
            <?php
            // Buscar avaliações desta pauta
            $stmt = $pdo->prepare("
                SELECT a.*, u.nome AS aluno_nome
                FROM avaliacoes a
                JOIN users u ON a.aluno_id = u.id
                WHERE a.pauta_id = ?
            ");
            $stmt->execute([$pauta['id']]);
            $avaliacoes = $stmt->fetchAll();
            ?>
            <form method="POST">
                <table>
                    <tr>
                        <th>Aluno</th>
                        <th>Nota Final</th>
                    </tr>
                    <?php foreach($avaliacoes as $av): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($av['aluno_nome']); ?></td>
                        <td>
                            <input type="text" name="notas[<?php echo $av['id']; ?>]" value="<?php echo htmlspecialchars($av['nota']); ?>">
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </table>
                <button type="submit" name="registar_notas">Registar/Atualizar Notas</button>
            </form>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
</body>
</html>