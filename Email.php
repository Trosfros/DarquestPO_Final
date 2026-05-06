<?php

declare(strict_types=1);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

class Email
{

    public static function readConfig(string $configPath): void {

        $config = parse_ini_file($configPath, true);
        $gmailConfig = $config["phpmailer"];

        define("MAILHOST", "smtp.gmail.com");
        define("USERNAME", $gmailConfig["gmailfrom"]);
        define("PASSWORD", $gmailConfig["gmailapppass"]);
        define("SEND_FROM", $gmailConfig["gmailfrom"]);
        define("SEND_FROM_NAME", $gmailConfig["gmailfromname"]);
        define("REPLY_TO_NAME", $gmailConfig["gmailreplyname"]);

    }

    public static function send(string $email, string $subject, string $message): bool
    {
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->SMTPAuth = true;

        $mail->Host = MAILHOST;
        $mail->Username = USERNAME;
        $mail->Password = PASSWORD;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        // Set CharSet to UTF-8
        $mail->CharSet = 'UTF-8'; 
        
        $mail->setFrom(SEND_FROM, SEND_FROM_NAME);
        $mail->addAddress($email);
        $mail->addReplyTo(SEND_FROM, REPLY_TO_NAME);

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $message;
        $mail->AltBody = $message;

        return $mail->send();
    }
}