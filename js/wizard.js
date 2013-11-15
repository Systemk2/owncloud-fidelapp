(function($) {
	var fidelwizard = null;
	fidelwizard = {
		doSelection : function(event) {
			event.preventDefault();
			var eventId = event.target.id;
			var selection = $('input[name=fidelapp_accessType]:checked').val();
			if (!selection) {
				// None of the radio buttons selected
				return;
			}
			var url = OC.Router.generate('fidelapp_wizard');
			var selection2 = null;
			var domainOrIp = null;
			var useSSL = $('#fidelapp_https').prop('checked');
			if (eventId == 'fidelapp_fixedipordomain') {
				selection2 = $('input[name=fidelapp_fixedIpOrDomain]:checked')
						.val();
				if (!selection2) {
					// None of the radio buttons selected
					return;
				}
				if (selection2 == 'fixedIp') {
					domainOrIp = $('#fidelapp_fixedIp').val();
				} else if (selection2 == 'domainName') {
					domainOrIp = $('#fidelapp_domainName').val();
				}
			}
			var captcha = null;
			var fidelboxUser = null;
			if (eventId == 'fidelapp_fidelbox') {
				captcha = $('#fidelapp_captcha').val();
				fidelboxUser = $('#fidelboxUser').val();
			}
			$('#fidelapp_errors').hide();
			$('#rightcontent').prepend('<div id="fidelapp_spinner"><img src="' + OC.imagePath('core', 'loader.gif') + '" /></div>');
			$.ajax({
				type : 'GET',
				url : url,
				data : {
					selection : selection,
					selection2 : selection2,
					domainOrIp : domainOrIp,
					useSSL : useSSL,
					captcha : captcha,
					fidelboxUser : fidelboxUser
				},
				async : false,
				success : function(html) {
					$('#content').html(html);
					if (eventId == 'fidelapp_fixedipordomain' || eventId == 'fidelapp_fidelbox') {
						/*OC.dialogs.info(t('fidelapp',
								'The settings have been saved'), t('fidelapp',
								'Saved'));*/
					}
				},
				error : function(error) {
					OC.dialogs.alert(error, t('fidelapp', 'Ajax Error'));
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
						$('#content').on('click', '#fidelapp_fixedipordomain, #fidelapp_fidelbox',
								fidelwizard.doSelection);
					});
})(jQuery);