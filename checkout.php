<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/funcoes.php';

$base_url = '';

// Somente usuários autenticados podem finalizar a compra
exigir_login('checkout.php');

$usuario_id = (int) $_SESSION['usuario_id'];

// ---------------------------------------------------------
// Tela de confirmação (checkout.php?pedido=ID)
// Só mostra pedidos do próprio usuário.
// ---------------------------------------------------------
$pedido_id = filter_input(INPUT_GET, 'pedido', FILTER_VALIDATE_INT);

if ($pedido_id !== false && $pedido_id !== null) {
    $stmt = $pdo->prepare('SELECT id, data_pedido, forma_pagamento, valor_total
                           FROM pedidos WHERE id = ? AND usuario_id = ?');
    $stmt->execute([$pedido_id, $usuario_id]);
    $pedido = $stmt->fetch();

    if (!$pedido) {
        http_response_code(404);
        $titulo_pagina = 'Pedido não encontrado';
        include __DIR__ . '/includes/header.php';
        echo '<div class="container mt-4"><div class="alert alert-warning">Pedido não encontrado.</div>'
           . '<a href="pedidos.php" class="btn btn-tec">Meus pedidos</a></div>';
        include __DIR__ . '/includes/footer.php';
        exit;
    }

    $stmt = $pdo->prepare('SELECT pr.nome, i.quantidade, i.preco_unitario
                           FROM itens_pedido i
                           INNER JOIN produtos pr ON pr.id = i.produto_id
                           WHERE i.pedido_id = ?
                           ORDER BY i.id');
    $stmt->execute([$pedido['id']]);
    $itens_confirmados = $stmt->fetchAll();

    $titulo_pagina = 'Compra confirmada';
    include __DIR__ . '/includes/header.php';
    ?>
    <div class="container mt-4">
        <div class="alert alert-success">
            <h1 class="h4">Compra confirmada!</h1>
            Seu pedido <strong>#<?= (int) $pedido['id'] ?></strong> foi registrado com sucesso.
            (Pagamento simulado: nenhuma cobrança real foi feita.)
        </div>

        <div class="resumo-carrinho mb-3">
            <p class="mb-1"><strong>Data:</strong> <?= e(formatar_data($pedido['data_pedido'])) ?></p>
            <p class="mb-3"><strong>Pagamento:</strong> <?= e(nome_forma_pagamento($pedido['forma_pagamento'])) ?></p>
            <ul class="list-unstyled mb-3">
                <?php foreach ($itens_confirmados as $i): ?>
                    <li class="d-flex justify-content-between border-bottom py-1">
                        <span><?= (int) $i['quantidade'] ?> x <?= e($i['nome']) ?></span>
                        <span><?= formatar_preco($i['quantidade'] * $i['preco_unitario']) ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
            <p class="fw-bold fs-5 d-flex justify-content-between mb-0">
                <span>Total</span><span class="preco"><?= formatar_preco($pedido['valor_total']) ?></span>
            </p>
        </div>

        <a href="pedidos.php" class="btn btn-tec">Meus pedidos</a>
        <a href="produtos.php" class="btn btn-outline-secondary">Continuar comprando</a>
    </div>
    <?php
    include __DIR__ . '/includes/footer.php';
    exit;
}

// ---------------------------------------------------------
// Checkout normal
// ---------------------------------------------------------
$carrinho = $_SESSION['carrinho'] ?? [];

if (empty($carrinho) || !is_array($carrinho)) {
    definir_mensagem('warning', 'Seu carrinho está vazio.');
    header('Location: carrinho.php');
    exit;
}

// ---------------------------------------------------------
// Processamento do pedido (POST)
// ---------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    exigir_csrf();

    $forma_pagamento = $_POST['forma_pagamento'] ?? '';

    if (!array_key_exists($forma_pagamento, formas_pagamento())) {
        definir_mensagem('danger', 'Selecione uma forma de pagamento válida.');
        header('Location: checkout.php');
        exit;
    }

    // IDs em ordem crescente: evita travamentos (deadlock) entre compras simultâneas
    $ids = array_map('intval', array_keys($carrinho));
    sort($ids);

    try {
        $pdo->beginTransaction();

        $total = 0;
        $itens_para_gravar = [];

        foreach ($ids as $id) {
            $quantidade = (int) $carrinho[$id];

            // FOR UPDATE trava a linha do produto até o fim da transação,
            // então duas compras ao mesmo tempo não vendem o mesmo estoque.
            $stmt = $pdo->prepare('SELECT id, nome, preco, estoque, ativo FROM produtos WHERE id = ? FOR UPDATE');
            $stmt->execute([$id]);
            $produto = $stmt->fetch();

            if (!$produto || !$produto['ativo']) {
                throw new RuntimeException('Um dos produtos do carrinho não está mais disponível.');
            }
            if ($quantidade < 1 || $quantidade > $produto['estoque']) {
                throw new RuntimeException('Estoque insuficiente para "' . $produto['nome'] . '".');
            }

            // O preço vem sempre do banco, nunca do navegador
            $total += $produto['preco'] * $quantidade;
            $itens_para_gravar[] = [
                'produto_id' => $produto['id'],
                'quantidade' => $quantidade,
                'preco'      => $produto['preco'],
            ];
        }

        // 1. Registro do pedido
        $stmt = $pdo->prepare('INSERT INTO pedidos (usuario_id, forma_pagamento, valor_total) VALUES (?, ?, ?)');
        $stmt->execute([$usuario_id, $forma_pagamento, $total]);
        $novo_pedido_id = (int) $pdo->lastInsertId();

        // 2 e 3. Itens do pedido com o preço unitário do momento da compra
        $stmt_item = $pdo->prepare('INSERT INTO itens_pedido (pedido_id, produto_id, quantidade, preco_unitario)
                                    VALUES (?, ?, ?, ?)');
        // 4. Atualização do estoque (a condição impede estoque negativo)
        $stmt_estoque = $pdo->prepare('UPDATE produtos SET estoque = estoque - ? WHERE id = ? AND estoque >= ?');

        foreach ($itens_para_gravar as $item) {
            $stmt_item->execute([$novo_pedido_id, $item['produto_id'], $item['quantidade'], $item['preco']]);

            $stmt_estoque->execute([$item['quantidade'], $item['produto_id'], $item['quantidade']]);
            if ($stmt_estoque->rowCount() !== 1) {
                throw new RuntimeException('Não foi possível atualizar o estoque. Tente novamente.');
            }
        }

        $pdo->commit();

        // 5. Limpa o carrinho
        unset($_SESSION['carrinho']);

        // 6. Exibe a confirmação
        header('Location: checkout.php?pedido=' . $novo_pedido_id);
        exit;

    } catch (RuntimeException $ex) {
        // Erros de regra de negócio (mensagens escritas por nós)
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        definir_mensagem('danger', $ex->getMessage() . ' Revise seu carrinho.');
        header('Location: carrinho.php');
        exit;
    } catch (PDOException $ex) {
        // Erros de banco: mensagem genérica (sem detalhes técnicos)
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        definir_mensagem('danger', 'Não foi possível finalizar a compra. Nenhum valor foi cobrado. Tente novamente.');
        header('Location: carrinho.php');
        exit;
    }
}

// ---------------------------------------------------------
// Exibição do resumo (GET)
// ---------------------------------------------------------
$ids = array_map('intval', array_keys($carrinho));
$marcadores = implode(',', array_fill(0, count($ids), '?'));
$stmt = $pdo->prepare("SELECT id, nome, preco, estoque FROM produtos WHERE ativo = 1 AND id IN ($marcadores)");
$stmt->execute($ids);
$produtos_banco = [];
foreach ($stmt->fetchAll() as $p) {
    $produtos_banco[$p['id']] = $p;
}

$itens = [];
$total = 0;
foreach ($carrinho as $id => $qtd) {
    // Se algo mudou (produto sem estoque ou desativado), volta para o carrinho,
    // que sabe ajustar e avisar o cliente.
    if (!isset($produtos_banco[$id]) || $qtd > $produtos_banco[$id]['estoque'] || $produtos_banco[$id]['estoque'] < 1) {
        definir_mensagem('warning', 'Alguns itens mudaram de disponibilidade. Revise seu carrinho.');
        header('Location: carrinho.php');
        exit;
    }
    $p = $produtos_banco[$id];
    $p['quantidade'] = (int) $qtd;
    $p['subtotal']   = $p['preco'] * $qtd;
    $total += $p['subtotal'];
    $itens[] = $p;
}

$titulo_pagina = 'Finalizar compra';
include __DIR__ . '/includes/header.php';
?>

<div class="container mt-4">
    <h1 class="h3 mb-3" style="color: var(--azul-escuro);">Finalizar compra</h1>

    <?php exibir_mensagem(); ?>

    <div class="row g-4">
        <div class="col-12 col-lg-7">
            <h2 class="h5">Resumo do pedido</h2>
            <?php foreach ($itens as $item): ?>
                <div class="item-carrinho d-flex justify-content-between">
                    <div>
                        <div class="fw-semibold"><?= e($item['nome']) ?></div>
                        <div class="small text-muted">
                            <?= (int) $item['quantidade'] ?> x <?= formatar_preco($item['preco']) ?>
                        </div>
                    </div>
                    <div class="preco"><?= formatar_preco($item['subtotal']) ?></div>
                </div>
            <?php endforeach; ?>
            <a href="carrinho.php" class="small">Alterar carrinho</a>
        </div>

        <div class="col-12 col-lg-5">
            <form method="post" action="checkout.php" class="resumo-carrinho">
                <?= csrf_campo() ?>
                <h2 class="h5">Forma de pagamento</h2>

                <?php $primeira = true; foreach (formas_pagamento() as $codigo => $nome): ?>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="radio" name="forma_pagamento"
                               id="pg_<?= e($codigo) ?>" value="<?= e($codigo) ?>" <?= $primeira ? 'checked' : '' ?> required>
                        <label class="form-check-label" for="pg_<?= e($codigo) ?>"><?= e($nome) ?></label>
                    </div>
                <?php $primeira = false; endforeach; ?>

                <p class="small text-muted mt-3">
                    Simulação acadêmica: o pagamento é confirmado imediatamente, sem cobrança real.
                </p>

                <hr>
                <div class="d-flex justify-content-between fw-bold fs-5 mb-3">
                    <span>Total</span>
                    <span class="preco"><?= formatar_preco($total) ?></span>
                </div>
                <button type="submit" class="btn btn-tec w-100">Confirmar compra</button>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
