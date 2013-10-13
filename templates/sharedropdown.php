<div id="fidelapp_dropdown" class="drop" data-item-type="{{ itemType }}"
	data-item-source="{{ itemSource }}">
	<form id="fidelapp_share">
		<div id="fidelap_linkShareWith">
			<input id="fidelapp_shareWith" type="text"
				placeholder="{{ trans('Share with') }}" /> <input
				id="fidelapp_shareButton" type="submit" value="{{ trans('Share') }}" />
		</div>
		<input type="checkbox" name="fidelapp_sendEmail"
			id="fidelapp_sendEmail" value="1" /><label for="fidelapp_sendEmail"
			style="">{{ trans('Send e-Mail now') }}</label>

		<div id="link">
			<div id="fidelapp_linkPass">
				<input id="fidelapp_linkPassText" type="password"
					placeholder="{{ trans('Password') }}" />
			</div>
			<input type="checkbox" name="fidelapp_showPassword"
				id="fidelapp_showPassword" value="1" /><label
				for="fidelapp_showPassword" style="">{{ trans('Send password in e-Mail') }}</label>
		</div>
		<div id="link">
			<input type="checkbox" name="fidelapp_expirationCheckbox"
				id="fidelapp_expirationCheckbox" value="1" /><label
				for="fidelapp_expirationCheckbox">{{ trans('Set expiration date') }}</label>
			<br> <input id="fidelapp_expirationDate" type="text"
				placeholder="{{ trans('Expiration date') }}" style="width: 90%;" />
		</div>
	</form>
</div>
