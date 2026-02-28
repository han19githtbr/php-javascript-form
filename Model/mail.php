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
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

require __DIR__ . '/../Model/CacheConnection.php';

$nomeUsuario     = $_POST['nome']      ?? '';
$mensagemUsuario = $_POST['mensagem']  ?? '';
$emailUsuario    = $_POST['correio']   ?? '';
$email           = $_POST['email']     ?? '';

if (empty($nomeUsuario) || empty($mensagemUsuario) || empty($emailUsuario) || empty($email)) {
    echo json_encode(['error' => true, 'mensagem' => 'Por favor, preencha todos os campos.']);
    exit;
}

// ── 1. ENVIAR O E-MAIL (lógica original mantida) ─────────────────
$mail = new PHPMailer(true);

try {
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = $_ENV['MAIL_USERNAME'];
    $mail->Password   = $_ENV['MAIL_PASSWORD'];
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;
    $mail->CharSet    = 'UTF-8';

    $mail->setFrom($emailUsuario, $nomeUsuario);
    $mail->addAddress($email);
    $mail->Subject = 'Mensagem de Contato !IMPORTANTE!';
    $mail->isHTML(true);
    $mail->Body = "
        <div style='font-family:sans-serif; max-width:600px; margin:0 auto; background:#343a40; padding:20px;'>
            <div style='background:#cce5ff; border:1px solid #b8daff; border-radius:4px; padding:12px 20px; margin-bottom:20px; font-size:1.2em;'>
                <strong>Mensagem de:</strong> $nomeUsuario
            </div>
            <div style='color:#eee; font-size:18px; margin-bottom:30px;'>
                $mensagemUsuario
            </div>
            <div style='background:#48494a; color:#ddd; text-align:center; padding:10px; font-size:14px;'>
                Pode responder para: <span style='text-decoration:underline;'>$emailUsuario</span>
            </div>
        </div>
    ";

    $mail->send();

} catch (Exception $e) {
    echo json_encode(['error' => true, 'mensagem' => 'Erro ao enviar: ' . $mail->ErrorInfo]);
    exit;
}

// ── 2. INTEGRAÇÃO COM O CACHÉ: registrar paciente e log ──────────
$cacheInfo = '';
try {
    $cache  = new CacheConnection();
    $pdo    = $cache->getPDO();
    $tPac   = $cache->tabela('Saude.Paciente');
    $tLog   = $cache->tabela('Saude.LogMensagem');

    // Verifica se o paciente (remetente) já existe no Caché
    $stmt = $pdo->prepare("SELECT PacienteId FROM $tPac WHERE Email = :email");
    $stmt->execute([':email' => $emailUsuario]);
    $paciente = $stmt->fetch();

    if ($paciente) {
        $pacienteId = $paciente['PacienteId'];
        $cacheInfo  = 'Paciente já registrado no Caché (ID: ' . $pacienteId . ').';
    } else {
        // Cadastra o novo paciente no Caché
        $insert = $pdo->prepare("
            INSERT INTO $tPac (Nome, Email) VALUES (:nome, :email)
        ");
        $insert->execute([':nome' => $nomeUsuario, ':email' => $emailUsuario]);
        $pacienteId = $pdo->lastInsertId();
        $cacheInfo  = 'Paciente cadastrado no Caché (ID: ' . $pacienteId . ').';
    }

    // Registra o log da mensagem enviada
    $log = $pdo->prepare("
        INSERT INTO $tLog (PacienteId, Destinatario) VALUES (:id, :dest)
    ");
    $log->execute([':id' => $pacienteId, ':dest' => $email]);

} catch (Exception $e) {
    // Falha no Caché não impede o retorno de sucesso do e-mail
    $cacheInfo = 'Aviso: não foi possível registrar no Caché. ' . $e->getMessage();
}

echo json_encode([
    'error'      => false,
    'mensagem'   => 'Mensagem enviada com sucesso!',
    'cache_info' => $cacheInfo,
]);
