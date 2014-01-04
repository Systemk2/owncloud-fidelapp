(function($) {
	var fidelappPasswords = null;
	fidelappPasswords = {
		removeContact : function(event) {
			event.preventDefault();
			if ($(event.target).hasClass('disabled')) {
				return;
			}
			var contactId = $(event.target).closest('[data-contact-id]').attr(
					'data-contact-id');
			if (!contactId) {
				return;
			}
			$(event.target).addClass('disabled fidelapp_spinner').removeClass('fidelapp_delete');
			$.post(OC.filePath('fidelapp', 'ajax', 'removecontact.php'), {
				contactId : contactId,
			}, fidelappPasswords.handleAjaxResult);
		},
		submitPassword : function(event) {
			event.preventDefault();
			if ($(event.target).hasClass('disabled')) {
				return;
			}
			// Find parent element that has a contact id
			var contactId = $(event.target).closest('[data-contact-id]').attr(
					'data-contact-id');
			if (!contactId) {
				return;
			}
			$(event.target).addClass('disabled fidelapp_spinner').removeClass('checked');
			var password = $('#fidelapp_password_' + contactId).val();
			$.post(OC.filePath('fidelapp', 'ajax', 'setpassword.php'), {
				contactId : contactId,
				password : password
			}, fidelappPasswords.handleAjaxResult);
		},
		handleAjaxResult : function(result) {
			$('.fidelapp_spinner').each(function() {
				$(this).removeClass('fidelapp_spinner disabled');
				if($(this).is('[id^=fidelapp_passwordSubmit_]')) {
					$(this).addClass('checked');
				} else {
					$(this).addClass('fidelapp_delete');
				}
			});
			if (result && result.status == 'success') {
				if(result.action == 'PASSWORD_CHANGED') {
					$('#fidelapp_password_' + result.contact).val(result.password).attr('data-original-password', result.password);
				} else if(result.action == 'CONTACT_REMOVED') {
					$('[data-contact-id=' +  result.contact + ']').remove();
				}
			} else {
				// Reset displayed passwords to original value
				$('input[id^=fidelapp_password_]').each( function() {
					$(this).val($(this).attr('data-original-password'));
				});
				
				var message;
				if (result && result.message) {
					message = result.message;
				} else {
					message = result;
				}
				OC.dialogs.alert(message, t('fidelapp',
						'Error while submitting request'));
			}
		},
		passwordInputChangeEvent : function(event) {
			var submitLink = $(event.target).siblings(
					'a[id^="fidelapp_passwordSubmit_"]');
			if (submitLink.length != 1) {
				return;
			}
			var password = $(event.target).val();
			if (password.trim().length >= 6 && password != $(event.target).attr('data-original-password')) {
				submitLink.removeClass('disabled');
			} else {
				submitLink.addClass('disabled');
			}
		},

	};

	$(document).ready(
			function() {
				$('#fidelapp_password_table').on('click',
						'a[id^=fidelapp_passwordSubmit_]',
						fidelappPasswords.submitPassword);
				$('#fidelapp_password_table').on('click',
						'a[id^=fidelapp_contactDelete_]',
						fidelappPasswords.removeContact);
				$('#fidelapp_password_table').on('keyup',
						'input[id^=fidelapp_password_]',
						fidelappPasswords.passwordInputChangeEvent);
			});

})(jQuery);
