<?php
    
    include_once('running_dev.php');

    if(running_dev()) {
        $db_server = 'localhost';
        $db_user = 'root';
        $db_pass = 'Jaguar23';
        $database = "diia";
    } else {
        $db_server = 'mysql.hostinger.com';
        $db_user = 'u409626884_diia';
        $db_pass = 'Jaguar23';
        $database = "u409626884_diia";
    }

    $db = new mysqli($db_server, $db_user, $db_pass, $database);
    mysqli_set_charset($db,"utf8");

    if (!db) {
        exit("Cant connect to DB");
    }

?>