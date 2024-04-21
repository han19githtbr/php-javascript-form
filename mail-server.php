<?php
    // Configurações do servidor SMTP do Gmail
    use PHPMailer\PHPMailer\PHPMailer;
    use PHPMailer\PHPMailer\Exception;

    require 'vendor/autoload.php'; // Caminho para o PHPMailer
    require 'config.php'; // Arquivo de configuração com suas credenciais

    // Configurações OAuth2
    $clientId = "232593245689-stksiqs15jlrorrvp1fghuual9rc1g34.apps.googleusercontent.com";
    $clientSecret = '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';
    $redirect_uri = "http://localhost/teste-programacao-comlurb/";

    // Verifique se há um código de autorização na solicitação
    $code = isset($_GET['code']) ? $_GET['code'] : null;

    if ($code) {
        // Troca o código de autorização por um token de acesso
        $data = array(
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'code' => $code,
            'redirect_uri' => $redirect_uri,
            'grant_type' => 'authorization_code'
        );

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://oauth2.googleapis.com/token",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => http_build_query($data),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            echo "Erro ao obter o token de atualização: " . $err;
        } else {
            $response_data = json_decode($response, true);
            $refresh_token = $response_data['refresh_token'];

            // Use o token de atualização para enviar o email
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->Port = 587;
            $mail->SMTPSecure = 'ssl';
            $mail->SMTPAuth = true;
            $mail->Username = $config['username']; // Seu endereço de email do Gmail
            $mail->Password = $config['password']; // Sua senha do Gmail
            
            // Configurações do email
            $mail->setFrom($emailUsuario, $nomeUsuario);
            $mail->addAddress('milliance23@gmail.com', 'Handy');
            $mail->Subject = 'Teste';
            $mail->Body = 'Teste de Programação';

            // Envia o email
            if ($mail->send()) {
                echo 'Email enviado com sucesso!';
            } else {
                echo 'Erro ao enviar o email: ' . $mail->ErrorInfo;
            }
        }
    } else {
        // Se não houver código de autorização, redirecione para a URL de autorização
        $authUrl = 'https://accounts.google.com/o/oauth2/auth?' . http_build_query(array(
            'response_type' => 'code',
            'client_id' => $clientId,
            'redirect_uri' => $redirect_uri,
            'scope' => 'https://www.googleapis.com/auth/gmail.compose'
        ));

        header('Location: ' . $authUrl);
        exit;
    }
?>

