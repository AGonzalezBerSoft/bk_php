<?php

require 'vendor/autoload.php';
require __DIR__.'/s3.php';

use Dotenv\Dotenv;
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

class files {
    public $path;
    public $file;
    public function makeBackup() {
        $response = new stdClass();
        $response->success = false;
        $file = "$this->file.tar.gz";
        $response->file = $file;
        $command = "tar -czvf $file --exclude='.git' --exclude='logs' --exclude='vendor' -C $this->path .";
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
    if (isset($_ENV["AWS_BUCKET_$i"]) && isset($_ENV["PATH_BACKUP_$i"]) && isset($_ENV["PATH_NAME_BACKUP_$i"])) {
        try {
            $tmp = new files();
            $tmp->path = $_ENV["PATH_BACKUP_$i"];
            $tmp->file = $_ENV["PATH_NAME_BACKUP_$i"];
            $make = $tmp->makeBackup();
            if ($make->success) {
                $tmp = new s3();
                $tmp->setBucket($_ENV["AWS_BUCKET_$i"]);
                $tmp->setFilePath(__DIR__.'/'.$make->file);
                $tmp->setKeyName(date("Y-m-d")."_".$make->file);
                if($tmp->upload()) {
                    unlink(__DIR__.'/'.$make->file);
                }
            }
        } catch (Exception $e) {
            echo 'Error: ' . $e->getMessage();
        }
    }
}
?>
