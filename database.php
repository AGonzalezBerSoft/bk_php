<?php
require 'vendor/autoload.php';
require __DIR__.'/s3.php';

use Dotenv\Dotenv;
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

class database {
    public $dbHost = 'localhost';
    public $dbPort = '3306';
    public $dbUsername;
    public $dbPassword;
    public $dbName;
    public $backupFile;

    public function makeBackup() {
        $response = new stdClass();
        $response->success = false;
        $file = __DIR__."/data/$this->backupFile.sql";
        $command = "mysqldump --opt --host=$this->dbHost --port=$this->dbPort --user=$this->dbUsername --password='$this->dbPassword' $this->dbName > $file";
        exec($command, $output, $return_var);
        if ($return_var !== 0) {
            $response->msg = "There was a problem backing up the database.";
        } else {
            if (file_exists("$file.gz")) {
                unlink("$file.gz");
            }
            $command = "gzip --force $file";
            exec($command, $output, $return_var);
            if ($return_var !== 0) {
                $response->msg = "There was a problem backing up the database.";
            } else {
                $response->success = true;
                $response->file = "$file.gz";
            }
        }
        return $response;
    }
}

for ($i=0; $i < $_ENV['COUNT_DATABASES']; $i++) { 
    $tmp = new database();
    if (isset($_ENV["DB_HOST_$i"])) {
        $tmp->dbHost = $_ENV["DB_HOST_$i"];
    }
    if (isset($_ENV["DB_PORT_$i"])) {
        $tmp->dbPort = $_ENV["DB_PORT_$i"];
    }
    if (isset($_ENV["DB_AWS_BUCKET_$i"]) && isset($_ENV["DB_USER_$i"]) && isset($_ENV["DB_PASSWORD_$i"]) && isset($_ENV["DB_NAME_$i"]) && isset($_ENV["DB_NAME_BACKUP_$i"])) {
        try {
            $file_cp = $_ENV["DB_NAME_BACKUP_CP_$i"];
            $file_cp = __DIR__."/data/$file_cp.sql.gz";
            unlink($file_cp);
            $tmp->dbUsername = $_ENV["DB_USER_$i"];
            $tmp->dbPassword = $_ENV["DB_PASSWORD_$i"];
            $tmp->dbName = $_ENV["DB_NAME_$i"];
            $tmp->backupFile = $_ENV["DB_NAME_BACKUP_$i"];
            $backup = $tmp->makeBackup();
            if($backup->success) {
                $tmp = new s3();
                $tmp->setBucket($_ENV["DB_AWS_BUCKET_$i"]);
                $tmp->setFilePath($backup->file);
                $random = substr(str_shuffle('abcdefghijklmnopqrstuvwxyz'), 0, 5);
                if (date('j') == 1) {
                    $tmp->setKeyName("monthly_".date("Y-m-d")."_".$random."_".basename($backup->file));
                } else {
                    $tmp->setKeyName("daily_".date("Y-m-d")."_".$random."_".basename($backup->file));
                }
                if($tmp->upload()) {
                    if(copy($backup->file, $file_cp)) {
                        unlink($backup->file);
                    }
                }
            }
        } catch (\Throwable $th) {
            print_r($th->getMessage());
        }

    }
}