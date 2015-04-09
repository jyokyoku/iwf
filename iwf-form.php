<?php
/**
 * Inspire WordPress Framework (IWF)
 *
 * @package        IWF
 * @author         Masayuki Ietomi <jyokyoku@gmail.com>
 * @copyright      Copyright(c) 2011 Masayuki Ietomi
 * @link           http://inspire-tech.jp
 */

require_once dirname( __FILE__ ) . '/iwf-loader.php';
require_once dirname( __FILE__ ) . '/iwf-functions.php';
require_once dirname( __FILE__ ) . '/iwf-tag.php';

class IWF_Form {
	public static function input( $name, $value = null, array $attributes = array() ) {
		if ( is_array( $name ) ) {
			$attributes = $name;

		} else {
			$attributes['name']  = $name;
			$attributes['value'] = $value;
		}

		if ( ! isset( $attributes['id'] ) && isset( $attributes['name'] ) ) {
			$attributes['id'] = self::_generate_id( $attributes['name'] );
		}

		if ( empty( $attributes['type'] ) ) {
			$attributes['type'] = 'text';
		}

		$label = iwf_get_array_hard( $attributes, 'label' );
		$html  = IWF_Tag::create( 'input', $attributes );

		if ( $label ) {
			$label_attributes = ! empty( $attributes['id'] ) ? array( 'for' => $attributes['id'] ) : array();
			$html             = IWF_Tag::create( 'label', $label_attributes, sprintf( self::_filter_label( esc_html( $label ), $attributes['type'] ), $html ) );
		}

		return apply_filters( 'iwf_form_input', $html, $name, $value, $attributes );
	}

	public static function text( $name, $value = null, array $attributes = array() ) {
		if ( is_array( $name ) ) {
			$attributes = $name;

		} else {
			$attributes['name']  = $name;
			$attributes['value'] = $value;
		}

		$attributes['type'] = __FUNCTION__;

		$html = self::input( $attributes );

		return apply_filters( 'iwf_form_text', $html, $name, $value, $attributes );
	}

	public static function password( $name, $value = null, array $attributes = array() ) {
		if ( is_array( $name ) ) {
			$attributes = $name;

		} else {
			$attributes['name']  = $name;
			$attributes['value'] = $value;
		}

		$attributes['type'] = __FUNCTION__;

		$html = self::input( $attributes );

		return apply_filters( 'iwf_form_password', $html, $name, $value, $attributes );
	}

	public static function hidden( $name, $value = null, array $attributes = array() ) {
		if ( is_array( $name ) ) {
			$attributes = $name;

		} else {
			$attributes['name']  = $name;
			$attributes['value'] = $value;
		}

		$attributes['type'] = __FUNCTION__;

		$html = self::input( $attributes );

		return apply_filters( 'iwf_form_hidden', $html, $name, $value, $attributes );
	}

	public static function file( $name, array $attributes = array() ) {
		if ( is_array( $name ) ) {
			$attributes = $name;

		} else {
			$attributes['name'] = $name;
		}

		if ( isset( $attributes['value'] ) ) {
			unset( $attributes['value'] );
		}

		$attributes['type'] = __FUNCTION__;

		$html = self::input( $attributes );

		return apply_filters( 'iwf_form_file', $html, $name, $attributes );
	}

	public static function textarea( $name, $value = null, array $attributes = array() ) {
		if ( is_array( $name ) ) {
			$attributes = $name;

		} else {
			$attributes['name']  = $name;
			$attributes['value'] = $value;
		}

		if ( ! isset( $attributes['id'] ) && isset( $attributes['name'] ) ) {
			$attributes['id'] = self::_generate_id( $attributes['name'] );
		}

		$label = iwf_get_array_hard( $attributes, 'label' );
		$value = esc_textarea( iwf_get_array_hard( $attributes, 'value', '' ) );

		$html = IWF_Tag::create( 'textarea', $attributes, $value );

		if ( $label ) {
			$label_attributes = ! empty( $attributes['id'] ) ? array( 'for' => $attributes['id'] ) : array();
			$html             = IWF_Tag::create( 'label', $label_attributes, sprintf( self::_filter_label( esc_html( $label ), __FUNCTION__ ), $html ) );
		}

		return apply_filters( 'iwf_form_textarea', $html, $name, $value, $attributes );
	}

