<?php
require 'vendor/autoload.php';

use Aws\S3\S3Client;
use Aws\Exception\AwsException;

class s3 {
    private $bucket;
    private $filePath;
    private $keyName;

    public function upload() {
        try {
            $s3Client = new S3Client([
                'region'  => 'us-east-1', // Cambia esto a tu región
                'version' => 'latest',
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
    public function setBucket($bucket) {
        $this->bucket = $bucket;
    }
    public function setFilePath($filePath) {
        $this->filePath = $filePath;
    }
    public function setKeyName($keyName) {
        $this->keyName = $keyName;
    }
}