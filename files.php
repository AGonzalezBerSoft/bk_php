<?php

require 'vendor/autoload.php';
require __DIR__.'/s3.php';

use Dotenv\Dotenv;
if (version_compare(PHP_VERSION, '7.0.0') >= 0) {
    $dotenv = Dotenv::createImmutable(__DIR__);
} else {
    $dotenv = new Dotenv(__DIR__);
}
$dotenv->load();

class files {
    public $path;
    public $file;
    public function makeBackup() {
        $response = new stdClass();
        $response->success = false;
        $response->file = "/tmp/$this->file.tar.gz";
        $command = "tar -czvf $response->file --exclude='.git' --exclude='logs' --exclude='vendor' --exclude='graduados' -C $this->path .";
        exec($command, $output, $return_var);
        if ($return_var !== 0) {
            $response->msg = "There was a problem backing up the database.";
        } else {
            $response->success = true;
        }
        return $response;
    }
}


for ($i=0; $i < $_ENV['COUNT_FILES']; $i++) { 
    if (isset($_ENV["FILE_AWS_BUCKET_$i"]) && isset($_ENV["FILE_BACKUP_$i"]) && isset($_ENV["FILE_NAME_BACKUP_$i"])) {
        try {
            $tmp = new files();
            $tmp->path = $_ENV["FILE_BACKUP_$i"];
            $tmp->file = $_ENV["FILE_NAME_BACKUP_$i"];
            $make = $tmp->makeBackup();
            if ($make->success) {
                $tmp = new s3();
                $tmp->setBucket($_ENV["FILE_AWS_BUCKET_$i"]);
                $tmp->setFilePath($make->file);
                $tmp->setKeyName(date("Y-m-d")."_".basename($make->file));
                if($tmp->upload()) {
                    unlink($make->file);
                }
            }
        } catch (Exception $e) {
            echo 'Error: ' . $e->getMessage();
        }
    }
}
?>
