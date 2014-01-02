/*
 * jQuery plugin - Twitter Bootstrap 3 extension
 * Custom Select for Bootstrap 3

 * Originally by: LisaStoz
 * Based on her version 1.5 from 17 December 2013
 *
 * Copyright 2013 LisaStoz
 */

;
(function($) {

    var plugin_name = "bootstrapSelect";

    function bootstrapSelect(element, settings) {
        this.element = element;
        this._defaults = {
            'button-style': 'btn-default',
            'field-size': '',
            'container-class': '',
            'max-visible-items': 0, /* scrollbars disabled */
            'filter-type': '',
            'filter-start-from': 1,
            'filter-case-sensitive': false,
            'custom-value': 'custom'
        };
        this.settings = $.extend({}, this._defaults, settings);
        this._name = plugin_name;
        this._option_values = [];
        this._currently_selected_option = 0;
        this._currently_highlighted_option = 0;
        this._options_container_height = 0;
        this._options_container_spacing = 0;
        this._option_height = 0;
        this._max_height = 0;
        this._currently_from_top = 0;
        this._currently_in_mode = 0;
        this.init();
    }

    /* ---- */

    bootstrapSelect.prototype = {
        init: function() {

            var _self = this;

            /* validation - begin */

            var allowed_button_styles = new Array('primary', 'info', 'success', 'warning', 'danger', 'default');
            var allowed_field_sizes = new Array('lg', 'sm');
            var allowed_filter_types = new Array('begins', 'contains');

            if ($.inArray(_self.settings['button-style'].replace('btn-', ''), allowed_button_styles) < 0) {
                _self.settings['button-style'] = '';
            }

            if ($.inArray(_self.settings['field-size'].replace('input-', ''), allowed_field_sizes) < 0) {
                _self.settings['field-size'] = '';
            }

            if ($.inArray(_self.settings['filter-type'], allowed_filter_types) < 0) {
                _self.settings['filter-type'] = '';
            }

            _self.settings['container-size'] = '';
            if (_self.settings['field-size'] > '') {
                _self.settings['container-size'] = _self.settings['field-size'].replace('input-', '');
            }

            /* validation - end */

            /* read from existing structure - begin */

            _self.generatedSelect = $(_self.element);

            _self.generatedSelect.find('UL.dropdown-menu LI A').each(function () {
                _self._option_values.push([ $(this).html(), $(this).attr('href'), true]);
            });

            if(_self.generatedSelect.find('INPUT[type=hidden]').val() > '') {
            	var val = _self.generatedSelect.find('INPUT[type=hidden]').val();
                _self.set_value(val);
            }

            /* read from existing structure - end */

            /* preparations for html structure build - begin */
            /*
            var options = $(_self.element).find('option');
            var name = $(_self.element).attr('name');

            var attributes_string = "";
            $(_self.element.attributes).each(function() {
                var attr_value = this.nodeValue;
                if (attr_value !== undefined && attr_value !== false && this.nodeName !== 'name' && this.nodeName !== 'class') {
                    attributes_string = attributes_string + " " + this.nodeName + '="' + attr_value + '"';
                }
            });
			*/
			/* preparations for html structure build - end */

            /* build html structure - begin */
            /*
            var container_size = '';
            if (_self.settings['container-size'] > '') {
                container_size = 'bootstrap-' + _self.settings['container-size'];
            }
            _self.generatedSelect = $('<div class="bootstrap-select input-group ' + _self.settings['container-class'] + ' ' + container_size + '"></div>');
            _self.generatedSelect.append('<input type="text" autocomplete="off" name="' + name + '_label" class="form-control ' + _self.settings['field-size'] + '" ' + attributes_string + '/>');
            _self.generatedSelect.append('<input type="hidden" name="' + name + '" />');
            _self.generatedSelect.append('<div class="input-group-btn "></div>');
            _self.generatedSelect.find('.input-group-btn').append('<button tabindex="-1" type="button" class="btn ' + _self.settings['button-style'] + ' dropdown-toggle"><span class="caret"></span></button>');
            _self.generatedSelect.find('.input-group-btn').append('<ul tabindex="-1" class="pull-right dropdown-menu ' + _self.settings['button-style'].replace('btn-', '') + '"></ul>');
            options.each(function() {
                _self.generatedSelect.find('ul').append('<li ><a tabindex="-1" href="' + $(this).val() + '">' + $(this).html() + '</a></li>');
                if ($(this).is(':selected')) {
                    _self.set_value($(this).val());
                }
                _self._option_values.push([ $(this).html(), $(this).val(), true]);
            });

            $(_self.element).replaceWith(_self.generatedSelect);
            */

            /* build html structure - end */


            /* bind events - start */


            /* mouse click on any of dropdown options */
            _self.generatedSelect.on('click', '.dropdown-menu li a', function(e) {
                e.preventDefault();
                _self.close();
                _self.set_value($(this).attr('href'));

                if(_self._currently_in_mode > 0)
                	_self.generatedSelect.find('input[type="text"]').focus();
            });

            /* text field gains focus */
            _self.generatedSelect.find('input[type="text"]').bind('focus', function() {
            	if(_self._currently_in_mode > 0)
            		return ;

                _self.open();
                if (_self.settings['filter-type'] > '') {
                    /* when typeahead is on, show all values first */
                    _self.show_all_options();
                }
            });

            /* button click opens/closes dropdpwn */
            _self.generatedSelect.find('button').bind('click', function(e) {
                if (_self.is_open()) {
                    _self.close();
                }
                /* only has effect if it is not disabled */
                else if ( !$(this).hasClass('disabled') ) {
                    _self.generatedSelect.find('input[type="text"]').focus();

                    if(_self._currently_in_mode > 0)
                    	_self.open();
                }
            });

            if ( _self.settings['filter-type'] > '' ) {
                /* typeahead functionality */
                _self.generatedSelect.find('input[type="text"]').bind('keyup', function(e) {
                    var control_codes = [9, 13, 38, 40];
                    if (control_codes.indexOf(e.keyCode) === -1) {
                        var inputted_value = $(this).val();
                        if (inputted_value.length >= _self.settings['filter-start-from'] ) {
                            _self.highlight_value('');
                            _self.generatedSelect.find('a').each(function(){
                                if ($(this).html() === '') {
                                    /* empty option should always be available */
                                    $(this).closest('li').show();
                                    _self._option_values[_self.generatedSelect.find('li').index($(this).closest('li'))][2] = true;
                                }
                                else if (_self[_self.settings['filter-type']]($(this).html(), inputted_value))  {
                                    /* options that match filter should be shown */
                                    $(this).closest('li').show();
                                    _self._option_values[_self.generatedSelect.find('li').index($(this).closest('li'))][2] = true;
                                }
                                else {
                                    /* option doesn't match filter */
                                    $(this).closest('li').hide();
                                    _self._option_values[_self.generatedSelect.find('li').index($(this).closest('li'))][2] = false;
                                }
                            });
                        }
                        else {
                            _self.show_all_options();
                        }
                    }
                });

				_self.generatedSelect.find('input[type="text"]').bind('blur', function(e){
                    _self.set_value(_self._option_values[_self._currently_selected_option][1]);
                });
            }

            _self.generatedSelect.find('input[type="text"]').bind('blur', function(e){
            	if(_self._currently_in_mode > 0)
            		_self.set_value($(this).val());
            });

            _self.generatedSelect.find('button').bind('blur', function(e){
                _self.close();
            });

            _self.generatedSelect.find('input[type="text"]').bind('paste keydown', function(e) {


                if (e.keyCode == 38 ) { /* up arrow key */

                	if(!_self.is_open()) {
                		_self.open();
                		return ;
                	}

                    if (_self.settings['filter-type'] > '') {
                        /* only highlight, but not select value when typeahead is on */
                        var found_next_to_highlight = false;
                        while(!found_next_to_highlight) {
                            var index = (_self._option_values.length + (_self._currently_highlighted_option - 1)) % _self._option_values.length;
                            _self.highlight_value(_self._option_values[index][1]);
                            if (_self._option_values[index][2]) {
                                found_next_to_highlight = true;
                            }
                        }
                    }
                    else {
                        _self._currently_selected_option = (_self._option_values.length + (_self._currently_selected_option - 1)) % _self._option_values.length;
                        _self.set_value(_self._option_values[_self._currently_selected_option][1]);
                    }
                }
                else if (e.keyCode == 40) { /* down arrow key */

                	if(!_self.is_open()) {
                		_self.open();
                		return ;
                	}

                    if (_self.settings['filter-type'] > '') {
                        /* only highlight, but not select value when typeahead is on */
                        var found_next_to_highlight = false;
                        while(!found_next_to_highlight) {
                            var index = (_self._currently_highlighted_option + 1) % _self._option_values.length;
                            _self.highlight_value(_self._option_values[index][1]);
                            if (_self._option_values[index][2]) {
                                found_next_to_highlight = true;
                            }
                        }
                    }
                    else {
                        _self._currently_selected_option = (_self._currently_selected_option + 1) % _self._option_values.length;
                        _self.set_value(_self._option_values[_self._currently_selected_option][1]);
                    }
                }
                else if (e.keyCode == 13) { /* enter key */
                    if (_self.settings['filter-type'] > '') {
                        /* when typeahead is on, then value gets selected on enter key only (or click) */
                        _self.set_value(_self.generatedSelect.find('li.active a').attr('href'));
                    }

                    var hasBeenOpen = _self.is_open();
                    _self.close();

                    // In normal mode: loose focus
                    // In custom mode: prevent sending the form (if dropdown has been open)
                    if(_self._currently_in_mode == 0)
                    	_self.generatedSelect.find('input[type="text"]').blur();
                    else if(hasBeenOpen)
                    	e.preventDefault();
                    else
                    	_self.set_value($(this).val());
                }
                else if (e.keyCode === 9) {
                    _self.close();
                }
                else if (e.keyCode !== 9) { /* tab should behave as normal */

                	if(_self._currently_in_mode) {
                		if(_self.is_open())
                			_self.close();

            			return ;
                	}

                    if ( _self.settings['filter-type'] === '' ) {
                        /* only prevent typing if typeahead is off */
                        e.preventDefault(); /* if other than tab keys pressed - prevent typing */

                        /* mimic select html element behaviour
                         * when it has focus and key pressed
                         * tries to match the option starting with that key (letter/number/other symbol) */

                        var starts_with = String.fromCharCode(e.keyCode); /* get the char from pressed key code */
                        var matched_element = false; /* begin with assuming that nothing was matched */
                        var counter = _self._currently_selected_option; /* looking for the matching option starts from current element */
                        while (!matched_element) {
                            counter = (counter + 1) % _self._option_values.length; /* move to the next option, but if end is reached - jump to the beginning */
                            if (counter === _self._currently_selected_option) {
                                /* full loop through the options completed
                                 * stop looking for the right option */
                                matched_element = true;
                            }
                            else if (_self._option_values[counter][0][0] === starts_with) {
                                /* key matched with the option */
                                matched_element = true;
                            }
                        }
                        _self.set_value(_self._option_values[counter][1]);
                    }

                    /* close this select instance when tab pressed */
                }
            });

            /* close dropdown when clicked outside */
            $('html').bind('click', function(e) {
                var inputField = $(_self.generatedSelect).find('input[type="text"]');
                var liOption = $(_self.generatedSelect).find('.dropdown-menu li a');
                var button = $(_self.generatedSelect).find('button');
                var span = $(_self.generatedSelect).find('button span');
                if ((!$(e.target).is(inputField)) && (!$(e.target).is(liOption)) && (!$(e.target).is(button)) && (!$(e.target).is(span))) {
                    _self.close();
                }
            });

            /* bind events - end */

        },
        /* simply highlights option */
        highlight_value: function(new_value) {
            var option = $(this.generatedSelect).find('.dropdown-menu li a[href="' + new_value + '"]').closest('li');
            $(this.generatedSelect).find('.dropdown-menu li').removeClass('active');
            option.addClass('active');
            this._currently_highlighted_option = $(this.generatedSelect).find('li').index(option);

            if (this._max_height > 0) { /* only scroll to selected element, if there is vertical scrollbar */
                var visible_options_before_count = 0;
                for (var i = 0; i < this._currently_highlighted_option; i++)
                {
                    if (this._option_values[i][2]) {
                        visible_options_before_count++;
                    }
                }
                var option_from_top = visible_options_before_count * this._option_height; /* distance between curr selected el and top */
                if ( ( option_from_top < this._currently_from_top ) || ( option_from_top >= (this._currently_from_top + this._max_height - this._options_container_spacing) ) ) {
                    /* only scroll if current option is outside visible area */
                    $(this.generatedSelect).find('.dropdown-menu').scrollTop(option_from_top);
                    this._currently_from_top = option_from_top;
                }
            }

        },
        /* show all options (unfilter) */
        show_all_options: function() {
            this.generatedSelect.find('li').show();
            for (var i = 0; i < this._option_values.length; i++)
            {
                this._option_values[i][2] = true;
            }
        },
        /* select value */
        set_value: function(new_value) {
            var option = $(this.generatedSelect).find('.dropdown-menu li a[href="' + new_value + '"]').closest('li');

            // Custom values
            var customValue = option.length == 0 || new_value == this.settings['custom-value'];
            if(customValue) {
            	option = $(this.generatedSelect).find('.dropdown-menu li a[href="' + this.settings['custom-value'] + '"]').closest('li');
            }

            var old_value = this.get_value();
            this._currently_selected_option = $(this.generatedSelect).find('li').index(option);
            this.highlight_value(option.find('a').attr('href'));

            if(customValue)
            	$(this.generatedSelect).find('input[type="text"]').val(new_value == this.settings['custom-value'] ? '' : new_value);
            else
            	$(this.generatedSelect).find('input[type="text"]').val(option.find('a').html());

            if(new_value != this.settings['custom-value']) $(this.generatedSelect).find('input[type="hidden"]').val(new_value);
            else $(this.generatedSelect).find('input[type="hidden"]').val('');

            if (old_value !== new_value) {
                $(this.generatedSelect).find('input[type="text"]').change();

                if(customValue)
                	this.enter_custom_mode();

                else if(this._currently_in_mode > 0)
                	this.exit_custom_mode();
            }
        },
        /* return selected value */
        get_value: function() {
            return $(this.generatedSelect).find('.dropdown-menu li[class*="active"]').find('a').attr('href');
        },
        /* close dropdown */
        close: function() {
            $(this.generatedSelect).find('.input-group-btn').removeClass('open').removeClass('dropup');
            $(this.generatedSelect).find('.input-group-btn button').removeClass('active');
        },
        /* open dropdown */
        open: function() {
            $(this.generatedSelect).find('.input-group-btn').addClass('open');
            $(this.generatedSelect).find('.input-group-btn button').addClass('active');

            if (this._options_container_height === 0) {
                /* no need to recalc if it has been done once */
                this._options_container_height = $(this.generatedSelect).find('ul').outerHeight(true);
                this._options_container_spacing = this._options_container_height - $(this.generatedSelect).find('ul').height();
                this._option_height = $(this.generatedSelect).find('li').outerHeight(true);
            }
            /* if scrollbar needed - begin */
            if ( (this.settings['max-visible-items'] > 0) && (this._max_height === 0) ) {
                /* no need to recalc if it has been done once */
                this._max_height = this.settings['max-visible-items'] * this._option_height + this._options_container_spacing
                $(this.generatedSelect).find('ul').css('max-height', this._max_height);
            }
            /* if scrollbar need - end */

            /* by default dropdown goes below the text field. check if there is anough space for it
             * otherwise it should go above */
            if (this._options_container_height > ($(document).height() - $(this.generatedSelect).find('ul').offset().top) ) {
                $(this.generatedSelect).find('.input-group-btn').addClass('dropup');
            }
        },
        /* return true/false depending on open/closed state of dropdown */
        is_open: function() {
            return $(this.generatedSelect).find('.input-group-btn').hasClass('open');
        },
        /* disable select entirely */
        disable: function() {
            $(this.generatedSelect).find('input[type="text"]').attr('disabled', 'disabled');
            $(this.generatedSelect).find('button').addClass('disabled');
            this.close();
        },
        /* enable select entirely */
        enable: function() {
            $(this.generatedSelect).find('input[type="text"]').removeAttr('disabled');
            $(this.generatedSelect).find('button').removeClass('disabled');
        },
        /* return select state disabled/enabled */
        is_disabled: function() {
            return $(this.generatedSelect).find('button').hasClass('disabled');
        },
        /* filter function for options starting with typed in value */
        begins: function(search_in, search_for) {
            if (this.settings['filter-case-sensitive'])
                return (search_in.indexOf(search_for) === 0);
            else
                return (search_in.toLowerCase().indexOf(search_for.toLowerCase()) === 0);
        },
        /* filter function for options containing typed in value */
        contains: function(search_in, search_for) {
            if (this.settings['filter-case-sensitive'])
                return (search_in.indexOf(search_for) >= 0);
            else
                return (search_in.toLowerCase().indexOf(search_for.toLowerCase()) >= 0);
        },
        add_option: function(value, label, position) {
            if (typeof position !== 'undefined') {
                if (position > 0) {
                    $('<li><a tabindex="-1" href="'+value+'">'+label+'</a></li>').insertAfter($(this.generatedSelect).find('.dropdown-menu li:nth-child('+position+')'));
                    this._option_values.splice(position, 0, [label, value, true]);
                    /* newly inserted option may push down currently active option */
                    if (position <= this._currently_selected_option ) {
                        this._currently_selected_option++;
                    }
                    if (position <= this._currently_highlighted_option ) {
                        this._currently_highlighted_option++;
                        this._currently_from_top = this._currently_from_to + this._option_height;
                    }
                }
            } else {
                $(this.generatedSelect).find('.dropdown-menu').append($('<li><a href="'+value+'">'+label+'</a></li>'));
                this._option_values.push([label, value, true]);
            }
        },
        remove_option: function(position) {
            $(this.generatedSelect).find('.dropdown-menu li:nth-child('+(position+1)+')').remove();
            this._option_values.splice(position, 1);
            if (position === this._currently_selected_option) {
                this.set_value('');
            }
            else if (position < this._currently_selected_option ) {
                this._currently_selected_option--;
            }
            if (position === this._currently_highlighted_option) {
                this.highlight_value('');
            }
            else if (position < this._currently_highlighted_option ) {
                this._currently_highlighted_option--;
                this._currently_from_top = this._currently_from_to - this._option_height;
            }
        },
        get_option_index_for_value: function (value) {
        	for(var i in this._option_values) {
        		if(this._option_values[i][1] == value)
        			return i;
        	}
        },
        set_mode: function (mode) {
        	this._currently_in_mode = mode;
        },
        enter_custom_mode: function () {
        	var customOptionIndex = this.get_option_index_for_value(this.settings['custom-value']);
        	if(customOptionIndex == undefined || this._currently_in_mode == 1) return ;

        	this.set_mode(1);

			$(this.generatedSelect).prepend('<span class="input-group-addon input-group-addon-custom">' + this._option_values[customOptionIndex][0] + ':</span>');
        },
        exit_custom_mode: function () {
        	$(this.generatedSelect).find('.input-group-addon.input-group-addon-custom').remove();

        	this.set_mode(0);
        }
    };

    /* ---- */

    $.fn[plugin_name] = function(settings) {
        return this.each(function() {

            if (!$.data(this, "plugin_" + plugin_name)) {
                $.data(this, "plugin_" + plugin_name, new bootstrapSelect(this, settings));
            }
        });
    };

})(jQuery);
