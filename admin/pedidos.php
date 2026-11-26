<?php
require_once __DIR__ . '/../includes/auth.php';
exigir_admin('../');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/funcoes.php';

$base_url = '../';

$pedidos = $pdo->query('SELECT p.id, p.data_pedido, p.forma_pagamento, p.valor_total, u.nome AS cliente
                        FROM pedidos p
                        INNER JOIN usuarios u ON u.id = p.usuario_id
                        ORDER BY p.data_pedido DESC, p.id DESC')->fetchAll();

$titulo_pagina = 'Pedidos (admin)';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/admin_menu.php';
?>

<div class="container mt-3">
    <h1 class="h3 mb-3" style="color: var(--azul-escuro);">Pedidos realizados</h1>

    <?php if (empty($pedidos)): ?>
        <div class="alert alert-light border">Nenhum pedido realizado ainda.</div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-sm align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Pedido</th>
                        <th>Data</th>
                        <th>Cliente</th>
                        <th>Pagamento</th>
                        <th class="text-end">Total</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($pedidos as $p): ?>
                    <tr>
                        <td>#<?= (int) $p['id'] ?></td>
                        <td class="text-nowrap"><?= e(formatar_data($p['data_pedido'])) ?></td>
                        <td><?= e($p['cliente']) ?></td>
                        <td><?= e(nome_forma_pagamento($p['forma_pagamento'])) ?></td>
                        <td class="text-end text-nowrap"><?= formatar_preco($p['valor_total']) ?></td>
                        <td><a href="pedido.php?id=<?= (int) $p['id'] ?>" class="btn btn-sm btn-outline-tec">Detalhes</a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
