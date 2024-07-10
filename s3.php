<?php
require 'vendor/autoload.php';

use Aws\S3\S3Client;
use Aws\Exception\AwsException;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception AS ExceptionPHPMailer;

use Dotenv\Dotenv;
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

class s3 {
    private $bucket;
    private $filePath;
    private $keyName;

    public function upload() {
        try {
            $s3Client = new S3Client([
                'region'  => 'us-east-1',
                'version' => 'latest',
                'credentials' => [
                    'key'    => $_ENV['AWS_ACCESS_KEY_ID'],
                    'secret' => $_ENV['AWS_SECRET_ACCESS_KEY'],
                ]
            ]);

            $result = $s3Client->putObject([
                'Bucket' => $this->bucket,
                'Key' => $this->keyName,
                'SourceFile' => $this->filePath,
            ]);
        
            echo "El archivo se ha cargado exitosamente: " . $result['ObjectURL'] . PHP_EOL;
            return true;
        } catch (AwsException $e) {
            echo "Error al cargar el archivo: " . $e->getMessage() . PHP_EOL;
            return false;
        }
    }
    public function setBucket(String $bucket) {
        $this->bucket = $bucket;
    }
    public function setFilePath(String $filePath) {
        $this->filePath = $filePath;
    }
    public function setKeyName(String $keyName) {
        $this->keyName = $keyName;
    }
}

class MailSMTPTransactional
{
    public function send(Array $destino, String $asunto, String $cuerpo)
    {
        $response = new stdClass();
        $response->success = false;
        $response->data = '';
        $response->errors = [];
        try {
            for ($provider=1; $provider <= 3; $provider++) { 
                $mail = $this->envioEmail($provider);
                if($mail->success) {
                    $mail = $mail->mail;
                    $mail->Subject = $asunto; 
                    $mail->Body = $cuerpo;
                    $mail->CharSet = 'UTF-8';
                    foreach ($destino as $dest) {
                        $mail->addAddress($dest['email'], $dest['name']);
                    }
                    $response->success = $mail->send();
                    if ($response->success) {
                        break;
                    } else {
                        $response->errors[] = "Proveedor ($provider): ".$mail->ErrorInfo;
                    }
                } else {
                    $response->errors[] = "Proveedor ($provider): ".$mail->error;
                }
            }
        } catch (\Throwable $th) {
            $response->success = false;
            $response->errors[] = $th->getMessage();
        }
        return $response;
    }
    protected function envioEmail($provider)
    {
        $response = new stdClass();
        $response->success = false;
        try {
            if (isset($_ENV["MAIL_USER_$provider"]) && isset($_ENV["MAIL_PSW_$provider"]) && isset($_ENV["MAIL_SERVER_$provider"]) && isset($_ENV["MAIL_PORT_$provider"]) && isset($_ENV["MAIL_SMTPSECURE_$provider"]) && isset($_ENV["MAIL_FROM_$provider"]) && isset($_ENV["MAIL_FROM_NAME_$provider"])) {
                try {
                    $mail = new PHPMailer();
                    $mail->Username = $_ENV["MAIL_USER_$provider"];
                    $mail->Password = $_ENV["MAIL_PSW_$provider"];
                    $mail->SMTPSecure = $_ENV["MAIL_SMTPSECURE_$provider"];
                    $mail->Host = $_ENV["MAIL_SERVER_$provider"];
                    $mail->Port = $_ENV["MAIL_PORT_$provider"];
                    $mail->IsSMTP();
                    $mail->SMTPAuth = true;
                    $mail->setFrom($_ENV["MAIL_FROM_$provider"], $_ENV["MAIL_FROM_NAME_$provider"]);
                    $mail->IsHTML(true);
                    $response->success = true;
                    $response->mail = $mail; 
                } catch (ExceptionPHPMailer $th) {
                    throw new Exception($th->getMessage(), 1);
                }
            } else {
                throw new Exception("undefined constant", 1);
            }
        } catch (\Throwable $th) {
            $response->error = $th->getMessage();
            $response->success = false;
        }
        return $response;
    }
}