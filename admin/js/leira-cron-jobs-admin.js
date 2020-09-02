/**
 * This file is used on the cron jobs page to power quick-editing.
 */

/* global inlineEditL10n, ajaxurl, inlineEditCron */

window.wp = window.wp || {};
inlineEditL10n = {
    error: "Error while saving the changes.",
    saved: "Changes saved."
};

/**
 * Consists of functions relevant to the inline taxonomy editor.
 *
 * @namespace inlineEditCron
 *
 * @property {string} type The type of inline edit we are currently on.
 * @property {string} what The type property with a hash prefixed and a dash suffixed.
 */
(function ($, wp) {

    window.inlineEditCron = {

        /**
         * Initializes the inline cron job editor by adding event handlers to be able to quick edit.
         *
         * @this inlineEditCron
         * @memberof inlineEditCron
         * @returns {void}
         */
        init: function () {
            var t = this, row = $('#inline-edit');

            t.type = $('#the-list').attr('data-wp-lists').substr(5);
            t.what = '#' + t.type + '-';

            $('#the-list').on('click', '.editinline', function () {
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
             * Change datetime fields to current user time
             */
            inlineEditCron.updateDates();

            /**
             * Set timezone offset input. Offset is in minutes
             */
            $('#inline-edit input[name="offset"]').val(new Date().getTimezoneOffset());

            /**
             * Handle rate us footer click
             */
            $('body').on('click', 'a.leira-cron-jobs-admin-rating-link', function () {
                $.post(ajaxurl, {
                    action: 'leira-cron-jobs-footer-rated',
                    _wpnonce: $(this).data('nonce')
                }, function () {
                    //on success do nothing
                });
                $(this).parent().text($(this).data('rated'));
            });
        },

        /**
         *
         */
        updateDates: function () {
            $('#the-list abbr.date-time-field').each(function (index, el) {
                el = $(el);
                var value = el.data('utc-time');
                var format = el.data('date-format') + ' ' + el.data('time-format');
                var date = new Date(value * 1000);
                el.text(inlineEditCron.dateFormat(format, date));

            })
        },

        /**
         * Return a formatted string from a date Object mimicking PHP's date() functionality
         *
         * format  string  "Y-m-d H:i:s" or similar PHP-style date format string
         * date    mixed   Date Object, Datestring, or milliseconds
         *
         */
        dateFormat: function (format, date) {
            if (typeof (date) === 'Date') {
                //continue
            } else if (!date || date === "") {
                date = new Date();
            } else if (typeof (date) !== 'object') {
                date = new Date(date.replace(/-/g, "/")); // attempt to convert string to date object
            }

            var string = '',
                mo = date.getMonth(),   // month (0-11)
                m1 = mo + 1,			    // month (1-12)
                dow = date.getDay(),    // day of week (0-6)
                d = date.getDate(),     // day of the month (1-31)
                y = date.getFullYear(), // 1999 or 2003
                h = date.getHours(),    // hour (0-23)
                mi = date.getMinutes(), // minute (0-59)
                s = date.getSeconds();  // seconds (0-59)

            for (var i = 0, len = format.length; i < len; i++) {
                switch (format[i]) {
                    case 'j': // Day of the month without leading zeros  (1 to 31)
                        string += d;
                        break;

                    case 'd': // Day of the month, 2 digits with leading zeros (01 to 31)
                        string += (d < 10) ? "0" + d : d;
                        break;

                    case 'l': // (lowercase 'L') A full textual representation of the day of the week
                        var days = Array("Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday");
                        string += days[dow];
                        break;

                    case 'w': // Numeric representation of the day of the week (0=Sunday,1=Monday,...6=Saturday)
                        string += dow;
                        break;

                    case 'D': // A textual representation of a day, three letters
                        days = Array("Sun", "Mon", "Tue", "Wed", "Thr", "Fri", "Sat");
                        string += days[dow];
                        break;

                    case 'm': // Numeric representation of a month, with leading zeros (01 to 12)
                        string += (m1 < 10) ? "0" + m1 : m1;
                        break;

                    case 'n': // Numeric representation of a month, without leading zeros (1 to 12)
                        string += m1;
                        break;

                    case 'F': // A full textual representation of a month, such as January or March
                        var months = Array("January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December");
                        string += months[mo];
                        break;

                    case 'M': // A short textual representation of a month, three letters (Jan - Dec)
                        months = Array("Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec");
                        string += months[mo];
                        break;

                    case 'Y': // A full numeric representation of a year, 4 digits (1999 OR 2003)
                        string += y;
                        break;

                    case 'y': // A two digit representation of a year (99 OR 03)
                        string += y.toString().slice(-2);
                        break;

                    case 'H': // 24-hour format of an hour with leading zeros (00 to 23)
                        string += (h < 10) ? "0" + h : h;
                        break;

                    case 'g': // 12-hour format of an hour without leading zeros (1 to 12)
                        var hour = (h === 0) ? 12 : h;
                        string += (hour > 12) ? hour - 12 : hour;
                        break;

                    case 'h': // 12-hour format of an hour with leading zeros (01 to 12)
                        hour = (h === 0) ? 12 : h;
                        hour = (hour > 12) ? hour - 12 : hour;
                        string += (hour < 10) ? "0" + hour : hour;
                        break;

                    case 'a': // Lowercase Ante meridiem and Post meridiem (am or pm)
                        string += (h < 12) ? "am" : "pm";
                        break;

                    case 'i': // Minutes with leading zeros (00 to 59)
                        string += (mi < 10) ? "0" + mi : mi;
                        break;

                    case 's': // Seconds, with leading zeros (00 to 59)
                        string += (s < 10) ? "0" + s : s;
                        break;

                    case 'c': // ISO 8601 date (eg: 2012-11-20T18:05:54.944Z)
                        string += date.toISOString();
                        break;

                    default:
                        string += format[i];
                }
            }

            return string;
        },

        /**
         * Toggles the quick edit based on if it is currently shown or hidden.
         *
         * @this inlineEditCron
         * @memberof inlineEditCron
         *
         * @param {HTMLElement} el An element within the table row or the table row itself that we want to quick edit.
         * @returns {void}
         */
        toggle: function (el) {
            var t = this;

            $(t.what + t.getId(el)).css('display') === 'none' ? t.revert() : t.edit(el);
        },

        /**
         * Shows the quick editor
         *
         * @this inlineEditCron
         * @memberof inlineEditCron
         *
         * @param {string|HTMLElement} id The ID of the term we want to quick edit or an element within the table row or the table row itself.
         * @returns {boolean} Always returns false.
         */
        edit: function (id) {
            var editRow, rowData, val,
                t = this;
            t.revert();

            // Makes sure we can pass an HTMLElement as the ID.
            if (typeof (id) === 'object') {
                id = t.getId(id);
            }

            editRow = $('#inline-edit').clone(true), rowData = $('#inline_' + id);
            $('td', editRow).attr('colspan', $('th:visible, td:visible', '.wp-list-table.widefat:first thead').length);

            $(t.what + id).hide().after(editRow).after('<tr class="hidden"></tr>');

            $('> div', rowData).each(function (index, value) {
                value = $(value);
                var name = value.attr('class');
                value = value.text();

                if (name === 'time') {
                    $(':input[name=time]', editRow).val(value);//original time
                    var time = new Date(value * 1000);
                    var pad = "00";
                    var hh = time.getHours().toString();
                    hh = pad.substring(0, pad.length - hh.length) + hh; //leading zeros
                    var mn = time.getMinutes().toString();
                    mn = pad.substring(0, pad.length - mn.length) + mn; //leading zeros
                    $(':input[name=mm]', editRow).val(time.getMonth() + 1);//month
                    $(':input[name=jj]', editRow).val(time.getDate());//day
                    $(':input[name=aa]', editRow).val(time.getFullYear());//year
                    $(':input[name=hh]', editRow).val(hh);//hour
                    $(':input[name=mn]', editRow).val(mn);//minutes
                    $(':input[name=ss]', editRow).val(time.getSeconds());//minutes

                } else if (name === 'action') {
                    $(':input[name=_action]', editRow).val(value);
                } else {

                    $(':input[name=' + name + ']', editRow).val(value);
                }
            });

            $(editRow).attr('id', 'edit-' + id).addClass('inline-editor').show();
            $('.ptitle', editRow).eq(0).focus();

            return false;
        },

        /**
         * Saves the quick edit data to the server and replaces the table row with the HTML retrieved from the server.
         *
         * @this inlineEditCron
         * @memberof inlineEditCron
         *
         * @param {string|HTMLElement} id The ID of the term we want to quick edit or an element within the table row or the table row itself.
         * @returns {boolean} Always returns false.
         */
        save: function (id) {
            var params, fields, tax = $('input[name="taxonomy"]').val() || '';

            // Makes sure we can pass an HTMLElement as the ID.
            if (typeof (id) === 'object') {
                id = this.getId(id);
            }

            $('table.widefat .spinner').addClass('is-active');

            params = {
                action: 'inline-save-cron-job',
            };

            fields = $('#edit-' + id).find(':input').serialize();
            params = fields + '&' + $.param(params);

            // Do the ajax request to save the data to the server.
            $.post(ajaxurl, params,
                /**
                 * Handles the response from the server
                 *
                 * Handles the response from the server, replaces the table row with the response
                 * from the server.
                 *
                 * @param {string} r The string with which to replace the table row.
                 */
                function (r) {
                    var row, new_id, option_value,
                        $errorNotice = $('#edit-' + id + ' .inline-edit-save .notice-error'),
                        $error = $errorNotice.find('.error');

                    $('table.widefat .spinner').removeClass('is-active');

                    if (r) {
                        if (-1 !== r.indexOf('<tr')) {
                            $(inlineEditCron.what + id).siblings('tr.hidden').addBack().remove();
                            new_id = $(r).attr('id');

                            $('#edit-' + id).before(r).remove();

                            if (new_id) {
                                option_value = new_id.replace(inlineEditCron.type + '-', '');
                                row = $('#' + new_id);
                            } else {
                                option_value = id;
                                row = $(inlineEditCron.what + id);
                            }

                            // Update the value in the Parent dropdown.
                            $('#parent').find('option[value=' + option_value + ']').text(row.find('.row-title').text());

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
         * @returns {void}
         */
        revert: function () {
            var id = $('table.widefat tr.inline-editor').attr('id');

            if (id) {
                $('table.widefat .spinner').removeClass('is-active');
                $('#' + id).siblings('tr.hidden').addBack().remove();
                id = id.substr(id.lastIndexOf('-') + 1);

                // Show the cron job row and move focus back to the Quick Edit button.
                $(this.what + id).show().find('.editinline').attr('aria-expanded', 'false').focus();
            }
        },

        /**
         * Retrieves the ID of the term of the element inside the table row.
         *
         * @memberof inlineEditCron
         *
         * @param {HTMLElement} o An element within the table row or the table row itself.
         * @returns {string} The ID of the term based on the element.
         */
        getId: function (o) {
            var id = o.tagName === 'TR' ? o.id : $(o).parents('tr').attr('id'), parts = id.split('-');

            return parts[parts.length - 1];
        }
    };

    $(document).ready(function () {
        inlineEditCron.init();
    });

})(jQuery, window.wp);
