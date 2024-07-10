## Linux
copy .env.example -> .env

Configurar el numero de backup de base de datos, y de archivos indicando el path y parametros.
Programas los crontab 

TZ=America/Bogota
05 00 * * * php ~/backup/database.php
00 03 */3 * * php ~/backup/files.php

## Windows
