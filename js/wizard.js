(function($) {
	var fidelwizard = null;
	fidelwizard = {
			selectedAccessType: function(event) {
				var selection = event.target.id;
				var url = OC.Router.generate('fidelapp_wizard', {
					selection : selection
				});
				$.ajax({
					type : 'GET',
					url : url,
					async : false,
					success : function(html) {
						$('#content').html(html);
					},
					error : function(error) {
						OC.dialogs.alert(error, t('fidelapp',
						'Ajax Error'));
					}
				});
			},
			togglefixedIpOrDomain: function(event) {
				var id = event.target.id;
				if((id == 'fidelapp_fixedIp' && !$('#fidelapp_radio_fixedIp').prop('checked')) || id == 'fidelapp_radio_fixedIp') {
					$('#fidelapp_radio_domainName').removeProp('checked');
					$('#fidelapp_radio_fixedIp').prop('checked', 'true');
					$('#fidelapp_domainName').val('').prop('readonly', 'true');
					$('#fidelapp_fixedIp').removeProp('readonly');
				} else if((id == 'fidelapp_domainName' && !$('#fidelapp_radio_domainName').prop('checked'))  || id == 'fidelapp_radio_domainName') {
					$('#fidelapp_radio_fixedIp').removeProp('checked');
					$('#fidelapp_radio_domainName').prop('checked', 'true');
					$('#fidelapp_fixedIp').val('').prop('readonly', 'true');
					$('#fidelapp_domainName').removeProp('readonly');
				}
			}
	};
	$(document).ready(function() {
		$('#content').on('change', 'input[name=fidelapp_accessType]', fidelwizard.selectedAccessType);
		$('#content').on('click', 'input[name=fidelapp_fixedIpOrDomain], #fidelapp_fixedIp, #fidelapp_domainName', fidelwizard.togglefixedIpOrDomain);
	});
})(jQuery);