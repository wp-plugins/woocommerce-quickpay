function quickpay_url_modify() {
	"use strict";
	var amountField = document.getElementById('quickpay_split_amount'),
		amount = amountField.value.replace(',', '.'),
		splitCapture = document.getElementById('quickpay_split_button'),
		splitCaptureFinalize = document.getElementById('quickpay_split_finalize_button'),
		balanceContainer = document.getElementById('quickpay_balance_container'),
		balance = document.getElementById('quickpay_balance').childNodes[0].nodeValue;

	amountField.value = amount;
	splitCapture.setAttribute('href', splitCapture.getAttribute('href').replace(new RegExp("&amount=.*"), "") + '&amount=' + amount);
	splitCaptureFinalize.setAttribute('href', splitCaptureFinalize.getAttribute('href') + '&amount=' + amount);

	if (parseFloat(amount) > parseFloat(balance)) {
		splitCapture.style.display = 'none';
		splitCaptureFinalize.style.display = 'none';
		balanceContainer.style.color = 'red';
	} else if (parseFloat(amount) === parseFloat(balance)) {
		splitCapture.style.display = 'none';
		balanceContainer.style.color = '';
	} else {
		splitCapture.style.display = '';
		splitCaptureFinalize.style.display = '';
		balanceContainer.style.color = '';
	}
}

function notify(message) {
	"use strict";
	var quickpay_split_amount = document.getElementById('quickpay_split_amount'),
		value = quickpay_split_amount === null ? '' : quickpay_split_amount.value;

	if (confirm(message.replace(new RegExp("-AMOUNT-"), value) + ' Are you sure you want to continue?')) {
		return true;
	}

	return false;
}

(function ($) {
	"use strict";
	var qpform = $('#quickpay_payment_form');
	if (qpform !== undefined && qpform !== null) {
		setTimeout(function () {
			qpform.submit();
		}, 5000);
	}
})(jQuery);