<?php

    // Manages all elements
    // expects a accion parameter: [listar, agregar, modificar, consultar]
    
    // Listar
    // if get username and password returns elements with pending status and users drafts (marked as state: draft)
    // if not username and password provided returns only published elements
    // tipo filter

    // Consultar
    // Returns the element. If not published user and password is required. Drafts can be accesed by the user who owns it

    // update
    // Only loged users can perform this action. Drafts can only be edited by the owner

    //agregar
    // Only loged users can perform this action. 

include_once('cors_config.php');
include_once('debug_config.php');
include_once('db_config.php');

// $uploaddir = '/home/ezequiel/Projects/diia_webapp/img/principales/';
$uploaddir = '/home/u409626884/public_html/img/princiaples/';
$relative = 'img/principales/';
    
function logeado($current_email, $current_pass, $db) {
    $error_message = null;
    if (!$current_email || !$current_pass) {
        return false;
    } else {
        $SQL = $db->prepare('SELECT * FROM usuarios WHERE email = ? AND pass = ? AND rol <> \'docente\'');
        $SQL->bind_param('ss', $current_email, $current_pass);
        $SQL->execute();
        $result = $SQL->get_result();
        if ($result->num_rows == 0) {
            return false;
        }
    }
    return true;
}

function array_a_elemento($arr) {
    $returning = array(
        'id' => $arr['id'],
        'autor' => $arr['autor'],
        'titulo' => $arr['titulo'],
        'contenido' => $arr['contenido'],
        'tipo' => $arr['tipo'],
        'estado' => $arr['estado'],
        'creado' => $arr['creado'],
        'publicado' => $arr['publicado'],
        'imagen_principal' => isset($arr['imagen_principal'])? $arr['imagen_principal'] : null
    );
    return $returning;
}

