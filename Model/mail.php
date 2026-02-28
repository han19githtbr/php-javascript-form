<?php
/**
 * mail.php — Controller de envio de e-mail via Resend API
 *
 * MELHORIAS APLICADAS:
 * 1. [SEGURANÇA] Validação e sanitização de todos os inputs do POST antes de qualquer uso.
 * 2. [SEGURANÇA] Limite de tamanho nos campos para evitar payloads maliciosos.
 * 3. [SEGURANÇA] Validação de formato de e-mail com filter_var (padrão PHP nativo).
 * 4. [ORGANIZAÇÃO] Responsabilidades separadas em funções com nomes descritivos.
 * 5. [ORGANIZAÇÃO] Constantes nomeadas substituem "magic strings" espalhadas no código.
 * 6. [ROBUSTEZ] Tratamento de erro do cURL separado do tratamento de resposta HTTP.
 * 7. [PADRÃO] Respostas JSON padronizadas com chave "sucesso" (boolean) em vez de "error".
 * 8. [CLAREZA] Comentários objetivos apenas onde o código não é autoexplicativo.
 */

declare(strict_types=1);

header('Content-Type: application/json; charset=UTF-8');
ini_set('display_errors', '0');
error_reporting(0);

require __DIR__ . '/../vendor/autoload.php';

// ── Constantes ────────────────────────────────────────────────────
// MELHORIA 5: magic strings substituídas por constantes nomeadas
const CAMPO_TAMANHO_MAX_NOME     = 100;
const CAMPO_TAMANHO_MAX_EMAIL    = 150;
const CAMPO_TAMANHO_MAX_MENSAGEM = 2000;
const RESEND_API_URL             = 'https://api.resend.com/emails';
const RESEND_HTTP_OK             = 200;

// ── Carrega variáveis de ambiente ─────────────────────────────────
carregarEnv();

// ── Recebe e valida os dados do formulário ────────────────────────
// MELHORIA 1 e 2: toda entrada externa é validada e tem tamanho limitado
$campos = receberEValidarPost();
if ($campos['erro']) {
    responder(false, $campos['mensagem']);
}

[
    'nomeUsuario'     => $nomeUsuario,
    'mensagemUsuario' => $mensagemUsuario,
    'emailUsuario'    => $emailUsuario,
    'emailDestino'    => $emailDestino,
] = $campos;

// ── Configurações do Resend vindas do .env ────────────────────────
$resendKey      = $_ENV['MAIL_PASSWORD']  ?? '';
$remetenteEmail = $_ENV['MAIL_FROM']      ?? 'onboarding@resend.dev';
$remetenteNome  = $_ENV['MAIL_FROM_NAME'] ?? 'Sistema de Contato';

if (empty($resendKey)) {
    responder(false, 'Configuração de e-mail ausente no servidor.');
}

// ── Envia o e-mail ────────────────────────────────────────────────
$resultadoEnvio = enviarEmailResend(
    $resendKey, $remetenteEmail, $remetenteNome,
    $emailDestino, $emailUsuario, $nomeUsuario, $mensagemUsuario
);

if (!$resultadoEnvio['sucesso']) {
    responder(false, $resultadoEnvio['mensagem']);
}

// ── Registra no banco ─────────────────────────────────────────────
$cacheInfo = registrarNoBanco($nomeUsuario, $emailUsuario, $emailDestino);

responder(true, 'Mensagem enviada com sucesso!', $cacheInfo);


// ══════════════════════════════════════════════════════════════════
// FUNÇÕES
// ══════════════════════════════════════════════════════════════════

/**
 * Carrega o arquivo .env quando disponível (ambiente local).
 */
function carregarEnv(): void
{
    $envFile = __DIR__ . '/../.env';
    if (file_exists($envFile)) {
        $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
        $dotenv->load();
    }

    // Fallback: alguns hosts populam getenv() mas não $_ENV
    foreach (['MAIL_PASSWORD', 'MAIL_FROM', 'MAIL_FROM_NAME'] as $var) {
        if (empty($_ENV[$var]) && getenv($var) !== false) {
            $_ENV[$var] = getenv($var);
        }
    }
}

