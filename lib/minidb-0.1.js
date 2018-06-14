var endpoint = 'api/progress.php';

class MiniDB {
	constructor(classes_load) {
		this.collections = {};
		this.last_checkpoint = 0;
		this.pending = {};
		this.classes = {}
		for (var class_load of classes_load) {
			var class_name = class_load.name;
			this.classes[class_name] = class_load;
			this.collections[class_name] = {};
		}

		if (!('MINIDB' in localStorage)) {
			for (var class_load of classes_load) {
				localStorage[`MINIDB|${class_load.name}`] = "[]";
			}
			localStorage['MINIDB'] = JSON.stringify({
				"last_checkpoint": 0,
				"pending": []
			})
			console.log('No saved instance of MiniDB in localStorage. A new instance is created.')
		} else {
			var settings = JSON.parse(localStorage['MINIDB']);
			this.last_checkpoint = settings.last_checkpoint || 0;
			for (var pending_json of settings.pending) {
				this.pending[pending_json.id] = pending_json;
			}

			for(class_name in this.classes) {
				var ids = JSON.parse(localStorage[`MINIDB|${class_name}`]);
				for (var id of ids) {
					this.collections[class_name][id] = new this.classes[class_name](JSON.parse(localStorage[`MINIDB|${class_name}|${id}`]));
				}
			}
		}
		this.endpoint = endpoint;
		this.after_sync = null;
	}

	add(object, sync) {
		var class_name = object.constructor.name;
		var id = object.id;
        this.collections[class_name][id] = object;

		localStorage[`MINIDB|${class_name}|${id}`] = JSON.stringify(object);
		localStorage[`MINIDB|${class_name}`] = JSON.stringify(Object.keys(this.collections[class_name]));

		if (sync) this.sync(object);
	}


	get(Class, options) {
		var class_name = Class.name;
		// options {id: string, filter: function, order: function, sample: boolean, first: boolean}
		if (!options) {
			options = {};
		}
		if (options.id) {
			if (!(class_name in this.collections)) {
				return undefined
			}
			return this.collections[class_name][options.id];	
		} else {
			if (!options.filter) {
				options.filter = d => true;
			}
			var returning_objs = [];
			for (var id in this.collections[class_name]) {
				var object = this.collections[class_name][id];
				if (options.filter(object)) {
					returning_objs.push(object);
				}
			}
			if (options.order) {
				returning_objs.sort(options.order)
			}
			if (options.sample || options.first) {
				if (returning_objs.length == 0) {
					return null;
				}
				var index = options.sample ? Math.floor(Math.random() * (returning_objs.length)) : 0
				return returning_objs[index];
			} else {
				return returning_objs;
			}
		}
	}

	update(Class, new_fieds) {
		var to_modify = this.get(Class, {id: new_fieds.id});
		if (to_modify) {
			to_modify.update(new_fieds);
			this.add(to_modify)
		} else {
			var new_word = new Word(new_fieds);
			this.add(new_word)
		}
	}

	// data is a list of json that has an id
	sync(objects) {
		if (!objects) {
			objects = [];
		}

		if (!Array.isArray(objects)) {
			objects = [objects];
		}

		for (var object of objects){
			var id = this.encode_id(object.constructor.name, object.id);
			var json_min = object.toJSON(true);
			json_min.id = id;
			this.pending[id] = json_min;
		}

		this.save_localy();

		var list_pending_encoded = [];
		for(var id in this.pending) {
			list_pending_encoded.push(this.pending[id]);
		}

		if (email) {
			$.ajax({
				context: this,
				type: "POST",
				url: this.endpoint,
				dataType: "json",
				data: {
					'email': email, 
					'pass': pass,
					'sincronize_data': JSON.stringify(list_pending_encoded), 
					'last_checkpoint': this.last_checkpoint
				},
				success: function (status) {
					if (status.succesfully) {

						for (var data of status.need_sincronize){
							var collection_id = this.decode_id(data.id);
							var class_name = collection_id[0];
							data.id = collection_id[1];
							// if is not here I must retrive it
							this.update(this.classes[class_name], data)
						}
						for (var sincronized_id of status.sincronized_ids){
							delete this.pending[sincronized_id];
						}

						this.last_checkpoint = status.last_checkpoint;
						this.save_localy();
						if (this.after_sync) this.after_sync(false);
					} else {
						if (this.after_sync) this.after_sync(true);
						console.log('WARNING: Could not send data to sincronize.', status);
					}
				},
				error: function (xhr, status, errorThrown) {
					console.log('WARNING: Server error to sincronize.', status);
					if (this.after_sync) this.after_sync(true); 
				}
			});
		}
	}

	encode_id(class_name, id) {
		return class_name + '|' + id
	}

	decode_id(encoded_id) {
		return encoded_id.split('|');
	}

	save_localy() {
		var list_pending = [];
		for(var id in this.pending) {
			list_pending.push(this.pending[id]);
		}
		localStorage['MINIDB'] = JSON.stringify({
			"last_checkpoint": this.last_checkpoint,
			"pending": list_pending
		})
	}
}