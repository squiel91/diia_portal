$(() => banner_remover())

function banner_remover() {
	var banner = $('body').children().last()
	if (banner.css('z-index') == "9999999") {
		banner.remove()
		return true
	} else {
		console.log('No banner to remove.')
		return false
	}
}