	public static function select( $name, $options = array(), array $attributes = array() ) {
		if ( is_array( $name ) ) {
			$attributes = $name;

		} else {
			$attributes['name']    = $name;
			$attributes['options'] = $options;
		}

		$selected = null;

		foreach ( array( 'selected', 'checked' ) as $_selected_key ) {
			if ( array_key_exists( $_selected_key, $attributes ) ) {
				if ( empty( $selected ) ) {
					$selected = iwf_get_array_hard( $attributes, $_selected_key );

				} else {
					unset( $attributes[ $_selected_key ] );
				}
			}
		}

		if ( ! is_array( $selected ) ) {
			$selected = array( $selected );
		}

		$options = array();

		foreach ( array( 'options', 'values', 'value' ) as $_value_key ) {
			if ( array_key_exists( $_value_key, $attributes ) ) {
				if ( empty( $options ) ) {
					$options = iwf_get_array_hard( $attributes, $_value_key );

				} else {
					unset( $attributes[ $_value_key ] );
				}
			}
		}

		if ( ! is_array( $options ) ) {
			$options = array();
		}

		if ( $empty = iwf_get_array_hard( $attributes, 'empty' ) ) {
			if ( $empty === true || $empty === 1 ) {
				$empty = '';
			}

			$empty = iwf_html_tag( 'option', array( 'value' => '' ), $empty );
		}

		if ( ! isset( $attributes['id'] ) && isset( $attributes['name'] ) ) {
			$attributes['id'] = self::_generate_id( $attributes['name'] );
		}

		$label = iwf_get_array_hard( $attributes, 'label' );
		$html  = IWF_Tag::create( 'select', $attributes, $empty . self::_generate_options( $name, $options, $selected, iwf_check_value_only( $options ) ) );

		if ( $label ) {
			$label_attributes = ! empty( $attributes['id'] ) ? array( 'for' => $attributes['id'] ) : array();
			$html             = IWF_Tag::create( 'label', $label_attributes, sprintf( self::_filter_label( esc_html( $label ), __FUNCTION__ ), $html ) );
		}

		return apply_filters( 'iwf_form_select', $html, $name, $options, $attributes );
	}

	public static function checkbox( $name, $value = null, array $attributes = array() ) {
		if ( is_array( $name ) ) {
			$attributes = $name;

		} else {
			$attributes['name']  = $name;
			$attributes['value'] = $value;
		}

		$hidden_field = iwf_get_array( $attributes, 'hidden_field', null, true );

		$attributes['type'] = __FUNCTION__;
		$html               = '';

		if ( isset( $attributes['name'] ) && $hidden_field !== false ) {
			$html = IWF_Tag::create( 'input', array(
				'type'    => 'hidden',
				'value'   => '',
				'name'    => $attributes['name'],
				'id'      => self::_generate_id( $attributes['name'] . '_hidden' ),
				'_escape' => true
			) );
		}

		$html .= self::input( $attributes );

		return apply_filters( 'iwf_form_checkbox', $html, $name, $value, $attributes );
	}

	public static function checkboxes( $name, $values = null, array $attributes = array() ) {
		if ( is_array( $name ) ) {
			$attributes = $name;

		} else {
			$attributes['name']   = $name;
			$attributes['values'] = $values;
		}

		list( $name, $before, $after, $separator ) = array_values( iwf_get_array_hard( $attributes, array( 'name', 'before', 'after', 'separator' ) ) );

		if ( $separator === null ) {
			$separator = '&nbsp;&nbsp;';
		}

		$checked = null;

		foreach ( array( 'checked', 'selected' ) as $_checked_key ) {
			if ( array_key_exists( $_checked_key, $attributes ) ) {
				if ( empty( $checked ) ) {
					$checked = iwf_get_array_hard( $attributes, $_checked_key );

				} else {
					unset( $attributes[ $_checked_key ] );
				}
			}
		}

		if ( ! is_array( $checked ) ) {
			$checked = array( $checked );
		}

		$values = array();

		foreach ( array( 'values', 'options', 'value' ) as $_value_key ) {
			if ( array_key_exists( $_value_key, $attributes ) ) {
				if ( empty( $values ) ) {
					$values = iwf_get_array_hard( $attributes, $_value_key );

				} else {
					unset( $attributes[ $_value_key ] );
				}
			}
		}

		if ( ! is_array( $values ) ) {
			$values = array( (string) $values => $values );
		}

		$value_only = iwf_check_value_only( $values );

		$checkboxes = array();
		$i          = 0;

		$values       = array_unique( $values );
		$values_total = count( $values );

		foreach ( $values as $label => $value ) {
			$_attributes = $attributes;
			$_name       = $name . "[{$i}]";

			if ( $value_only ) {
				$label = $value;
			}

			$_attributes['label']   = $label;
			$_attributes['checked'] = in_array( $value, $checked );
			$_attributes['id']      = self::_generate_id( $_name );

			$checkbox     = $before . self::checkbox( $_name, $value, $_attributes ) . $after;
			$checkboxes[] = apply_filters( 'iwf_form_checkboxes_single', $checkbox, $name, $i, $values_total, $value, $_attributes );
			$i ++;
		}

		$html = implode( $separator, $checkboxes );

		return apply_filters( 'iwf_form_checkboxes', $html, $name, $values, $attributes );
	}

