<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/funcoes.php';

$base_url = '';

if (!isset($_SESSION['carrinho']) || !is_array($_SESSION['carrinho'])) {
    $_SESSION['carrinho'] = [];
}

/*
 * O carrinho fica em $_SESSION['carrinho'] no formato:
 *   [ id_do_produto => quantidade, ... ]
 * Preço e estoque são sempre lidos do banco (nunca da sessão ou do formulário).
 */

// Busca um produto ativo pelo ID
function buscar_produto_ativo($pdo, $id)
{
    $stmt = $pdo->prepare('SELECT id, nome, preco, estoque FROM produtos WHERE id = ? AND ativo = 1');
    $stmt->execute([$id]);
    return $stmt->fetch();
}

// ---------------------------------------------------------
// Ações (formulários enviados por POST)
// ---------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    exigir_csrf();

    $acao       = $_POST['acao'] ?? '';
    $produto_id = filter_input(INPUT_POST, 'produto_id', FILTER_VALIDATE_INT);
    $quantidade = filter_input(INPUT_POST, 'quantidade', FILTER_VALIDATE_INT);

    if ($produto_id === false || $produto_id === null || $produto_id < 1) {
        definir_mensagem('danger', 'Produto inválido.');
        header('Location: carrinho.php');
        exit;
    }

    if ($acao === 'adicionar') {
        $produto = buscar_produto_ativo($pdo, $produto_id);

        if (!$produto) {
            definir_mensagem('danger', 'Produto não encontrado ou indisponível.');
            header('Location: produtos.php');
            exit;
        }

        if ($quantidade === false || $quantidade === null || $quantidade < 1) {
            definir_mensagem('danger', 'Informe uma quantidade válida.');
            header('Location: produto.php?id=' . $produto_id);
            exit;
        }

        $nova_quantidade = ($_SESSION['carrinho'][$produto_id] ?? 0) + $quantidade;

        if ($nova_quantidade > $produto['estoque']) {
            definir_mensagem('danger', 'Quantidade acima do estoque disponível (' . (int) $produto['estoque'] . ' unidade(s)).');
            header('Location: produto.php?id=' . $produto_id);
            exit;
        }

        $_SESSION['carrinho'][$produto_id] = $nova_quantidade;
        definir_mensagem('success', 'Produto adicionado ao carrinho.');
        header('Location: carrinho.php');
        exit;
    }

    if ($acao === 'atualizar') {
        $produto = buscar_produto_ativo($pdo, $produto_id);

        if (!$produto || !isset($_SESSION['carrinho'][$produto_id])) {
            definir_mensagem('danger', 'Produto não encontrado no carrinho.');
        } elseif ($quantidade === false || $quantidade === null || $quantidade < 1) {
            definir_mensagem('danger', 'Informe uma quantidade válida (mínimo 1). Para excluir, use o botão Remover.');
        } elseif ($quantidade > $produto['estoque']) {
            definir_mensagem('danger', 'Quantidade acima do estoque disponível (' . (int) $produto['estoque'] . ' unidade(s)).');
        } else {
            $_SESSION['carrinho'][$produto_id] = $quantidade;
            definir_mensagem('success', 'Quantidade atualizada.');
        }
        header('Location: carrinho.php');
        exit;
    }

    if ($acao === 'remover') {
        unset($_SESSION['carrinho'][$produto_id]);
        definir_mensagem('success', 'Produto removido do carrinho.');
        header('Location: carrinho.php');
        exit;
    }

    // Ação desconhecida
    header('Location: carrinho.php');
    exit;
}

// ---------------------------------------------------------
// Exibição do carrinho
// ---------------------------------------------------------
$itens = [];
$total = 0;
$avisos = [];

