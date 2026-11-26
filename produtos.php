<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/funcoes.php';

$titulo_pagina = 'Produtos';
$base_url = '';

// Filtros recebidos pela URL (GET)
$busca        = trim($_GET['busca'] ?? '');
$categoria_id = filter_input(INPUT_GET, 'categoria', FILTER_VALIDATE_INT);
if ($categoria_id !== false && $categoria_id !== null && $categoria_id < 1) {
    $categoria_id = null;
}
if ($categoria_id === false) {
    $categoria_id = null;
}
if (mb_strlen($busca) > 100) {
    $busca = mb_substr($busca, 0, 100);
}

// Lista de categorias para o filtro
$categorias = $pdo->query('SELECT id, nome FROM categorias ORDER BY nome')->fetchAll();

// Monta a consulta: as condições são fixas e os valores entram por parâmetros (?)
$sql = 'SELECT p.id, p.nome, p.preco, p.estoque, p.imagem
        FROM produtos p
        WHERE p.ativo = 1';
$parametros = [];

if ($categoria_id !== null) {
    $sql .= ' AND p.categoria_id = ?';
    $parametros[] = $categoria_id;
}

if ($busca !== '') {
    $sql .= ' AND p.nome LIKE ?';
    // Escapa % e _ para que sejam tratados como texto comum na busca
    $busca_like = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $busca);
    $parametros[] = '%' . $busca_like . '%';
}

$sql .= ' ORDER BY p.nome';

$stmt = $pdo->prepare($sql);
$stmt->execute($parametros);
$produtos = $stmt->fetchAll();

include __DIR__ . '/includes/header.php';
?>

<div class="container mt-4">
    <h1 class="h3 mb-3" style="color: var(--azul-escuro);">Produtos</h1>

    <form method="get" action="produtos.php" class="row g-2 mb-4">
        <div class="col-12 col-md-5">
            <input type="text" name="busca" class="form-control" placeholder="Nome do produto"
                   value="<?= e($busca) ?>" maxlength="100">
        </div>
        <div class="col-8 col-md-4">
            <select name="categoria" class="form-select">
                <option value="">Todas as categorias</option>
                <?php foreach ($categorias as $c): ?>
                    <option value="<?= (int) $c['id'] ?>" <?= ($categoria_id === (int) $c['id']) ? 'selected' : '' ?>>
                        <?= e($c['nome']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-4 col-md-3 d-flex gap-2">
            <button type="submit" class="btn btn-tec flex-grow-1">Filtrar</button>
            <a href="produtos.php" class="btn btn-outline-secondary">Limpar</a>
        </div>
    </form>

    <p class="text-muted small"><?= count($produtos) ?> produto(s) encontrado(s)</p>

    <?php if (empty($produtos)): ?>
        <div class="alert alert-light border">
            Nenhum produto encontrado. Tente outro nome ou limpe os filtros.
        </div>
    <?php else: ?>
        <div class="row g-3">
            <?php foreach ($produtos as $p): ?>
                <?php include __DIR__ . '/includes/produto_card.php'; ?>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
