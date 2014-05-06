/**
 * Inspire WordPress Framework (IWF)
 *
 * @package        IWF
 * @author        Masayuki Ietomi
 * @copyright    Copyright(c) 2011 Masayuki Ietomi
 */

(function ($, window) {
	$(function () {
		var default_send_to_editor = window.send_to_editor;

		$('button.media_button').live('click', function () {
			var field = $(this).data('for'),
				$element = $('input[name="' + field + '"], textarea[name="' + field + '"]');

			if ($element) {
				var insertAtCaret;

				if (window.getSelection) { // modern browser
					insertAtCaret = function (value) {
						$element.each(function () {
							var current = this.value,
								start = this.selectionStart,
								end = start + value.length;

							this.value = current.substr(0, start) + value + current.substr(start);
							this.setSelectionRange(end, end);
						});
					}

				} else if (document.selection) { // IE
					var ranges = [];

					$element.each(function () {
						this.focus();
						range = document.selection.createRange();
						ranges.push(range);
					});

					insertAtCaret = function (value) {
						$element.each(function (i) {
							ranges[i].text = value;
							this.focus();
						});
					}

				} else {
					return;
				}

				var type = $(this).data('type'),
					value = $(this).data('value') || 'url',
					mode = $(this).data('mode') || 'replace';

				type = type ? 'type=' + type + '&amp;' : '';
				tb_show('', 'media-upload.php?post_id=0&amp;' + type + 'TB_iframe=true');

				$('#TB_iframeContent').load(function () {
					var iframe_window = $('#TB_iframeContent')[0].contentWindow;
					rewrite_button();

					if (typeof iframe_window.prepareMediaItemInit == 'function') {
						var old_prepare_media_item_init = iframe_window.prepareMediaItemInit;

						iframe_window.prepareMediaItemInit = function (fileObj) {
							old_prepare_media_item_init(fileObj);
							rewrite_button();
						}
					}
				});

				window.send_to_editor = function (html) {
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

				$('#TB_window').bind('tb_unload', function () {
					window.send_to_editor = default_send_to_editor;
					$('#TB_iframeContent').unbind('load');
				});
			}
		});

		function rewrite_button() {
			$('#TB_iframeContent').contents().find('tr.submit input[type=submit]').val(iwfCommonL10n.insertToField);
		}
	})
})(jQuery, window);