if (!empty($_SESSION['carrinho'])) {
    $ids = array_map('intval', array_keys($_SESSION['carrinho']));
    // Gera um "?" para cada ID (os valores continuam indo por parâmetro)
    $marcadores = implode(',', array_fill(0, count($ids), '?'));

    $stmt = $pdo->prepare("SELECT id, nome, preco, estoque, imagem
                           FROM produtos
                           WHERE ativo = 1 AND id IN ($marcadores)");
    $stmt->execute($ids);
    $produtos_banco = [];
    foreach ($stmt->fetchAll() as $p) {
        $produtos_banco[$p['id']] = $p;
    }

    foreach ($_SESSION['carrinho'] as $id => $qtd) {
        // Produto desativado, excluído ou sem estoque: sai do carrinho
        if (!isset($produtos_banco[$id]) || $produtos_banco[$id]['estoque'] < 1) {
            unset($_SESSION['carrinho'][$id]);
            $avisos[] = 'Um produto foi removido do carrinho porque não está mais disponível.';
            continue;
        }

        $p = $produtos_banco[$id];

        // Estoque diminuiu depois que o cliente adicionou: ajusta a quantidade
        if ($qtd > $p['estoque']) {
            $qtd = (int) $p['estoque'];
            $_SESSION['carrinho'][$id] = $qtd;
            $avisos[] = 'A quantidade de "' . $p['nome'] . '" foi ajustada ao estoque disponível.';
        }

        $p['quantidade'] = $qtd;
        $p['subtotal']   = $p['preco'] * $qtd;
        $total += $p['subtotal'];
        $itens[] = $p;
    }
}

$titulo_pagina = 'Carrinho';
include __DIR__ . '/includes/header.php';
?>

<div class="container mt-4">
    <h1 class="h3 mb-3" style="color: var(--azul-escuro);">Carrinho de compras</h1>

    <?php exibir_mensagem(); ?>
    <?php foreach (array_unique($avisos) as $aviso): ?>
        <div class="alert alert-warning"><?= e($aviso) ?></div>
    <?php endforeach; ?>

    <?php if (empty($itens)): ?>
        <div class="alert alert-light border">
            Seu carrinho está vazio.
        </div>
        <a href="produtos.php" class="btn btn-tec">Ver produtos</a>
    <?php else: ?>
        <div class="row g-4">
            <div class="col-12 col-lg-8">
                <?php foreach ($itens as $item): ?>
                    <div class="item-carrinho">
                        <div class="row g-2 align-items-center">
                            <div class="col-3 col-md-2">
                                <img src="<?= e(caminho_imagem($item['imagem'], $base_url)) ?>"
                                     alt="<?= e($item['nome']) ?>" class="img-carrinho">
                            </div>
                            <div class="col-9 col-md-10">
                                <a href="produto.php?id=<?= (int) $item['id'] ?>" class="fw-semibold text-decoration-none">
                                    <?= e($item['nome']) ?>
                                </a>
                                <div class="small text-muted">Unitário: <?= formatar_preco($item['preco']) ?></div>
                                <div class="preco">Subtotal: <?= formatar_preco($item['subtotal']) ?></div>

                                <div class="d-flex flex-wrap gap-2 mt-2">
                                    <form action="carrinho.php" method="post" class="d-flex gap-2">
                                        <?= csrf_campo() ?>
                                        <input type="hidden" name="acao" value="atualizar">
                                        <input type="hidden" name="produto_id" value="<?= (int) $item['id'] ?>">
                                        <input type="number" name="quantidade" class="form-control form-control-sm campo-qtd"
                                               value="<?= (int) $item['quantidade'] ?>" min="1"
                                               max="<?= (int) $item['estoque'] ?>" data-max="<?= (int) $item['estoque'] ?>"
                                               aria-label="Quantidade de <?= e($item['nome']) ?>" required>
                                        <button type="submit" class="btn btn-sm btn-outline-tec">Atualizar</button>
                                    </form>
                                    <form action="carrinho.php" method="post">
                                        <?= csrf_campo() ?>
                                        <input type="hidden" name="acao" value="remover">
                                        <input type="hidden" name="produto_id" value="<?= (int) $item['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Remover</button>
                                    </form>
                                </div>
                                <div class="small text-muted mt-1">Máximo: <?= (int) $item['estoque'] ?> unidade(s)</div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="col-12 col-lg-4">
                <div class="resumo-carrinho">
                    <h2 class="h5">Resumo</h2>
                    <div class="d-flex justify-content-between small mb-1">
                        <span>Itens</span>
                        <span><?= array_sum(array_column($itens, 'quantidade')) ?></span>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between fw-bold fs-5 mb-3">
                        <span>Total</span>
                        <span class="preco"><?= formatar_preco($total) ?></span>
                    </div>
                    <a href="checkout.php" class="btn btn-tec w-100 mb-2">Finalizar compra</a>
                    <a href="produtos.php" class="btn btn-outline-secondary w-100">Continuar comprando</a>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
