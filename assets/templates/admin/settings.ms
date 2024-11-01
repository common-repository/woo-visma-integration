{{# header }}<section id="wetail-header">{{{ header }}}</section>{{/ header }}
<div class="wrap">
	<h1>{{ title }}
	    <a href="https://wetail.io/support/" class="page-title-action" target="_blank">Support</a>
	    <a href="https://docs.wetail.io/woocommerce/visma-integration/" class="page-title-action" target="_blank">FAQ</a>
	    {{# buy }}
            <a href="https://wetail.io/service/intergrationer/woocommerce-visma/" class="button-primary page-title-action" target="_blank">Order License</a>
        {{/ buy }}
    </h1>

	<!--<div class="welcome-panel">
		<a class="welcome-panel-close" href="#">Dismiss</a>
		<h2>Welcome to Visma for WooCommerce!</h2>
		<p class="about-description">Some dummy text here.</p>

	</div>-->

	{{# hasTabs }}
	<h2 class="nav-tab-wrapper">
		{{# tabs }}
		<a class="nav-tab nav-tab-{{ tab.name }} {{# tab.selected }}nav-tab-active{{/ tab.selected }} {{ tab.class }}" href="options-general.php?page=visma&tab={{ tab.name }}">{{ tab.title }}</a>
		{{/ tabs }}
	</h2>
	{{/ hasTabs }}

	<form method="post" action="options.php">
		{{{ hidden }}}
		{{# sections }}
			{{# section.title }}
				<h2 class="title" {{# section.name }}data-section="{{{ . }}}"{{/ section.name }}>{{{ . }}}</h2>
			{{/ section.title }}

			{{# section.description }}
				<p>{{{ . }}}</p>
			{{/ section.description }}

			<table class="form-table {{# section.name }}{{{ . }}}{{/ section.name }}">
				<tbody>
					{{# section.fields }}
					<tr class="{{ field.class }}">
						<th scope="row">{{{ field.title }}}</th>
						<td>
						    {{# field.info }}
                                {{# field.tooltip }}{{#formatTooltip}}{{ . }}{{/formatTooltip}}{{/ field.tooltip }}
                                {{# field.html_tooltip }}{{#formatHtmlTooltip}}{{{ . }}}{{/formatHtmlTooltip}}{{/ field.html_tooltip }}
                                <span>{{{ field.value }}}</span>
                            {{/ field.info }}

							{{# field.text }}
							    {{# field.tooltip }}{{#formatTooltip}}{{ . }}{{/formatTooltip}}{{/ field.tooltip }}
                                {{# field.html_tooltip }}{{#formatHtmlTooltip}}{{{ . }}}{{/formatHtmlTooltip}}{{/ field.html_tooltip }}
								<input type="text" name="{{ field.name }}" value="{{ field.value }}" class="{{ field.class }}{{# field.short }} short-text{{/ field.short }}" autocomplete="off" placeholder="{{ field.placeholder }}">
							{{/ field.text }}

							{{# field.number }}
                                {{# field.tooltip }}{{#formatTooltip}}{{ . }}{{/formatTooltip}}{{/ field.tooltip }}
                                {{# field.html_tooltip }}{{#formatHtmlTooltip}}{{{ . }}}{{/formatHtmlTooltip}}{{/ field.html_tooltip }}
                                <input type="number" {{# field.min }}min="{{ field.min }}"{{/ field.min }} {{# field.max }}max="{{ field.max }}"{{/ field.max }} name="{{ field.name }}" value="{{ field.value }}" class="{{ field.class }}" autocomplete="off" placeholder="{{ field.placeholder }}">
                            {{/ field.number }}

							{{# field.password }}
							    {{# field.tooltip }}{{#formatTooltip}}{{ . }}{{/formatTooltip}}{{/ field.tooltip }}
                                {{# field.html_tooltip }}{{#formatHtmlTooltip}}{{{ . }}}{{/formatHtmlTooltip}}{{/ field.html_tooltip }}
								<input type="password" name="{{ name }}" value="{{ selected }}" class="{{ field.class }}" autocomplete="off" placeholder="{{ placeholder }}">
							{{/ field.password }}

							{{# field.dropdown }}
							    {{# field.tooltip }}{{#formatTooltip}}{{ . }}{{/formatTooltip}}{{/ field.tooltip }}
                                {{# field.html_tooltip }}{{#formatHtmlTooltip}}{{{ . }}}{{/formatHtmlTooltip}}{{/ field.html_tooltip }}
								<select name="{{ field.name }}" class="{{ field.class }}{{# field.short }} short-text{{/ field.short }}">
									{{# field.options }}
										<option value="{{ option.value }}" {{# option.selected }}selected="selected"{{/ option.selected }}>{{ option.label }}</option>
									{{/ field.options }}
								</select>
							{{/ field.dropdown }}

							{{# field.radio }}
								{{# field.options }}
									<p>
										<label for="{{ field.name }}">
										    {{# option.tooltip }}{{#formatTooltip}}{{ . }}{{/formatTooltip}}{{/ option.tooltip }}
                                            {{# option.html_tooltip }}{{#formatHtmlTooltip}}{{{ . }}}{{/formatHtmlTooltip}}{{/ option.html_tooltip }}
											<input id="{{ field.name }}" type="radio" name="{{ field.name }}" value="{{ option.value }}" {{# option.checked }}checked="checked"{{/ option.checked }} class="{{ option.class }}"> {{{ option.label }}}
										</label>
									</p>
								{{/ field.options }}
							{{/ field.radio }}

							{{# field.checkbox }}
								<p>
									<input type="hidden" name="{{ field.name }}" value="0">
									<label for="{{ field.name }}">
									    {{# field.tooltip }}{{#formatTooltip}}{{ . }}{{/formatTooltip}}{{/ field.tooltip }}
                                        {{# field.html_tooltip }}{{#formatHtmlTooltip}}{{{ . }}}{{/formatHtmlTooltip}}{{/ field.html_tooltip }}
										<input id="{{ field.name }}" type="checkbox" name="{{ field.name }}" value="1" {{# field.checked }}checked="checked"{{/ field.checked }} class="{{ field.class }}"> {{{ field.label }}}
									</label>
								</p>
							{{/ field.checkbox }}

							{{# field.checkboxes }}
								<fieldset>
									<legend class="screen-reader-text">
										<span>{{ title }}</span>
									</legend>

									{{# field.options }}
										<label for="{{ option.name }}">
										    {{# option.tooltip }}{{#formatTooltip}}{{ . }}{{/formatTooltip}}{{/ option.tooltip }}
                                            {{# option.html_tooltip }}{{#formatHtmlTooltip}}{{{ . }}}{{/formatHtmlTooltip}}{{/ option.html_tooltip }}
											<input type="hidden" name="{{ option.name }}" value="0">
											<input id="{{ option.name }}" type="checkbox" name="{{ option.name }}" value="1" {{# option.checked }}checked="checked"{{/ option.checked }} class="{{ option.class }}"> {{{ option.label }}}
										</label>
										{{# option.description }}
										    <p class="description">{{{ . }}}</p>
										{{/ option.description }}
									{{/ field.options }}
								</fieldset>
							{{/ field.checkboxes }}

							{{# field.table }}
							    {{# field.tooltip }}{{#formatTooltip}}{{ . }}{{/formatTooltip}}{{/ field.tooltip }}
                                {{# field.html_tooltip }}{{#formatHtmlTooltip}}{{{ . }}}{{/formatHtmlTooltip}}{{/ field.html_tooltip }}
								<table class="wp-list-table widefat fixed striped posts {{ field.class }}">
									<thead>
										<tr>
											{{# table.columns }}
											<th class="column-{{ column.name }}" style="text-align: center;">{{{ column.title }}}{{# column.tooltip }}{{#formatTooltip}}{{ . }}{{/formatTooltip}}{{/ column.tooltip }}</th>
											{{/ table.columns }}
										</tr>
									</thead>
									<tbody {{# table.id }}id="{{ . }}"{{/ table.id }}>
										{{# table.rows }}
											{{> admin/settings/table-row }}
										{{/ table.rows }}
									</tbody>
								</table>

								{{# table.addRowButton }}
								<p><a href="#" class="button {{ table.addRowButtonClass }}">Add row</a></p>
								{{/ table.addRowButton }}
							{{/ field.table }}

							{{# field.html }}
							    {{# field.tooltip }}{{#formatTooltip}}{{ . }}{{/formatTooltip}}{{/ field.tooltip }}
                                {{# field.html_tooltip }}{{#formatHtmlTooltip}}{{{ . }}}{{/formatHtmlTooltip}}{{/ field.html_tooltip }}
								{{{ . }}}
							{{/ field.html }}

							{{# field.button }}
							    {{# field.tooltip }}{{#formatTooltip}}{{ . }}{{/formatTooltip}}{{/ field.tooltip }}
                                {{# field.html_tooltip }}{{#formatHtmlTooltip}}{{{ . }}}{{/formatHtmlTooltip}}{{/ field.html_tooltip }}
							    <a href="#" class="button button-primary button-hero visma-admin-action" {{# field.data }}data-{{ key }}="{{ value }}" {{/ field.data }}style="text-align: center; width: 240px">{{ field.button.text }}</a> <span class="spinner visma-spinner hero"></span>
							{{/ field.button }}

							{{# field.after }}
							{{{ . }}}
							{{/ field.after }}

							{{# field.description }}
								<p class="description">{{{ . }}}</p>
							{{/ field.description }}
						</td>
					</tr>
					{{/ section.fields }}
				</tbody>
			</table>
		{{/ sections }}

		{{# saveButton }}
		<p class="submit">
			<button class="button-primary">{{saveButton}}</button>
		</p>
		{{/ saveButton }}
	</form>
</div>
