(function($) {
	var file_delivery = null;
	file_delivery = {
		droppedDown : false,
		init : function() {
			if (typeof FileActions !== 'undefined') {
				FileActions.register('all', t('fidelapp',
						'Secure file delivery'), OC.PERMISSION_READ, OC
						.imagePath('fidelapp', 'logo_small.png'),
						file_delivery.handleDropDown);
			}
			;
		},
		handleDropDown : function(file) {
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
					file_delivery.attachAutocomplete();
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
		},
		attachAutocomplete : function() {
			$('#fidelapp_shareWith').autocomplete(
					{
						minLength : 1,
						source : function(search, response) {
							$.get(OC.filePath('fidelapp', 'ajax',
									'autocomplete.php'), {
								search : search.term
							}, function(result) {
								if (result.status == 'success'
										&& result.data.length > 0) {
									response(result.data);
								} else {
									response([ t('fidelapp',
											'No matching contacts') ]);
								}
							});
						}
					});
		},
		submitShare : function(event) {
			event.preventDefault();
			if($('#fidelapp_submitLink').hasClass('disabled')) {
				return;
			}
			var itemType = $('#fidelapp_dropdown').data('item-type');
			var itemSource = $('#fidelapp_dropdown').data('item-source');
			var file = $('tr').filterAttr('data-id', String(itemSource)).data(
					'file');
			var shareWith = $('#fidelapp_shareWith').val();
			$('#fidelapp_shareWith').val(t('core', 'Sending ...'));
			$('#fidelapp_submitLink').addClass('disabled');
			$.post(OC.filePath('fidelapp', 'ajax', 'share.php'), {
				action : 'share',
				shareWith : shareWith,
				itemType : itemType,
				itemSource : itemSource,
				file : file
			}, function(result) {
				$('#fidelapp_shareWith').val('');
				if (result && result.status == 'success') {
					alert("Shared");
					var tr = $('tr').filterAttr('data-file', file);
					file_delivery.showDropDown(tr);
				} else {
					OC.dialogs.alert(result.data.message, t('fidelapp',
							'Error while sharing'));
				}
			});
		},
		changeEvent : function(event) {
			var pattern = new RegExp(
					/^[+a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,4}$/i);
			var email = $('#fidelapp_shareWith').val();
			if (pattern.test(email)) {
				$('#fidelapp_submitLink').removeClass('disabled');
			} else {
				$('#fidelapp_submitLink').addClass('disabled');
			}
		}
	};

	$(document)
			.ready(
					function() {
						file_delivery.init();

						// Hide dropdown if click is detected outside the
						// dropdown
						$(this)
								.click(
										function(event) {
											if (file_delivery.droppedDown
													&& $('#fidelapp_dropdown')
															.has(event.target).length === 0) {
												// Avoid closing dropdown on
												// autocomplete events
												if ($(event.target).parents(
														".ui-autocomplete").length === 0)
													file_delivery
															.hideDropDown();
											}
										});
						$('#fileList').on('keyup', '#fidelapp_shareWith',
								file_delivery.changeEvent);
						$('#fileList').on('mouseenter', '#fidelapp_dropdown',
								file_delivery.changeEvent);
						$('#fileList').on('click', '#fidelapp_submitLink',
								file_delivery.submitShare);
					});

})(jQuery);
