<?php
/*
 * Funções usadas pelo painel administrativo (validação de produtos e upload de imagem).
 */

// Converte "1.499,90" ou "1499.90" em número. Retorna false se for inválido.
function converter_preco($texto)
{
    $texto = trim($texto);
    $texto = str_replace(' ', '', $texto);

    if (strpos($texto, ',') !== false) {
        // Formato brasileiro: remove pontos de milhar e troca a vírgula por ponto
        $texto = str_replace('.', '', $texto);
        $texto = str_replace(',', '.', $texto);
    }

    if (!preg_match('/^\d+(\.\d{1,2})?$/', $texto)) {
        return false;
    }

    $valor = (float) $texto;
    if ($valor <= 0 || $valor > 99999999.99) {
        return false;
    }
    return $valor;
}

// Valida os dados do formulário de produto.
// Retorna [$dados, $erros]. $dados já vem pronto para ser gravado.
function validar_dados_produto($pdo, $post)
{
    $erros = [];

    $nome      = trim($post['nome'] ?? '');
    $descricao = trim($post['descricao'] ?? '');
    $categoria = filter_var($post['categoria_id'] ?? '', FILTER_VALIDATE_INT);
    $preco     = converter_preco($post['preco'] ?? '');
    $estoque   = filter_var($post['estoque'] ?? '', FILTER_VALIDATE_INT);

    if (mb_strlen($nome) < 3 || mb_strlen($nome) > 150) {
        $erros[] = 'O nome deve ter entre 3 e 150 caracteres.';
    }
    if (mb_strlen($descricao) > 2000) {
        $erros[] = 'A descrição deve ter no máximo 2000 caracteres.';
    }

    if ($categoria === false || $categoria < 1) {
        $erros[] = 'Selecione uma categoria.';
    } else {
        $stmt = $pdo->prepare('SELECT id FROM categorias WHERE id = ?');
        $stmt->execute([$categoria]);
        if (!$stmt->fetch()) {
            $erros[] = 'Categoria inválida.';
        }
    }

    if ($preco === false) {
        $erros[] = 'Informe um preço válido maior que zero (ex.: 1499,90).';
    }

    if ($estoque === false || $estoque < 0 || $estoque > 1000000) {
        $erros[] = 'O estoque deve ser um número inteiro entre 0 e 1.000.000.';
    }

    $dados = [
        'nome'         => $nome,
        'descricao'    => $descricao,
        'categoria_id' => ($categoria === false) ? 0 : $categoria,
        'preco'        => ($preco === false) ? '' : $preco,
        'estoque'      => ($estoque === false) ? 0 : $estoque,
        'destaque'     => isset($post['destaque']) ? 1 : 0,
        'ativo'        => isset($post['ativo']) ? 1 : 0,
    ];

    return [$dados, $erros];
}

// Valida e salva a imagem enviada.
// Retorna o nome do arquivo salvo, ou null se nenhum arquivo foi enviado ou se houve erro
// (os erros são acrescentados em $erros).
function processar_upload_imagem($arquivo, &$erros)
{
    if (!isset($arquivo) || $arquivo['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if ($arquivo['error'] !== UPLOAD_ERR_OK) {
        $erros[] = 'Não foi possível enviar a imagem (erro ' . (int) $arquivo['error'] . '). Verifique o tamanho do arquivo.';
        return null;
    }

    if ($arquivo['size'] > 2 * 1024 * 1024) {
        $erros[] = 'A imagem deve ter no máximo 2 MB.';
        return null;
    }

    if (!is_uploaded_file($arquivo['tmp_name'])) {
        $erros[] = 'Arquivo de imagem inválido.';
        return null;
    }

    // getimagesize lê o conteúdo real do arquivo: não aceita um texto/PHP renomeado para .jpg
    $info = @getimagesize($arquivo['tmp_name']);
    $tipos_permitidos = [
        IMAGETYPE_JPEG => 'jpg',
        IMAGETYPE_PNG  => 'png',
        IMAGETYPE_WEBP => 'webp',
    ];

    if ($info === false || !isset($tipos_permitidos[$info[2]])) {
        $erros[] = 'A imagem deve ser um arquivo JPG, PNG ou WEBP válido.';
        return null;
    }

    if ($info[0] > 6000 || $info[1] > 6000) {
        $erros[] = 'A imagem é grande demais (máximo 6000 x 6000 pixels).';
        return null;
    }

    // Nome aleatório e extensão definida por nós (o nome original é ignorado)
    $nome_arquivo = bin2hex(random_bytes(16)) . '.' . $tipos_permitidos[$info[2]];
    $destino = __DIR__ . '/../uploads/' . $nome_arquivo;

    if (!move_uploaded_file($arquivo['tmp_name'], $destino)) {
        $erros[] = 'Não foi possível salvar a imagem no servidor.';
        return null;
    }

    return $nome_arquivo;
}

// Apaga uma imagem da pasta uploads (se existir)
function apagar_imagem($nome_arquivo)
{
    if (!empty($nome_arquivo) && preg_match('/^[a-f0-9]{32}\.(jpg|png|webp)$/', $nome_arquivo)) {
        $caminho = __DIR__ . '/../uploads/' . $nome_arquivo;
        if (is_file($caminho)) {
            unlink($caminho);
        }
    }
}
