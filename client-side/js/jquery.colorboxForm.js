/**
 * AJAX forms inside the Colorbox
 * 
 * @requires ajaxSubmit
 */
(function($){

	$.fn.colorboxForm = function (args) {
	
	var form = $(this);

	var defaults = {
			errorMsg:		"An error has occured. Please try again",
			errorElement:	null,
			onSuccess:		function (data, config) {
				window.location.reload(false);
			},
			onRedirect:		function (data, config) {
				$.colorbox({
					href: data.redirect
				});
			},
			onError:		function (data, config) {
				var msg;
				if (!(msg = data.message)) {
					msg = config.errorMsg;
				}
				if (!config.errorElement) {
					config.errorElement = this.find('.errorBlock');
					if(config.errorElement.find('li:first').length == 0)
						config.errorElement.append($('<li class="text-error">'));
				}
				config.errorElement.find('li:first').html(msg);
				if(config.errorElement.effect) {
					config.errorElement.effect('bounce', {
						times: 3,
						distance: 25,
						direction: 'right',
						mode: 'show'
					}, 500);
				}
			},
			onSpecial:		function (data, config) {
				return (data.error === true || data.success === true || data.redirect);
			}
			
		},
		config = $.extend({}, defaults, args),
		ajaxError = false;;

	form.live('submit', function (e) {
		var $this = $(this),
			ajaxFailed = function (jqXHR, textStatus, errorThrown) {
				ajaxError = true;
				$this.trigger('submit');
			};

		if (!ajaxError) {
			$this.ajaxSubmit(function (data, textStatus, jqXHR) {
				if (data.success === true) {
					config.onSuccess.call($this, data, config);
				} else if (data.redirect) {
					//window.location.href = data.redirect;
					config.onRedirect.call($this, data, config);
				} else if (data.error === true) {
					config.onError.call($this, data, config)
				}
				if (config.onSpecial.call($this, data, config) === false) {
					ajaxFailed();
				}
			}, {
				error: ajaxFailed
			});	
			
			e.stopImmediatePropagation();
			e.preventDefault();
			return false;
		}
	});

	return this;
};

})(jQuery);