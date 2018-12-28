<?php
/**
 * Base view for javascripts.
 * 
 * @author Michal Kliment, Ondřej Fibich
 */

// IDE complementation
if (FALSE): ?><script type="text/javascript"><?php endif

?>
	
// After document load
$(document).ready(function()
{
	/* Form additional functionality ******************************************/
	
	/**
	 * Reload given element from given URL
	 * Due to bug in jQuery element has to be given as selector
	 *
	 * @author Michal Kliment
	 */
	function reload_element (element, url, limit, source_element)
	{
		source_element = source_element || element;
		
		//console.log(source_element);
		
		$.ajax({
			async: false,
			type: 'POST',
			url: url,
			success: function (data){
				
				//console.log(data);
				
				data = $(data).find(source_element);
				
				if (limit != undefined && limit.attr != undefined)
				{	
					data.children().each(function (){
						if (!in_array($(this).attr(limit.attr), limit.values))
							$(this).remove();
					});
				}
				
				//console.log(data.html());
				
				if (data.html() != null)
					$(element).html(data.html());
				
				// jQuery tabs
				$('#tabs, .tabs').tabs();
				
				//console.log($(element).html());
			}
		});
	}
	
	/**
	 * Adds increase and decrease button after an input and set their events
	 * 
	 * @param e			Element to which buttons are added
	 * @param min		Minimal value [optional - default 0]
	 * @param max		Maximal value [optional - default not limited]
	 * @author Ondrej Fibich
	 */
	function input_add_increase_decrease_buttons(e, min, max)
	{
		var a_attrs = { href: '#', style: 'margin: 4px' };
		var decrease = '<?php echo html::image(array('src' => 'media/images/icons/ico_math_minus.gif', 'title' => __('Decrease'))) ?>';
		var increase = '<?php echo html::image(array('src' => 'media/images/icons/ico_math_plus.gif', 'title' => __('Increase'))) ?>';
		
		if (min == undefined || isNaN(min))
		{
			min = 0;
		}
		
		if (max == undefined || isNaN(max))
		{
			max = undefined;
		}
		
		e.addClass('iaidb_loaded'); // do not load again in base
		
		e.after($('<a>').attr(a_attrs).html(decrease).click(function ()
		{
			var v = parseInt(e.val());
			
			if (isNaN(v))
			{
				v = 0;
			}
			
			if (v > min)
			{
				e.val(v - 1);
				e.trigger('keyup');
			}
			
			return false;
		})).after($('<a>').attr(a_attrs).html(increase).click(function ()
		{
			var v = parseInt(e.val());
			
			if (isNaN(v))
			{
				v = 0;
			}
			
			if ((max == undefined) || (v < max))
			{
				e.val(v + 1);
				e.trigger('keyup');
			}
			
			return false;
		}));
	}
	
	/**
	 * Adds zoom in and zoom out buttons after a map and set their events
	 * 
	 * @param e			Element to which buttons are added
	 * @param min		Minimal zoom [optional - default 1]
	 * @param max		Maximal zoom [optional - default not limited]
	 * @author David Raska
	 */
	function map_add_zoom_buttons(e, min, max)
	{
		if (min === undefined || isNaN(min))
		{
			min = 1;
		}
		
		if (max === undefined || isNaN(max))
		{
			max = undefined;
		}
		
		$(e).append('<div class="map_zoom_in" title="<?php echo __('Zoom in') ?>"></div>');
		$(e).append('<div class="map_zoom_out" title="<?php echo __('Zoom out') ?>"></div>');
		
		$(e).find('.map_zoom_in').click(function(){
			var img = $(e).find('img');
			
			var src = img.attr('src');
			var re = new RegExp(/zoom=(\d*)/);
			var zoom = src.match(re);
			
			if (zoom && (max === undefined || zoom[1] < max))
			{
				src = src.replace(re, 'zoom='+(+zoom[1]+1).toString());
				img.attr('src', src);
			}
			
			return false;
		});
		
		$(e).find('.map_zoom_out').click(function(){
			var img = $(e).find('img');
			
			var src = img.attr('src');
			var re = new RegExp(/zoom=(\d*)/);
			var zoom = src.match(re);
			
			if (zoom && (min === undefined || zoom[1] > min))
			{
				src = src.replace(re, 'zoom='+(zoom[1]-1).toString());
				img.attr('src', src);
			}
			
			return false;
		});
	}
	
	$('.gmap').each(function (i, e)
	{
		map_add_zoom_buttons(e, 6, 20);
	});
	
	// sort unordered grids
	$('table').tablesorter();

	// jQuery tabs
	$('#tabs, .tabs').tabs();
	
	// activate increase/decrease buttons
	$('input.increase_decrease_buttons, .increase_decrease_buttons input[type="text"]').each(function (i, e)
	{
		if (!$(e).hasClass('iaidb_loaded'))
		{
			input_add_increase_decrease_buttons($(e));
		}
	});
	
	// dialog with help
	$('.help_hint').unbind('click').bind('click', function ()
	{
		var title = $(this).attr('title');
		
		if (title.length)
		{
			$('<div class="left">' + title + '</div>').dialog({
				title: '<?php echo html::image('/media/images/icons/help_small.png') ?> <?php echo __('Help') ?>',
				modal: true,
				position: ['center', 150]
			});
		}
		
		return false;
	});
	
<?php if (!$nobase): ?>

	// hide loader animation
	$('#loading-overlay').hide();
	
	/* Dialog functionality ***************************************************/
	
	context = $('html');
	
	/**
	 * Main dialogs object, performs all actions with dialogs
	 * 
	 * @var object
	 */
	dialogs = {
		
		/**
		 * Array of all dialogs
		 *
		 * @var array
		 */
		_items: {},
		
		/**
		 * Adds new dialog
		 *
		 * @author Michal Kliment
		 * @param object link
		 * @param object parent
		 * @param boolean isReloadOn
		 */
		add: function (link, parent, isReloadOn)
		{
			var id = str_replace('popup-link-', '', link.attr('id'));
			
			if (this._items[id] == undefined)
				this._items[id] = new Dialog(id, parent, isReloadOn);
			
			return this._items[id];
		},
		
		/**
		 * Gets dialog
		 *
		 * @author Michal Kliment
		 * @param object dialog
		 */
		get: function (dialog)
		{
			var id = str_replace('dialog-', '', dialog.attr('id'));
			
			return this._items[id];
		},
		
		/**
		 * Hides all dialogs
		 *
		 * @author Michal Kliment
		 */
		hide: function ()
		{
			for (var i in this._items)
			{
				this._items[i].hide();
			}
		},
		
		/**
		 * Links onject, performs actions with popup links
		 *
		 * @var object
		 */
		links: {
			
			/**
			 * Count of all popup links
			 *
			 * @var integer
			 */
			_count: 0,
			
			/**
			 * Array of parents
			 *
			 * @var array
			 */
			_parents: {},
			
			/**
			 * Array of children
			 *
			 * @var array
			 */
			_children: {},
			
			/**
			 * Adds new popup link
			 * 
			 * @author Michal Kliment
			 * @param object link
			 * @param object parent
			 */
			add: function (link, parent)
			{
				this._count++;

				link.attr('id', 'popup-link-'+this._count);
				
				this._parents[this._count] = parent;
			},
			
			/**
			 * Gets link by dialog
			 *
			 * @author Michal Kliment
			 * @param object dialog
			 */
			get: function (dialog)
			{
				var id = str_replace('dialog-', '', dialog.attr('id'));
			
				return $("#popup-link-"+id);
			},
			
			/**
			 * Gets parent of link
			 * 
			 * @author Michal Kliment
			 * @param object link 
			 */
			getParent: function (link)
			{
				var id = str_replace('popup-link-', '', link.attr('id'));
				
				return this._parents[id];
			}
		}
	};
	
	/**
	 * Contructor of dialog class, creates new dialog
	 * 
	 * @author Michal
	 * @param integer id
	 * @param object parent
	 * @param boolean isReloadOn
	 */
	function Dialog(id, parent, isReloadOn)
	{
		this.id = id;
		this.parent_context = context;
		
		// jquery object, represents real html element
		this._element = $('<div class="dialog" id="dialog-'+this.id+'"></div>');
		
		// crates dialog
		var pc = this.parent_context;
		this._element.dialog({
			autoOpen: false,
			modal: true,
			position: ['center', 'center'],
			width: 'auto',
			close: function ()
			{
				context = pc;
			}
		});
		
		this._data = '';
		
		// set parent
		this.setParent(parent);
		
		// set reload on
		this.setReloadOn(isReloadOn);
	}
	
	/**
	 * Dialog class prototype
	 *
	 * @author Michal Kliment
	 */
	Dialog.prototype = {
		
		/**
		 * Loads given url and data in dialog
		 *
		 * @author Michal Kliment
		 * @param string url
		 * @param array data
		 */
		load: function (url, data)
		{
			if (url !== undefined)
				this._url = url;
			
			var glue = (this._url.indexOf('?') == -1) ? '?' : '&';
			
			var parent = this;
			
			this._element.html('<div class="loading"></div>');			
			this.setOption("position", ['center', (parseInt(screen.availHeight)-parseInt(this._element.css('height')))/2-250]);
				
			$.ajax({
				async: false,
				type: 'POST',
				url: this._url+glue+'noredirect=1',
				data: data,
				success: function (data)
				{
					if (data.length)
					{
						// detecting returned data's type by first character
						switch (data.charAt(0))
						{
							// result is html
							case '<':
								parent._format = 'html';
								parent._data = $(data).find('#content-padd');
								break;

							// result is json
							case '{':
								parent._format = 'json';
								parent._data = jQuery.parseJSON(data);
								break;
						}
					}
					else
					{
						parent._format = 'text';
						parent._data = data;
					}
				}
			});
			
			if (this._format == 'html')
			{
				// set title of dialog
				this.setOption('title', $("h2:first", this._data).html());
				
				var status = $('<div>').append($(this._data).find('.status-message').clone()).remove().html();

				// remove breadcrumbs and h2
				$(this._data).children('.breadcrumbs, h2, .status-message, .action_logs').remove();

				// remove br only from beginning
				while (true)
				{
					if (!$(this._data).children(':first').is('br'))
						break;

					$(this._data).children(':first').remove();
				}

				$(this._data).find('.popup_link').each(function (){
					dialogs.links.add($(this), parent);
				});

				// load html to dialog
				this._element.html(status + this._data.html());

				this.setOption("position", ['center', (parseInt(screen.availHeight)-parseInt(this._element.css('height')))/2-250]);

				/**
				 * @todo Do it better without global variable
				 */
				context = this._element;
				$.getScript('<?php echo url_lang::base() ?>js/'+str_replace('<?php echo url_lang::base() ?>', '', this._url)+glue+'nobase=1');
				
				this._element.find(".form").validate();
				
				update_select_multiple();
			}
			
		},
	
		/**
		* Shows dialog
		* 
		* @author Michal Kliment
		*/
		show: function ()
		{
			this._element.dialog('open');
		},
		
		/**
		 * Hides dialog
		 *
		 * @author Michal Kliment
		 */
		hide: function ()
		{
			this._element.dialog('close');
			context = this.parent_context;
		},
		
		/**
		 * Sets option for dialog
		 *
		 * @author Michal Kliment
		 */
		setOption: function (key, value)
		{
			this._element.dialog('option', key, value);
		},
		
		/**
		 * Gets option value from dialog
		 *
		 * @author Michal Kliment
		 */
		getOption: function (key)
		{
			this._element.dialog('option', key);
		},
		
		/**
		 * Sets parent for dialog
		 *
		 * @author Michal Kliment
		 */
		setParent: function (parent)
		{
			this._parent = parent;
		},
		
		/**
		 * Gets parent for dialog
		 *
		 * @author Michal Kliment
		 */
		getParent: function ()
		{
			return this._parent;
		},
		
		/**
		 * Gets serialized form data from dialog
		 *
		 * @author Michal Kliment
		 */
		getFormData: function ()
		{	
			$('.right_dropdown:visible').each(function ()
			{
					$('#'+this.id+' option').attr('selected', 'selected');
			});
			
			return this._element.find("form").serializeArray();
		},
		
		/**
		 * Gets format of returned data of dialog
		 *
		 * @author Michal Kliment
		 */
		getFormat: function ()
		{
			return this._format;
		},
		
		/**
		 * Gets returned data of dialog
		 *
		 * @author Michal Kliment
		 */
		getData: function ()
		{
			return this._data;
		},
		
		/**
		 * Gets current URL of dialog
		 *
		 * @author Michal Kliment
		 */
		getUrl: function ()
		{
			return this._url;
		},
		
		/**
		 * Checks if on submit of form, content should reload
		 * 
		 * @author Ondřej Fibich
		 * @return boolean
		 */
		isReloadOn: function ()
		{
			return this._reload_on;
		},
		
		/**
		 * Sets should reload on
		 * 
		 * @author Ondřej Fibich
		 * @param boolean reloadOn
		 */
		setReloadOn: function (reloadOn)
		{
			this._reload_on = (reloadOn === true)
		},
		
		/**
		 * Get link which triggered dialog
		 */
		getLink: function ()
		{
			return $('#popup-link-' + this.id);
		}
	};
	
	// adding popup links
	$('.popup_link').each(function ()
	{
		dialogs.links.add(($(this)));
	});
	
	/**
	 * Opens dialog window
	 *
	 * @author Michal Kliment
	 */
	$('.popup_link').live('click', function()
	{
		if (!jQuery.browser.mobile ||
			(jQuery.browser.mobile && $(this).hasClass('popup-add'))
		   )
		{
			var $this = $(this);
			$('#loading-overlay').show();

			setTimeout(function()
			{
				var url = ($this.attr('href')) ? $this.attr('href') : $this.parent().attr('href');

				// find parent of dialog (another dialog or main page)
				var parent = dialogs.links.getParent($this);

				// create new dialog
				var dialog = dialogs.add($this, parent, !$this.hasClass('isReloadOff'));

				// load url in it
				dialog.load(url);
				
				// init TinyMCE Editors in dialog
				if (window['advancedTinyMCE'] !== undefined)
				{
					tinyMCE.init(advancedTinyMCE);
				}
				
				$('#loading-overlay').hide();
				// no returned data, close dialog and reload parent
				if (dialog.getFormat() != 'html')
				{	
					//var parent = dialog.getParent();
					//
					// parent is main page
					if (parent === undefined)
					{
						// reload content div with new data
						reload_element('#content-padd', window.location.href);
						$('html, body').animate({scrollTop: 0}, 'slow');
					}
					else
						// parent is dialog, reload it
						parent.load();
				}
				else
					// else show dialog
					dialog.show();
			}, 2);

			return false;
		}
	});
	
	/**
	 * Submit of form in dialog
	 *
	 * @author Michal Kliment
	 */
	$('.dialog form:not(.nopopup)').live('submit', function()
	{
		// get dialog
		var dialog = dialogs.get($(this).parent());
		
		var glue = ($(this).attr('action').indexOf('?') == -1) ? '?' : '&';
		
		var url = $(this).attr('action')+glue+'noredirect=1';
		
		// find link which open this dialog
		var link = dialogs.links.get($(this).parent());
		
		// find parent of dialog (another dialog or main page)
		var parent = dialog.getParent();
		
		// do ajax submit of form
		dialog.load(url, dialog.getFormData());
		
		// result is json
		if (dialog.getFormat() == 'json')
		{
			// hide dialog
			dialog.hide();
			
			// get returned data
			var data = dialog.getData();
			
			// find url of parent
			if (parent !== undefined)
				url = parent.getUrl()
			else
				url = window.location.href;
			
			// has been created new object
			if (data.id)
			{
				// link which opens this dialog is link to popup adding
				if (link.hasClass('popup-add'))
				{
					// load drowdown to which we will add new option
					var dropdown = link.prev();
					
					var limit = {};
					
					if (dropdown.attr('multiple') == 'multiple')
					{
						limit = {
							attr : 'value',
							values: []
						}
						
						dropdown.children().each(function ()
						{
							limit.values.push($(this).val())
						});
						
						limit.values.push(data.id);
					}
					
					// reload it (already with new option)
					if (dialog.isReloadOn())
					{
						reload_element('#'+$(dropdown).attr('id'), url, limit);
					}
					
					dropdown.trigger('addOption', data.id);
					
					dropdown.trigger('change');

					// set new option as selected
					dropdown.val(data.id);
					
					//console.log(dropdown.children());

					dropdown.trigger('change');
				}
				else
				{					
					// else redirect to new object
					window.location.href = data.url;
				}
			}
			else
			{	
				// parent is main page
				if (parent === undefined)
				{
					// hide all opened dialogs
					dialogs.hide();
					
					// reload content div with new data
					reload_element('#content-padd', url);
					$('html, body').animate({scrollTop: 0}, 'slow');
				}
				else
					// parent is dialog, we reload it
					parent.load();
			}
		}
		// format is text
		else if (dialog.getFormat() == 'text')
		{
			// hide dialog
			dialog.hide();
			
			// find url of parent
			if (parent !== undefined)
				url = parent.getUrl()
			else
				url = window.location.href;
			
			// parent is main page
			if (parent === undefined)
			{
				// hide all opened dialogs
				dialogs.hide();
					
				// reload content div with new data
				reload_element('#content-padd', url);
				$('html, body').animate({scrollTop: 0}, 'slow');
			}
			else
				// parent is dialog, we reload it
				parent.load();
		}
		
		// prevent real submit
		return false;
	});
	
	/* AJAX fulltext search ***************************************************/

	var r_get = null;

	// function for delay during search
	var delay = (function()
	{
		var timer = 0;
		return function(callback, ms)
		{
			clearTimeout (timer);
			timer = setTimeout(callback, ms);
		};
	})();

	// AJAX search after keypress
	$('#keyword').keypress(function()
	{
		// start search
		delay (function ()
		{
			if ($('#keyword').val().length >=1)
			{
				if (r_get != null)
				{
					r_get.abort();
					r_get = null;
				}
				$('#whisper').html('<img src="<?php echo url::base() ?>media/images/icons/animations/ajax-loader-big.gif" class="ajax-loader-big" />');
				$('#whisper').show('slow');
				
				r_get = $.get('<?php echo url_lang::base() ?>search/ajax/', {
					q: $('#keyword').val() 
				}, function (data)
				{
					$('#whisper').html(data);
					r_get = null;
					r_get = null;
				});
			}
			else
				$('#whisper').hide('slow');

		}, 500);
	});
	
	// trigger search also after on-click action
	$('#keyword').click(function ()
	{
		$('#keyword').trigger('keypress');
	});

	/* Multiple dropdown functionality ****************************************/
	
	/**
	 * @var Variable of values of dropdown
	 * @see dropdown_update_values
	 * @see update_select_multiple
	 */
	var select_multiple = new Array();
	
	/**
	 * Switch values
	 * 
	 * @author Michal Kliment
	 * @see update_select_multiple
	 */
	function dropdown_update_values(key, value, from, to)
	{
		for (var i in select_multiple[from])
		{
			if (select_multiple[from][i]['key'] == key)
				delete select_multiple[from][i];
		}
		
		select_multiple[to].push({'key': key, 'value': value});
	}
	
	/**
	 * AJAX functionality of dividing of select to two parts with swiching
	 * capability of their values. Used at device admins and engeneers and etc.
	 * 
	 * @author Michal Kliment
	 */
	function update_select_multiple()
	{
		$('select[multiple="multiple"]').not('.v, .left_dropdown, .right_dropdown').each(function ()
		{
			var id = this.id;

			var html = $(this).parent().html();
			$(this).parent().html("<table style='margin-top: 15px; margin-bottom: 15px;'><tr><td><select id='"+this.id+"_options'></select></td><td><table style='width:100px;text-align:center;'><tr><td><input title='<?php echo __('Remove items') ?>' style='width: 80px' type=button class='dropdown_button right_dropdown_button' id='"+this.id+"_right_button' value='◄ <?php echo __('Remove') ?>'></td></tr><tr><td>&nbsp;</td></tr><tr><td><input title='<?php echo __('Add items') ?>' style='width: 80px' type=button class='dropdown_button left_dropdown_button' id='"+this.id+"_left_button' value='<?php echo __('Add') ?> ►'></td></tr></table></td><td>"+html+"</td></tr><tr><td><input type=text class='dropdown_button_search' id='"+this.id+"_options_button_search'><input type='button' style='width: 30px;' value='X' class='dropdown_button_search_clear' id='"+this.id+"_options_button_search_clear'></td><td>&nbsp;</td><td><input type=text class='dropdown_button_search' id='"+this.id+"_button_search'><input type='button' style='width: 30px;' value='X' class='dropdown_button_search_clear' id='"+this.id+"_button_search_clear'></td></tr></table>");

			$('#'+this.id).parent().parent().children('th').css('width', '100px');
			$('#'+this.id).addClass('right_dropdown')
			$('#'+this.id).removeClass('required')
			$('#'+this.id).css('width', '250px');

			$('#'+this.id+'_options').css('width', '250px');
			$('#'+this.id+'_options').attr('size', $(this).attr('size'));
			$('#'+this.id+'_options').attr('multiple', 'multiple');
			$('#'+this.id+'_options').addClass('left_dropdown');

			var options = [];
			$('#'+this.id+' option').not(':selected').each(function ()
			{
				options.push('<option value="'+$(this).attr('value')+'">'+$(this).text()+'</option>');
				$(this).remove();
			});
			$('#'+this.id+'_options').html(options.join(''));

			$('#'+this.id+' option').removeAttr('selected');

			select_multiple[id] = new Array();
			select_multiple[id+'_options'] = new Array();

			$('#'+id+' option').each(function ()
			{
				select_multiple[id][select_multiple[id].length] = {'key': $(this).attr('value'), 'value': $(this).html()};
			});

			$('#'+id+'_options option').each(function ()
			{
				select_multiple[id+'_options'][select_multiple[id+'_options'].length] = {'key': $(this).attr('value'), 'value': $(this).html()};
			});
		});
	
	}
	
	// trigger select multiple
	update_select_multiple();
	
	/**
 	 * Add option to multiple select and reload element
	 * 
	 * @author Michal Kliment
	 */
	function multiple_select_add_option(select_id, new_option_id)
	{
		var limit = {
			attr: 'value',
			values: []
		};
		
		$('#'+select_id+'_options').children().each(function ()
		{
			limit.values.push($(this).val());
		});
		
		limit.values.push(new_option_id);
		
		reload_element('#'+select_id+'_options', "<?php echo url_lang::base().url_lang::current(0,1) ?>", limit, '#'+select_id);
	}
        
	/**
	 * Search in multiple
	 * 
	 * @author Ondřej Fibich
	 */
	function multiple_select_search(select_id, search_for)
	{
		// clear
		$('#'+select_id).html('');
		// search
		search_for = strtolower(search_for)
		
		for (var i in select_multiple[select_id])
		{
			if (strtolower(select_multiple[select_id][i]['value']).search(search_for) != -1)
			{
				$('#'+select_id).append('<option value="'+select_multiple[select_id][i]['key']+'">'+select_multiple[select_id][i]['value']+'</option>');
			}
		}
	}
        
	$('select[multiple="multiple"] option').live('dblclick', function ()
	{
		if ($(this).parent().hasClass('right_dropdown'))
		{
			var id = $(this).parent().attr('id');
			$('#'+id+'_options').append('<option value="'+$(this).attr('value')+'">'+$(this).text()+'</option>');
			dropdown_update_values($(this).attr('value'), $(this).text(), id, id+'_options');
		}
		else
		{
			var id = str_replace('_options', '', $(this).parent().attr('id'));
			$('#'+id).append('<option value="'+$(this).attr('value')+'">'+$(this).text()+'</option>');
			dropdown_update_values($(this).attr('value'), $(this).text(), id+'_options', id);
		}
		$(this).remove();
		$('.dropdown_button_search').trigger('keyup');
	});

	$('.dropdown_button').live('click', function ()
	{
		if ($(this).hasClass('right_dropdown_button'))
		{
			var id = str_replace('_right_button', '', this.id);
			$('#'+id+" option:selected").each(function()
			{
				$('#'+id+'_options').append('<option value="'+$(this).attr('value')+'">'+$(this).text()+'</option>');
				dropdown_update_values($(this).attr('value'), $(this).text(), id, id+'_options');
				$(this).remove();
			});
		}
		else
		{
			var id = str_replace('_left_button', '', this.id);
			$('#'+id+"_options option:selected").each(function()
			{
				$('#'+id).append('<option value="'+$(this).attr('value')+'">'+$(this).text()+'</option>');
				dropdown_update_values($(this).attr('value'), $(this).text(), id+'_options', id);
				$(this).remove();
			});
		}
		$('.dropdown_button_search').trigger('keyup');
	});

	$('.dropdown_button_search').live('keyup', function ()
	{
		var id = str_replace('_button_search', '', this.id);
		multiple_select_search(id, $(this).val());
	});

	$('.dropdown_button_search_clear').live('click', function()
	{
		var id = str_replace('_clear', '', this.id);

		$('#'+id).val('').trigger('keyup');
	});

	$('form').submit(function()
	{
		$('form .right_dropdown').each(function ()
		{
			// before submit - show filtered values (fixes #367)
			multiple_select_search(this.id, '');
			// select all fields in right part of multiple field
			$('#'+this.id+' option').attr('selected', true);
		});
	});
	
	/* Send as form ***********************************************************/
	$('.as_form').click(function(){
		$(this).parent().submit();
		return false;
	});

	/* Form helpers ***********************************************************/

	// ip adresses add check due to subnet
	$('#ip_address, input.ip_address').live('keyup', function()
	{
		// test if ip address is complete and valid
		var complete = this.value.match(/^((25[0-5])|(2[0-4][0-9])|(1[0-9][0-9])|([1-9][0-9])|[0-9])\.((25[0-5])|(2[0-4][0-9])|(1[0-9][0-9])|([1-9][0-9])|[0-9])\.((25[0-5])|(2[0-4][0-9])|(1[0-9][0-9])|([1-9][0-9])|[0-9])\.((25[0-5])|(2[0-4][0-9])|(1[0-9][0-9])|([1-9][0-9])|[0-9])$/);
		var subnet = document.getElementById('subnet_id');
		
		// search in diffenerent element (see device add form)
		if (!subnet)
		{
			subnet = $(this).parent().parent().find('select[name^="subnet"]')[0];
		}
		
		if (!subnet)
		{
			return;
		}
			
		// for each subnets from select box
		for (var i = 0; i < subnet.options.length; i++)
		{
			var value = subnet.options[i].value;
			var text = subnet.options[i].text;
		
			if (value != 0)
			{
				var arr = text.split(' ');
				var subnet_address = arr[0].substr(0, arr[0].length-1);

				arr = subnet_address.split('/');
				var address = arr[0];
				var netmask = arr[1];
				var range = Math.pow(2, 32-netmask);

				// ip address is valid and complete
				if (complete != null)
				{
					// test if ip address belongs to this subnet
					if ((ip2long(this.value) > ip2long(address)) &&
						(ip2long(this.value) < (ip2long(address) + range)))
					{
						// choose this
						subnet.selectedIndex = i;
						break;
					}
				}

				//  test if this subnet starts with uncomplete ip address
				if (this.value != '' &&
					address.substr(0, this.value.length).indexOf(this.value)!=-1)
				{
					// choose this
					subnet.selectedIndex = i;
					break;
				}

				// else choose default
				subnet.selectedIndex = 0;
			}
		}
	});

	// fix MAC values in inputs
	$('.mac, .mac_address').live('keyup', function ()
	{
		if ($(this).val().indexOf('-') != -1)
		{
			$(this).val(str_replace('-', ':', $(this).val()));
		}
	});
	
	// functionality of hiding/showing form group
	$('.group-button').live('click', function ()
	{
		var $items = $('.' + $(this).parent().parent().attr('id') + '-items', context);
		var img = 'add';
		var title = '<?php echo __('Show form items') ?>';
		
		if ($items.length && $($items.get(0)).is(':visible'))
		{
			$items.hide();
			$(this).trigger('groupHided');
		}
		else
		{
			$items.show();
			$(this).trigger('groupShowed', this);
			img = 'minus';
			title = '<?php echo __('Hide form items') ?>';
		}
		
		$(this).attr('src', '<?php echo url::base() ?>media/images/icons/ico_'+img+'.gif');
		$(this).attr('title', title);
		$(this).parent().toggleClass('disable');
	});

	// activate date picker on class .date after focus
	$('.date').live('focus', function ()
	{
		var date_input = $(this);
		$(this).datepicker({
			dateFormat:			'yy-mm-dd',
			changeMonth:		true,
			changeYear:			true,
			showOtherMonths:	true,
			selectOtherMonths:	true,
			yearRange:			'c-100:c+100',
			minDate:			date_input.attr('minDate'),
			maxDate:			date_input.attr('maxDate'),
			onClose: function(dateText, inst)
			{
				$(this).trigger('keyup');
			}
		});
	});

	/* Validators *************************************************************/
	
	$.validator.addMethod('ip_address', function(value)
	{
		value = value.replace(new RegExp("\\n", "g"), ",");
		return value == '' || value.match(/^(((25[0-5])|(2[0-4][0-9])|(1[0-9][0-9])|([1-9][0-9])|[0-9])\.((25[0-5])|(2[0-4][0-9])|(1[0-9][0-9])|([1-9][0-9])|[0-9])\.((25[0-5])|(2[0-4][0-9])|(1[0-9][0-9])|([1-9][0-9])|[0-9])\.((25[0-5])|(2[0-4][0-9])|(1[0-9][0-9])|([1-9][0-9])|[0-9]),?)+$/g);
	}, '<?php echo __('Invalid IP address') ?>');
	
	$.validator.addMethod('ip_address_check', function(value, element)
	{
		var ret = false;
		var subnet_id = $(element).parent().find('select[name^="subnet"]').val();
		
		$.ajax({
			url:		'<?php echo url_lang::base() ?>json/ip_address_check',
			async:		false,
			dataType:	'json',
			data:		{ip_address: value, subnet_id: subnet_id},
			success:	function(result)
			{
				if(result.state)
				{
					ret = true;
				}
				else
				{
					$.validator.messages.ip_address_check = result.message;
				}
            }
		});
		
		return ret;
	}, '<?php echo __('IP address already exists.') ?>');

	$.validator.addMethod('cidr', function(value)
	{
		var result = value.match(/^((25[0-5])|(2[0-4][0-9])|(1[0-9][0-9])|([1-9][0-9])|[0-9])\.((25[0-5])|(2[0-4][0-9])|(1[0-9][0-9])|([1-9][0-9])|[0-9])\.((25[0-5])|(2[0-4][0-9])|(1[0-9][0-9])|([1-9][0-9])|[0-9])\.((25[0-5])|(2[0-4][0-9])|(1[0-9][0-9])|([1-9][0-9])|[0-9])\/((3[0-2])|(2[0-9])|(1[0-9])|([0-9]))$/);
		if (!result)
			return false;
		var s = value.split('/');
		return ((ip2long(s[0]) & (0xffffffff<<(32-s[1]) & 0xffffffff)) == ip2long(s[0]));
	}, '<?php echo __('Invalid IP address') ?>');

	$.validator.addMethod('mac_address', function(value)
	{
		return (value == '' || value.match(/^([0-9a-fA-F]{2}:){5}[0-9a-fA-F]{2}$/));
	}, '<?php echo __('Invalid MAC address') ?>');
	
	$.validator.addMethod('mac_address_check', function(value, element)
	{
		var ret = false,
			subnet_id = null,
			ip_id = null;
		
		// for devices/add
		var subnet_dropdown_name = $(element).attr('name').replace('mac', 'subnet');
		var $subnet_dropdown = $('select[name="' + subnet_dropdown_name + '"]');
		
		if ($subnet_dropdown.length)
		{
			subnet_id = $subnet_dropdown.val();
		}
		
		if (subnet_id && value && value.length)
		{
			$.ajax({
				url:		'<?php echo url_lang::base() ?>json/mac_address_check',
				async:		false,
				dataType:	'json',
				data:		{mac: value, subnet_id: subnet_id, ip_address_id: ip_id},
				success:	function(result)
				{
					if(result.state)
					{
						ret = true;
					}
					else
					{
						$.validator.messages.mac_address_check = result.message;
					}
				}
			});
			return ret;
		}
		else
		{ // if subnet is not set -> mac is always correct
			return true;
		}
	}, '<?php echo __('MAC address already exists.') ?>');

	$.validator.addMethod('to_field', function(value)
	{
		var usernames = explode(',', trim(trim(value), ','));
		
		var match = true;
		var index;
		
		for (index = 0; index < usernames.length; index++)
		{
			if (!(trim(usernames[index]).match(<?php echo Settings::get('username_regex') ?>)))
			{
				match = false;
			}
		}
		
		return match;
	}, '<?php echo __('Invalid value, correct format: login, login') ?>');

	$.validator.addMethod('suffix', function(value)
	{
		return value.match(/^\/([^\/]+\/)*$/);
	}, '<?php echo __('Suffix has to start with slash character, has to end with slash character and contains only a-z, 0-9, - and /') ?>');

	$.validator.addMethod('var_sym', function(value)
	{
		return value.match(/^[0-9]{1,10}$/);
	}, '<?php echo __('Bad variable symbol format.') ?>');

	$.validator.addMethod('ulogd_active_count', function(value)
	{
		return value.match(/^[0-9]+(\.[0-9]+)?%?$/);
	}, '<?php echo __('Bad format.') ?>');

	$.validator.addMethod('byte_size', function(value)
	{
		return value.match(/^([0-9]+(\.[0-9]+)?[k|M|G|T]?B)?$/);
	}, '<?php echo __('Bad format.') ?>');
	
	$.validator.addMethod('speed_size', function(value)
	{
		return (value == '' || value.match(/^([0-9]+[k|M|G|T]?)(\/[0-9]+[k|M|G|T]?)?$/i));
	}, '<?php echo __('Bad format.') ?>');
	
	// set up for password checker
	$.validator.passwordRating.messages = {
		'too-short':	'<?php echo __('Too short') ?>',
		'very-weak':	'<?php echo __('Very weak') ?>',
		'weak':			'<?php echo __('Weak') ?>',
		'good':			'<?php echo __('Good') ?>',
		'strong':		'<?php echo __('Strong') ?>'
	}
	
	/* Export dialog **********************************************************
	 
	// @TODO missing export/grid controller
	
	// dialog for export
	var $export_dialog = $('#export-div').dialog({
		autoOpen: false,
		title: '<?php echo __('Export') ?>',
		modal: true,
		position: ['center', 50],
		width: 330,
		heigth: 30
	});
	
	// Export GRID add export button
	$('#grid-label').append(' | <span id="export-grid-button" class="hand"><img src="<?php echo url::base() ?>media/images/icons/grid_action/transfer.png" /> <?php echo __('Export')?></span>');
	
	// export GRID button
	$('#export-grid-button').click(function()
	{
		$('#export-form').attr('action', '<?php echo url_lang::base() ?>export/grid');
		$('#export-form-html').val($('.grid_table').html());
		$('#export-form-filename').val($('#content h2').text());
		
		$export_dialog.dialog('open');
		
		$('#export-form').submit(function ()
		{
			$export_dialog.dialog('close');
		});
	});
	
	/* Trigers and other functionality ****************************************/
	
	// asking before delete action
	$('.delete_link').live('click', function(e)
	{
		var confirm = window.confirm('<?php echo __('Do you really want to delete this record') ?>?');
		
		if (!confirm)
			e.stopImmediatePropagation();
		
		return confirm;
	});
	
	// validate all form
	$('.form').validate({
		errorPlacement: function(error, el)
		{
			// if element has multiple inputs in row, insert error message after
			// second input to prevent destroying layout
			if (el.hasClass('join1') || el.hasClass('join2'))
			{
				error.insertAfter(el.parent().find('.join2'));
			}
			else // insert error after first element
			{
				error.insertAfter(el);
			}
		}
	});

	// gave focus to focus classed objects
	$('.focus').focus();
	
	// auto resize for non WYSIWYG textareas
	$('textarea').not('.wysiwyg').not('.wysiwyg_simple').autoResize();
	
	// trigger autosize by default
	$('textarea.autosize').trigger('keyup');
	
	// Function shows menu and hides content on mobile device
	function cellphone_show_menu()
	{
		$('#menu').slideDown(500, function()
		{
			$('#content').addClass('dispNone');	
		});
	}
	
	// Function hides menu and shows content on mobile device
	function cellphone_hide_menu()
	{
		$('#content').removeClass('dispNone');
		setTimeout(function()
		{
			$('#menu').slideUp(500);
		}, 2);
	}
	
	// Set show and hide menu events on mobile device
	if (jQuery.browser.mobile)
	{
		$('#cellphone_hide_menu').click(function()
		{
			cellphone_hide_menu();
		});
		
		$('#cellphone_show_menu').click(function()
		{
			//hide tooltip
			
			$('#cellphone_menu_tooltip').fadeOut();
			
			if ($('#content').hasClass('dispNone'))
			{
				cellphone_hide_menu();
			}
			else
			{
				cellphone_show_menu();
			}
		});

		// menu tooltip
		if ($.cookie('cellphone_menu_tooltip') != '1')
		{
			$('#cellphone_menu_tooltip').fadeIn(function(){
				$.cookie('cellphone_menu_tooltip', '1', { path: '<?php echo Settings::get('suffix') ?>' });
			});
		}
		
		// hide tooltip
		$('#cellphone_menu_tooltip').click(function()
		{
			$(this).fadeOut();
		});
	}

<?php if ($this->popup): ?>
	/* Pop-up actions *********************************************************/
	
	// set size by content
	var content_div = $('#content');
	var w_width = content_div.width() + parseInt(content_div.css('padding-left'), 10) * 4
				+ parseInt(content_div.css('margin-left'), 10) * 4;
	var w_height = content_div.height() + parseInt(content_div.css('padding-top'), 10) * 3
				+ parseInt(content_div.css('margin-top'), 10) * 3;
	
	window.resizeTo(w_width, w_height);
<?php endif ?>
<?php endif ?>
<?php

/*
 * Display another subviews for this query
 */

foreach ($views as $view)
{
	echo $view;
}

?>
	

});
	
