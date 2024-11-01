<div id="pacsoft-sync-options-dialog" hidden>
	<p class="selected-service-indicator">
		<span>{{ i18n.selectedServiceIndicator }}</span>
		<span class="selected-service">{{ i18n.noServiceSelected }}</span>
	</p>

	<table class="form-table">
		<tr valign="top" hidden>
			<th scope="row">{{ i18n.selectPacsoftService }}</th>
			<td>
				<select class="pacsoft-services">
					<option value=""></option>
					{{# services }}
					<option value="{{ code }}" data-woocommerce-pacsoft-service-base-country="{{ country }}">{{ title }}</option>
					{{/ services }}
				</select>
			</td>
		</tr>
		<tr valign="form">
			<th scope="row">{{ i18n.selectPacsoftService }}</th>
			<td>
				<input class="filter" placeholder="{{ i18n.placeHolderSearchServices }}" type="text"/>
			</td>
			<td>
				<p class="submit">
					<a href="#" class="button syncPacsoftOrderWithOptions">{{ i18n.syncOrder }}</a>
				</p>
			</td>
		</tr>
	</table>

	<ul class="services-to-filter">
		{{# services }}
		<li value="{{ code }}" data-service="{{ title }}" data-woocommerce-pacsoft-service-base-country="{{ country }}" hidden>{{ title }}</li>
		{{/ services }}
	</ul>
</div>