<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/funcoes.php';

$base_url = '';

// O produto é identificado pelo ID na URL (produto.php?id=1)
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

$produto = false;
if ($id !== false && $id !== null && $id > 0) {
    $stmt = $pdo->prepare('SELECT p.id, p.nome, p.descricao, p.preco, p.estoque, p.imagem,
                                  c.nome AS categoria
                           FROM produtos p
                           INNER JOIN categorias c ON c.id = p.categoria_id
                           WHERE p.id = ? AND p.ativo = 1');
    $stmt->execute([$id]);
    $produto = $stmt->fetch();
}

if (!$produto) {
    http_response_code(404);
    $titulo_pagina = 'Produto não encontrado';
    include __DIR__ . '/includes/header.php';
    ?>
    <div class="container mt-4">
        <div class="alert alert-warning">Produto não encontrado ou indisponível.</div>
        <a href="produtos.php" class="btn btn-tec">Voltar aos produtos</a>
    </div>
    <?php
    include __DIR__ . '/includes/footer.php';
    exit;
}

$titulo_pagina = $produto['nome'];
$disp = disponibilidade($produto['estoque']);

// Quantidade que o cliente já tem deste produto no carrinho
$no_carrinho = (int) ($_SESSION['carrinho'][$produto['id']] ?? 0);
$pode_adicionar = $produto['estoque'] - $no_carrinho;

include __DIR__ . '/includes/header.php';
?>

<div class="container mt-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb small">
            <li class="breadcrumb-item"><a href="index.php">Início</a></li>
            <li class="breadcrumb-item"><a href="produtos.php">Produtos</a></li>
            <li class="breadcrumb-item active" aria-current="page"><?= e($produto['categoria']) ?></li>
        </ol>
    </nav>

    <?php exibir_mensagem(); ?>

    <div class="row g-4">
        <div class="col-12 col-md-5">
            <img src="<?= e(caminho_imagem($produto['imagem'], $base_url)) ?>"
                 alt="<?= e($produto['nome']) ?>" class="img-produto-detalhe">
        </div>

        <div class="col-12 col-md-7">
            <h1 class="h3"><?= e($produto['nome']) ?></h1>
            <p class="text-muted small mb-2">Categoria: <?= e($produto['categoria']) ?></p>

            <p class="preco fs-3 mb-1"><?= formatar_preco($produto['preco']) ?></p>
            <p class="mb-3">
                <span class="badge <?= $disp['classe'] ?>"><?= $disp['texto'] ?></span>
                <span class="small text-muted ms-2"><?= (int) $produto['estoque'] ?> unidade(s) em estoque</span>
            </p>

            <p><?= nl2br(e($produto['descricao'])) ?></p>

            <?php if ($produto['estoque'] <= 0): ?>
                <button class="btn btn-secondary" disabled>Produto indisponível</button>
            <?php elseif ($pode_adicionar <= 0): ?>
                <div class="alert alert-info">
                    Você já tem todas as unidades disponíveis no carrinho.
                </div>
                <a href="carrinho.php" class="btn btn-tec">Ver carrinho</a>
            <?php else: ?>
                <?php if ($no_carrinho > 0): ?>
                    <p class="small text-muted">Você já tem <?= $no_carrinho ?> unidade(s) no carrinho.</p>
                <?php endif; ?>
                <form action="carrinho.php" method="post" class="row g-2 align-items-end">
                    <?= csrf_campo() ?>
                    <input type="hidden" name="acao" value="adicionar">
                    <input type="hidden" name="produto_id" value="<?= (int) $produto['id'] ?>">
                    <div class="col-4 col-sm-3">
                        <label for="quantidade" class="form-label small mb-1">Quantidade</label>
                        <input type="number" id="quantidade" name="quantidade" class="form-control"
                               value="1" min="1" max="<?= (int) $pode_adicionar ?>" data-max="<?= (int) $pode_adicionar ?>" required>
                    </div>
                    <div class="col-8 col-sm-9">
                        <button type="submit" class="btn btn-tec w-100">
                            <i class="bi bi-cart-plus"></i> Adicionar ao carrinho
                        </button>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
