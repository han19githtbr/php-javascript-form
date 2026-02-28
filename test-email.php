<?php
require 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Carrega o .env
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$mail = new PHPMailer(true);

try {
    // Configurações de debug - mostra tudo
    $mail->SMTPDebug = SMTP::DEBUG_SERVER;
    $mail->Debugoutput = function($str, $level) {
        echo "SMTP: $str\n";
    };
    
    $mail->isSMTP();
    $mail->Host       = $_ENV['MAIL_HOST'] ?? 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = $_ENV['MAIL_USERNAME'];
    $mail->Password   = $_ENV['MAIL_PASSWORD'];
    $mail->SMTPSecure = $_ENV['MAIL_ENCRYPTION'] ?? PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = $_ENV['MAIL_PORT'] ?? 587;
    $mail->CharSet    = 'UTF-8';
    
    // Configurações SSL menos restritivas
    $mail->SMTPOptions = [
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true
        ]
    ];

    $mail->setFrom($_ENV['MAIL_USERNAME'], 'Teste');
    $mail->addAddress($_ENV['MAIL_USERNAME']); // Envia para você mesmo
    $mail->Subject = 'Teste de Conexão SMTP';
    $mail->Body    = 'Se você recebeu este email, a configuração SMTP está funcionando!';

    $mail->send();
    echo "\n✅ Email enviado com sucesso!\n";
    
} catch (Exception $e) {
    echo "\n❌ Erro ao enviar: " . $mail->ErrorInfo . "\n";
}