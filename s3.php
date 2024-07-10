<?php
require 'vendor/autoload.php';

use Aws\S3\S3Client;
use Aws\Exception\AwsException;

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