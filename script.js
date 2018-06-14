var current_email = localStorage['email'] || sessionStorage['email']
var current_pass = localStorage['pass'] || sessionStorage['pass']

var json_element_list = undefined

function parse_query_string() {
  var query = location.search.replace('?', '')
  var vars = query.split("&");
  var query_string = {};
  for (var i = 0; i < vars.length; i++) {
    var pair = vars[i].split("=");
    // If first entry with this name
    if (typeof query_string[pair[0]] === "undefined") {
      query_string[pair[0]] = decodeURIComponent(pair[1]);
      // If second entry with this name
    } else if (typeof query_string[pair[0]] === "string") {
      var arr = [query_string[pair[0]], decodeURIComponent(pair[1])];
      query_string[pair[0]] = arr;
      // If third or later entry with this name
    } else {
      query_string[pair[0]].push(decodeURIComponent(pair[1]));
    }
  }
  return query_string;
}

function opacy(scrolled) {
	var max = 0.6
	var min = 0.25

	var interval = 200
	var corresponding = max - ((scrolled / interval) * (max - min))
	return corresponding > min? corresponding : min 
}

function paralax(scrolled) {
	var ratio = 0.1
	return '-' + (scrolled * ratio) + 'px'
}

var intro_colapsed = true

function toggle_intro () {
	if (intro_colapsed) {
		$('.ocultar_intro').text('Leer menos →')
		$('.adicion_intro').show()
		intro_colapsed = false
	} else {
		$('.ocultar_intro').text('Seguir leyendo →')
		$('.adicion_intro').hide()
		intro_colapsed = true	
	}
}

var login_colapsed = true
function login_panel () {
	if(current_email) {
		window.location = 'area_trabajo.html'
	} else {
		if (login_colapsed) {
			$('#toggle_login').text('Cerrar panel ↑')
			$('#login').show().find('#email').focus()
			login_colapsed = false
		} else {
			$('#login').hide()
			$('#toggle_login').text('Area de trabajo →')
			login_colapsed = true	
		}
	}
}

function set_to_scrolled() {
	  var scrollTop = $(window).scrollTop()
	  var opacity = opacy(scrollTop) 
	  var paralax_top = paralax(scrollTop)
	  $('#parallax_background').css('opacity', opacity).css('top', paralax_top)
}

$(function setup () {
	$('body > div:nth-child(3)').hide()
	$(window).scroll(set_to_scrolled)
	set_to_scrolled()

})
function enter_work_area() {
	var pass = prompt('Ingresa la contraseña. \n\nSi no la tienes habla con uno de los responsables del proyecto.')
	if (pass == '12345678') {
		alert('Ya estas dentro')
	} else {
		if (pass != null && pass != undefined) { 
			alert('Contraseña incorrecta. Intenta nuevamente.')
		}
	}
}

function elemento_el(elemento_json) {
	var id = elemento_json.id
	var titulo = elemento_json.titulo
	var contenido =  elemento_json.contenido
	var contenido_resumido = $('<div>' + contenido + '</div>').text().split('.')[0]

	var elemento_el = $(`<div class="elemento"></div>`)
	if (elemento_json.imagen_principal) {
		elemento_el.append(`<img src="${elemento_json.imagen_principal}" style="width: 100%">`)
	}
	elemento_el.append(
		$(`<h1>${titulo}</h1>`),
		$(`<p class="contenido">${contenido_resumido} <a href="elemento.html?id=${id}">Ver mas →</a></p>`)
	)
	return elemento_el
}

function actualizar() {
	var noticias_el = $('#noticias_disp')
	var actividades_el = $('#actividades_disp')
	var materiales_el = $('#materiales_disp')

	noticias_json = filtrar_tipo('noticia')

	noticias_el.empty()
	if (noticias_json.length > 0) {
		noticias_el.append(
			$('<h2>').text('Noticias'),
			$('<a href="elementos.html?tipo=noticia">ver todas →</a>'),
			elemento_el(noticias_json[0])
		)
	} else {
		noticias_el.hide()
	}

	actividades_json = filtrar_tipo('actividad')
	actividades_el.empty()
	if (actividades_json.length > 0) {
		actividades_el.append(
			$('<h2>').text('Actividades'),
			$('<a href="elementos.html?tipo=actividad">ver todas →</a>'),
			elemento_el(actividades_json[0])
		)
	} else {
		actividades_el.hide()
	}

	materiales_json = filtrar_tipo('material')
	materiales_el.empty()
	if (materiales_json.length > 0) {
		materiales_el.append(
			$('<h2>').text('Materiales'),
			$('<a href="elementos.html?tipo=material">ver todos →</a>'),
			elemento_el(materiales_json[0])
		)
	} else {
		materiales_el.hide()
	}
}

function filtrar_tipo(tipo) {
	json_element_filtered_list = []
	for (json_element of json_element_list) {
		if (tipo != 'todo' && json_element.tipo != tipo) {
			continue
		}
		json_element_filtered_list.push(json_element)
	}
	return json_element_filtered_list
}

$(() => {
	var parametros = parse_query_string();
	var clave = parametros.clave
	if (clave) {
		$('input#email').hide()
		$('#setea_contrasenia').show()
		login_panel()
		$('input#pass').focus()
	}

	$('#submit').click(() => {
		var email = $('input#email').val().trim()
		var pass = $('input#pass').val()
		$.ajax({
			type: "POST",
			url: "api/usuarios.php",
			dataType: "json",
			data: {'email': email, 'pass': pass, accion: 'login', clave: clave},
			success: function (user_status) {
				if (!user_status.error) {
					if (user_status.rol != 'docente') {
						if ($('input#mantener_sesion').prop('checked')) {
							localStorage['email'] = email;
							localStorage['pass'] = pass;
							localStorage['rol'] = user_status.rol;
						} else {
							sessionStorage['email'] = email;
							sessionStorage['pass'] = pass;
							sessionStorage['rol'] = user_status.rol;
						}
						$('#login_error').empty()
						window.location.replace("area_trabajo.html");
					} else {
						window.location = 'plataforma/'
					}
				} else {
					$('#login_error').text(user_status.error)
				}
			},
			error: function () {
				$('#login_error').text("Hubo un error al conectarse con el servidor.")
			}
		})
	})

	$.ajax({
		type: 'POST',
		format: 'json',
		url: 'api/elementos.php',
		data: {accion: 'listar'},
		success: function (data) {
			json_element_list = data
			actualizar()
		},
		error: function() {
			console.log(`No se ha podido conectar con el servidor.`)
		}
	})
})