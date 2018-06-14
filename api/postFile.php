<?php
	$respuesta = array();

	// $uploaddir = '/home/ezequiel/Projects/diia_webapp/img/media/';
	$uploaddir = '/home/u409626884/public_html/img/media/';
	$relative = 'img/media/';
	$nombre_arch = '_' . mt_rand(1000000,9999999) . $_FILES['archivo']['name'];

	$uploadfile =  $uploaddir . basename($nombre_arch);
	$relativefile = $relative . basename($nombre_arch);
	if (move_uploaded_file($_FILES['archivo']['tmp_name'], $uploadfile)) {
		$respuesta['url'] = $relativefile;
	} else {
		$respuesta['error'] = 'No se ha podido guardar el archivo.';
	}

	header('Content-Type: application/json; charset=utf-8;');
	echo json_encode($respuesta, JSON_UNESCAPED_UNICODE);

?>
