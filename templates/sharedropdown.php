<div id="fidelapp_dropdown" class="drop" data-item-type="{{ itemType }}"
	data-item-source="{{ itemSource }}">
	<input id="itemType" type="hidden" value='{{ itemType }}' /> <input
		id="itemSource" type="hidden" value='{{ itemSource }}' />
	<form id="fidelapp_share">
		<input id="fidelapp_shareWith" type="text"
			placeholder="{{ trans('Share with') }}" /> <a
			id="fidelapp_submitLink" class="action checked disabled">&nbsp;&nbsp;</a>
		<div>
			<ul id="shareWithFidelAppList">
				{% for shareItem in shareItems %}
					<li>{{ shareItem.email }}</li>
				{% endfor %}
			</ul>
		</div>
	</form>
</div>
