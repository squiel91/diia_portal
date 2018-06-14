<?php

    include_once('running_dev.php');

    if(running_dev()) {
		ini_set('display_errors', 1);
		error_reporting(1);
	} else {
		ini_set('display_errors', 1);
		error_reporting(1);
	}	

?>