/**
 * AJAX requests to display Nette snippet in Colorbox
 * 
 * @requires colorbox
 */
(function($){

	$.fn.colorboxSnippet = function (args) {
	
		var elements = $(this),
			defaults = {
				url: null,
				snippet: null,
				width: null,
				height: null,
			},
			args = $.extend({}, defaults, args);

		if(jQuery().colorbox) {
				elements.live('click', function (e) {

					var el = $(this),
						ajaxUrl = null;
					
					if(args.url == null || args.url == undefined) {
						ajaxUrl = el.attr('href');
					} else {
						if(typeof(args.url) == "function")
							ajaxUrl = args.url(el, args);
						else
							ajaxUrl = args.url;
					}

					if(ajaxUrl === false) return ;

					if (ajaxUrl == undefined || ajaxUrl == '') {
						console.error("No URL given for colorboxSnippet");
						return ;
					}

					// Remove previous cbox content
					$('#cboxLoadedContent').empty();

					// Show colorbox preloading
					$.colorbox({
						open: true,
						initialWidth: args.width == null ? 500 : args.width,
						initialHeight: args.height == null ? 300 : args.height,
						width: args.width,
						height: args.height
					});

					$.ajax({
						url: ajaxUrl,
						success: function (payload) {

							var htmlData = "No such snippet";

							if(payload.snippets) {
								if(args.snippet == null) {
	            					for(var i in payload.snippets) {
	            						htmlData = payload.snippets[i];
	            						break;
	            					}
            					} else {
            						var defaultPrefix = 'snippet-';
            						if(args.snippet.slice(0, defaultPrefix.length) != defaultPrefix)
            							args.snippet = defaultPrefix + args.snippet;

            						if(payload.snippets[args.snippet])
										htmlData = payload.snippets[args.snippet];
								}
            				}

            				// TODO: Some random id
            				var inlineElId = 'cboxInlineContent-123';

            				$("BODY").append("<div id=\"" + inlineElId + "\" style=\"display: none;\"/>");

            				var inlineEl = $('#' + inlineElId);
            				inlineEl.html(htmlData);

							$.colorbox({
								inline: true,
								href: inlineEl.children(),
								onComplete: function () {
									inlineEl.remove();
								},

								// We cannot use colorbox HTML property, because colorbox
								// does not call the right load events :-( (on ready, etc...)
								// Workaround by creating an inline element so we don't have to bother
								// with .live() events.
								// html: htmlData,
								
								width: $('#colorbox').outerWidth(),
								height: $('#colorbox').outerHeight()
							});
						},
						error: function (jqXHR, textStatus, errorThrown) {
							console.error('Error while loading snippet to colorbox: ' + ajaxUrl + ', ' + errorThrown);

							// TODO: Some nice looking translatable message
							var htmlData = '<div style="padding: 40px; text-align: center;">Error while loading content</div>';

							$.colorbox({
								html: htmlData
							});
						}
					});

					e.preventDefault();
					e.stopImmediatePropagation();
					return false;
				});


		} else
			console.error("Colorbox not loaded");

		return this;
	};

})(jQuery);
