/**
 * Inspire WordPress Framework (IWF)
 *
 * @package        IWF
 * @author        Masayuki Ietomi
 * @copyright    Copyright(c) 2011 Masayuki Ietomi
 */

(function ($, window) {
	$(function () {
		var iwf_media_frames = {};

		$(document).on('click', '.button.media_button', function (event) {
			event.preventDefault();

			var field = $(this).data('for'),
				$element = $('input[name="' + field + '"], textarea[name="' + field + '"]'),
				to_codemirror = $element.hasClass('iwf-codemirror'),
				insert_at_caret;

			if ($element.length < 1 || (to_codemirror && !$element.data('codemirror'))) {
				return;
			}

			var codemirror_object = $element.data('codemirror');

			if (!_.isUndefined(iwf_media_frames[field])) {
				iwf_media_frames[field].open();
				return;
			}

			if (!to_codemirror) {
				if (window.getSelection) { // modern browser
					insert_at_caret = function (value) {
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

					insert_at_caret = function (value) {
						$element.each(function (i) {
							ranges[i].text = value;
							this.focus();
						});
					}

				} else {
					return;
				}
			}

			var filter = $(this).data('filter') || $(this).data('type') || '',
				format = $(this).data('format') || $(this).data('value') || 'url',
				mode = $(this).data('mode') || ($element.get(0).tagName.toLowerCase() == 'input' ? 'replace' : 'insert'),
				$preview = $('.iwf-preview[data-for="' + field + '"]');

			iwf_media_frames[field] = wp.media.frames.iwfMedia = wp.media({
				title: iwfCommonL10n.insertToField,
				library: {
					type: filter
				},
				button: {
					text: iwfCommonL10n.insertToField
				},
				multiple: false
			});

			iwf_media_frames[field].off('content:create:browse');
			iwf_media_frames[field].on('content:create:browse', function (content) {
				var state = this.state();

				this.$el.removeClass('hide-toolbar');

				content.view = new wp.media.view.AttachmentsBrowser({
					controller: this,
					collection: state.get('library'),
					selection: state.get('selection'),
					model: state,
					sortable: state.get('sortable'),
					search: state.get('searchable'),
					filters: _.isEmpty(filter) ? 'all' : false,
					display: format == 'html',
					dragInfo: state.get('dragInfo'),
					AttachmentView: state.get('AttachmentView')
				});
			}, iwf_media_frames[field]);

			iwf_media_frames[field].on('select', function () {
				var state = this.state(),
					attachment = state.get('selection').first().toJSON(),
					display = state.display(state.get('selection').first()).toJSON(),
					insert_data;

				if (format == 'html') {
					var html;

					if (!wp.media.view.settings.captions) {
						delete attachment.caption;
					}

					var props = wp.media.string.props(display, attachment);

					if ('image' === attachment.type) {
						html = wp.media.string.image(props);

					} else if ('video' === attachment.type) {
						html = wp.media.string.video(props, attachment);

					} else if ('audio' === attachment.type) {
						html = wp.media.string.audio(props, attachment);

					} else {
						html = wp.media.string.link(props);
					}

					insert_data = html;

				} else if (!_.isUndefined(attachment[format])) {
					insert_data = attachment[format];
				}

				switch (mode) {
					case 'insert':
						if (!to_codemirror) {
							insert_at_caret(insert_data);

						} else {
							codemirror_object.replaceSelection(insert_data);
						}

						break;

					case 'append':
						if (!to_codemirror) {
							$element.val($element.val() + insert_data);

						} else {
							var code_text = codemirror_object.getValue();
							code_text += insert_data;
							codemirror_object.setValue(code_text);
						}

						break;

					case 'replace':
					default:
						if (!to_codemirror) {
							$element.val(insert_data);

						} else {
							codemirror_object.setValue(insert_data);
						}
				}

				$preview.trigger('change-media', [attachment]);

			}, iwf_media_frames[field]);

			iwf_media_frames[field].open();
		});
	})
})(jQuery, window);
