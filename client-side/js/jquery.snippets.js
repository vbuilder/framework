/**
 * Routines for loading Nette snippets
 *
 * Example:
 *
 * $('BODY').on('click', 'A[data-target="' + $modal.attr('id')+ '"]', function () {
 *		$modal.find('.modal-content').loadSnippet({
 *			url: $(this).attr('href'),
 *			success: function () {
 *				$modal.modal();
 *			}
 *		});
 *
 * 		return false;
 * });
 */
$(function () {

	// Routine for handling errors from jqXHR
	function handleFailedRequest(jqxhr, textStatus, error) {

		var contentType = jqxhr.getResponseHeader('Content-Type');
		if(contentType == null || !contentType.match(/application\/json/i)) {
			console.error("Snippet request returned non-JSON content. Did you forget to call $this->redrawControl()?")
		}

		var err = textStatus + ", " + error;
		console.error("Snippet request failed: " + err);
	}

	// This method is called when the request was successful,
	// and all the snippets has been loaded
	function finish(payload, options) {
		if(options.success) {
			options.success(payload);
		}
	}

	// Routine for injecting any inline JS or CSS
	function injectInlineScripts(payload) {
		if(payload.webFiles) {
			if(payload.webFiles.js)
				$('BODY').append('<script>' + payload.webFiles.js + '</script>');

			if(payload.webFiles.css)
				$('HEAD').append('<style>' + payload.webFiles.css + '</style>');
		}
	}

	// Default options
	function prepareGenericOptions(options, element) {
		var defaults = {
			url: window.location.href,
			data: null,
			element: element,
			success: null
		};

		return $.extend({}, defaults, options);
	}

	$.fn.updateSnippets = function (options) {

		options = prepareGenericOptions(options, $(this));

		var jqxhr = $.getJSON(options.url, options.data, function (payload) {

			if(payload.snippets) {
				for(var i in payload.snippets) {
					options.element.find('#' + i).html(payload.snippets[i]);
				}
			}

			// Inject any inline JS or CSS
			injectInlineScripts(payload);

			// Triggers success event
			finish(payload, options);
		});

		jqxhr.fail(handleFailedRequest);

	};

	$.fn.loadSnippet = function (options) {

		options = $.extend({}, {
			name: null

		}, prepareGenericOptions(options, $(this)));

		var jqxhr = $.getJSON(options.url, options.data, function (payload) {

			if(payload.snippets) {
				for(var i in payload.snippets) {
					if(i == options.name || options.name == null) {
						options.element.html(payload.snippets[i]);
						break;
					}
				}
			}

			// Inject any inline JS or CSS
			injectInlineScripts(payload);

			// Triggers success event
			finish(payload, options);
		});

		jqxhr.fail(handleFailedRequest);

	};

});