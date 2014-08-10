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

class IWF_Tag {
	protected $stack = array();

	protected $elements = array();

	protected $context_stack = array();

	public function __call( $method, $args ) {
		if ( preg_match( '/^(open|close)_([a-zA-Z_]+)$/', $method, $matches ) ) {
			call_user_func( array( $this, $matches[1] ), $matches[2], $args );

		} else {
			$attributes = !empty( $args ) ? (array)array_shift( $args ) : array();
			$this->open( $method, $attributes );
		}

		return $this;
	}

	public function __get( $property ) {
		return $this->{$property}();
	}

	public function open( $tag, $attributes = array() ) {
		$tag = strtolower( $tag );
		$element = new IWF_Tag_Element_Node( $tag, $attributes );

		if ( !$element->is_empty() ) {
			$this->stack[] = $tag;
		}

		$this->elements[] = $element;

		return $this;
	}

	public function close( $tag = null ) {
		if ( !empty( $this->stack ) ) {
			$current_tag = array_pop( $this->stack );

			if ( !empty( $tag ) && strtolower( $tag ) !== $current_tag ) {
				trigger_error( 'Tag "' . strtolower( $tag ) . '" is not current opened tag', E_USER_WARNING );

			} else {
				$this->elements[] = new IWF_Tag_Element_Node( $current_tag, false );
			}
		}

		return $this;
	}

	public function func( $callback ) {
		$args = func_get_args();
		$args = array_splice( $args, 1 );

		if ( is_callable( $callback ) ) {
			if ( $result = call_user_func_array( $callback, $args ) ) {
				$this->html( $result );
			}
		}

		return $this;
	}

	public function all_close() {
		while ( $this->stack ) {
			$this->close();
		}

		return $this;
	}

	public function clear() {
		$this->clear_stack();
		$this->clear_elements();

		return $this;
	}

	public function clear_stack() {
		$this->stack = array();

		return $this;
	}

	public function clear_elements() {
		$this->elements = array();

		return $this;
	}

	public function html( $html ) {
		$this->elements[] = new IWF_Tag_Element_Html( $html );

		return $this;
	}

	public function render() {
		$this->all_close();

		$html = '';

		foreach ( $this->elements as $element ) {
			$html .= $element->render();
		}

		$this->clear();

		return $html;
	}

	public function switch_context() {
		$this->context_stack[] = array(
			'elements' => $this->elements,
			'stack' => $this->stack
		);

		$this->clear();
	}

	public function restore_context() {
		if ( count( $this->context_stack ) > 0 ) {
			$this->clear();
			$context_stack = array_pop( $this->context_stack );

			$this->elements = $context_stack['elements'];
			$this->stack = $context_stack['stack'];
		}
	}

	public function restore_all_context() {
		while ( $this->context_stack ) {
			$this->restore_context();
		}
	}

	public static function create( $tag, $attributes = array(), $content = null ) {
		$open = new IWF_Tag_Element_Node( $tag, $attributes );

		if ( $content !== false && !is_null( $content ) ) {
			$close = new IWF_Tag_Element_Node( $tag, false );
			$html = $open->render() . $content . $close->render();

		} else {
			$html = $open->render();
		}

		return $html;
	}
}

interface IWF_Tag_Element_Interface {
	public function render();
}

class IWF_Tag_Element_Node implements IWF_Tag_Element_Interface {
	protected static $open_tag_format = '<%s%s>';

	protected static $close_tag_format = '</%s>';

	protected static $empty_tag_format = '<%s%s />';

	protected static $attribute_format = '%s="%s"';

	protected static $empty_tags = array(
		'area', 'base', 'br', 'col', 'hr', 'img',
		'input', 'link', 'meta', 'param'
	);

	protected static $minimized_attributes = array(
		'compact', 'checked', 'declare', 'readonly', 'disabled', 'selected',
		'defer', 'ismap', 'nohref', 'noshade', 'nowrap', 'multiple', 'noresize'
	);

	protected $tag;

	protected $close;

	protected $attributes = array();

	public function __construct( $tag, $attributes = array() ) {
		$this->tag = $tag;
		$this->close = ( $attributes === false );
		$this->attributes = $attributes;
	}

	public function is_empty() {
		return in_array( $this->tag, self::$empty_tags );
	}

	public function render() {
		if ( $this->close ) {
			$html = sprintf( self::$close_tag_format, $this->tag );

		} else {
			$attributes = ( $attributes = self::parse_attributes( $this->attributes ) ) ? ' ' . $attributes : '';

			if ( $this->is_empty() ) {
				$html = sprintf( self::$empty_tag_format, $this->tag, $attributes );

			} else {
				$html = sprintf( self::$open_tag_format, $this->tag, $attributes );
			}
		}

		return $html;
	}

	public static function parse_attributes( $attributes = array() ) {
		$formatted = array();

		foreach ( wp_parse_args( $attributes ) as $property => $value ) {
			if ( is_array( $value ) ) {
				$value = implode( ' ', $value );
			}

			if ( is_string( $value ) ) {
				$value = trim( $value );
			}

			if ( is_numeric( $property ) ) {
				if ( empty( $value ) || strpos( ' ', $value ) !== false ) {
					continue;
				}

				$property = $value;

			} else if ( in_array( $property, self::$minimized_attributes ) ) {
				if ( $value !== true && $value !== '1' && $value !== 1 && $value != $property ) {
					continue;
				}

				$value = $property;
			}

			$formatted[] = sprintf( self::$attribute_format, $property, esc_attr( $value ) );
		}

		return implode( ' ', $formatted );
	}

	public static function add_class( array &$attributes, $class ) {
		if ( empty( $attributes['class'] ) ) {
			$attributes['class'] = $class;

		} else {
			$attributes['class'] .= " {$class}";
		}
	}
}

class IWF_Tag_Element_Html implements IWF_Tag_Element_Interface {
	protected $html;

	public function __construct( $html ) {
		$this->html = $html;
	}

	public function render() {
		return $this->html;
	}
}