<?php
require_once __DIR__ . '/../includes/auth.php';
exigir_admin('../');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/funcoes.php';

$base_url = '../';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$pedido = false;

if ($id !== false && $id !== null && $id > 0) {
    $stmt = $pdo->prepare('SELECT p.id, p.data_pedido, p.forma_pagamento, p.valor_total,
                                  u.nome AS cliente, u.email
                           FROM pedidos p
                           INNER JOIN usuarios u ON u.id = p.usuario_id
                           WHERE p.id = ?');
    $stmt->execute([$id]);
    $pedido = $stmt->fetch();
}

if (!$pedido) {
    definir_mensagem('danger', 'Pedido não encontrado.');
    header('Location: pedidos.php');
    exit;
}

$stmt = $pdo->prepare('SELECT pr.nome, i.quantidade, i.preco_unitario
                       FROM itens_pedido i
                       INNER JOIN produtos pr ON pr.id = i.produto_id
                       WHERE i.pedido_id = ?
                       ORDER BY i.id');
$stmt->execute([$pedido['id']]);
$itens = $stmt->fetchAll();

$titulo_pagina = 'Pedido #' . $pedido['id'];
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/admin_menu.php';
?>

<div class="container mt-3">
    <h1 class="h3 mb-3" style="color: var(--azul-escuro);">Pedido #<?= (int) $pedido['id'] ?></h1>

    <div class="row g-3 mb-3">
        <div class="col-12 col-md-6">
            <div class="resumo-carrinho">
                <h2 class="h6">Cliente</h2>
                <p class="mb-0"><?= e($pedido['cliente']) ?></p>
                <p class="small text-muted mb-0"><?= e($pedido['email']) ?></p>
            </div>
        </div>
        <div class="col-12 col-md-6">
            <div class="resumo-carrinho">
                <h2 class="h6">Pedido</h2>
                <p class="mb-0">Data: <?= e(formatar_data($pedido['data_pedido'])) ?></p>
                <p class="mb-0">Pagamento: <?= e(nome_forma_pagamento($pedido['forma_pagamento'])) ?></p>
            </div>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-sm align-middle">
            <thead class="table-light">
                <tr>
                    <th>Produto</th>
                    <th class="text-end">Qtd.</th>
                    <th class="text-end">Preço unit.</th>
                    <th class="text-end">Subtotal</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($itens as $i): ?>
                <tr>
                    <td><?= e($i['nome']) ?></td>
                    <td class="text-end"><?= (int) $i['quantidade'] ?></td>
                    <td class="text-end text-nowrap"><?= formatar_preco($i['preco_unitario']) ?></td>
                    <td class="text-end text-nowrap"><?= formatar_preco($i['quantidade'] * $i['preco_unitario']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr class="fw-bold">
                    <td colspan="3" class="text-end">Total</td>
                    <td class="text-end text-nowrap"><?= formatar_preco($pedido['valor_total']) ?></td>
                </tr>
            </tfoot>
        </table>
    </div>

    <a href="pedidos.php" class="btn btn-outline-secondary">Voltar aos pedidos</a>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