// ini_set('display_errors', 1);
// error_reporting(~0);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $accion = isset($_POST['accion'])? $_POST['accion'] : null;
    $current_email = isset($_POST['current_email'])? strtolower($_POST['current_email']) : null;
    $current_pass = isset($_POST['current_pass'])? $_POST['current_pass'] : null;
    
    switch ($accion) {
        case 'listar': 
            $SQL_string = 'SELECT * FROM elementos ';
            $loged = logeado($current_email, $current_pass, $db);
            if  ($loged) {
                $SQL_string = $SQL_string . 'WHERE (estado != "borrador" OR (estado = "borrador" AND autor ="' . $current_email . '")) ';
            } else {
                $SQL_string = $SQL_string . 'WHERE estado = "publicado" ';
            }

            $tipo = isset($_POST['tipo'])? $_POST['tipo'] : null;
            if ($tipo) {
                $SQL_string = $SQL_string .  ' AND tipo = "' . $tipo . '"';
            }

            if  ($loged) {
                $SQL_string = $SQL_string . ' ORDER BY publicado DESC';
            } else {
                $SQL_string = $SQL_string . ' ORDER BY creado DESC';
            }

            $SQL = $db->prepare($SQL_string);
            $SQL->execute();
            $result = $SQL->get_result();
            $SQL->free_result();
            $SQL->close();
            $lista_elementos = array();
            while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
                $elemento = array_a_elemento($row);
                array_push($lista_elementos, $elemento);
            }
            $respuesta = $lista_elementos;
            break;

        case 'agregar':

            $titulo = isset($_POST['titulo'])? $_POST['titulo'] : null;
            $dbtenido = isset($_POST['contenido'])? $_POST['contenido'] : null;
            $tipo = isset($_POST['tipo'])? $_POST['tipo'] : null;
            $estado = isset($_POST['estado'])? $_POST['estado'] : null;

            if (logeado($current_email, $current_pass, $db)) {
                if ($titulo && $dbtenido && $tipo && $estado) {

                    $imagen_principal = null;
                    $uploadfile =  null;
                    $relativefile = null;
                    if (isset($_FILES['imagen_principal'])) {
                        $uploadfile =  $uploaddir . basename($_FILES['imagen_principal']['name']);
                        $relativefile = $relative . basename($_FILES['imagen_principal']['name']);
                        if (move_uploaded_file($_FILES['imagen_principal']['tmp_name'], $uploadfile)) {
                            $imagen_principal = $relativefile;
                        }
                    }

                    $creado = date("Y-m-d H:i:s");
                    $publicado = $estado == 'publicado'? date("Y-m-d H:i:s") : null;


                    $SQL = $db->prepare("INSERT INTO elementos (titulo, contenido, autor, tipo, estado, creado, publicado, imagen_principal) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                    $SQL->bind_param('ssssssss', 
                        $titulo,
                        $dbtenido,
                        $current_email,
                        $tipo,
                        $estado,
                        $creado,
                        $publicado,
                        $imagen_principal
                    );
                    $result = $SQL->execute();
                    if ($result) {
                        $SQL = $db->prepare('SELECT * FROM elementos WHERE id = ' . mysqli_insert_id($db));
                        $SQL->execute();
                        $result = $SQL->get_result();
                        $SQL->free_result();
                        $SQL->close();
                        $row = $result->fetch_array(MYSQLI_ASSOC);
                        $elemento = array_a_elemento($row);
                        $respuesta = $elemento;

                    } else {
                        $respuesta['error'] = 'Error al insertar la noticia. Es nuestra culpa.'; 
                        $respuesta['detalle'] = $SQL->error; 
                    }
                } else {
                    $respuesta['error'] = 'Faltan parametros. Requeridos: titulo, contenido, tipo y estado.';
                }
            } else {
                $respuesta['error'] = 'Debe proeer usuario y contrasenia del invocante.'; 
            }
            break;

        case 'modificar':
            if (logeado($current_email, $current_pass, $db)) {
                $id = isset($_POST['id'])? $_POST['id'] : null;
                if ($id) { 
                    $SQL = $db->prepare('SELECT * FROM elementos WHERE id = ' . $id);
                    $SQL->execute();
                    $result = $SQL->get_result();
                    $SQL->free_result();
                    $SQL->close();
                    if ($result->num_rows > 0) {
                        $row = $result->fetch_array(MYSQLI_ASSOC);
                        $elemento = array_a_elemento($row);
                        if ($elemento['estado'] == 'borrador' && $elemento['autor'] != $current_email) {
                            $respuesta['error'] = 'Solo el autor puede modificar su borrador.';
                        } else {
                            $imagen_principal = null;
                            $uploadfile =  null;
                            $relativefile = null;
                            if (isset($_FILES['imagen_principal'])) {
                                $uploadfile =  $uploaddir . basename($_FILES['imagen_principal']['name']);
                                $relativefile = $relative . basename($_FILES['imagen_principal']['name']);
                                if (move_uploaded_file($_FILES['imagen_principal']['tmp_name'], $uploadfile)) {
                                    $imagen_principal = $relativefile;
                                }
                            }
                            $titulo = isset($_POST['titulo'])? $_POST['titulo'] : null;
                            $dbtenido = isset($_POST['contenido'])? $_POST['contenido'] : null;
                            $tipo = isset($_POST['tipo'])? $_POST['tipo'] : null;
                            $estado = isset($_POST['estado'])? $_POST['estado'] : null;

                            $publicado = $estado == 'publicado' && $elemento['estado'] != 'publicado'? date("Y-m-d H:i:s") : null;

                            // echo '$imagen_principal = ' . $imagen_principal;
                            $SQL = $db->prepare("UPDATE elementos
                                SET
                                    titulo = COALESCE(?, titulo),
                                    contenido = COALESCE(?, contenido),
                                    tipo = COALESCE(?, tipo),
                                    estado = COALESCE(?, estado),
                                    publicado = COALESCE(?, publicado),
                                    imagen_principal = COALESCE(?, imagen_principal)
                                WHERE
                                    id=?");
                            $SQL->bind_param('ssssssi', 
                                $titulo,
                                $dbtenido,
                                $tipo,
                                $estado,
                                $publicado,
                                $imagen_principal,
                                $id
                            );
                            $result = $SQL->execute();
                            if ($result) {
                                $SQL = $db->prepare('SELECT * FROM elementos WHERE id = ' . $id);
                                $SQL->execute();
                                $result = $SQL->get_result();
                                $SQL->free_result();
                                $SQL->close();
                                $row = $result->fetch_array(MYSQLI_ASSOC);
                                $elemento = array_a_elemento($row);
                                $respuesta = $elemento;
                            } else {
                                $respuesta['error'] = 'Error al modificar la noticia. Es nuestra culpa.'; 
                                $respuesta['detalle'] = $SQL->error; 
                            }
                        }
                    } else {
                        $respuesta['error'] = 'No se encuentra el elemento.';
                    }
                } else {
                    $respuesta['error'] = 'Se debe proveer id del elemento.';
                }
            } else {
                $respuesta['error'] = 'Debe proeer usuario y contrasenia del invocante.'; 
            }         
            break;

        case 'consultar':
            $id = isset($_POST['id'])? $_POST['id'] : null;
            if ($id) { 
                $SQL = $db->prepare('SELECT * FROM elementos WHERE id = ' . $id);
                $SQL->execute();
                $result = $SQL->get_result();
                $SQL->free_result();
                $SQL->close();
                if ($result->num_rows > 0) {
                    $row = $result->fetch_array(MYSQLI_ASSOC);
                    $elemento = array_a_elemento($row);
                    switch ($elemento['estado']) {
                        case 'publicado':
                            $respuesta = $elemento;
                            break;

                        case 'pendiente':
                            if (logeado($current_email, $current_pass, $db)) {
                                $respuesta = $elemento;
                            } else {
                                $respuesta['error'] = 'Debe proeer usuario y contrasenia del invocante.'; 
                            }
                            break;

                        case 'borrador':
                            if (logeado($current_email, $current_pass, $db)) {
                                if ($current_email == $elemento['autor']) {
                                    $respuesta = $elemento;
                                } else {
                                    $respuesta['error'] = 'Solo el autor puede acceder a la noticia en estado borrador.'; 
                                }
                            } else {
                                $respuesta['error'] = 'Debe proeer usuario y contrasenia del invocante.'; 
                            }
                            break;
                        case 'archivado':
                            if (logeado($current_email, $current_pass, $db)) {
                                if ($current_email == $elemento['autor']) {
                                    $respuesta = $elemento;
                                } else {
                                    $respuesta['error'] = 'Solo el autor puede acceder a la noticia en estado archivado.'; 
                                }
                            } else {
                                $respuesta['error'] = 'Debe proeer usuario y contrasenia del invocante.'; 
                            }
                            break;
                        default:
                            $respuesta['error'] = 'Ha habido un error. Es nuestra culpa.'; 
                    }
                } else {
                    $respuesta['error'] = 'No se encuentra el elemento.';
                }
            } else {
                $respuesta['error'] = 'Se debe proveer id del elemento.';
            }
            break;

        default:
            $respuesta['errors'] = 'No incluye accion o no es valida';
    }
    header('Content-Type: application/json; charset=utf-8;');
    echo json_encode($respuesta, JSON_UNESCAPED_UNICODE);
}

?>