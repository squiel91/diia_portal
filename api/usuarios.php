<?php

include_once('cors_config.php');
include_once('debug_config.php');
include_once('db_config.php');

function nivel_admin($current_email, $current_pass, $db) {
    $error_message = null;
    if (!$current_email || !$current_pass) {
        $error_message = 'Se debe proveer usuario y contraseña del que invoca.';
    } else {
        $SQL = $db->prepare('SELECT * FROM usuarios WHERE email = ? AND pass = ?');
        $SQL->bind_param('ss', $current_email, $current_pass);
        $SQL->execute();
        $result = $SQL->get_result();
        if ($result->num_rows > 0) {
            $row = $result->fetch_array(MYSQLI_ASSOC);
            if($row['rol'] != 'administrador') {
                $error_message = 'Se debe tener rol administrador para invocar esta accion.';
            }
        } else {
            $error_message = 'Credenciales incorrectas.';
        }
    }
    if (!is_null($error_message)) {
        $exit_array = array("error" => $error_message);
        echo json_encode($exit_array, JSON_UNESCAPED_UNICODE);
        exit(0);
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    header('Content-Type: application/json; charset=utf-8;');
    $accion = isset($_POST['accion'])? $_POST['accion'] : null;
    $current_email = isset($_POST['current_email'])? strtolower($_POST['current_email']) : null;
    $current_pass = isset($_POST['current_pass'])? $_POST['current_pass'] : null;
    
    switch ($accion) {
        case 'listar': {
            nivel_admin($current_email, $current_pass, $db);
            $SQL = $db->prepare('SELECT * FROM usuarios');
            $SQL->execute();
            $result = $SQL->get_result();
            $SQL->free_result();
            $SQL->close();
            $lista_usuarios = array();
            while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
                $usuario = array(
                    'email' => $row['email'],
                    'rol' => $row['rol'],
                    'id_docente' => $row['id_docente']

                );
                array_push($lista_usuarios, $usuario);
            }
            echo json_encode($lista_usuarios, JSON_UNESCAPED_UNICODE);
            break;
        }

        case 'agregar': {
            nivel_admin($current_email, $current_pass, $db);
            $mensaje = array();
            $errors = false;

            $email = isset($_POST['email'])? $_POST['email'] : null;
            $rol = isset($_POST['rol'])? $_POST['rol'] : null;
            $id_docente = isset($_POST['id_docente'])? $_POST['id_docente'] : null;

            $SQL = $db->prepare('SELECT * FROM usuarios WHERE email = ?');
            $SQL->bind_param('s', $email);
            $SQL->execute();
            $result = $SQL->get_result();

            if ($result->num_rows > 0) {
                $errors = true;
                $mensaje['error'] = 'Ya existe usuario con mismo email.';
            } else {
                $seteo_pass = mt_rand(1000000,9999999);
                $SQL = $db->prepare("INSERT INTO usuarios (email, rol, id_docente, seteo_pass) VALUES (?, ?, ?, ?)");
                $SQL->bind_param('sssi', $email, $rol, $id_docente, $seteo_pass);
                $result = $SQL->execute();
                if ($result) {
                    $mensaje['success'] = true;
                    // mail($email , 'Setup de contraseña DIIA' , '<h1>Nuevo usuario plataforma DIIA</h1><p>Un administrador de <a href="http://diia.edu.uy">diia.edu.uy</a> te ha agregado como usuario de la web DIIA. Sigue el siguiente link para establecer tu nueva constraseña (o copia y pega esta dirección en tu navegador):</p><p><a href="https://diia.edu.uy?clave=' . $seteo_pass . '">https://diia.edu.uy?clave=' . $seteo_pass . '</a></p><p>Si crees que este email no es para ti hazle caso omiso o responde consultando sobre el mismo.</p><p>Atentamente, equipo de proyecto DIIA.</p>', "From: proyecto@diia.edu.uy\r\nMIME-Version: 1.0\r\nContent-Type: text/html; charset=UTF-8\r\n");
                } else {
                    $mensaje['error'] = 'Error al ingresar al sistema.';
                }
            }
            echo json_encode($mensaje, JSON_UNESCAPED_UNICODE);
            break;
        }

        case 'eliminar': {
            nivel_admin($current_email, $current_pass, $db);
            $mensaje = array();
            $errors = false;

            $email = isset($_POST['email'])? $_POST['email'] : null;

            $SQL = $db->prepare('DELETE FROM usuarios WHERE email = ?');
            $SQL->bind_param('s', $email);
            $SQL->execute();
            $result = $SQL->get_result();
            $mensaje['success'] = true;
            echo json_encode($mensaje, JSON_UNESCAPED_UNICODE);
            break;
        }   

        case 'login': {
            $mensaje = array();
            $errors = false;

            $email = isset($_POST['email'])? $_POST['email'] : null;
            $pass = isset($_POST['pass'])? $_POST['pass'] : null;
            $clave = isset($_POST['clave'])? $_POST['clave'] : null;


            if ($pass) {

                if ($clave) {
                    $SQL = $db->prepare('SELECT * FROM usuarios WHERE seteo_pass = ?');
                    $SQL->bind_param('i', $clave);
                    $SQL->execute();
                    $result = $SQL->get_result();
                    if ($result->num_rows > 0) {
                        $row = $result->fetch_array(MYSQLI_ASSOC);
                        $email = $row['email'];
                        $SQL = $db->prepare("UPDATE usuarios
                            SET
                                pass = ?,
                                seteo_pass = NULL
                            WHERE
                                email=?");
                        $SQL->bind_param('ss', $pass, $email);
                        $result = $SQL->execute();
                    } else {
                        $mensaje['error'] = 'Clave para seteo de contraseña incorrecta';
                    }
                } else {
                    $SQL = $db->prepare('SELECT * FROM usuarios WHERE email = ? AND pass = ?');
                    $SQL->bind_param('ss', $email, $pass);
                    $SQL->execute();
                    $result = $SQL->get_result();
                    if ($result->num_rows > 0) {
                        $row = $result->fetch_array(MYSQLI_ASSOC);
                        if ($row['id_docente'] == 'docente') {
                            $mensaje['error'] = 'Los docentes solo pueden iniciar sesión en la plataforma DIIA, no en el portal de noticias';
                        } else {
                            $mensaje['success'] = true;
                            $mensaje['email'] = $row['email'];
                            $mensaje['rol'] = $row['rol'];
                            $mensaje['id_docente'] = $row['id_docente'];
                        }
                    } else {
                        $mensaje['error'] = 'Email o contraseña incorrectos.';
                    }
                }
            } else {
                $mensaje['error'] = 'La contraseña no puede ser vacia.';
            }
            echo json_encode($mensaje, JSON_UNESCAPED_UNICODE);
            break;
        }

        default: {
            $mensaje['errors'] = 'No incluye accion o no es valida';
            echo json_encode($mensaje, JSON_UNESCAPED_UNICODE);

        }
    }
}

?>