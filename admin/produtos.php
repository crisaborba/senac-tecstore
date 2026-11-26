<?php
require_once __DIR__ . '/../includes/auth.php';
exigir_admin('../');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/funcoes.php';
require_once __DIR__ . '/../includes/admin_funcoes.php';

$base_url = '../';

// ---------------------------------------------------------
// Ações: ativar, desativar e excluir (POST + token CSRF)
// ---------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    exigir_csrf();

    $acao       = $_POST['acao'] ?? '';
    $produto_id = filter_input(INPUT_POST, 'produto_id', FILTER_VALIDATE_INT);

    if ($produto_id === false || $produto_id === null || $produto_id < 1) {
        definir_mensagem('danger', 'Produto inválido.');
        header('Location: produtos.php');
        exit;
    }

    if ($acao === 'desativar' || $acao === 'ativar') {
        $valor = ($acao === 'ativar') ? 1 : 0;
        $stmt = $pdo->prepare('UPDATE produtos SET ativo = ? WHERE id = ?');
        $stmt->execute([$valor, $produto_id]);
        definir_mensagem('success', $valor ? 'Produto ativado.' : 'Produto desativado (não aparece mais na loja).');

    } elseif ($acao === 'excluir') {
        // Produto que já foi vendido não pode ser excluído (histórico de pedidos): só desativado
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM itens_pedido WHERE produto_id = ?');
        $stmt->execute([$produto_id]);

        if ($stmt->fetchColumn() > 0) {
            definir_mensagem('warning', 'Este produto já aparece em pedidos e não pode ser excluído. Use "Desativar".');
        } else {
            $stmt = $pdo->prepare('SELECT imagem FROM produtos WHERE id = ?');
            $stmt->execute([$produto_id]);
            $imagem = $stmt->fetchColumn();

            try {
                $stmt = $pdo->prepare('DELETE FROM produtos WHERE id = ?');
                $stmt->execute([$produto_id]);
                if ($stmt->rowCount() === 1) {
                    apagar_imagem($imagem);
                    definir_mensagem('success', 'Produto excluído.');
                } else {
                    definir_mensagem('danger', 'Produto não encontrado.');
                }
            } catch (PDOException $ex) {
                definir_mensagem('danger', 'Não foi possível excluir o produto. Use "Desativar".');
            }
        }
    }

    header('Location: produtos.php');
    exit;
}

// ---------------------------------------------------------
// Listagem (inclui produtos desativados)
// ---------------------------------------------------------
$produtos = $pdo->query('SELECT p.id, p.nome, p.preco, p.estoque, p.ativo, c.nome AS categoria
                         FROM produtos p
                         INNER JOIN categorias c ON c.id = p.categoria_id
                         ORDER BY p.id DESC')->fetchAll();

$titulo_pagina = 'Produtos (admin)';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/admin_menu.php';
?>

<div class="container mt-3">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
        <h1 class="h3 mb-0" style="color: var(--azul-escuro);">Produtos</h1>
        <a href="produto_cadastro.php" class="btn btn-tec">Cadastrar produto</a>
    </div>

    <?php exibir_mensagem(); ?>

    <div class="table-responsive">
        <table class="table table-sm align-middle">
            <thead class="table-light">
                <tr>
                    <th>ID</th>
                    <th>Nome</th>
                    <th>Categoria</th>
                    <th class="text-end">Preço</th>
                    <th class="text-end">Estoque</th>
                    <th>Status</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($produtos as $p): ?>
                <tr>
                    <td><?= (int) $p['id'] ?></td>
                    <td><?= e($p['nome']) ?></td>
                    <td><?= e($p['categoria']) ?></td>
                    <td class="text-end text-nowrap"><?= formatar_preco($p['preco']) ?></td>
                    <td class="text-end"><?= (int) $p['estoque'] ?></td>
                    <td>
                        <?php if ($p['ativo']): ?>
                            <span class="badge bg-success">Ativo</span>
                        <?php else: ?>
                            <span class="badge bg-secondary">Inativo</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-nowrap">
                        <a href="produto_editar.php?id=<?= (int) $p['id'] ?>" class="btn btn-sm btn-outline-tec">Editar</a>

                        <form method="post" action="produtos.php" class="d-inline">
                            <?= csrf_campo() ?>
                            <input type="hidden" name="produto_id" value="<?= (int) $p['id'] ?>">
                            <input type="hidden" name="acao" value="<?= $p['ativo'] ? 'desativar' : 'ativar' ?>">
                            <button type="submit" class="btn btn-sm btn-outline-secondary">
                                <?= $p['ativo'] ? 'Desativar' : 'Ativar' ?>
                            </button>
                        </form>

                        <form method="post" action="produtos.php" class="d-inline"
                              onsubmit="return confirm('Excluir este produto definitivamente?');">
                            <?= csrf_campo() ?>
                            <input type="hidden" name="produto_id" value="<?= (int) $p['id'] ?>">
                            <input type="hidden" name="acao" value="excluir">
                            <button type="submit" class="btn btn-sm btn-outline-danger">Excluir</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
