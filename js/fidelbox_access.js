(function($) {
	var fidelappconfig = null;
	fidelappconfig = {
		doSelection : function(event) {
			event.preventDefault();
			var eventId = event.target.id;
			var selection = $('input[name=fidelapp_accessType]:checked').val();
			var url = OC.Router.generate('fidelapp_appconfig');
			var domain = null;
			var fixedIp = null;
			var port = null;
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
					url = OC.Router.generate('fidelapp_appconfig_fixed_ip');

				} else if (selection2 == 'domainName') {
					domain = $('#fidelapp_domainName').val();
					url = OC.Router.generate('fidelapp_appconfig_domain_name');
				}
			} else if (eventId == 'fidelapp_fidelbox_delete') {
				url = OC.Router
						.generate('fidelapp_appconfig_delete_fidelbox_account');
			} else if (eventId == 'fidelapp_https') {
				url = OC.Router.generate('fidelapp_appconfig_ssl');
			} else if (eventId == 'fidelapp_non_standard_port_check') {
				url = OC.Router.generate('fidelapp_appconfig_port');
			} else if (selection == 'accessTypeFidelbox') {
				url = OC.Router.generate('fidelapp_appconfig_redirect');
			} else if (selection == 'accessTypeDirect') {
				// We do not know which of fixed IP or domain name to chose
				// Just take the first one
				url = OC.Router.generate('fidelapp_appconfig_fixed_ip');
			} 
			if ($('#fidelapp_non_standard_port_check').prop('checked')) {
				port = $('#fidelapp_port').val();
			} else {
				port = 'STANDARD_PORT';
			}
			$('#fidelapp_errors').hide();
			$('#rightcontent').prepend(
					'<div id="fidelapp_spinner"><img src="'
							+ OC.imagePath('core', 'loading.gif')
							+ '" /></div>');
			$.ajax({
				type : 'GET',
				url : url,
				data : {
					domain : domain,
					fixedIp : fixedIp,
					port : port,
					useSSL : useSSL,
					ajax : true
				},
				async : false,
				success : function(html) {
					$('#content').html(html);
				},
				error : function(error) {
					$('#fidelapp_spinner').remove();
					var errorMessage = '';
					try {
						if(typeof error.statusText != 'undefined') {
							errorMessage += 'Status: ' + error.statusText + ' ';
						}
						if (typeof error.responseText != 'undefined') {
							errorObject = $.parseJSON(error.responseText);
							errorMessage += 'Message: ' + errorObject.message;
						}
					} catch (e) {
						// Ignore
					}
					OC.dialogs
							.alert(errorMessage, t('fidelapp', 'Error while executing action ') + url);
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
				$('#fidelapp_domainName').prop('readonly', 'true');
				$('#fidelapp_fixedIp').removeProp('readonly');
			} else if ((id == 'fidelapp_domainName' && !$(
					'#fidelapp_radio_domainName').prop('checked'))
					|| id == 'fidelapp_radio_domainName') {
				$('#fidelapp_radio_fixedIp').removeProp('checked');
				$('#fidelapp_radio_domainName').prop('checked', 'true');
				$('#fidelapp_fixedIp').prop('readonly', 'true');
				$('#fidelapp_domainName').removeProp('readonly');
			}
		},
		toggleReadonlyState : function(event) {
			var pattern = new RegExp(/^[0-9]{2,5}$/i);
			var port = $('#fidelapp_port').val();
			$('#fidelapp_non_standard_port_check').removeAttr('checked');
			if (pattern.test(port)) {
				$('#fidelapp_non_standard_port_check').removeAttr('disabled');
			} else {
				$('#fidelapp_non_standard_port_check').attr('disabled', 'true');
			}
		}
	};
	$(document)
			.ready(
					function() {
						$('#content').on('change',
								'input[name=fidelapp_accessType]',
								fidelappconfig.doSelection);
						$('#content')
								.on(
										'click',
										'input[name=fidelapp_fixedIpOrDomain], #fidelapp_fixedIp, #fidelapp_domainName',
										fidelappconfig.togglefixedIpOrDomain);
						$('#content')
								.on(
										'click',
										'#fidelapp_fixedipordomain, #fidelapp_fidelbox, #fidelapp_https, #fidelapp_non_standard_port_check, #fidelapp_fidelbox_delete',
										fidelappconfig.doSelection);
						$('#content').on('keyup', '#fidelapp_port',
								fidelappconfig.toggleReadonlyState);
					});
})(jQuery);