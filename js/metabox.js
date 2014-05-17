/**
 * Inspire WordPress Framework (IWF)
 *
 * @package   IWF
 * @author    Masayuki Ietomi
 * @copyright Copyright(c) 2011 Masayuki Ietomi
 */

(function ($) {
	$(function () {
		$('form#post').validation({
			errHoverHide: true,
			errTipCloseBtn: false,
			stepValidation: true,
			customAddError: function () {
				$('input[type="submit"], a.submitdelete', '#submitpost').each(function () {
					$(':button, :submit', '#submitpost').each(function () {
						var t = $(this);

						if (t.hasClass('button-primary')) {
							t.removeClass('button-primary-disabled');

						} else {
							t.removeClass('button-disabled');
						}
					});

					if ($(this).attr('id') == 'publish') {
						$('#major-publishing-actions .spinner').hide();

					} else {
						$('#minor-publishing .spinner').hide();
					}
				});
			}
		});
	});
})(jQuery, window);