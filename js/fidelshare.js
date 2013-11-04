(function($) {
	var fidelapp = null;
	fidelapp = {
		droppedDown : false,
		init : function() {
			if (typeof FileActions !== 'undefined') {
				FileActions.register('all', t('fidelapp',
						'Secure file delivery'), OC.PERMISSION_READ, OC
						.imagePath('fidelapp', 'logo_small.png'),
						fidelapp.handleDropDown);
			}
			;
		},
		handleDropDown : function(file) {
			var tr = $('tr').filterAttr('data-file', file);
			// Check if drop down is already visible for a different file
			if (fidelapp.droppedDown) {
				if ($(tr).data('id') != $('#fidelapp_dropdown').attr(
						'data-item-source')) {
					fidelapp.hideDropDown(function() {
						$(tr).addClass('mouseOver');
						fidelapp.showDropDown(tr);
					});
				} else {
					fidelapp.hideDropDown();
				}
			} else {
				$(tr).addClass('mouseOver');
				fidelapp.showDropDown(tr);
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
					if (html.indexOf("<!-- fidelapp dropdown -->") != 0)
						return;
					fidelapp.hideDropDown();
					var appendTo = $(tr).find('td.filename');
					$(html).appendTo(appendTo);
					fidelapp.attachAutocomplete();
					fidelapp.droppedDown = true;
					$('#fidelapp_dropdown').closest('td').find('[data-action]')
							.addClass('fidelapp_permanent');
				},
				error : function(error) {
					OC.dialogs.alert(error, t('fidelapp',
					'Ajax error'));
				}
			});
		},
		hideDropDown : function(callback) {
			$('#fidelapp_dropdown').hide(
					'blind',
					function() {
						fidelapp.droppedDown = false;
						if (typeof FileActions !== 'undefined') {
							$('tr').removeClass('mouseOver');
						}
						$('#fidelapp_dropdown').closest('td').find(
								'[data-action]').removeClass(
								'fidelapp_permanent');
						$('#fidelapp_dropdown').remove();
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
			if ($('#fidelapp_submitLink').hasClass('disabled')) {
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
				shareWith : shareWith,
				itemType : itemType,
				itemSource : itemSource,
				file : file
			}, function(result) {
				$('#fidelapp_shareWith').val('');
				if (result && result.status == 'success') {
					var tr = $('tr').filterAttr('data-file', file);
					fidelapp.showDropDown(tr);
				} else {
					var message;
					if (result && result.data && result.data.message) {
						message = result.data.message;
					} else {
						message = result;
					}
					OC.dialogs.alert(message, t('fidelapp',
							'Error while sharing'));
				}
			});
		},
		submitPassword : function(event) {
			event.preventDefault();
			// Find parent element that has a contact id
			var contactId = $(event.target).closest('[data-contact-id]').attr(
					'data-contact-id');
			if (!contactId) {
				return;
			}
			if ($('#fidelapp_passwordSubmitLink_' + contactId).hasClass(
					'disabled')) {
				return;
			}
			$('#fidelapp_passwordSubmitLink_' + contactId).addClass('disabled');
			var itemSource = $('#fidelapp_dropdown').data('item-source');
			var file = $('tr').filterAttr('data-id', String(itemSource)).data(
					'file');
			var password = $('#fidelapp_password_' + contactId).val();
			$('#fidelapp_password_' + contactId).val(t('core', 'Sending ...'));
			$.post(OC.filePath('fidelapp', 'ajax', 'setpassword.php'), {
				contactId : contactId,
				password : password
			}, function(result) {
				if (result && result.status == 'success') {
					var tr = $('tr').filterAttr('data-file', file);
					fidelapp.showDropDown(tr);
				} else {
					var message;
					if (result && result.data && result.data.message) {
						message = result.data.message;
					} else {
						message = result;
					}
					OC.dialogs.alert(message, t('fidelapp',
							'Error while setting password'));
				}
			});
		},
		shareInputChangeEvent : function(event) {
			var pattern = new RegExp(
					/^[+a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,4}$/i);
			var email = $('#fidelapp_shareWith').val();
			if (pattern.test(email)) {
				$('#fidelapp_submitLink').removeClass('disabled');
			} else {
				$('#fidelapp_submitLink').addClass('disabled');
			}
		},
		passwordInputChangeEvent : function(event) {
			var submitLink = $(event.target).siblings(
					'a[id^="fidelapp_passwordSubmitLink_"]');
			if (submitLink.length != 1) {
				return;
			}
			var password = $(event.target).val();
			if (password.trim().length >= 6) {
				submitLink.removeClass('disabled');
			} else {
				submitLink.addClass('disabled');
			}
		},
		handleShareDetails : function(event) {
			var contactId = $(event.target).closest('[data-contact-id]').attr(
					'data-contact-id');
			if (!contactId) {
				return;
			}
			var contactDetailsElement = $('.fidelapp_contact_details[data-contact-id='
					+ contactId + ']');
			if (contactDetailsElement.hasClass('hidden')) {
				contactDetailsElement.removeClass('hidden');
			} else {
				contactDetailsElement.addClass('hidden');
			}
		},
		toggleShareLink : function(event) {
			var contactId = $(event.target).closest('[data-contact-id]').attr(
					'data-contact-id');
			if (!contactId) {
				return;
			}
			var inputElement = $('#fidelapp_shareLink_' + contactId);
			if (event.target.checked) {
				inputElement.removeClass('hidden');
			} else {
				inputElement.addClass('hidden');
			}

		}
	};

	$(document)
			.ready(
					function() {
						fidelapp.init();

						// Hide dropdown if click is detected outside the
						// dropdown
						$(this)
								.click(
										function(event) {
											if (fidelapp.droppedDown
													&& $('#fidelapp_dropdown')
															.has(event.target).length === 0) {
												// Avoid closing dropdown on
												// autocomplete events
												if ($(event.target).parents(
														".ui-autocomplete").length === 0)
													fidelapp.hideDropDown();
											}
										});
						$('#fileList').on('keyup', '#fidelapp_shareWith',
								fidelapp.shareInputChangeEvent);
						$('#fileList').on('mouseenter', '#fidelapp_dropdown',
								fidelapp.shareInputChangeEvent);
						$('#fileList').on('click', '#fidelapp_submitLink',
								fidelapp.submitShare);
						$('#fileList').on('click',
								'a[id^=fidelapp_passwordSubmitLink_]',
								fidelapp.submitPassword);
						$('#fileList').on('keyup',
								'input[id^=fidelapp_password_]',
								fidelapp.passwordInputChangeEvent);
						$('#fileList').on('change', '[id^=fidelapp_showlink_]',
								fidelapp.toggleShareLink);
					});

})(jQuery);
