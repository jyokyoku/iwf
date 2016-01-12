/**
 * Inspire WordPress Framework (IWF)
 *
 * @package   IWF
 * @author    Masayuki Ietomi
 * @copyright Copyright(c) 2011 Masayuki Ietomi
 */

(function ($) {
	$(function () {
		$('div[class="validation"]').parents('form').validation({
			errHoverHide: true,
			errTipCloseBtn: false,
			stepValidation: true
		});
	})
})(jQuery, window);
