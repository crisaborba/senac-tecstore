<?php
require_once __DIR__ . '/../includes/auth.php';
exigir_admin('../');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/funcoes.php';
require_once __DIR__ . '/../includes/admin_funcoes.php';

$base_url = '../';

$categorias = $pdo->query('SELECT id, nome FROM categorias ORDER BY nome')->fetchAll();

$erros = [];
$dados = [
    'nome' => '', 'descricao' => '', 'categoria_id' => 0,
    'preco' => '', 'estoque' => 0, 'destaque' => 0, 'ativo' => 1,
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    exigir_csrf();

    list($dados, $erros) = validar_dados_produto($pdo, $_POST);

    // Só processa a imagem se os demais dados estiverem corretos
    $imagem = null;
    if (empty($erros)) {
        $imagem = processar_upload_imagem($_FILES['imagem'] ?? null, $erros);
    }

    if (empty($erros)) {
        try {
            $stmt = $pdo->prepare('INSERT INTO produtos (categoria_id, nome, descricao, preco, estoque, imagem, destaque, ativo)
                                   VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
            $stmt->execute([
                $dados['categoria_id'], $dados['nome'], $dados['descricao'],
                $dados['preco'], $dados['estoque'], $imagem, $dados['destaque'], $dados['ativo'],
            ]);

            definir_mensagem('success', 'Produto cadastrado com sucesso.');
            header('Location: produtos.php');
            exit;
        } catch (PDOException $ex) {
            apagar_imagem($imagem);
            $erros[] = 'Não foi possível salvar o produto. Tente novamente.';
        }
    } else {
        // Se houve erro depois do upload, não deixa arquivo sobrando
        apagar_imagem($imagem);
    }
}

$titulo_pagina = 'Cadastrar produto';
$texto_botao = 'Cadastrar produto';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/admin_menu.php';
?>

<div class="container mt-3">
    <div class="row">
        <div class="col-12 col-lg-8">
            <h1 class="h3 mb-3" style="color: var(--azul-escuro);">Cadastrar produto</h1>
            <?php include __DIR__ . '/_form_produto.php'; ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
