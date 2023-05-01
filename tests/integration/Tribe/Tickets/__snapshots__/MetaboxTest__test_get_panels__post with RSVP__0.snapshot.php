a:3:{s:4:"list";s:1240:"<div
	id="tribe_panel_base"
	class="ticket_panel panel_base"
	aria-hidden="false"
	data-save-prompt="You have unsaved changes to your tickets. Discard those changes?"
>
	<div class="tribe_sectionheader ticket_list_container tribe_no_capacity">
					<div class="ticket_table_intro">
								<a
					class="button-secondary"
					href="http://wordpress.test/wp-admin/edit.php?post_type=post&#038;page=tickets-attendees&#038;event_id=5102"
				>
					View Attendees				</a>
			</div>
			
<div id="ticket_list_wrapper">

	<table id="tribe_ticket_list_table" class="tribe-tickets-editor-table eventtable ticket_list eventForm widefat fixed">
		<thead>
			<tr class="table-header">
				<th class="ticket_name column-primary">Tickets</th>
				<th class="ticket_price">Price</th>
				<th class="ticket_capacity">Capacity</th>
				<th class="ticket_available">Available</th>
				<th class="ticket_edit"></th>
			</tr>
		</thead>
				<tbody class="tribe-tickets-editor-table-tickets-body">
				</tbody>

		<tbody>
					</tbody>
	</table>

</div>
			</div>
	<div class="tribe-ticket-control-wrap">
		
		<button id="settings_form_toggle" class="button-secondary tribe-button-icon tribe-button-icon-settings">
			Settings		</button>

		
	</div>
	
</div>";s:8:"settings";s:1886:"
<div id="tribe_panel_settings" class="ticket_panel panel_settings" aria-hidden="true" >
	<h4>Ticket Settings	</h4>

	<section class="settings_main">
		
	<fieldset class="screen-reader-text">
									<input
					type="radio"
					class="tribe-ticket-editor-field-default_provider settings_field"
					name="tribe-tickets[settings][default_provider]"
					id="provider_TEC_Tickets_Commerce_Module_radio"
					value="TEC\Tickets\Commerce\Module"
					checked
				>
						</fieldset>
	</section>
	<section id="tribe-tickets-image">
		<div class="tribe-tickets-image-upload">
			<div class="input_block">
				<span class="ticket_form_label tribe-strong-label">Ticket header image:				</span>
				<p class="description">
					Select an image from your Media Library to display on emailed ticket. For best results, use a .jpg, .png, or .gif at least 1160px wide.				</p>
			</div>
			<input
				type="button"
				class="button"
				name="tribe-tickets[settings][header_image]"
				id="tribe_ticket_header_image"
				value="Select an Image"
			/>

			<span id="tribe_tickets_image_preview_filename" class="">
				<span class="dashicons dashicons-format-image"></span>
				<span class="filename"></span>
			</span>
		</div>
		<div class="tribe-tickets-image-preview">
			<a class="tribe_preview" id="tribe_ticket_header_preview">
							</a>
			<p class="description">
				<a href="#" id="tribe_ticket_header_remove">Remove</a>
			</p>

			<input
				type="hidden"
				id="tribe_ticket_header_image_id"
				class="settings_field"
				name="tribe-tickets[settings][header_image_id]"
				value=""
			/>
		</div>
	</section>

	<input type="button" id="tribe_settings_form_save" name="tribe_settings_form_save" value="Save settings" class="button-primary" />
	<input type="button" id="tribe_settings_form_cancel" name="tribe_settings_form_cancel" value="Cancel" class="button-secondary" />
</div>
";s:6:"ticket";s:9770:"
<div id="tribe_panel_edit" class="ticket_panel panel_edit tribe-validation" aria-hidden="true"
	 data-default-provider="TEC\Tickets\Commerce\Module">
	
	<div id="ticket_form" class="ticket_form tribe_sectionheader tribe-validation">
		<div id="ticket_form_table" class="eventtable ticket_form">
			<div
					class="tribe-dependent"
					data-depends="#Tribe__Tickets__RSVP_radio"
					data-condition-is-not-checked
			>
				<h4
						id="ticket_title_add"
						class="ticket_form_title tribe-dependent"
						data-depends="#ticket_id"
						data-condition-is-empty
				>
					Add new ticket				</h4>
				<h4
						id="ticket_title_edit"
						class="ticket_form_title tribe-dependent"
						data-depends="#ticket_id"
						data-condition-is-not-empty
				>
					Edit ticket				</h4>
			</div>
			<div
					class="tribe-dependent"
					data-depends="#Tribe__Tickets__RSVP_radio"
					data-condition-is-checked
			>
				<h4
						id="rsvp_title_add"
						class="ticket_form_title tribe-dependent"
						data-depends="#ticket_id"
						data-condition-is-empty
				>
					Add new RSVP				</h4>
				<h4
						id="rsvp_title_edit"
						class="ticket_form_title tribe-dependent"
						data-depends="#ticket_id"
						data-condition-is-not-empty
				>
					Edit RSVP				</h4>
			</div>
			<section id="ticket_form_main" class="main"
					 data-datepicker_format="1">

				
				<div class="input_block">
					<label class="ticket_form_label ticket_form_left" for="ticket_name">
						Name:					</label>
					<input
							type='text'
							id='ticket_name'
							name='ticket_name'
							class="ticket_field ticket_form_right"
							size='25'
							value="Test RSVP ticket for 5102"
							data-validation-is-required
							data-validation-error="RSVP type is a required field"
					/>
					<span
							class="tribe_soft_note ticket_form_right"
							data-depends="#Tribe__Tickets__RSVP_radio"
							data-condition-not-checked
					>The ticket name is displayed on the frontend of your website and within ticket emails.					</span>
					<span
							class="tribe_soft_note ticket_form_right"
							data-depends="#Tribe__Tickets__RSVP_radio"
							data-condition-is-checked
					>The RSVP name is displayed on the frontend of your website and within RSVP emails.					</span>
				</div>
				<div class="input_block">
					<label class="ticket_form_label ticket_form_left"
						   for="ticket_description">Description:</label>
					<textarea
							rows="5"
							cols="40"
							name="ticket_description"
							class="ticket_field ticket_form_right"
							id="ticket_description"
					>Ticket RSVP ticket excerpt for 5102</textarea>
					<div class="input_block">
						<label class="tribe_soft_note">
							<input
									type="checkbox"
									id="tribe_tickets_show_description"
									name="ticket_show_description"
									value="1"
									class="ticket_field ticket_form_left"
									 checked='checked'							>
							Show description on frontend ticket form.						</label>
					</div>
				</div>
				<div class="input_block">
					<label class="ticket_form_label ticket_form_left"
						   for="ticket_start_date">Start sale:</label>
					<div class="ticket_form_right">
						<input
								autocomplete="off"
								type="text"
								class="tribe-datepicker tribe-field-start_date ticket_field"
								name="ticket_start_date"
								id="ticket_start_date"
								value="4/30/2023"
								data-validation-type="datepicker"
								data-validation-is-less-or-equal-to="#ticket_end_date"
								data-validation-error="{&quot;is-required&quot;:&quot;Start sale date cannot be empty.&quot;,&quot;is-less-or-equal-to&quot;:&quot;Start sale date cannot be greater than End Sale date&quot;}"
						/>
						<span class="helper-text hide-if-js">YYYY-MM-DD</span>
						<span class="datetime_seperator"> at </span>
						<input
								autocomplete="off"
								type="text"
								class="tribe-timepicker tribe-field-start_time ticket_field"
								name="ticket_start_time"
								id="ticket_start_time"
																data-step="30"
								data-round="00:00:00"
								value="09:47:20"
								aria-label="Ticket start date"
						/>
						<span class="helper-text hide-if-js">HH:MM</span>
						<span class="dashicons dashicons-editor-help"
							  title="If you do not set a start sale date, tickets will be available immediately.">
			</span>
					</div>
				</div>
				<div class="input_block">
					<label class="ticket_form_label ticket_form_left"
						   for="ticket_end_date">End sale:</label>
					<div class="ticket_form_right">
						<input
								autocomplete="off"
								type="text"
								class="tribe-datepicker tribe-field-end_date ticket_field"
								name="ticket_end_date"
								id="ticket_end_date"
								value="5/2/2023"
						/>
						<span class="helper-text hide-if-js">YYYY-MM-DD</span>
						<span class="datetime_seperator"> at </span>
						<input
								autocomplete="off"
								type="text"
								class="tribe-timepicker tribe-field-end_time ticket_field"
								name="ticket_end_time"
								id="ticket_end_time"
																data-step="30"
								data-round="00:00:00"
								value="09:47:20"
								aria-label="Ticket end date"
						/>
						<span class="helper-text hide-if-js">HH:MM</span>
						<span class="dashicons dashicons-editor-help"
							  title="If you do not set an end sale date, tickets will be available forever."
						></span>
					</div>
				</div>
				<fieldset id="tribe_ticket_provider_wrapper" class="input_block" aria-hidden="true">
					<legend class="ticket_form_label">Sell using:</legend>
											<input
								type="radio"
								name="ticket_provider"
								id="Tribe__Tickets__RSVP_radio"
								value="Tribe__Tickets__RSVP"
								class="ticket_field ticket_provider"
								tabindex="-1"
								 checked='checked'						>
						<span>
							RSVPs						</span>
											<input
								type="radio"
								name="ticket_provider"
								id="TEC\Tickets\Commerce\Module_radio"
								value="TEC\Tickets\Commerce\Module"
								class="ticket_field ticket_provider"
								tabindex="-1"
														>
						<span>
							Tickets Commerce						</span>
									</fieldset>
				<div
	class="price tribe-dependent"
		data-depends="#Tribe__Tickets__RSVP_radio"
	data-condition-is-not-checked
	>
	<div class="input_block">
		<label for="ticket_price" class="ticket_form_label ticket_form_left">Price:</label>
		<input
			type="text"
			id="ticket_price"
			name="ticket_price"
			class="ticket_field ticket_form_right"
			size="7"
			value=""
						data-validation-error="Ticket price must be greater than zero."		/>
					<p class="description ticket_form_right">
				Leave blank for free Ticket			</p>
				</div>

	</div><div
	class="input_block ticket_advanced_Tribe__Tickets__RSVP tribe-dependent"
	data-depends="#Tribe__Tickets__RSVP_radio"
	data-condition-is-checked
>
	<label
		for="Tribe__Tickets__RSVP_capacity"
		class="ticket_form_label ticket_form_left"
	>
		Capacity:	</label>
	<input
		type='text' id='Tribe__Tickets__RSVP_capacity'
		name='tribe-ticket[capacity]'
		class="ticket_field tribe-rsvp-field-capacity ticket_form_right"
		size='7'
		value='100'
	/>
	<span class="tribe_soft_note ticket_form_right">Leave blank for unlimited</span>
</div>

<div
	class="input_block ticket_advanced_Tribe__Tickets__RSVP tribe-dependent"
	data-depends="#Tribe__Tickets__RSVP_radio"
	data-condition-is-checked
>
	<label
		for="tribe-tickets-rsvp-not-going"
		class="ticket_form_label ticket_form_left"
	>
		Can&#039;t Go:	</label>
	<input
		type="checkbox"
		id="tribe-tickets-rsvp-not-going"
		name="tribe-ticket[not_going]"
		class="ticket_field tribe-rsvp-field-not-going ticket_form_right"
		value="yes"
			/>
	<span class="tribe_soft_note ticket_form_right">Enable &quot;Can&#039;t Go&quot; responses</span>
</div>
			</section>
			<div class="accordion">
				<div class="tribe-dependent" data-depends="#Tribe__Tickets__RSVP_radio" data-condition-is-not-checked>
	<button class="accordion-header tribe_advanced_meta">
		Advanced	</button>
	<section id="ticket_form_advanced" class="advanced accordion-content" data-datepicker_format="1">
		<h4 class="accordion-label screen_reader_text">Advanced Settings</h4>
		<div id="advanced_fields">
			<div id="Tribe__Tickets__Commerce__PayPal__Main_advanced" class="tribe-dependent" data-depends="#Tribe__Tickets__Commerce__PayPal__Main_radio" data-condition-is-checked></div>		</div>
	</section><!-- #ticket_form_advanced -->
</div>
			</div>

						<div class="ticket_bottom">
				<input
						type="hidden"
						name="ticket_id"
						id="ticket_id"
						class="ticket_field"
						value="5103"
				/>
				<input
						type="button"
						id="ticket_form_save"
						class="button-primary tribe-dependent tribe-validation-submit"
						name="ticket_form_save"
						value="Save ticket"
						data-depends="#Tribe__Tickets__RSVP_radio"
						data-condition-is-not-checked
				/>
				<input
						type="button"
						id="rsvp_form_save"
						class="button-primary tribe-dependent tribe-validation-submit"
						name="ticket_form_save"
						value="Save RSVP"
						data-depends="#Tribe__Tickets__RSVP_radio"
						data-condition-is-checked
				/>
				<input
						type="button"
						id="ticket_form_cancel"
						class="button-secondary"
						name="ticket_form_cancel"
						value="Cancel"
				/>

				
				<div id="ticket_bottom_right">
					<a href="?dialog=move_ticket_types&ticket_type_id=5103&check=2c0ebc32a8&TB_iframe=true" class="thickbox tribe-ticket-move-link">Move RSVP</a> | <span><a href="#" attr-provider="Tribe__Tickets__RSVP" attr-ticket-id="5103" id="ticket_delete_5103" class="ticket_delete">Delete RSVP</a></span>				</div>
			</div>
		</div>
	</div>
</div>
";}