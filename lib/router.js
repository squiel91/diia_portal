class Menu {
	constructor () {
		var menu = this
		this.menu_button = $('#open_menu').click(() => menu.toggle()); 
		this.menu_el = $('#menu');
		this.menu_layer = $('#menu_layer').hide().click(() => menu.close())
		this.collapsed = true;
	}

	toggle() {
		if (this.collapsed) {
			this.open()
		} else {
			this.close()
		}
	}

	open() {
		this.menu_el.animate({right: '0pt'}, 200);
		this.menu_layer.fadeIn(200);
		this.collapsed = false
	}

	close() {
		this.menu_el.animate({right: '-200pt'}, 100);
		this.menu_layer.fadeOut(100);
		this.collapsed = true
	}
}

class Tab {
	constructor(name, router) {
		this.view_stack = []
		this.el = $('#' + name)
		this.current_view = null
		this.router = router
	}

	stack(new_view, base) {
		if (base) {
			this.view_stack = []
			this.el.empty()
		} else {
			this.view_stack.push(this.el.children().detach())	
		}
		this.current_view = new_view
		this.el.append(new_view)
		if (this.view_stack.length > 0) this.router.back(true)
	}

	unstack () {
		this.current_view = this.view_stack.pop();
		// this.el.children().detach(); // here I should do a simple empty
		this.el.empty().append(this.current_view);
		if (this.view_stack.length == 0) this.router.back(false)
	}

	display(show) {
		if(show) {
			this.el.show()
		} else {
			this.el.hide()
		}
		if (this.view_stack.lenght > 0) this.router.back(true) 
		else this.router.back(false) 
	}
}

class Router {
	constructor(tab_names) {
		this.menu = new Menu()

		this.tabs = {}
		for (var tab_name of tab_names) {
			this.tabs[tab_name] = new Tab(tab_name, this)
		}

		this.back_el = $('#back').click(() => this.unstack()).hide();
		
		this.change(tab_names[0]);
	}

	change(tab_name) {
		if (this.current_tab) this.current_tab.display(false)
		this.current_tab = this.tabs[tab_name]
		this.current_tab.display(true)
		this.menu.close();
	}

	back(show) {
		if(show) {
			this.back_el.show()
		} else {
			this.back_el.hide()
		}
	}

	on(tab_name) {
		return this.tabs[tab_name]
	}

	stack(new_view, base) {
		this.current_tab.stack(new_view, base)
	}

	unstack () {
		this.current_tab.unstack()
	}
}