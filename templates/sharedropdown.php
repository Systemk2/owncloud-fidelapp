<!--  <form id="fidelapp_emailPrivateLink" >  -->
<div id="fidelapp_dropdown" class="drop" data-item-type="{{ itemType }}"
	data-item-source="{{ itemSource }}">
	<input id="fidelapp_shareWith" type="text"
		placeholder="{{ trans('Share with') }}" />
		<input id="fidelapp_emailButton" type="submit" value="{{ trans('Send e-Mail') }}" />
	<div id="link">
		<div id="fidelapp_linkPass">
			<input id="fidelapp_linkPassText" type="password"
				placeholder="{{ trans('Password') }}" />
		</div>
		<input type="checkbox" name="fidelapp_showPassword"
			id="fidelapp_showPassword" value="1" style="" /><label
			for="fidelapp_showPassword" style="">{{ trans('Send password in
			e-Mail') }}</label>
	</div>
	<div id="link">
		<input type="checkbox" name="fidelapp_expirationCheckbox"
			id="fidelapp_expirationCheckbox" value="1" /><label
			for="fidelapp_expirationCheckbox">{{ trans('Set expiration date') }}</label>
		<br> <input id="fidelapp_expirationDate" type="text"
			placeholder="{{ trans('Expiration date') }}" style="width: 90%;" />
	</div>
</div>
<!-- </form>  -->
