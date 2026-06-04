<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Bersihkan input
    $name = htmlspecialchars(strip_tags($_POST['Name']));
    $phone = htmlspecialchars(strip_tags($_POST['PhoneNumber']));
    $email = filter_var($_POST['Email'], FILTER_SANITIZE_EMAIL);
    $message = htmlspecialchars(strip_tags($_POST['Message']));

    $mail = new PHPMailer(true);

    try {
        // Konfigurasi SMTP
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'caturanakkartika5@gmail.com'; // Gmail
        $mail->Password = 'ggbreihpuruozpnc'; // App Password
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;

        // Penerima & pengirim
        $mail->setFrom('caturanakkartika5@gmail.com', 'Smart Parking System');
        $mail->addAddress('caturanakkartika5@gmail.com'); // email tujuan
        $mail->addReplyTo($email, $name);

        // Email format HTML
        $mail->isHTML(true);
        $mail->Subject = 'New Contact Message from Smart Parking System';

        $mailContent = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; }
                .header { background: #fc9d22; color: #fff; padding: 10px; text-align: center; }
                .content { padding: 20px; }
                .footer { font-size: 12px; color: #555; margin-top: 20px; }
                .field { margin-bottom: 10px; }
                .label { font-weight: bold; }
            </style>
        </head>
        <body>
            <div class='header'><h2>New Contact Message</h2></div>
            <div class='content'>
                <div class='field'><span class='label'>Name:</span> $name</div>
                <div class='field'><span class='label'>Email:</span> $email</div>
                <div class='field'><span class='label'>Phone:</span> $phone</div>
                <div class='field'><span class='label'>Message:</span><br>$message</div>
                <div class='field'><span class='label'>Sent At:</span> ".date('d M Y H:i:s')."</div>
            </div>
            <div class='footer'>This email was sent from Smart Parking System contact form.</div>
        </body>
        </html>
        ";

        $mail->Body = $mailContent;

        $mail->send();
        echo 'success';
    } catch (Exception $e) {
        echo "error: {$mail->ErrorInfo}";
    }
}
?>
