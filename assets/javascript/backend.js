( function( $ ) {
	"use strict";

	QuickPay.prototype.init = function() {
		// Add event handlers
		this.actionBox.on( 'click', '[data-action]', $.proxy( this.callAction, this ) );
		this.actionBox.on( 'keyup', ':input[name="quickpay_split_amount"]', $.proxy( this.balancer, this ) );
	};

	QuickPay.prototype.callAction = function( e ) {
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

	QuickPay.prototype.capture = function() {
		var request = this.request( {
			quickpay_action : 'capture'
		} );
	};

	QuickPay.prototype.cancel = function() {
		var request = this.request( {
			quickpay_action : 'cancel'
		} );
	};

	QuickPay.prototype.refund = function() {
		var request = this.request( {
			quickpay_action : 'refund'
		} );
	};

	QuickPay.prototype.split_capture = function() {
		var request = this.request( {
			quickpay_action : 'splitcapture',
			amount : parseFloat( $('#quickpay_split_amount').val() ),
			finalize : 0
		} );
	};

	QuickPay.prototype.split_finalize = function() {
		var request = this.request( {
			quickpay_action : 'splitcapture',
			amount : parseFloat( $('#quickpay_split_amount').val() ),
			finalize : 1
		} );
	};

	QuickPay.prototype.request = function( dataObject ) {
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

	QuickPay.prototype.showLoader = function( e, show ) {
		if( show ) {
			this.actionBox.append( this.loaderBox );
		} else {
			this.actionBox.find( this.loaderBox ).remove();
		}
	};

	QuickPay.prototype.balancer = function(e) {
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
    
    
    QuickPayAjaxStatus.prototype.init = function() {
        var self = this;
        if (this.elements.length) {
            this.elements.each(function() {
                var $this = $(this);

                var data = {};
                
                data['quickpay-transaction-id'] = $this.data('quickpay-transaction-id');
                data['quickpay-post-id'] = $this.data('quickpay-post-id');

                self.ajaxGetTransaction(data).done(function(transactionData){
                    self.handleTransactionData($this, transactionData);
                });
         
            });
        }
    };
    
    QuickPayAjaxStatus.prototype.handleTransactionData = function($element, transactionData) {
        $.each(transactionData, function(key, data) {
            var contentElement = $element.find('[data-quickpay-show="' + key + '"]');
                contentElement.text(data.value);
            
            if (data.hasOwnProperty('attr')) {
                $.each(data.attr, function(attr, attrValue) {
                    if(!!contentElement.attr(attr)) {
                        attrValue += ' ' + contentElement.attr(attr);  
                    }
                    contentElement.attr(attr, attrValue); 
                });
            }
            
            $element.removeClass('quickpay-loader');
        });
    };
    
    QuickPayAjaxStatus.prototype.ajaxGetTransaction = function(data) {
        var promise = $.Deferred();
        $.getJSON(ajaxurl, $.extend( {}, { action : 'quickpay_get_transaction_information' }, data ), function(response) {
           promise.resolve(response); 
        });
        return promise;
    };
    
	// DOM ready
	$(function() {
		new QuickPay().init();
        new QuickPayAjaxStatus().init();
	});

	function QuickPay() {
		this.actionBox 	= $( '#quickpay-payment-actions' );
		this.postID		= $( '#post_ID' );
		this.loaderBox 	= $( '<div class="loader"></div>');
	}
    
    function QuickPayAjaxStatus() {
        this.elements = $( '[data-quickpay-transaction-id]' );
    }

})(jQuery);