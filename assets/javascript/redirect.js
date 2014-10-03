( function( $ ) {
	"use strict";

	// DOM ready
	$(function() {
		var qpform = $('#quickpay_payment_form');
		if (qpform !== undefined && qpform !== null) {
			setTimeout(function () {
				qpform.submit();
			}, 5000);
		}
	});

})(jQuery);