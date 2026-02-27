<?php
header('Content-Type: application/json');
ini_set('display_errors', 0);
error_reporting(0);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

$nomeUsuario     = $_POST['nome']      ?? '';
$mensagemUsuario = $_POST['mensagem']  ?? '';
$emailUsuario    = $_POST['correio']   ?? '';
$email           = $_POST['email']     ?? '';

if (empty($nomeUsuario) || empty($mensagemUsuario) || empty($emailUsuario) || empty($email)) {
    echo json_encode(['error' => true, 'mensagem' => 'Por favor, preencha todos os campos.']);
    exit;
}

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
    echo json_encode(['error' => false, 'mensagem' => 'Mensagem enviada com sucesso!']);

} catch (Exception $e) {
    echo json_encode(['error' => true, 'mensagem' => 'Erro ao enviar: ' . $mail->ErrorInfo]);
}
?>