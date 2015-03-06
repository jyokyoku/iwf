/**
 * Inspire WordPress Framework (IWF)
 *
 * @package        IWF
 * @author        Masayuki Ietomi
 * @copyright    Copyright(c) 2011 Masayuki Ietomi
 */

(function ($, window) {
	$(function () {
		$('input, textarea').placeholder();

		$('.iwf-preview').each(function () {
			var target = $(this).data('for'),
				preview = this;

			if (!target) {
				return true;
			}

			$(preview).css('background-size', 'contain');

			$(document).on('change blur', 'input[name="' + target + '"]', function () {
				var val = $(this).val();

				if (val && val.match(/\.(jpg|jpeg|gif|png)$/i)) {
					$(preview).css('background-image', 'url(' + val + ')');

				} else {
					$(preview).css('background-image', 'none');
				}
			});

			$('input[name="' + target + '"]').trigger('change');
		});

		$(document).on('change-media', '.iwf-preview', function (event, attachment) {
			if (attachment.type == 'image') {
				$(this).css('background-image', 'url(' + attachment.url + ')');

			} else {
				$(this).css('background-image', 'none');
			}
		});

		$('button.reset_button').live('click', function () {
			var field = $(this).data('for');

			if (field) {
				$('input[name="' + field + '"]').each(function () {
					if ($(this).is(':checkbox') || $(this).is(':radio')) {
						$(this).attr('checked', false).change();

					} else {
						$(this).val('').change();
					}
				});

				$('select[name="' + field + '"]').attr('selected', false).change();
				$('textarea[name="' + field + '"]').val('').change();
			}
		});

		$('input.color_picker_field').each(function () {
			var color,
				show_input = typeof $(this).data('show-input') == 'undefined' ? true : $(this).data('show-input'),
				show_alpha = typeof $(this).data('show-alpha') == 'undefined' ? true : $(this).data('show-alpha'),
				show_initial = typeof $(this).data('show-initial') == 'undefined' ? true : $(this).data('show-initial'),
				show_palette = typeof $(this).data('show-palette') == 'undefined' ? true : $(this).data('show-palette'),
				allow_empty = typeof $(this).data('allow-empty') == 'undefined' ? true : $(this).data('allow-empty'),
				show_selection_palette = typeof $(this).data('show-selection-palette') == 'undefined' ? true : $(this).data('show-selection-palette'),
				max_palette_size = $(this).data('max-palette-size');

			if ($(this).val()) {
				color = $(this).val();
			}

			if (!color) {
				color = '#000';
			}

			$(this).spectrum({
				color: color,
				flat: false,
				showInput: !!show_input,
				showInitial: !!show_initial,
				showPalette: !!show_palette,
				showAlpha: !!show_alpha,
				allowEmpty: !!allow_empty,
				showSelectionPalette: !!show_selection_palette,
				maxPaletteSize: max_palette_size || 10,
				preferredFormat: !show_alpha ? "hex" : "rgb",
				localStorageKey: "spectrum." + $(this).attr('name')
			});
		});

		$('input[type=text].date_field, button.date_picker').each(function () {
			var $self;

			if ($(this).is('input:text')) {
				$self = $(this);

			} else if ($(this).is('button.date_picker')) {
				var field = $(this).data('for');
				$self = $('input[name=' + field + ']');

				if (!$self) {
					return;
				}

				$(this).click(function () {
					$self.trigger('focus');
				});

			} else {
				return;
			}

			var settings = $.extend({}, {
				cancelText: iwfCommonL10n.cancelText,
				dateFormat: 'yy-mm-dd',
				dateOrder: 'yymmdd',
				dayNames: [
					iwfCommonL10n.sunday, iwfCommonL10n.monday, iwfCommonL10n.tuesday,
					iwfCommonL10n.wednesday, iwfCommonL10n.thursday, iwfCommonL10n.friday, iwfCommonL10n.saturday
				],
				dayNamesShort: [
					iwfCommonL10n.sundayShort, iwfCommonL10n.mondayShort, iwfCommonL10n.tuesdayShort,
					iwfCommonL10n.wednesdayShort, iwfCommonL10n.thursdayShort, iwfCommonL10n.fridayShort, iwfCommonL10n.saturdayShort
				],
				dayText: iwfCommonL10n.dayText,
				hourText: iwfCommonL10n.hourText,
				minuteText: iwfCommonL10n.minuteText,
				mode: 'mixed',
				monthNames: [
					iwfCommonL10n.january, iwfCommonL10n.february, iwfCommonL10n.march, iwfCommonL10n.april,
					iwfCommonL10n.may, iwfCommonL10n.june, iwfCommonL10n.july, iwfCommonL10n.august,
					iwfCommonL10n.september, iwfCommonL10n.october, iwfCommonL10n.november, iwfCommonL10n.december
				],
				monthNamesShort: [
					iwfCommonL10n.januaryShort, iwfCommonL10n.februaryShort, iwfCommonL10n.marchShort, iwfCommonL10n.aprilShort,
					iwfCommonL10n.mayShort, iwfCommonL10n.juneShort, iwfCommonL10n.julyShort, iwfCommonL10n.augustShort,
					iwfCommonL10n.septemberShort, iwfCommonL10n.octoberShort, iwfCommonL10n.november, iwfCommonL10n.decemberShort
				],
				monthText: iwfCommonL10n.monthText,
				secText: iwfCommonL10n.secText,
				setText: iwfCommonL10n.setText,
				timeFormat: 'H:i',
				timeWheels: 'HHii',
				yearText: iwfCommonL10n.yearText
			}, $self.data());

			$self.scroller(settings);
			var date_value = $self.val();

			if (date_value.match(/^\d+$/)) {
				var date = new Date(),
					format = '';

				date.setTime(date_value * 1000);

				if (settings.preset == 'time') {
					format = settings.timeFormat;

				} else if (settings.preset == 'datetime') {
					format = settings.dateFormat + ' ' + settings.timeFormat;

				} else {
					format = settings.dateFormat;
				}

				$self.val($.scroller.formatDate(format, date, settings));
			}
		});

		CodeMirror.modeURL = iwf_url + '/js/codemirror/mode/%N/%N.js';

		$('textarea.iwf-codemirror').each(function () {
			var mode = $(this).data('mode'),
				indent_with_tabs = typeof $(this).data('indent_with_tabs') == 'undefined' ? true : $(this).data('indent_with_tabs'),
				indent_unit = typeof $(this).data('indent_unit') == 'undefined' ? 4 : parseInt($(this).data('indent_unit')),
				indent_size = typeof $(this).data('tab_size') == 'undefined' ? 4 : parseInt($(this).data('tab_size')),
				editor = CodeMirror.fromTextArea($(this).get(0), {
					lineNumbers: true,
					matchBrackets: true,
					autoCloseBrackets: true,
					autoCloseTags: true,
					indentWithTabs: indent_with_tabs,
					indentUnit: indent_unit,
					tabSize: indent_size
				}),
				rows = $(this).attr('rows') || 0;

			if (rows) {
				editor.setSize('100%', (18 * rows) + 'px');
			}

			editor.setOption('mode', mode);
			CodeMirror.autoLoadMode(editor, mode);

			$(this).data('codemirror', editor);
		});

		$('textarea.autosize').autosize({
			append: ''
		});
	})
})(jQuery, window);
