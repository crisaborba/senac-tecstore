<?php
/*
 * Autenticação e controle de acesso.
 * Este arquivo é incluído por todas as páginas (via header.php ou diretamente).
 * Dados do usuário logado ficam em $_SESSION:
 *   usuario_id, usuario_nome, usuario_tipo ('cliente' ou 'admin')
 */
// Cabeçalhos de segurança básicos
header('X-Frame-Options: SAMEORIGIN');               // impede a página de ser aberta dentro de iframes de outros sites
header('X-Content-Type-Options: nosniff');           // o navegador não "adivinha" o tipo dos arquivos
header('Referrer-Policy: strict-origin-when-cross-origin');

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.use_strict_mode', '1');          // recusa IDs de sessão que o servidor não criou
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'httponly' => true,                           // JavaScript não lê o cookie de sessão
        'samesite' => 'Lax',                          // cookie não vai em requisições vindas de outros sites
        'secure'   => !empty($_SERVER['HTTPS']),
    ]);
    session_start();
}

// Retorna true se existe um usuário autenticado
function usuario_logado()
{
    return !empty($_SESSION['usuario_id']);
}

// Retorna true se o usuário autenticado é administrador
function eh_admin()
{
    return usuario_logado() && ($_SESSION['usuario_tipo'] ?? '') === 'admin';
}

// Exige login. Se não estiver logado, envia para a tela de login
// e depois volta para a página que o usuário queria ($destino).
// $base_url: '' nas páginas da raiz e '../' nas páginas de /admin.
function exigir_login($destino = '', $base_url = '')
{
    if (!usuario_logado()) {
        $url = $base_url . 'login.php';
        if ($destino !== '') {
            $url .= '?redirecionar=' . urlencode($destino);
        }
        header('Location: ' . $url);
        exit;
    }
}

// Exige perfil de administrador (usado nas páginas de /admin).
function exigir_admin($base_url = '../')
{
    exigir_login('', $base_url);

    if (!eh_admin()) {
        http_response_code(403);
        echo '<!DOCTYPE html><html lang="pt-BR"><head><meta charset="UTF-8">'
           . '<meta name="viewport" content="width=device-width, initial-scale=1">'
           . '<title>Acesso negado</title>'
           . '<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"></head>'
           . '<body class="container mt-5"><div class="alert alert-danger">'
           . 'Acesso negado. Esta área é restrita a administradores.</div>'
           . '<a href="' . htmlspecialchars($base_url) . 'index.php" class="btn btn-primary">Voltar à loja</a>'
           . '</body></html>';
        exit;
    }
}

// Páginas para as quais é permitido voltar depois do login.
// Lista fixa: evita que alguém use o parâmetro para redirecionar para outro site.
function destino_pos_login($pedido)
{
    $permitidos = ['index.php', 'carrinho.php', 'checkout.php', 'pedidos.php', 'produtos.php'];
    return in_array($pedido, $permitidos, true) ? $pedido : 'index.php';
}

// ---------------------------------------------------------
// Proteção CSRF (usada nos formulários do painel administrativo)
// Impede que outro site envie formulários em nome do administrador logado.
// ---------------------------------------------------------
function csrf_token()
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Campo oculto para colocar dentro dos formulários POST
function csrf_campo()
{
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token()) . '">';
}

// Interrompe a execução se o token enviado não for o da sessão
function exigir_csrf()
{
    $enviado  = $_POST['csrf_token'] ?? '';
    $esperado = $_SESSION['csrf_token'] ?? '';

    // O token da sessão precisa existir (não vazio) e ser igual ao enviado
    if ($esperado === '' || !is_string($enviado) || !hash_equals($esperado, $enviado)) {
        http_response_code(403);
        die('Requisição inválida (token de segurança incorreto). Volte e tente novamente.');
    }
}