/**
 * Marks all checkboxes name[id]
 */
function mark_all_checkboxs(name, ids)
{
	var val = $('input[name=mark_all]').is(':checked');

	for(i = 0; i < count(ids); i++)
	{
		$('input[name="' + name + '[' + ids[i] + ']"]').attr('checked', val);
	}
}
	
/**
 * Status more info expander (status helper)
 */
function status_exception_expander(anchor)
{
	$(anchor).parent().find('.status-message-exception-body').slideDown('slow');
	$(anchor).hide('slow');
}

/*
 * This function returns the GPS coordinate conversion string in DD to DMS.
 */
function gps_dms(lat, lng)
{
	var flat = parseFloat(lat),
		flng = parseFloat(lng),
		latResult = gps_dms_coord(flat) + ((flat >= 0) ? 'N' : 'S'),
		lngResult = gps_dms_coord(flng) + ((flng >= 0) ? 'E' : 'W');
	return latResult + ' ' + lngResult;
}

function gps_dms_coord(val)
{
	var valDeg, valMin, valSec, result;

	val = Math.abs(val);

	valDeg = Math.floor(val);
	result = valDeg + "°";

	valMin = Math.floor((val - valDeg) * 60);
	result += valMin + "′";

	valSec = Math.round((val - valDeg - valMin / 60) * 3600 * 1000) / 1000;
	result += valSec + '″';

	return result;
}

<?php

/*
 * Display another subviews for this query
 */

foreach ($views_notready as $view)
{
	echo $view;
}
