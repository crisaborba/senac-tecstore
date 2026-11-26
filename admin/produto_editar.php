<?php
require_once __DIR__ . '/../includes/auth.php';
exigir_admin('../');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/funcoes.php';
require_once __DIR__ . '/../includes/admin_funcoes.php';

$base_url = '../';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if ($id === false || $id === null || $id < 1) {
    definir_mensagem('danger', 'Produto inválido.');
    header('Location: produtos.php');
    exit;
}

$stmt = $pdo->prepare('SELECT * FROM produtos WHERE id = ?');
$stmt->execute([$id]);
$produto = $stmt->fetch();

if (!$produto) {
    definir_mensagem('danger', 'Produto não encontrado.');
    header('Location: produtos.php');
    exit;
}

$categorias = $pdo->query('SELECT id, nome FROM categorias ORDER BY nome')->fetchAll();
$imagem_atual = $produto['imagem'];
$erros = [];

$dados = [
    'nome'         => $produto['nome'],
    'descricao'    => $produto['descricao'] ?? '',
    'categoria_id' => $produto['categoria_id'],
    'preco'        => number_format($produto['preco'], 2, ',', ''),
    'estoque'      => $produto['estoque'],
    'destaque'     => $produto['destaque'],
    'ativo'        => $produto['ativo'],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    exigir_csrf();

    list($dados, $erros) = validar_dados_produto($pdo, $_POST);

    $nova_imagem = null;
    if (empty($erros)) {
        $nova_imagem = processar_upload_imagem($_FILES['imagem'] ?? null, $erros);
    }

    if (empty($erros)) {
        // Se não enviou imagem nova, mantém a atual
        $imagem_final = $nova_imagem ?? $produto['imagem'];

        try {
            $stmt = $pdo->prepare('UPDATE produtos
                                   SET categoria_id = ?, nome = ?, descricao = ?, preco = ?,
                                       estoque = ?, imagem = ?, destaque = ?, ativo = ?
                                   WHERE id = ?');
            $stmt->execute([
                $dados['categoria_id'], $dados['nome'], $dados['descricao'], $dados['preco'],
                $dados['estoque'], $imagem_final, $dados['destaque'], $dados['ativo'], $id,
            ]);

            // Imagem antiga só é apagada depois que o banco foi atualizado
            if ($nova_imagem !== null) {
                apagar_imagem($produto['imagem']);
            }

            definir_mensagem('success', 'Produto atualizado com sucesso.');
            header('Location: produtos.php');
            exit;
        } catch (PDOException $ex) {
            apagar_imagem($nova_imagem);
            $erros[] = 'Não foi possível salvar as alterações. Tente novamente.';
        }
    } else {
        apagar_imagem($nova_imagem);
    }
}

$titulo_pagina = 'Editar produto';
$texto_botao = 'Salvar alterações';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/admin_menu.php';
?>

<div class="container mt-3">
    <div class="row">
        <div class="col-12 col-lg-8">
            <h1 class="h3 mb-3" style="color: var(--azul-escuro);">Editar produto #<?= (int) $produto['id'] ?></h1>
            <?php include __DIR__ . '/_form_produto.php'; ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
