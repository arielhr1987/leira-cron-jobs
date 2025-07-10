/**
 * This file is used on the cron jobs page to power quick-editing.
 */

/* global inlineEditL10n, ajaxurl, inlineEditCron */

import $ from 'jquery';
import './admin.scss';

window.wp = window.wp || {};
inlineEditL10n = {
	error: 'Error while saving the changes.',
	saved: 'Changes saved.',
};

/**
 * Consists of functions relevant to the inline taxonomy editor.
 *
 * @namespace inlineEditCron
 *
 * @param    {Object} $    jQuery
 * @param    {Object} wp   The WordPress global object.
 *
 * @property {string} type The type of inline edit we are currently on.
 * @property {string} what The type property with a hash prefixed and a dash suffixed.
 */
(function ($, wp) {
	window.inlineEditCron = {
		/**
		 * Initializes the inline cron job editor by adding event handlers to be able to quick-edit.
		 *
		 * @this inlineEditCron
		 * @memberof inlineEditCron
		 * @return {void}
		 */
		init() {
			const t = this,
				row = $('#inline-edit'),
				theList = $('#the-list');

			t.type = theList.attr('data-wp-lists').substring(5);
			t.what = '#' + t.type + '-';

			theList.on('click', '.editinline', function () {
				$(this).attr('aria-expanded', 'true');
				inlineEditCron.edit(this);
			});

			/**
			 * Cancels inline editing when pressing escape inside the inline editor.
			 *
			 * @param {Object} e The keyup event that has been triggered.
			 */
			row.keyup(function (e) {
				// 27 = [escape]
				if (e.which === 27) {
					return inlineEditCron.revert();
				}
			});

			/**
			 * Cancels inline editing when clicking the cancel button.
			 */
			$('.cancel', row).click(function () {
				return inlineEditCron.revert();
			});

			/**
			 * Saves the inline edits when clicking the save button.
			 */
			$('.save', row).click(function () {
				return inlineEditCron.save(this);
			});

			/**
			 * Saves the inline edits when pressing enter inside the inline editor.
			 */
			$('input, select', row).keydown(function (e) {
				// 13 = [enter]
				if (e.which === 13) {
					return inlineEditCron.save(this);
				}
			});

			/**
			 * Saves the inline edits on submitting the inline edit form.
			 */
			$('#posts-filter input[type="submit"]').mousedown(function () {
				t.revert();
			});

			/**
			 * Change datetime fields to the current user time
			 */
			inlineEditCron.updateDates();

			/**
			 * Set timezone offset input. Offset is in minutes
			 */
			$('#inline-edit input[name="offset"]').val(
				new Date().getTimezoneOffset()
			);

			/**
			 * Handle rate us footer click
			 */
			$('body').on(
				'click',
				'a.leira-cron-jobs-admin-rating-link',
				function () {
					$.post(
						ajaxurl,
						{
							action: 'leira-cron-jobs-footer-rated',
							_wpnonce: $(this).data('nonce'),
						},
						function () {
							//on success do nothing
						}
					);
					$(this).parent().text($(this).data('rated'));
				}
			);
		},

		/**
		 * Update the date fields in the table to the current user time.
		 *
		 * @return {void}
		 */
		updateDates() {
			$('#the-list abbr.date-time-field').each(function (index, el) {
				el = $(el);
				const value = el.data('utc-time');
				const format =
					el.data('date-format') + ' ' + el.data('time-format');
				const date = new Date(value * 1000);
				el.text(inlineEditCron.dateFormat(format, date));
			});
		},

		/**
		 * Return a formatted string from a date Object mimicking PHP's date() functionality
		 *
		 * format string "Y-m-d H:i:s" or similar PHP-style date format string
		 * date mixed Date Object, Datestring, or milliseconds
		 *
		 * @param {string} format
		 * @param {Date} date
		 */
		dateFormat(format, date) {
			if (!(date instanceof Date)) {
				date = new Date(date);
			}

			const map = {
				Y: date.getFullYear(),
				y: date.getFullYear().toString().slice(-2),
				m: String(date.getMonth() + 1).padStart(2, '0'),
				n: date.getMonth() + 1,
				d: String(date.getDate()).padStart(2, '0'),
				j: date.getDate(),
				H: String(date.getHours()).padStart(2, '0'),
				h: String(((date.getHours() + 11) % 12) + 1).padStart(
					2,
					'0'
				),
				g: ((date.getHours() + 11) % 12) + 1,
				i: String(date.getMinutes()).padStart(2, '0'),
				s: String(date.getSeconds()).padStart(2, '0'),
				a: date.getHours() < 12 ? 'am' : 'pm',
				A: date.getHours() < 12 ? 'AM' : 'PM',
				D: date.toLocaleString('en-US', {weekday: 'short'}),
				l: date.toLocaleString('en-US', {weekday: 'long'}),
				M: date.toLocaleString('en-US', {month: 'short'}),
				F: date.toLocaleString('en-US', {month: 'long'}),
				c: date.toISOString(),
				w: date.getDay(),
			};

			return format.replace(/\\?([a-zA-Z])/g, (match, key) => {
				return map[key] !== undefined ? map[key] : key;
			});
		},

		/**
		 * Toggles the quick edit based on if it is currently shown or hidden.
		 *
		 * @this inlineEditCron
		 * @memberof inlineEditCron
		 *
		 * @param {HTMLElement} el An element within the table row or the table row itself that we want to quick edit.
		 * @return {void}
		 */
		toggle(el) {
			const t = this;

			$(t.what + t.getId(el)).css('display') === 'none' ? t.revert() : t.edit(el);
		},

		/**
		 * Shows the quick editor
		 *
		 * @this inlineEditCron
		 * @memberof inlineEditCron
		 *
		 * @param {string|HTMLElement} id The ID of the term we want to quickly edit or an element within the table row or the table row itself.
		 * @return {boolean} Always returns false.
		 */
		edit(id) {
			let editRow,
				rowData,
				val,
				t = this;
			t.revert();

			// Makes sure we can pass an HTMLElement as the ID.
			if (typeof id === 'object') {
				id = t.getId(id);
			}

			(editRow = $('#inline-edit').clone(true)),
				(rowData = $('#inline_' + id));
			$('td', editRow).attr(
				'colspan',
				$(
					'th:visible, td:visible',
					'.wp-list-table.widefat:first thead'
				).length
			);

			$(t.what + id)
				.hide()
				.after(editRow)
				.after('<tr class="hidden"></tr>');

			$('> div', rowData).each(function (index, value) {
				value = $(value);
				const name = value.attr('class');
				value = value.text();

				if (name === 'time') {
					$(':input[name=time]', editRow).val(value); //original time
					const time = new Date(value * 1000);
					const pad = '00';
					let hh = time.getHours().toString();
					hh = pad.substring(0, pad.length - hh.length) + hh; //leading zeros
					let mn = time.getMinutes().toString();
					mn = pad.substring(0, pad.length - mn.length) + mn; //leading zeros
					$(':input[name=mm]', editRow).val(time.getMonth() + 1); //month
					$(':input[name=jj]', editRow).val(time.getDate()); //day
					$(':input[name=aa]', editRow).val(time.getFullYear()); //year
					$(':input[name=hh]', editRow).val(hh); //hour
					$(':input[name=mn]', editRow).val(mn); //minutes
					$(':input[name=ss]', editRow).val(time.getSeconds()); //minutes
				} else if (name === 'action') {
					$(':input[name=_action]', editRow).val(value);
				} else {
					$(':input[name=' + name + ']', editRow).val(value);
				}
			});

			$(editRow)
				.attr('id', 'edit-' + id)
				.addClass('inline-editor')
				.show();
			$('.ptitle', editRow).eq(0).focus();

			return false;
		},

		/**
		 * Saves the quick edit data to the server and replaces the table row with the HTML retrieved from the server.
		 *
		 * @this inlineEditCron
		 * @memberof inlineEditCron
		 *
		 * @param {string|HTMLElement} id The ID of the term we want to quick-edit or an element within the table row or the table row itself.
		 * @return {boolean} Always returns false.
		 */
		save(id) {
			let params,
				fields,
				tax = $('input[name="taxonomy"]').val() || '';

			// Makes sure we can pass an HTMLElement as the ID.
			if (typeof id === 'object') {
				id = this.getId(id);
			}

			$('table.widefat .spinner').addClass('is-active');

			params = {
				action: 'inline-save-cron-job',
			};

			fields = $('#edit-' + id)
				.find(':input')
				.serialize();
			params = fields + '&' + $.param(params);

			// Do the ajax request to save the data to the server.
			$.post(
				ajaxurl,
				params,
				/**
				 * Handles the response from the server
				 *
				 * Handles the response from the server, replaces the table row with the response
				 * from the server.
				 *
				 * @param {string} r The string with which to replace the table row.
				 */
				function (r) {
					let row,
						new_id,
						option_value,
						$errorNotice = $('#edit-' + id + ' .inline-edit-save .notice-error'),
						$error = $errorNotice.find('.error');

					$('table.widefat .spinner').removeClass('is-active');

					if (r) {
						if (-1 !== r.indexOf('<tr')) {
							$(inlineEditCron.what + id)
								.siblings('tr.hidden')
								.addBack()
								.remove();
							new_id = $(r).attr('id');

							$('#edit-' + id)
								.before(r)
								.remove();

							if (new_id) {
								option_value = new_id.replace(inlineEditCron.type + '-', '');
								row = $('#' + new_id);
							} else {
								option_value = id;
								row = $(inlineEditCron.what + id);
							}

							// Update the value in the Parent dropdown.
							$('#parent')
								.find('option[value=' + option_value + ']')
								.text(row.find('.row-title').text());

							row.hide().fadeIn(400, function () {
								// Move focus back to the Quick Edit button.
								row.find('.editinline')
									.attr('aria-expanded', 'false')
									.focus();
								wp.a11y.speak(inlineEditL10n.saved);
							});

							inlineEditCron.updateDates();
						} else {
							$errorNotice.removeClass('hidden');
							$error.html(r);
							/*
							 * Some error strings may contain HTML entities (e.g. `&#8220`), let's use
							 * the HTML element's text.
							 */
							wp.a11y.speak($error.text());
						}
					} else {
						$errorNotice.removeClass('hidden');
						$error.html(inlineEditL10n.error);
						wp.a11y.speak(inlineEditL10n.error);
					}
				}
			);

			// Prevent submitting the form when pressing Enter on a focused field.
			return false;
		},

		/**
		 * Closes the quick edit form.
		 *
		 * @this inlineEditCron
		 * @memberof inlineEditCron
		 * @return {void}
		 */
		revert() {
			let id = $('table.widefat tr.inline-editor').attr('id');

			if (id) {
				$('table.widefat .spinner').removeClass('is-active');
				$('#' + id)
					.siblings('tr.hidden')
					.addBack()
					.remove();
				id = id.substring(id.lastIndexOf('-') + 1);

				// Show the cron job row and move focus back to the Quick Edit button.
				$(this.what + id)
					.show()
					.find('.editinline')
					.attr('aria-expanded', 'false')
					.focus();
			}
		},

		/**
		 * Retrieves the ID of the term of the element inside the table row.
		 *
		 * @memberof inlineEditCron
		 *
		 * @param {HTMLElement} o An element within the table row or the table row itself.
		 * @return {string} The ID of the term based on the element.
		 */
		getId(o) {
			const id = o.tagName === 'TR' ? o.id : $(o).parents('tr').attr('id'), parts = id.split('-');

			return parts[parts.length - 1];
		},
	};

	$(document).ready(function () {
		inlineEditCron.init();
	});
})(jQuery, window.wp);