/**
 * Lê, sanitiza e valida todos os campos do POST.
 *
 * MELHORIA 1: filter_var com FILTER_VALIDATE_EMAIL é o padrão PHP
 * para validar e-mails — mais confiável que regex manual.
 * MELHORIA 2: mb_substr garante que o campo seja truncado
 * corretamente mesmo em textos com caracteres multibyte (UTF-8).
 *
 * @return array{erro: bool, mensagem: string, ...campos}
 */
function receberEValidarPost(): array
{
    // MELHORIA 1: strip_tags remove possíveis injeções de HTML/JS nos campos de texto
    $nome      = strip_tags(trim($_POST['nome']     ?? ''));
    $mensagem  = strip_tags(trim($_POST['mensagem'] ?? ''));
    $correio   = trim($_POST['correio'] ?? '');
    $destino   = trim($_POST['email']   ?? '');

    if (empty($nome) || empty($mensagem) || empty($correio) || empty($destino)) {
        return ['erro' => true, 'mensagem' => 'Por favor, preencha todos os campos.'];
    }

    // MELHORIA 3: validação nativa de e-mail
    if (!filter_var($correio, FILTER_VALIDATE_EMAIL)) {
        return ['erro' => true, 'mensagem' => 'E-mail do remetente inválido.'];
    }
    if (!filter_var($destino, FILTER_VALIDATE_EMAIL)) {
        return ['erro' => true, 'mensagem' => 'E-mail do destinatário inválido.'];
    }

    // MELHORIA 2: limita o tamanho dos campos
    $nome     = mb_substr($nome,     0, CAMPO_TAMANHO_MAX_NOME);
    $correio  = mb_substr($correio,  0, CAMPO_TAMANHO_MAX_EMAIL);
    $destino  = mb_substr($destino,  0, CAMPO_TAMANHO_MAX_EMAIL);
    $mensagem = mb_substr($mensagem, 0, CAMPO_TAMANHO_MAX_MENSAGEM);

    return [
        'erro'            => false,
        'mensagem'        => '',
        'nomeUsuario'     => $nome,
        'mensagemUsuario' => $mensagem,
        'emailUsuario'    => $correio,
        'emailDestino'    => $destino,
    ];
}

/**
 * Monta o corpo HTML do e-mail.
 * htmlspecialchars() em todo conteúdo dinâmico — prevenção de XSS.
 */
function montarCorpoHtml(string $nome, string $mensagem, string $emailRemetente): string
{
    // MELHORIA 1 (SEGURANÇA): htmlspecialchars previne XSS no corpo do e-mail
    $nomeEsc     = htmlspecialchars($nome,           ENT_QUOTES, 'UTF-8');
    $mensagemEsc = nl2br(htmlspecialchars($mensagem, ENT_QUOTES, 'UTF-8'));
    $emailEsc    = htmlspecialchars($emailRemetente, ENT_QUOTES, 'UTF-8');

    return "
        <div style='font-family:sans-serif;max-width:600px;margin:0 auto;background:#343a40;padding:20px;border-radius:8px;'>
            <div style='background:#cce5ff;border:1px solid #b8daff;border-radius:4px;padding:12px 20px;margin-bottom:20px;font-size:1.2em;'>
                <strong>Mensagem de:</strong> {$nomeEsc}
            </div>
            <div style='color:#eee;font-size:18px;margin-bottom:30px;'>
                {$mensagemEsc}
            </div>
            <div style='background:#48494a;color:#ddd;text-align:center;padding:10px;font-size:14px;'>
                Pode responder para: <span style='text-decoration:underline;'>{$emailEsc}</span>
            </div>
        </div>
    ";
}

/**
 * Realiza a chamada à API do Resend e retorna o resultado.
 *
 * MELHORIA 6: o erro de conexão cURL (problema de rede/DNS) é tratado
 * separadamente do erro HTTP (resposta inesperada do servidor).
 *
 * @return array{sucesso: bool, mensagem: string}
 */
