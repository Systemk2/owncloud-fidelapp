(function($) {
	var fidelapp = null;
	fidelapp = {
		icon : OC.imagePath('fidelapp', 'logo_small.png'),
		droppedDown : false,
		file : null,
		init : function() {
			if (typeof FileActions !== 'undefined') {
				FileActions.register('all', t('fidelapp',
						'Secure file delivery'), OC.PERMISSION_READ,
						fidelapp.icon, fidelapp.handleDropDown);
			}
			;
		},
		handleDropDown : function(file) {
			// Check if drop down is already visible for a different file
			if (fidelapp.droppedDown) {
				if (fidelapp.file != null && fidelapp.file != file) {
					fidelapp.hideDropDown(function() {
						fidelapp.file = file;
						fidelapp.showDropDown();
					});
				} else {
					fidelapp.file = file;
					fidelapp.hideDropDown();
				}
			} else {
				fidelapp.file = file;
				fidelapp.showDropDown();
			}
		},
		showDropDown : function() {
			fidelapp.showSpinner();
			var tr = $('tr').filterAttr('data-file', fidelapp.file);
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
					fidelapp.hideSpinner();
					if(typeof html.message != 'undefined') {
						OC.dialogs.alert(html.message, t('fidelapp',
						'Could not create Dropdown'));
						return;
					}
					if (html.indexOf("<!-- fidelapp dropdown -->") != 0) {
						OC.dialogs.alert(html, t('fidelapp',
								'Could not create Dropdown'));
						return;
					}
					fidelapp.hideDropDown();
					$(tr).addClass('mouseOver');
					var appendTo = $(tr).find('td.filename');
					$(html).appendTo(appendTo);
					fidelapp.attachAutocomplete();
					fidelapp.droppedDown = true;
					$('#fidelapp_dropdown').closest('td').find('[data-action]')
							.addClass('fidelapp_permanent');
				},
				error : function(error) {
					fidelapp.hideSpinner();
					var errorMessage = "Unknown error while creating Dropdown";
					try {
						if (typeof error.responseText != 'undefined') {
							errorObject = $.parseJSON(error.responseText);
							errorMessage = errorObject.message;
						} else {
							errorMessage = error.statusText;
						}
					} catch (e) {
						// Ignore
					}
					OC.dialogs.alert(errorMessage, t('fidelapp', 'Error'));
				}
			});
		},
		hideDropDown : function(callback) {
			fidelapp.hideSpinner();
			$('#fidelapp_dropdown').closest('td').find('[data-action]')
					.removeClass('fidelapp_permanent');
			$('#fidelapp_dropdown').closest('tr').removeClass('mouseOver');
			fidelapp.droppedDown = false;
			$('#fidelapp_dropdown').remove();
			if (callback) {
				callback.call();
			}
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
		showSpinner : function() {
			var tr = $('tr').filterAttr('data-file', fidelapp.file);
			$(tr).find('img[src="' + fidelapp.icon + '"]').replaceWith(
					'<img id="fidelapp_spinner" src="'
							+ OC.imagePath('core', 'loader.gif') + '" />');
		},
		hideSpinner : function() {
			$('#fidelapp_spinner').replaceWith(
					'<img src="' + fidelapp.icon + '" />');
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
			fidelapp.showSpinner();
			$.post(OC.filePath('fidelapp', 'ajax', 'share.php'), {
				shareWith : shareWith,
				itemType : itemType,
				itemSource : itemSource,
				file : file
			}, fidelapp.handleAjaxResult);
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
			var password = $('#fidelapp_password_' + contactId).val();
			$('#fidelapp_shareWith').val(t('core', 'Sending ...'));
			fidelapp.showSpinner();
			$.post(OC.filePath('fidelapp', 'ajax', 'setpassword.php'), {
				contactId : contactId,
				password : password
			}, fidelapp.handleAjaxResult);
		},
		handleAjaxResult : function(result) {
			fidelapp.hideSpinner();
			if (result && result.status == 'success') {
				fidelapp.showDropDown();
			} else {
				$('#fidelapp_shareWith').val('');
				var message;
				if (result && result.data && result.data.message) {
					message = result.data.message;
				} else {
					message = result;
				}
				OC.dialogs.alert(message, t('fidelapp',
						'Error while submitting request'));
			}
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
				if($(event.target).prop('readonly')) {
					$(event.target).removeProp('checked');
					return;
				}
				inputElement.removeClass('hidden');
			} else {
				inputElement.addClass('hidden');
			}

		},
		toggleDetails : function(event) {
			var contactId = $(event.target).closest('[data-contact-id]').attr(
					'data-contact-id');
			if (!contactId) {
				return;
			}
			var details = $('#fidelapp_details_' + contactId);
			if (details.hasClass('hidden')) {
				// Hide all other details
				$('[id^=fidelapp_details_]').addClass('hidden');
				details.removeClass('hidden');
			} else {
				details.addClass('hidden');
			}
		},
		removeShare : function(event) {
			event.preventDefault();
			var shareId = $(event.target).closest('[data-share-id]').attr(
					'data-share-id');
			if (!shareId) {
				return;
			}
			$('#fidelapp_shareWith').val(t('core', 'Sending ...'));
			fidelapp.showSpinner();
			$.post(OC.filePath('fidelapp', 'ajax', 'removeshare.php'), {
				shareId : shareId
			}, fidelapp.handleAjaxResult);
		},
		addTooltip : function(event) {
			$(event.target).tipsy({
				gravity : 'n',
				fade : true
			});
		},
		submitDownloadType : function(event) {
			event.preventDefault();
			var shareId = $(event.target).closest('[data-share-id]').attr(
					'data-share-id');
			if (!shareId) {
				return;
			}
			var downloadType = $(event.target).val();
			if (!downloadType) {
				return;
			}
			$('#fidelapp_shareWith').val(t('core', 'Sending ...'));
			fidelapp.showSpinner();
			// Sometimes tooltips are not removed
			$('.tipsy').remove();
			$.post(OC.filePath('fidelapp', 'ajax', 'changedownloadtype.php'), {
				shareId : shareId,
				downloadType : downloadType
			}, fidelapp.handleAjaxResult);
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
						$('#fileList').on('click', '.fidelapp_triangle',
								fidelapp.toggleDetails);
						$('#fileList').on('click', '.fidelapp_delete',
								fidelapp.removeShare);
						$('#fileList').on('keyup',
								'input[id^=fidelapp_password_]',
								fidelapp.passwordInputChangeEvent);
						$('#fileList').on('change', '[id^=fidelapp_showlink_]',
								fidelapp.toggleShareLink);
						$('#fileList').on('mouseenter', '.fidelapp_tooltip',
								fidelapp.addTooltip);
						$('#fileList').on('change',
								'#fidelapp_download_type_div input',
								fidelapp.submitDownloadType);
					});

})(jQuery);
