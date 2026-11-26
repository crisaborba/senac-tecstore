<?php
/*
 * Conexão com o banco de dados usando PDO.
 * As credenciais ficam no arquivo .env (fora do código).
 * Uso nas páginas:  require_once __DIR__ . '/config/database.php';
 * e depois usar a variável $pdo.
 */

// Lê o arquivo .env e coloca as variáveis em um array
function carregar_env($caminho)
{
    $variaveis = [];

    if (!is_readable($caminho)) {
        return $variaveis;
    }

    $linhas = file($caminho, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($linhas as $linha) {
        $linha = trim($linha);

        // Ignora comentários e linhas sem "="
        if ($linha === '' || $linha[0] === '#' || strpos($linha, '=') === false) {
            continue;
        }

        list($chave, $valor) = explode('=', $linha, 2);
        $variaveis[trim($chave)] = trim($valor);
    }

    return $variaveis;
}

$env = carregar_env(__DIR__ . '/../.env');

$db_host = $env['DB_HOST'] ?? '';
$db_name = $env['DB_NAME'] ?? '';
$db_user = $env['DB_USER'] ?? '';
$db_pass = $env['DB_PASS'] ?? '';

if ($db_host === '' || $db_name === '' || $db_user === '') {
    die('Configuração do banco incompleta. Verifique o arquivo .env.');
}

try {
    $pdo = new PDO(
        "mysql:host=$db_host;dbname=$db_name;charset=utf8mb4",
        $db_user,
        $db_pass,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,  // erros viram exceções
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,        // resultados como array associativo
            PDO::ATTR_EMULATE_PREPARES   => false,                   // prepared statements reais
        ]
    );
} catch (PDOException $e) {
    // Não exibe detalhes técnicos (podem revelar credenciais)
    die('Erro ao conectar ao banco de dados. Verifique o arquivo .env e se o MySQL está ligado.');
}

// Qualquer erro de banco não tratado nas páginas vai para o log do Apache
// e o visitante vê apenas uma mensagem genérica (sem SQL, caminhos ou senhas).
set_exception_handler(function ($ex) {
    error_log('TecStore: ' . get_class($ex) . ': ' . $ex->getMessage() . ' em ' . $ex->getFile() . ':' . $ex->getLine());
    if (!headers_sent()) {
        http_response_code(500);
    }
    echo 'Ocorreu um erro inesperado. Tente novamente mais tarde.';
});