function enviarEmailResend(
    string $apiKey,
    string $remetenteEmail,
    string $remetenteNome,
    string $destino,
    string $replyTo,
    string $nome,
    string $mensagem
): array {
    $payload = [
        'from'     => "{$remetenteNome} <{$remetenteEmail}>",
        'to'       => [$destino],
        'reply_to' => $replyTo,
        'subject'  => 'Mensagem de Contato - ' . $nome,
        'text'     => "De: {$nome}\n\n{$mensagem}\n\nResponda para: {$replyTo}",
        'html'     => montarCorpoHtml($nome, $mensagem, $replyTo),
    ];

    $ch = curl_init(RESEND_API_URL);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($payload),
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . $apiKey,
            'Content-Type: application/json',
        ],
        CURLOPT_TIMEOUT        => 30,
    ]);

    $resposta   = curl_exec($ch);
    $httpStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErro   = curl_error($ch);
    curl_close($ch);

    // MELHORIA 6: falha de conexão vs falha de resposta tratadas separadamente
    if ($curlErro) {
        error_log("[mail.php] Erro cURL: {$curlErro}");
        return ['sucesso' => false, 'mensagem' => 'Falha na conexão com o serviço de e-mail.'];
    }

    if ($httpStatus !== RESEND_HTTP_OK) {
        $decoded = json_decode($resposta, true);
        $erroMsg = $decoded['message'] ?? $decoded['name'] ?? "HTTP {$httpStatus}";
        error_log("[mail.php] Resend HTTP {$httpStatus}: {$resposta}");
        return ['sucesso' => false, 'mensagem' => 'Erro no serviço de e-mail: ' . $erroMsg];
    }

    return ['sucesso' => true, 'mensagem' => ''];
}

/**
 * Registra o remetente e o log de envio no banco de dados.
 * Retorna uma string de status para exibição no front-end.
 */
function registrarNoBanco(string $nome, string $emailRemetente, string $emailDestino): string
{
    require_once __DIR__ . '/../Model/CacheConnection.php';

    try {
        $cache = new CacheConnection();
        $pdo   = $cache->getPDO();
        $tPac  = $cache->tabela('Saude.Paciente');
        $tLog  = $cache->tabela('Saude.LogMensagem');

        $stmt = $pdo->prepare("SELECT PacienteId FROM {$tPac} WHERE Email = :email");
        $stmt->execute([':email' => $emailRemetente]);
        $paciente = $stmt->fetch();

        if ($paciente) {
            $pacienteId = $paciente['PacienteId'];
            $info       = '✅ Paciente já registrado (ID: ' . $pacienteId . ')';
        } else {
            $insert = $pdo->prepare("INSERT INTO {$tPac} (Nome, Email) VALUES (:nome, :email)");
            $insert->execute([':nome' => $nome, ':email' => $emailRemetente]);
            $pacienteId = (int) $pdo->lastInsertId();
            $info       = '✅ Paciente cadastrado (ID: ' . $pacienteId . ')';
        }

        $log = $pdo->prepare("INSERT INTO {$tLog} (PacienteId, Destinatario) VALUES (:id, :dest)");
        $log->execute([':id' => $pacienteId, ':dest' => $emailDestino]);

        return $info;

    } catch (Exception $e) {
        error_log("[mail.php] Erro banco: " . $e->getMessage());
        return '⚠️ Email enviado, mas não foi possível registrar no banco.';
    }
}

/**
 * Emite a resposta JSON padronizada e encerra a execução.
 *
 * MELHORIA 7: usa "sucesso" (boolean positivo) em vez de "error" (boolean negativo),
 * tornando o código do front-end mais legível: if (data.sucesso) { ... }
 */
function responder(bool $sucesso, string $mensagem, string $cacheInfo = ''): never
{
    $payload = ['sucesso' => $sucesso, 'mensagem' => $mensagem];
    if ($cacheInfo !== '') {
        $payload['cache_info'] = $cacheInfo;
    }
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

