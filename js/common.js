/**
 * Inspire WordPress Framework (IWF)
 *
 * @package		IWF
 * @author		Masayuki Ietomi
 * @copyright	Copyright(c) 2011 Masayuki Ietomi
 */

(function($, window) {
	$(function() {
		var default_send_to_editor = window.send_to_editor;

		$('button.media_button').live('click', function() {
			var field = $(this).data('for'),
				$element = $('input[name="' + field + '"], textarea[name="' + field + '"]');

			if ($element) {
				var insertAtCaret;

				if (window.getSelection) { // modern browser
					insertAtCaret = function(value) {
						$element.each(function() {
							var current = this.value,
								start = this.selectionStart,
								end = start + value.length;

							this.value = current.substr(0, start) + value + current.substr(start);
							this.setSelectionRange(end, end);
						});
					}

				} else if (document.selection) { // IE
					var ranges = [];

					$element.each(function(){
						this.focus();
						range = document.selection.createRange();
						ranges.push(range);
					});

					insertAtCaret = function(value) {
						$element.each(function(i) {
							ranges[i].text = value;
							this.focus();
						});
					}

				} else {
					return;
				}

				var type  = $(this).data('type'),
					value = $(this).data('value') || 'url',
					mode  = $(this).data('mode') || 'replace';

				type = type ? 'type=' + type + '&amp;' : '';
				tb_show('', 'media-upload.php?post_id=0&amp;' + type + 'TB_iframe=true');

				$('#TB_iframeContent').load(function() {
					var iframe_window = $('#TB_iframeContent')[0].contentWindow;
					rewrite_button();

					if (typeof iframe_window.prepareMediaItemInit == 'function') {
						var old_prepare_media_item_init = iframe_window.prepareMediaItemInit;

						iframe_window.prepareMediaItemInit = function(fileObj) {
							old_prepare_media_item_init(fileObj);
							rewrite_button();
						}
					}
				});

				window.send_to_editor = function(html) {
					var html = '<div>' + html + '</div>', data = '';

					switch (value) {
						case 'tag':
							data = $(html).html();
							break;

						case 'url':
						default:
							if ($(html).find('img').length > 0) {
								data = $(html).find('img').attr('src');

							} else if ($(html).find('a').length > 0) {
								data = $(html).find('a').attr('href');

							} else {
								data = $(html).html();
							}
					}

					switch (mode) {
						case 'insert':
							insertAtCaret(data);
							break;

						case 'append':
							$element.val($element.val() + data);
							break;

						case 'replace':
						default:
							$element.val(data);
					}

					tb_remove();
				}

				$('#TB_window').bind('tb_unload', function() {
					window.send_to_editor = default_send_to_editor;
					$('#TB_iframeContent').unbind('load');
				});
			}
		});

		$('button.reset_button').live('click', function() {
			var field = $(this).data('for');

			if (field) {
				$('input[name="' + field + '"]').each(function() {
					if ($(this).is(':checkbox') || $(this).is(':radio')) {
						$(this).attr('checked', false);

					} else {
						$(this).val('');
					}
				});

				$('select[name="' + field + '"]').attr('selected', false);
				$('textarea[name="' + field + '"]').val('');
			}
		});

		$('input[type=text].date_field, button.date_picker').each(function() {
			var $self;

			if ($(this).is('input:text')) {
				$self = $(this);

			} else if ($(this).is('button.date_picker')) {
				var field = $(this).data('for');
				$self = $('input[name=' + field + ']');

				if (!$self) {
					return;
				}

				$(this).click(function() {
					$self.trigger('focus');
				});

			} else {
				return;
			}

			var settings = $.extend({}, {
				cancelText     : iwfCommonL10n.cancelText,
				dateFormat     : iwfCommonL10n.dateFormat,
				dateOrder      : iwfCommonL10n.dateOrder,
				dayNames       : [
					iwfCommonL10n.sunday, iwfCommonL10n.monday, iwfCommonL10n.tuesday,
					iwfCommonL10n.wednesday, iwfCommonL10n.thursday, iwfCommonL10n.friday, iwfCommonL10n.saturday
				],
				dayNamesShort  : [
					iwfCommonL10n.sundayShort, iwfCommonL10n.mondayShort, iwfCommonL10n.tuesdayShort,
					iwfCommonL10n.wednesdayShort, iwfCommonL10n.thursdayShort, iwfCommonL10n.fridayShort, iwfCommonL10n.saturdayShort
				],
				dayText        : iwfCommonL10n.dayText,
				hourText       : iwfCommonL10n.hourText,
				minuteText     : iwfCommonL10n.minuteText,
				mode           : 'mixed',
				monthNames     : [
					iwfCommonL10n.january, iwfCommonL10n.february, iwfCommonL10n.march, iwfCommonL10n.april,
					iwfCommonL10n.may, iwfCommonL10n.june, iwfCommonL10n.july, iwfCommonL10n.august,
					iwfCommonL10n.september, iwfCommonL10n.october, iwfCommonL10n.november, iwfCommonL10n.december
				],
				monthNamesShort: [
					iwfCommonL10n.januaryShort, iwfCommonL10n.februaryShort, iwfCommonL10n.marchShort, iwfCommonL10n.aprilShort,
					iwfCommonL10n.mayShort, iwfCommonL10n.juneShort, iwfCommonL10n.julyShort, iwfCommonL10n.augustShort,
					iwfCommonL10n.septemberShort, iwfCommonL10n.octoberShort, iwfCommonL10n.november, iwfCommonL10n.decemberShort
				],
				monthText      : iwfCommonL10n.monthText,
				secText        : iwfCommonL10n.secText,
				setText        : iwfCommonL10n.setText,
				timeFormat     : iwfCommonL10n.timeFormat,
				timeWheels     : iwfCommonL10n.timeWheels,
				yearText       : iwfCommonL10n.yearText
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

		function rewrite_button() {
			$('#TB_iframeContent').contents().find('tr.submit input[type=submit]').val(iwfCommonL10n.insertToField);
		}
	})
})(jQuery, window);
