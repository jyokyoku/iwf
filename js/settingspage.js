/**
 * Inspire WordPress Framework (IWF)
 *
 * @package		IWF
 * @author		Masayuki Ietomi
 * @copyright	Copyright(c) 2011 Masayuki Ietomi
 */

(function($) {
	$(function() {
		$('form[action="options.php"]').validation({
			errHoverHide: true,
			errTipCloseBtn: false,
			stepValidation: true
		});
	})
})(jQuery, window);
