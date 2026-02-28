<?php
/**
 * mail.php  (versão atualizada com integração ao Caché)
 * 
 * Após enviar o e-mail com sucesso, este arquivo:
 *   1. Verifica se o remetente já existe no banco Caché
 *   2. Se não existir, cadastra o paciente automaticamente
 *   3. Registra um log da mensagem enviada na tabela Saude.LogMensagem
 * 
 * ── QUERIES EQUIVALENTES NO CACHÉ REAL ───────────────────────────
 * 
 *   -- Verificar se paciente existe:
 *   SELECT PacienteId FROM Saude.Paciente WHERE Email = :email
 * 
 *   -- Cadastrar novo paciente:
 *   INSERT INTO Saude.Paciente (Nome, Email) VALUES (:nome, :email)
 * 
 *   -- Registrar log:
 *   INSERT INTO Saude.LogMensagem (PacienteId, Destinatario) 
 *   VALUES (:id, :dest)
 * 
 *   No ObjectScript (Caché nativo) seria:
 *   Set pac = ##class(Saude.Paciente).%New()
 *   Set pac.Nome  = nome
 *   Set pac.Email = email
 *   Do pac.%Save()
 * ──────────────────────────────────────────────────────────────────
 */
header('Content-Type: application/json');
ini_set('display_errors', 0);
error_reporting(0);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/../vendor/autoload.php';

// Carrega o .env apenas se existir
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
    $dotenv->load();
}

require __DIR__ . '/../Model/CacheConnection.php';

$nomeUsuario     = $_POST['nome']      ?? '';
$mensagemUsuario = $_POST['mensagem']  ?? '';
$emailUsuario    = $_POST['correio']   ?? '';
$email           = $_POST['email']     ?? '';

if (empty($nomeUsuario) || empty($mensagemUsuario) || empty($emailUsuario) || empty($email)) {
    echo json_encode(['error' => true, 'mensagem' => 'Por favor, preencha todos os campos.']);
    exit;
}

// ── 1. ENVIAR O E-MAIL com tratamento de erros melhorado ───
$mail = new PHPMailer(true);
$emailEnviado = false;
$erroEmail = '';

try {
    // Configurações de depuração - desative em produção
    // $mail->SMTPDebug = SMTP::DEBUG_SERVER;
    
    $mail->isSMTP();
    $mail->Host       = $_ENV['MAIL_HOST'] ?? 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = $_ENV['MAIL_USERNAME'];
    $mail->Password   = $_ENV['MAIL_PASSWORD'];
    $mail->SMTPSecure = $_ENV['MAIL_ENCRYPTION'] ?? PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = $_ENV['MAIL_PORT'] ?? 587;
    $mail->CharSet    = 'UTF-8';
    $mail->Timeout    = 30; // Timeout de 30 segundos
    
    // Configurações adicionais para evitar problemas de conexão
    $mail->SMTPOptions = array(
        'ssl' => array(
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true
        )
    );

    $mail->setFrom($_ENV['MAIL_USERNAME'], $nomeUsuario);
    $mail->addAddress($email);
    $mail->addReplyTo($emailUsuario, $nomeUsuario);
    $mail->Subject = 'Mensagem de Contato - Sistema Caché';
    $mail->isHTML(true);
    $mail->Body = "
        <div style='font-family:sans-serif; max-width:600px; margin:0 auto; background:#343a40; padding:20px;'>
            <div style='background:#cce5ff; border:1px solid #b8daff; border-radius:4px; padding:12px 20px; margin-bottom:20px; font-size:1.2em;'>
                <strong>Mensagem de:</strong> $nomeUsuario
            </div>
            <div style='color:#eee; font-size:18px; margin-bottom:30px;'>
                " . nl2br(htmlspecialchars($mensagemUsuario)) . "
            </div>
            <div style='background:#48494a; color:#ddd; text-align:center; padding:10px; font-size:14px;'>
                Pode responder para: <span style='text-decoration:underline;'>$emailUsuario</span>
            </div>
        </div>
    ";
    
    // Versão em texto plano para clientes que não suportam HTML
    $mail->AltBody = "Mensagem de: $nomeUsuario\n\n$mensagemUsuario\n\nPode responder para: $emailUsuario";

    $mail->send();
    $emailEnviado = true;

} catch (Exception $e) {
    $erroEmail = $mail->ErrorInfo;
    error_log("Erro ao enviar email: " . $erroEmail);
}

// Se o email não foi enviado, retorna erro
if (!$emailEnviado) {
    echo json_encode([
        'error' => true, 
        'mensagem' => 'Erro ao enviar email. Verifique sua conexão com a internet e as configurações SMTP.',
        'detalhes' => $erroEmail
    ]);
    exit;
}

// ── 2. REGISTRAR NO BANCO (com tratamento de erro suave) ───
$cacheInfo = '';
try {
    $cache  = new CacheConnection();
    $pdo    = $cache->getPDO();
    $tPac   = $cache->tabela('Saude.Paciente');
    $tLog   = $cache->tabela('Saude.LogMensagem');

    // Verifica se já existe
    $stmt = $pdo->prepare("SELECT PacienteId FROM $tPac WHERE Email = :email");
    $stmt->execute([':email' => $emailUsuario]);
    $paciente = $stmt->fetch();

    if ($paciente) {
        $pacienteId = $paciente['PacienteId'];
        $cacheInfo  = '✅ Paciente já registrado (ID: ' . $pacienteId . ')';
    } else {
        $insert = $pdo->prepare("INSERT INTO $tPac (Nome, Email) VALUES (:nome, :email)");
        $insert->execute([':nome' => $nomeUsuario, ':email' => $emailUsuario]);
        $pacienteId = $pdo->lastInsertId();
        $cacheInfo  = '✅ Paciente cadastrado (ID: ' . $pacienteId . ')';
    }

    // Registra o log
    $log = $pdo->prepare("INSERT INTO $tLog (PacienteId, Destinatario) VALUES (:id, :dest)");
    $log->execute([':id' => $pacienteId, ':dest' => $email]);

} catch (Exception $e) {
    $cacheInfo = '⚠️ Aviso: Email enviado mas não foi possível registrar no banco.';
    error_log("Erro ao registrar no banco: " . $e->getMessage());
}

echo json_encode([
    'error'      => false,
    'mensagem'   => 'Mensagem enviada com sucesso!',
    'cache_info' => $cacheInfo,
]);