	public static function radio( $name, $values = null, array $attributes = array() ) {
		if ( is_array( $name ) ) {
			$attributes = $name;

		} else {
			$attributes['name']   = $name;
			$attributes['values'] = $values;
		}

		list( $name, $before, $after, $separator ) = array_values( iwf_get_array_hard( $attributes, array( 'name', 'before', 'after', 'separator' ) ) );

		if ( $separator === null ) {
			$separator = '&nbsp;&nbsp;';
		}

		$checked = null;

		foreach ( array( 'checked', 'selected' ) as $_checked_key ) {
			if ( array_key_exists( $_checked_key, $attributes ) ) {
				if ( empty( $checked ) ) {
					$checked = iwf_get_array_hard( $attributes, $_checked_key );

				} else {
					unset( $attributes[ $_checked_key ] );
				}
			}
		}

		if ( is_array( $checked ) ) {
			$checked = reset( $checked );
		}

		$values = array();

		foreach ( array( 'values', 'options', 'value' ) as $_value_key ) {
			if ( array_key_exists( $_value_key, $attributes ) ) {
				if ( empty( $values ) ) {
					$values = iwf_get_array_hard( $attributes, $_value_key );

				} else {
					unset( $attributes[ $_value_key ] );
				}
			}
		}

		if ( ! is_array( $values ) ) {
			$values = array( (string) $values => $values );
		}

		$value_only = iwf_check_value_only( $values );

		$radios = array();
		$i      = 0;

		$values       = array_unique( $values );
		$values_total = count( $values );

		foreach ( $values as $label => $value ) {
			$_attributes = $attributes;

			if ( $value_only ) {
				$label = $value;
			}

			$_attributes['label']   = $label;
			$_attributes['checked'] = ( $value == $checked );
			$_attributes['type']    = 'radio';

			if ( $name ) {
				$_attributes['id'] = self::_generate_id( $name . '_' . $i );
			}

			$radio    = $before . self::input( $name, $value, $_attributes ) . $after;
			$radios[] = apply_filters( 'iwf_form_radio_single', $radio, $name, $i, $values_total, $value, $_attributes );
			$i ++;
		}

		$html = implode( $separator, $radios );

		return apply_filters( 'iwf_form_radio', $html, $name, $values, $attributes );
	}

	protected static function _generate_id( $name ) {
		return '_' . preg_replace( array( '/\]\[|\[/', '/(\[\]|\])/' ), array( '_', '' ), $name );
	}

	protected static function _filter_label( $label, $type = 'text' ) {
		if ( ! preg_match_all( '/(?:^|[^%])%(?:[0-9]+\$)?s/u', $label, $matches ) ) {
			$label = in_array( $type, array( 'checkbox', 'radio' ), true ) ? '%s&nbsp;' . $label : $label . '&nbsp;%s';
		}

		return $label;
	}

	protected static function _generate_options( $name, array $options, array $selected = array(), $value_only = false ) {
		$result        = '';
		$options_total = count( $options );
		$i             = 0;

		foreach ( $options as $label => $value ) {
			if ( is_array( $value ) ) {
				$attributes = array( 'label' => $label );
				$html       = IWF_Tag::create(
					'optgroup',
					$attributes,
					self::_generate_options( $name, $value, $selected, iwf_check_value_only( $value ) )
				);

				$result .= apply_filters( 'iwf_form_select_optgroup', $html, $name, $i, $options_total, $value, $attributes );

			} else {
				if ( $value_only ) {
					$label = $value;
				}

				$attributes = array( 'value' => $value );

				if ( in_array( $value, $selected ) ) {
					$attributes['selected'] = true;
				}

				$attributes = array_map( 'esc_attr', $attributes );
				$html       = IWF_Tag::create( 'option', $attributes, esc_html( $label ) );

				$result .= apply_filters( 'iwf_form_select_option', $html, $name, $i, $options_total, $value, $attributes );
			}

			$i ++;
		}

		return $result;
	}
}
