(function($) {
	var fidelappShares = null;
	fidelappShares = {
		selectActiveShares : function(event) {
			event.preventDefault();
			if ($('#fidelapp_activeshares_radio').hasClass('active')) {
				return;
			}
			var url = OC.Router.generate('fidelapp_shares');
			$('#fidelapp_selector').attr('action', url).submit();
		},
		selectReceiptNotices : function(event) {
			event.preventDefault();
			if ($('#fidelapp_receiptnotices_radio').hasClass('active')) {
				return;
			}
			var url = OC.Router.generate('fidelapp_receipts');
			$('#fidelapp_selector').attr('action', url).submit();
		},
		removeReceipt : function(event) {
			event.preventDefault();
			$(event.target).addClass('fidelapp_spinner').removeClass(
					'fidelapp_delete');
			$.post(OC.filePath('fidelapp', 'ajax', 'deletereceipt.php'), {
				sourceId : event.target.id
			}, function(result) {
				$(event.target).removeClass('fidelapp_spinner').addClass(
						'fidelapp_delete');
				if (result && result.status == 'success') {
					$(event.target).closest('tr').remove();
				} else {
					var message;
					if (result && result.message) {
						message = result.message;
					} else {
						message = result;
					}
					OC.dialogs.alert(message, t('fidelapp',
							'Error while submitting request'));
				}
			});
		}
	};

	$(document)
			.ready(
					function() {
						$('#fidelapp_activeshares_radio').on('click',
								fidelappShares.selectActiveShares);
						$('#fidelapp_receiptnotices_radio').on('click',
								fidelappShares.selectReceiptNotices);
						$('.fidelapp_delete').on('click',
								fidelappShares.removeReceipt);
					});

})(jQuery);
