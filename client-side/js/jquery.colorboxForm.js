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
						config.errorElement.append($('<li><span class="text-danger" style="display: none;">'));

				}			

				var span = config.errorElement.find('li:first span');
				span.hide().attr('class', 'text-danger');
				span.html(msg);

				span.fadeIn();
			},
			onSpecial:		function (data, config) {
				return (data.error === true || data.success === true || data.redirect);
			}
			
		},
		config = $.extend({}, defaults, args),
		ajaxError = false;;

	// Previously LIVE, do I really need it?
	form.on('submit', function (e) {
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