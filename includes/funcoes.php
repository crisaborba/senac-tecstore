<?php
/*
 * Funções de apoio usadas em várias páginas.
 */

// Escreve texto de forma segura no HTML (evita execução de HTML/JS indesejado)
function e($texto)
{
    return htmlspecialchars((string) $texto, ENT_QUOTES, 'UTF-8');
}

// Formata um número como preço: 1499.9 -> "R$ 1.499,90"
function formatar_preco($valor)
{
    return 'R$ ' . number_format((float) $valor, 2, ',', '.');
}

// Retorna o caminho da imagem do produto (ou a imagem padrão, se não houver)
function caminho_imagem($imagem, $base_url = '')
{
    if (!empty($imagem)) {
        return $base_url . 'uploads/' . rawurlencode($imagem);
    }
    return $base_url . 'assets/images/sem-imagem.svg';
}

// Texto e cor da disponibilidade conforme o estoque
function disponibilidade($estoque)
{
    if ($estoque <= 0) {
        return ['texto' => 'Indisponível', 'classe' => 'bg-secondary'];
    }
    if ($estoque <= 5) {
        return ['texto' => 'Últimas unidades', 'classe' => 'bg-warning text-dark'];
    }
    return ['texto' => 'Em estoque', 'classe' => 'bg-success'];
}

// ---------------------------------------------------------
// Mensagens temporárias (aparecem uma vez após um redirecionamento)
// ---------------------------------------------------------
function definir_mensagem($tipo, $texto)
{
    // $tipo: success, danger, warning ou info (classes do Bootstrap)
    $_SESSION['mensagem'] = ['tipo' => $tipo, 'texto' => $texto];
}

function exibir_mensagem()
{
    if (empty($_SESSION['mensagem'])) {
        return;
    }
    $m = $_SESSION['mensagem'];
    unset($_SESSION['mensagem']);
    echo '<div class="alert alert-' . e($m['tipo']) . '" role="alert">' . e($m['texto']) . '</div>';
}

// ---------------------------------------------------------
// Pedidos
// ---------------------------------------------------------
function formas_pagamento()
{
    return [
        'pix'    => 'PIX',
        'boleto' => 'Boleto',
        'cartao' => 'Cartão de crédito',
    ];
}

function nome_forma_pagamento($codigo)
{
    $formas = formas_pagamento();
    return $formas[$codigo] ?? $codigo;
}

// "2026-11-26 14:30:00" -> "26/11/2026 14:30"
function formatar_data($data)
{
    return date('d/m/Y H:i', strtotime($data));
}
