(function($) {
	var fidelwizard = null;
	fidelwizard = {
		doSelection : function(event) {
			event.preventDefault();
			var eventId = event.target.id;
			var selection = $('input[name=fidelapp_accessType]:checked').val();
			var url = OC.Router.generate('fidelapp_wizard');
			var accessType = null;
			var action = null;
			var domain = null;
			var fixedIp = null;
			var captcha = null;
			var fidelboxTempUser = null;
			var useSSL = $('#fidelapp_https').prop('checked');

			if (eventId == 'fidelapp_fixedipordomain') {
				var selection2 = $(
						'input[name=fidelapp_fixedIpOrDomain]:checked').val();
				if (!selection2) {
					// None of the radio buttons selected
					return;
				}
				if (selection2 == 'fixedIp') {
					fixedIp = $('#fidelapp_fixedIp').val();
					accessType = 'FIXED_IP';
				} else if (selection2 == 'domainName') {
					domain = $('#fidelapp_domainName').val();
					accessType = 'DOMAIN_NAME';
				}
				action = 'saveDirectAccess';
			} else if (eventId == 'fidelapp_fidelbox') {
				captcha = $('#fidelapp_captcha').val();
				fidelboxTempUser = $('#fidelboxTempUser').val();
				action = 'createFidelboxAccount';
				accessType = 'FIDELBOX_ACCOUNT';
			} else if (eventId == 'fidelapp_fidelbox_delete') {
				action = 'deleteFidelboxAccount';
			} else if(eventId == 'fidelapp_https') {
				action = 'changeSslState';
			}
			$('#fidelapp_errors').hide();
			$('#rightcontent')
					.prepend(
							'<div id="fidelapp_spinner"><img src="'
									+ OC.imagePath('core', 'loader.gif')
									+ '" /></div>');
			$.ajax({
				type : 'GET',
				url : url,
				data : {
					action : action,
					accessType : accessType,
					selection : selection,
					domain : domain,
					fixedIp : fixedIp,
					useSSL : useSSL,
					captcha : captcha,
					fidelboxTempUser : fidelboxTempUser,
					reload : true
				},
				async : false,
				success : function(html) {
					$('#content').html(html);
				},
				error : function(error) {
					OC.dialogs
							.alert(error, t('fidelapp', 'Transmission Error'));
				}
			});
		},
		togglefixedIpOrDomain : function(event) {
			var id = event.target.id;
			if ((id == 'fidelapp_fixedIp' && !$('#fidelapp_radio_fixedIp')
					.prop('checked'))
					|| id == 'fidelapp_radio_fixedIp') {
				$('#fidelapp_radio_domainName').removeProp('checked');
				$('#fidelapp_radio_fixedIp').prop('checked', 'true');
				$('#fidelapp_domainName').val('').prop('readonly', 'true');
				$('#fidelapp_fixedIp').removeProp('readonly');
			} else if ((id == 'fidelapp_domainName' && !$(
					'#fidelapp_radio_domainName').prop('checked'))
					|| id == 'fidelapp_radio_domainName') {
				$('#fidelapp_radio_fixedIp').removeProp('checked');
				$('#fidelapp_radio_domainName').prop('checked', 'true');
				$('#fidelapp_fixedIp').val('').prop('readonly', 'true');
				$('#fidelapp_domainName').removeProp('readonly');
			}
		}
	};
	$(document)
			.ready(
					function() {
						$('#content').on('change',
								'input[name=fidelapp_accessType]',
								fidelwizard.doSelection);
						$('#content')
								.on(
										'click',
										'input[name=fidelapp_fixedIpOrDomain], #fidelapp_fixedIp, #fidelapp_domainName',
										fidelwizard.togglefixedIpOrDomain);
						$('#content')
								.on(
										'click',
										'#fidelapp_fixedipordomain, #fidelapp_fidelbox, #fidelapp_https, #fidelapp_fidelbox_delete',
										fidelwizard.doSelection);
					});
})(jQuery);