( function( $ ) {
	"use strict";

	Quickpay.prototype.init = function() {
		// Add event handlers
		this.actionBox.on( 'click', '[data-action]', $.proxy( this.callAction, this ) );
		this.actionBox.on( 'keyup', ':input[name="quickpay_split_amount"]', $.proxy( this.balancer, this ) );
	};

	Quickpay.prototype.callAction = function( e ) {
		e.preventDefault();
		var target = $( e.target );
		var action = target.attr( 'data-action' );

		if( typeof this[action] !== 'undefined' ) {
			var message = target.attr('data-notify') || 'Are you sure you want to continue?';
			if( confirm( message ) ) {
				this[action]();	
			}
		}	
	};

	Quickpay.prototype.capture = function() {
		var request = this.request( {
			quickpay_action : 'capture'
		} );
	};

	Quickpay.prototype.cancel = function() {
		var request = this.request( {
			quickpay_action : 'cancel'
		} );
	};

	Quickpay.prototype.refund = function() {
		var request = this.request( {
			quickpay_action : 'refund'
		} );
	};

	Quickpay.prototype.split_capture = function() {
		var request = this.request( {
			quickpay_action : 'splitcapture',
			amount : parseFloat( $('#quickpay_split_amount').val() ),
			finalize : 0
		} );
	};

	Quickpay.prototype.split_finalize = function() {
		var request = this.request( {
			quickpay_action : 'splitcapture',
			amount : parseFloat( $('#quickpay_split_amount').val() ),
			finalize : 1
		} );
	};

	Quickpay.prototype.request = function( dataObject ) {
		var that = this;
		var request = $.ajax( {
			type : 'POST',
			url : ajaxurl,
			dataType: 'json',
			data : $.extend( {}, { action : 'quickpay_manual_transaction_actions', post : this.postID.val() }, dataObject ),
			beforeSend : $.proxy( this.showLoader, this, true ),
			success : function() {
				$.get( window.location.href, function( data ) {
					var newData = $(data).find( '#' + that.actionBox.attr( 'id' ) + ' .inside' ).html();
					that.actionBox.find( '.inside' ).html( newData );
					that.showLoader( false );
				} );
			}
		} );

		return request;
	};

	Quickpay.prototype.showLoader = function( e, show ) {
		if( show ) {
			this.actionBox.append( this.loaderBox );
		} else {
			this.actionBox.find( this.loaderBox ).remove();
		}
	};

	Quickpay.prototype.balancer = function(e) {
		var remainingField = $('.quickpay-remaining');
		var balanceField = $('.quickpay-balance');
		var amountField = $(':input[name="quickpay_split_amount"]');
		var btnCaptureSplit = $('#quickpay_split_button');
		var btnSplitFinalize = $('#quickpay_split_finalize_button');
		var amount = parseFloat(amountField.val().replace(',','.'));

		if( amount > parseFloat(remainingField.text()) || amount <= 0 || isNaN(amount) || amount == '') {
			amountField.addClass('warning');
			btnCaptureSplit.fadeOut().prop('disabled', true);
			btnSplitFinalize.fadeOut().prop('disabled', true);
		} else {
			amountField.removeClass('warning');
			btnCaptureSplit.fadeIn().prop('disabled', false);
			btnSplitFinalize.fadeIn().prop('disabled', false);
		}
	};

	// DOM ready
	$(function() {
		new Quickpay();
	});

	function Quickpay() {
		this.actionBox 	= $( '#quickpay-payment-actions' );
		this.postID		= $( '#post_ID' );
		this.loaderBox 	= $( '<div class="loader"></div>');
		this.init();
	}

})(jQuery);