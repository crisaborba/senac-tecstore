<?php
// Arquivo parcial: só funciona incluído por produto_cadastro.php ou produto_editar.php
if (!function_exists('e')) {
    http_response_code(404);
    exit;
}
/*
 * Formulário de produto (usado no cadastro e na edição).
 * Variáveis esperadas: $dados, $erros, $categorias, $texto_botao e, na edição, $imagem_atual.
 */
?>
<?php if (!empty($erros)): ?>
    <div class="alert alert-danger">
        <ul class="mb-0">
            <?php foreach ($erros as $erro): ?>
                <li><?= e($erro) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<form method="post" enctype="multipart/form-data" novalidate>
    <?= csrf_campo() ?>

    <div class="mb-3">
        <label for="nome" class="form-label">Nome</label>
        <input type="text" id="nome" name="nome" class="form-control" maxlength="150"
               value="<?= e($dados['nome']) ?>" required>
    </div>

    <div class="mb-3">
        <label for="descricao" class="form-label">Descrição</label>
        <textarea id="descricao" name="descricao" class="form-control" rows="4" maxlength="2000"><?= e($dados['descricao']) ?></textarea>
    </div>

    <div class="mb-3">
        <label for="categoria_id" class="form-label">Categoria</label>
        <select id="categoria_id" name="categoria_id" class="form-select" required>
            <option value="">Selecione...</option>
            <?php foreach ($categorias as $c): ?>
                <option value="<?= (int) $c['id'] ?>" <?= ((int) $dados['categoria_id'] === (int) $c['id']) ? 'selected' : '' ?>>
                    <?= e($c['nome']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="row">
        <div class="col-6 mb-3">
            <label for="preco" class="form-label">Preço (R$)</label>
            <input type="text" id="preco" name="preco" class="form-control" inputmode="decimal"
                   placeholder="1499,90" value="<?= e($dados['preco']) ?>" required>
        </div>
        <div class="col-6 mb-3">
            <label for="estoque" class="form-label">Estoque</label>
            <input type="number" id="estoque" name="estoque" class="form-control" min="0" max="1000000"
                   value="<?= e($dados['estoque']) ?>" required>
        </div>
    </div>

    <div class="mb-3">
        <label for="imagem" class="form-label">Imagem (JPG, PNG ou WEBP, até 2 MB)</label>
        <?php if (!empty($imagem_atual)): ?>
            <div class="mb-2">
                <img src="../<?= e(ltrim(caminho_imagem($imagem_atual, ''), '/')) ?>" alt="Imagem atual" style="max-height: 100px;">
                <div class="form-text">Imagem atual. Envie outra apenas se quiser substituí-la.</div>
            </div>
        <?php endif; ?>
        <input type="file" id="imagem" name="imagem" class="form-control" accept="image/jpeg,image/png,image/webp">
    </div>

    <div class="form-check mb-2">
        <input type="checkbox" class="form-check-input" id="destaque" name="destaque" value="1" <?= $dados['destaque'] ? 'checked' : '' ?>>
        <label class="form-check-label" for="destaque">Mostrar em destaque na página inicial</label>
    </div>
    <div class="form-check mb-3">
        <input type="checkbox" class="form-check-input" id="ativo" name="ativo" value="1" <?= $dados['ativo'] ? 'checked' : '' ?>>
        <label class="form-check-label" for="ativo">Produto ativo (visível na loja)</label>
    </div>

    <button type="submit" class="btn btn-tec"><?= e($texto_botao) ?></button>
    <a href="produtos.php" class="btn btn-outline-secondary">Cancelar</a>
</form>
