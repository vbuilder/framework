$(function() {
	
	//##//##// integerPicker:
	
	var changeAmount = function (input, addend) {
		var num = parseInt(input.val(), 10),
			result = num + addend;
		result >= 0 && input.val(result);
		input.triggerHandler('change');
	}, tmp = {
		integerPickerMore: 1,
		integerPickerLess: -1
	};
	for (var i in tmp) {

		$(document).on('click', '.'+i, ((function (m) {
			return function (e){
				changeAmount($('#'+$(this).attr('data-integerPicker-id')), tmp[m]);
				e.preventDefault();
			}
		})(i)));
	}

	$(document).on('keypress', '.integerPicker', function (e) {
		var $this = $(this),
			goodVal = $this.val();
		window.setTimeout(function () {
			if (!$this.val().match(/^[\d]+$/)) {
				$this.val(goodVal);
				$this.triggerHandler('change');
			}
		}, 5);
	});
});