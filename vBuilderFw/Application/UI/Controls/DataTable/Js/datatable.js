/**
 * AJAX requests to display Nette snippet as a subrow in a DataTable
 * 
 * @requires colorbox
 */
(function($) {

	// Draws the real content
	function showDetail(el, args, data) {
		// TODO: Possibility to override renderer by callback given in input arguments
		var renderedContent = data;

		replaceContentWith(el, renderedContent);
	}

	// Draws a loading message
	function showProcessing(el, args) {
		// TODO: Possibility to override renderer by callback given in input arguments
		var renderedContent = '<div class="loading">Načítám. Prosím čekejte.</div>';

		replaceContentWith(el, renderedContent);
	}

	// Draws an error message
	function showError(el, args, error) {
		// TODO: Possibility to override renderer by callback given in input arguments
		var renderedContent = '<div class="error">Vyskytla se chyba při načítání dat</div>';
		
		replaceContentWith(el, renderedContent);
	}

	// -------------------------------------------------------------------------

	// Replaces existing content of element with new one
	function replaceContentWith(el, newContent) {
		
		function showNewBlock() {
			var newBlock = $('<div style="display: none;" />').html(newContent);
			el.append(newBlock);
			newBlock.fadeIn({
				duration: 150
			});
		}

		if(el.children().size()) {
			var oldBlock = el.children().first();
			oldBlock.fadeOut({
				duration: 200,
				complete: function () {
					oldBlock.remove();
					showNewBlock();
				}
			});
		} else {
			showNewBlock();
		}
	}

	// -------------------------------------------------------------------------

	// Loads snippet data and replaces innerDetails content
	function loadDetail(el, args) {
		var subrowDiv = $(el.parents('TR').next()[0]).find(".innerDetails");

		$.ajax({
			url: args.url,
			success: function (payload) {
				processSnippetResponse(el, args, payload, function (el, snippet) {
					showDetail(subrowDiv, args, snippet);
				});
			},
			error: function (jqXHR, textStatus, errorThrown) {
				console.error('Error while loading snippet to DataTable: ' + args.url + ', ' + errorThrown);
				showError(subrowDiv, args, errorThrown);
			}
		});
	}

	// Processes snippet response data and takes care of redirects
	function processSnippetResponse(el, args, payload, onSuccess) {
		var subrowDiv = $(el.parents('TR').next()[0]).find(".innerDetails");
		
		// We received some snippets
		if(payload.snippets) {
			var htmlData = "No such snippet";

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

			onSuccess(el, htmlData);

			return ;
		}

		// We received redirect request
		else if(payload.redirect) {
			// console.log("Redirecting: " + payload.redirect);

			// TODO: some checking for infinite redirect
			$.ajax({
				url: payload.redirect,
				success: function (payload) {
					processSnippetResponse(el, args, payload, onSuccess);
				},
				error: function (jqXHR, textStatus, errorThrown) {
					console.error('Error while loading snippet to DataTable: ' + payload.redirect + ', ' + errorThrown);
					showError(subrowDiv, args, errorThrown);
				}
			});
		}

		// We didn't received either => error
		else {
			console.error("Received malformed data when requesting snippet from " + args.url);
			console.log(payload);
			showError(subrowDiv, args, "Malformed data");
		}
	}

	// -------------------------------------------------------------------------

	// Helper function which does all the heavy lifting
	function detailButtonClicked(el, args) {
		
		var oTable = el.parents('TABLE').dataTable(),
			nTr = el.parents('TR')[0];

		// Closes the subrow
		if(oTable.fnIsOpen(nTr)) {
			$('DIV.innerDetails', $(nTr).next()[0]).slideUp({
				duration: 300,
				complete: function () {
					oTable.fnClose(nTr);

					if(el.data('openLabel'))
						el.html(el.data('openLabel'));
				}
			});	

		// Opens the subrow with loading message and start the data request
		} else {
			var nDetailsRow = oTable.fnOpen(
				nTr, // row element
				'<div class="innerDetails" style="display: none;">' + '</div>', // HTML content of details subrow
				'details' // class name of subrow TD cell
			);

			// TD in row can't have any vertical padding or the animation suffers
			var paddingTop = $(nDetailsRow).find('TD').css('padding-top'),
				paddingBot = $(nDetailsRow).find('TD').css('padding-bottom');

			$(nDetailsRow).find('TD').css('padding-top', 0);
			$(nDetailsRow).find('TD').css('padding-bottom', 0);

			var subrowDiv = $('DIV.innerDetails', nDetailsRow)
				.css('padding-top', paddingTop)
				.css('padding-bottom', paddingBot);

			showProcessing(subrowDiv);
			subrowDiv.slideDown({ duration: 300 });

			if(args.closeLabel) {
				el.data('openLabel', el.html());
				el.html(args.closeLabel);
			}

			// Load the data
			loadDetail(el, args);
		}
	}

	// -------------------------------------------------------------------------

	// Registring colorboxSnippet as a listener on click event
	$.fn.dataTableDetailButton = function (args) {
	
		var elements = $(this),
			defaults = {
				url: null,
				closeLabel: null
			};

		if(jQuery().dataTable) {
			elements.live('click', function (e) {
				var el = $(this),
					args2 = $.extend({}, defaults, args);

				// URL from href attribute or given string / callback
				if(args2.url == null || args2.url == undefined)
					args2.url = el.attr('href');
				else if(typeof(args2.url) == "function")
					args2.url = args2.url(args2, el);

				// Perform the action
				detailButtonClicked(el, args2);

				// Prevent the rest
				e.preventDefault();
				e.stopImmediatePropagation();
				return false;
			});

		} else
			console.error("DataTable not loaded");

		return this;
	};

})(jQuery);
