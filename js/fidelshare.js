(function($) {
	var file_delivery = null;
	file_delivery = {
		droppedDown : false,
		init : function() {
			if (typeof FileActions !== 'undefined') {
				FileActions.register('all', t('fidelapp',
						'Secure file delivery'), OC.PERMISSION_READ, OC
						.imagePath('fidelapp', 'logo_small.png'),
						file_delivery.share);
			}
			;
		},
		share : function(file) {
			var tr = $('tr').filterAttr('data-file', file);
			// Check if drop down is already visible for a different file
			if (file_delivery.droppedDown) {
				if ($(tr).data('id') != $('#fidelapp_dropdown').attr(
						'data-item-source')) {
					file_delivery.hideDropDown(function() {
						$(tr).addClass('mouseOver');
						file_delivery.showDropDown(tr);
					});
				} else {
					file_delivery.hideDropDown();
				}
			} else {
				$(tr).addClass('mouseOver');
				file_delivery.showDropDown(tr);
			}
		},
		showDropDown : function(tr) {
			var itemSource = $(tr).data('id');
			var itemType = $(tr).data('type');
			if (itemType == 'dir') {
				itemType = 'folder';
			} else {
				itemType = 'file';
			}
			var url = OC.Router.generate('fidelapp_create_dropdown', {
				data_item_source : itemSource,
				data_item_type : itemType
			});
			$.ajax({
				type : 'GET',
				url : url,
				async : false,
				success : function(html) {
					var appendTo = $(tr).find('td.filename');
					$(html).appendTo(appendTo);
					file_delivery.droppedDown = true;
				},
				error : function(error) {
					alert('Error in ajax: ' + error);
				}
			});
		},
		hideDropDown : function(callback) {
			$('#fidelapp_dropdown').hide('blind', function() {
				file_delivery.droppedDown = false;
				$('#fidelapp_dropdown').remove();
				if (typeof FileActions !== 'undefined') {
					$('tr').removeClass('mouseOver');
				}
				if (callback) {
					callback.call();
				}
			});
		}
	};
	$(document).ready(file_delivery.init);
})(jQuery);
