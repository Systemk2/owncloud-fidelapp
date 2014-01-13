(function($) {
	var fidelappShares = null;
	fidelappShares = {
		selectActiveShares : function(event) {
			event.preventDefault();
			if($('#fidelapp_activeshares_radio').hasClass('active')) {
				return;
			}
			var url = OC.Router.generate('fidelapp_shares');
			$('#fidelapp_selector').attr('action', url).submit();
		},
		selectReceiptNotices : function(event) {
			event.preventDefault();
			if($('#fidelapp_receiptnotices_radio').hasClass('active')) {
				return;
			}
			var url = OC.Router.generate('fidelapp_receipts');
			$('#fidelapp_selector').attr('action', url).submit();
		}
	};

	$(document).ready(
			function() {
				$('#fidelapp_activeshares_radio').on('click',
						fidelappShares.selectActiveShares);
				$('#fidelapp_receiptnotices_radio').on('click',
						fidelappShares.selectReceiptNotices);
			});

})(jQuery);
