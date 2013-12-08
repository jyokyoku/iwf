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
require_once dirname( __FILE__ ) . '/iwf-tag.php';

class IWF_Form {
	public static function input( $name, $value = null, array $attributes = array() ) {
		if ( is_array( $name ) ) {
			$attributes = $name;

		} else {
			$attributes['name'] = $name;
			$attributes['value'] = $value;
		}

		if ( !isset( $attributes['id'] ) && isset( $attributes['name'] ) ) {
			$attributes['id'] = self::_generate_id( $attributes['name'] );
		}

		if ( empty( $attributes['type'] ) ) {
			$attributes['type'] = 'text';
		}

		$label = iwf_get_array_hard( $attributes, 'label' );

		$attributes = array_map( 'esc_attr', $attributes );
		$html = IWF_Tag::create( 'input', $attributes );

		if ( $label ) {
			$label_attributes = !empty( $attributes['id'] ) ? array( 'for' => $attributes['id'] ) : array();
			$html = IWF_Tag::create( 'label', $label_attributes, sprintf( self::_filter_label( esc_html( $label ), $attributes['type'] ), $html ) );
		}

		return $html;
	}

	public static function text( $name, $value = null, array $attributes = array() ) {
		if ( is_array( $name ) ) {
			$attributes = $name;

		} else {
			$attributes['name'] = $name;
			$attributes['value'] = $value;
		}

		$attributes['type'] = __FUNCTION__;

		return self::input( $attributes );
	}

	public static function password( $name, $value = null, array $attributes = array() ) {
		if ( is_array( $name ) ) {
			$attributes = $name;

		} else {
			$attributes['name'] = $name;
			$attributes['value'] = $value;
		}

		$attributes['type'] = __FUNCTION__;

		return self::input( $attributes );
	}

	public static function hidden( $name, $value = null, array $attributes = array() ) {
		if ( is_array( $name ) ) {
			$attributes = $name;

		} else {
			$attributes['name'] = $name;
			$attributes['value'] = $value;
		}

		$attributes['type'] = __FUNCTION__;

		return self::input( $attributes );
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

		return self::input( $attributes );
	}

	public static function textarea( $name, $value = null, array $attributes = array() ) {
		if ( is_array( $name ) ) {
			$attributes = $name;

		} else {
			$attributes['name'] = $name;
			$attributes['value'] = $value;
		}

		if ( !isset( $attributes['id'] ) && isset( $attributes['name'] ) ) {
			$attributes['id'] = self::_generate_id( $attributes['name'] );
		}

		$label = iwf_get_array_hard( $attributes, 'label' );
		$value = esc_textarea( iwf_get_array_hard( $attributes, 'value', '' ) );

		$attributes = array_map( 'esc_attr', $attributes );
		$html = IWF_Tag::create( 'textarea', $attributes, $value );

		if ( $label ) {
			$label_attributes = !empty( $attributes['id'] ) ? array( 'for' => $attributes['id'] ) : array();
			$html = IWF_Tag::create( 'label', $label_attributes, sprintf( self::_filter_label( esc_html( $label ), __FUNCTION__ ), $html ) );
		}

		return $html;
	}

	public static function select( $name, $options = array(), array $attributes = array() ) {
		if ( is_array( $name ) ) {
			$attributes = $name;

		} else {
			$attributes['name'] = $name;
			$attributes['options'] = $options;
		}

		$selected = iwf_extract_and_merge( $attributes, array( 'selected', 'checked' ) );
		$options = iwf_extract_and_merge( $attributes, array( 'options', 'value', 'values' ) );

		if ( $empty = iwf_get_array_hard( $attributes, 'empty' ) ) {
			if ( $empty === true || $empty === 1 ) {
				$empty = '';
			}

			$options = array_merge( array( $empty => '' ), $options );
		}

		$selected = (array)$selected;

		if ( !isset( $attributes['id'] ) && isset( $attributes['name'] ) ) {
			$attributes['id'] = self::_generate_id( $attributes['name'] );
		}

		$label = iwf_get_array_hard( $attributes, 'label' );
		$html = IWF_Tag::create( 'select', $attributes, self::_generate_options( $options, $selected ) );

		if ( $label ) {
			$label_attributes = !empty( $attributes['id'] ) ? array( 'for' => $attributes['id'] ) : array();
			$html = IWF_Tag::create( 'label', $label_attributes, sprintf( self::_filter_label( esc_html( $label ), __FUNCTION__ ), $html ) );
		}

		return $html;
	}

