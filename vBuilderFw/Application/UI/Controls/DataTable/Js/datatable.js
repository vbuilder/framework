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
		el.trigger('dtContentRemoved');
		el.data('dtLoadedContent', false);
	}

	// -------------------------------------------------------------------------

	// Replaces existing content of element with new one
	function replaceContentWith(el, newContent) {
		
		function showNewBlock() {
			el.html('<div style="display: none;" />');
			var newBlock = el.children().first().html(newContent);
			
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
	
	// Opens / closes detail row
	function setDetailOpen(el, args, open, callback) {

		var oTable = el.parents('TABLE').dataTable(),
			nTr = el.parents('TR')[0],
			subrowDiv = $(nTr).next().find('.innerDetails');

		// Close subrow if necessary
		if(oTable.fnIsOpen(nTr) && open == false) {

			$('DIV.innerDetails', $(nTr).next()[0]).slideUp({
				duration: 300,
				complete: function () {
					$(nTr).removeClass('withDetails');
					oTable.fnClose(nTr);

					$(this).trigger('dtContentRemoved');

					if(typeof(callback) == "function")
						callback();
				}
			});

		}

		// Open subrow if necessary
		else if(open == true) {
			if(!oTable.fnIsOpen(nTr)) {
				$(nTr).addClass('withDetails');

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

				if(typeof(callback) == "function")
					callback(subrowDiv);

				subrowDiv.slideDown({ duration: 300 });

				return subrowDiv;

			} else {
				if(typeof(callback) == "function")
					callback(subrowDiv);				
			}
		}

		return subrowDiv;
	}

	// Helper function which show / hides detail row
	function detailButtonClicked(el, args) {
		var oTable = el.parents('TABLE').dataTable(),
			nTr = el.parents('TR')[0],
			subrowDiv = $(nTr).next().find('.innerDetails');

		// Closes the subrow and updates the label
		if(oTable.fnIsOpen(nTr) && subrowDiv.size() && subrowDiv.data('dtLoadedContent') == args.url) {

			setDetailOpen(el, args, false);
		}

		// Opens the subrow
		else {
			var subrowDiv = setDetailOpen(el, args, true, showProcessing);
			console.log(subrowDiv);
			subrowDiv.data('dtLoadedContent', args.url);

			// Scroll to the top of row
			$('html, body').animate({ scrollTop: $(nTr).offset().top }, 'slow');

			subrowDiv.one('dtContentRemoved', function (e) {
				if(el.data('openLabel'))
					el.html(el.data('openLabel'));
			});

			if(args.closeLabel) {
				el.data('openLabel', el.html());
				el.html(args.closeLabel);
			}

			// Load the data
			loadDetail(el, args);
		}
	}


	// -------------------------------------------------------------------------

	// Sets row lock and lock animation
	// TODO: What if the button is in the detail row?
	function setRowLock(trEl, args, locked) {
		var nextRowFirstCellEl = trEl.next().children().first(), 
			lockingTrEl = trEl;

		// If detail row is shown, add him to the locking set
		if(nextRowFirstCellEl.size() > 0 && nextRowFirstCellEl.hasClass('details') && nextRowFirstCellEl.find('.innerDetails').size() > 0) {
			lockingTrEl = lockingTrEl.add(trEl.next());
		}

		if(locked) {

			// Prevent actions on all it's links if row is locked
			// TODO: forms?
			lockingTrEl.find('A').live('click', function (e) {
				if(trEl.data('dtRowUpdateLock') === true) {
					e.preventDefault();
					e.stopImmediatePropagation();
				}
			});

			// Skip if row is already locked
			if(trEl.data('dtRowUpdateLock') === true) return ;
			trEl.data('dtRowUpdateLock', true);

			// Add class to locked row
			lockingTrEl.addClass(args.classLocked);
			
			// Locking animation
			lockingTrEl.animate({
				opacity: args.rowOpacity,
			}, 350, 'swing');

		}

		else {
			if(trEl.data('dtRowUpdateLock') !== true) return ;
			trEl.data('dtRowUpdateLock', false);

			lockingTrEl.removeClass(args.classLocked);

			lockingTrEl.animate({
				opacity: 1.0,
			}, 350, 'swing');
		}
	}

	// Helper function for row update
	function rowUpdateButtonClicked(el, args) {
		var oTable = el.parents('TABLE').dataTable(),
			trEl = el.parents('TR')[0];

		setRowLock($(trEl), args, true);

		$.ajax({
			url: args.url,
			success: function (payload) {
				if(payload.aRowData)
					oTable.fnUpdate(payload.aRowData, trEl, undefined, false, true);

				setRowLock($(trEl), args, false);
			},
			error: function (jqXHR, textStatus, errorThrown) {
				console.error('Error while loading updated row data to DataTable: ' + args.url + ', ' + errorThrown);
				setDetailOpen(el, args, true, function (subrowDiv) {
					showError(subrowDiv, args, undefined);
				});
				

				setRowLock($(trEl), args, false);
			}
		});
	}

	// -------------------------------------------------------------------------

	// Registring dataTableDetailButton as a listener on click event
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

	// Registring dataTableRowUpdateButton as a listener on click event
	$.fn.dataTableRowUpdateButton = function (args) {
	
		var elements = $(this),
			defaults = {
				url: null,
				classLocked: 'locked',
				rowOpacity: 0.4
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
				rowUpdateButtonClicked(el, args2);

				// Prevent the rest
				e.preventDefault();
				e.stopImmediatePropagation();
				return false;
			});

		} else
			console.error("DataTable not loaded");

		return this;
	};

	

	// -------------------------------------------------------------------------

	// Helper function for alowing quick reset of all applied filters
	// 
	// @see http://datatables.net/forums/discussion/997/fnfilter-how-to-reset-all-filters-without-multiple-requests./p1
	// 
	// Usage:
	//   oDataTable.fnResetAllFilters(); 		// Reset and redraw
	//   oDataTable.fnResetAllFilters(false);	// Just reset the filters
	//   
	$.fn.dataTableExt.oApi.fnResetAllFilters = function (oSettings, bDraw/*default true*/) {
        for(iCol = 0; iCol < oSettings.aoPreSearchCols.length; iCol++) {
                oSettings.aoPreSearchCols[ iCol ].sSearch = '';
        }
        oSettings.oPreviousSearch.sSearch = '';
 
        if(typeof bDraw === 'undefined') bDraw = true;
        if(bDraw) this.fnDraw();
	}

})(jQuery);
