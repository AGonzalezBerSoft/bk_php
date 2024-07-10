<?php
ini_set("memory_limit", "2048M");
set_time_limit(360);
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
        $response->file = __DIR__."/data/$this->file.tar.gz";
        $command = "tar -czvf $response->file --exclude='.git' --exclude='logs' --exclude='vendor'  -C $this->path .";
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
                $random = substr(str_shuffle('abcdefghijklmnopqrstuvwxyz'), 0, 5);
                if (in_array(date('j'),[1,2,3])) {
                    $tmp->setKeyName("monthly_".date("Y-m-d")."_".$random."_".basename($make->file));
                } else {
                    $tmp->setKeyName("daily_".date("Y-m-d")."_".$random."_".basename($make->file));
                }
                if($tmp->upload()) {
                    unlink($make->file);
                }
            } else {
                throw new Exception($make->msg, 1);
            }
        } catch (\Throwable $th) {
            $tmp = new MailSMTPTransactional();
            $tmp->send([['email'=> 'soporte@bersoft.co', 'name' => 'Soporte BerSoft']], "Error backup AWS - ".$_ENV["FILE_AWS_BUCKET_$i"], $th->getMessage());
        }
    } else {
        $tmp = new MailSMTPTransactional();
        $tmp->send([['email'=> 'soporte@bersoft.co', 'name' => 'Soporte BerSoft']], "Error backup AWS - ".$_ENV["FILE_AWS_BUCKET_$i"], "Error en parametros");
    }
}
?>
