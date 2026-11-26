<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/funcoes.php';

$base_url = '';

exigir_login('pedidos.php');

$usuario_id = (int) $_SESSION['usuario_id'];

// Somente pedidos do usuário autenticado (usuario_id vem da sessão)
$stmt = $pdo->prepare('SELECT id, data_pedido, forma_pagamento, valor_total
                       FROM pedidos
                       WHERE usuario_id = ?
                       ORDER BY data_pedido DESC, id DESC');
$stmt->execute([$usuario_id]);
$pedidos = $stmt->fetchAll();

// Itens de todos os pedidos de uma vez
$itens_por_pedido = [];
if (!empty($pedidos)) {
    $ids = array_map('intval', array_column($pedidos, 'id'));
    $marcadores = implode(',', array_fill(0, count($ids), '?'));

    $stmt = $pdo->prepare("SELECT i.pedido_id, pr.nome, i.quantidade, i.preco_unitario
                           FROM itens_pedido i
                           INNER JOIN produtos pr ON pr.id = i.produto_id
                           WHERE i.pedido_id IN ($marcadores)
                           ORDER BY i.id");
    $stmt->execute($ids);
    foreach ($stmt->fetchAll() as $item) {
        $itens_por_pedido[$item['pedido_id']][] = $item;
    }
}

$titulo_pagina = 'Meus pedidos';
include __DIR__ . '/includes/header.php';
?>

<div class="container mt-4">
    <h1 class="h3 mb-3" style="color: var(--azul-escuro);">Meus pedidos</h1>

    <?php exibir_mensagem(); ?>

    <?php if (empty($pedidos)): ?>
        <div class="alert alert-light border">Você ainda não fez nenhum pedido.</div>
        <a href="produtos.php" class="btn btn-tec">Ver produtos</a>
    <?php else: ?>
        <?php foreach ($pedidos as $pedido): ?>
            <div class="item-carrinho">
                <div class="d-flex flex-wrap justify-content-between mb-2">
                    <strong>Pedido #<?= (int) $pedido['id'] ?></strong>
                    <span class="small text-muted"><?= e(formatar_data($pedido['data_pedido'])) ?></span>
                </div>
                <p class="small mb-2">Pagamento: <?= e(nome_forma_pagamento($pedido['forma_pagamento'])) ?></p>

                <ul class="list-unstyled small mb-2">
                    <?php foreach ($itens_por_pedido[$pedido['id']] ?? [] as $item): ?>
                        <li class="d-flex justify-content-between border-bottom py-1">
                            <span><?= (int) $item['quantidade'] ?> x <?= e($item['nome']) ?></span>
                            <span><?= formatar_preco($item['quantidade'] * $item['preco_unitario']) ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>

                <div class="d-flex justify-content-between fw-bold">
                    <span>Total</span>
                    <span class="preco"><?= formatar_preco($pedido['valor_total']) ?></span>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
