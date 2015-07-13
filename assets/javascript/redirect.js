( function( $ ) {
	"use strict";

	// DOM ready
	$(function() {
		var qpform = $('#quickpay-payment-form');
		if (qpform.length) {
			setTimeout(function () {
				qpform.submit();
			}, 5000);
		}
	});

})(jQuery);