	public static function checkbox( $name, $value = null, array $attributes = array() ) {
		if ( is_array( $name ) ) {
			$attributes = $name;

		} else {
			$attributes['name'] = $name;
			$attributes['value'] = $value;
		}

		$attributes['type'] = __FUNCTION__;
		$html = '';

		if ( isset( $attributes['name'] ) ) {
			$html = IWF_Tag::create( 'input', array(
				'type' => 'hidden', 'value' => '', 'name' => esc_attr( $attributes['name'] ),
				'id' => esc_attr( self::_generate_id( $attributes['name'] . '_hidden' ) )
			) );
		}

		$html .= self::input( $attributes );

		return $html;
	}

	public static function checkboxes( $name, $values = null, array $attributes = array() ) {
		if ( is_array( $name ) ) {
			$attributes = $name;

		} else {
			$attributes['name'] = $name;
			$attributes['values'] = $values;
		}

		list( $name, $before, $after, $separator ) = array_values( iwf_get_array_hard( $attributes, array( 'name', 'before', 'after', 'separator' ) ) );

		if ( $separator === null ) {
			$separator = '&nbsp;&nbsp;';
		}

		$checked = reset( iwf_extract_and_merge( $attributes, array( 'checked', 'selected' ) ) );
		$values = iwf_extract_and_merge( $attributes, array( 'value', 'values', 'options' ) );

		if ( !is_array( $values ) ) {
			$values = array( (string)$values => $values );
		}

		$checkboxes = array();
		$i = 0;

		foreach ( array_unique( $values ) as $label => $value ) {
			$_attributes = $attributes;
			$_name = null;

			if ( is_array( $value ) ) {
				if ( key( $value ) !== 0 ) {
					list( $_name, $value ) = each( $value );

				} else if ( count( $value ) > 1 ) {
					list( $_name, $value ) = array_values( $value );

				} else {
					$value = reset( $value );
				}
			}

			if ( empty( $_name ) ) {
				$_name = $name . "[{$i}]";
				$i++;
			}

			if ( is_int( $label ) ) {
				$label = $value;
			}

			$_attributes['label'] = $label;
			$_attributes['checked'] = ( $value == $checked );
			$_attributes['id'] = self::_generate_id( $_name );

			$checkboxes[] = $before . self::checkbox( $_name, $value, $_attributes ) . $after;
		}

		return implode( esc_html( $separator ), $checkboxes );
	}

	public static function radio( $name, $values = null, array $attributes = array() ) {
		if ( is_array( $name ) ) {
			$attributes = $name;

		} else {
			$attributes['name'] = $name;
			$attributes['values'] = $values;
		}

		list( $name, $before, $after, $separator ) = array_values( iwf_get_array_hard( $attributes, array( 'name', 'before', 'after', 'separator' ) ) );

		if ( $separator === null ) {
			$separator = '&nbsp;&nbsp;';
		}

		$checked = reset( iwf_extract_and_merge( $attributes, array( 'checked', 'selected' ) ) );
		$values = iwf_extract_and_merge( $attributes, array( 'value', 'values', 'options' ) );

		if ( !is_array( $values ) ) {
			$values = array( (string)$values => $values );
		}

		$radios = array();
		$i = 0;

		foreach ( array_unique( $values ) as $label => $value ) {
			$_attributes = $attributes;

			if ( is_int( $label ) ) {
				$label = $value;
			}

			$_attributes['label'] = $label;
			$_attributes['checked'] = ( $value == $checked );
			$_attributes['type'] = 'radio';

			if ( $name ) {
				$_attributes['id'] = self::_generate_id( $name . '_' . $i );
			}

			$radios[] = $before . self::input( $name, $value, $_attributes ) . $after;
			$i++;
		}

		return implode( esc_html( $separator ), $radios );
	}

	protected static function _generate_id( $name ) {
		return '_' . preg_replace( array( '/\]\[|\[/', '/(\[\]|\])/' ), array( '_', '' ), $name );
	}

	protected static function _filter_label( $label, $type = 'text' ) {
		if ( !preg_match_all( '/(?:^|[^%])%(?:[0-9]+\$)?s/u', $label, $matches ) ) {
			$label = in_array( $type, array( 'checkbox', 'radio' ), true ) ? '%s&nbsp;' . $label : $label . '&nbsp;%s';
		}

		return $label;
	}

	protected static function _generate_options( array $options, array $selected = array() ) {
		$html = '';

		foreach ( $options as $label => $value ) {
			if ( is_array( $value ) ) {
				$html .= IWF_Tag::create(
					'optgroup',
					array( 'label' => $label ),
					self::_generate_options( $value, $selected )
				);

			} else {
				if ( is_int( $label ) ) {
					$label = $value;
				}

				$option_attributes = array( 'value' => $value );

				if ( in_array( $value, $selected ) ) {
					$option_attributes['selected'] = true;
				}

				$option_attributes = array_map( 'esc_attr', $option_attributes );
				$html .= IWF_Tag::create( 'option', $option_attributes, esc_html( $label ) );
			}
		}

		return $html;
	}
}