<?php
/**
 * Type class
 *
 * @author Gerry Demaret <gerry@tigron.be>
 * @author Christophe Gosiau <christophe@tigron.be>
 */

namespace Skeleton\Application\Api\Media;

class Type {

	/**
	 * Additional Properties
	 *
	 * @access public
	 * @var $\Skeleton\Application\Api\Media\Type|null $additional_properties
	 */
	public $additional_properties = null;

	/**
	 * Description
	 * An optional description for the media_type
	 *
	 * @access public
	 * @var string $description
	 */
	public $description = null;

	/**
	 * Example
	 * An optional example for the media_type
	 *
	 * @access public
	 * @var mixed $example
	 */
	public $example = null;

	/**
	 * Format
	 *
	 * @access public
	 * @var string $format
	 */
	public $format = null;

	/**
	 * Nullable
	 *
	 * @access public
	 * @var boolean $nullable
	 */
	public $nullable = null;

	/**
	 * Deprecated
	 *
	 * @access public
	 * @var boolean $deprecated
	 */
	public $deprecated = null;

	/**
	 * Properties
	 *
	 * @access public
	 * @var array $propertiess
	 */
	public $properties = [];

	/**
	 * Readonly
	 *
	 * @access public
	 * @var bool $readonly
	 */
	public $readonly = null;

	/**
	 * Default
	 *
	 * @access public
	 * @var bool $default
	 */
	public $default = null;

	/**
	 * Writeonly
	 *
	 * @access public
	 * @var bool $writeonly
	 */
	public $writeonly = null;

	/**
	 * Enum
	 *
	 * @access public
	 * @var array $enum
	 */
	public $enum = null;

	/**
	 * Required
	 *
	 * @access public
	 * @var bool $required
	 */
	public $required = false;

	/**
	 * The data type
	 *
	 * @access public
	 * @var string $type
	 */
	public $type = '';

	/**
	 * Valuetype
	 * The object in the array or the classname in case of type=object
	 *
	 * @access public
	 * @var string $value_type
	 */
	public $value_type = null;

	/**
	 * Validate
	 *
	 * @access public
	 */
	public function validate() {
		// Non primitive data types
		$non_primitives = [ 'object', 'array' ];
		if (in_array($this->type, $non_primitives)) {
			// The value_type must be a valid classname
			if ($this->type == 'object') {
				if (!class_exists($this->value_type)) {
					throw new \Exception('' .
						'Media type validation error: Media type with type ' .
						$this->type . ' references value type ' .
						$this->value_type . ' but it does not exist'
					);
				}
			}

			if (isset($this->format)) {
				throw new \Exception('' .
					'Media type validation error: Media type with type ' .
					$this->type . ' has defined a format, which is not allowed'
				);
			}

			return;
		}

		// Primitive data types
		$primitives = [ 'integer', 'number', 'string', 'boolean' ];

		if (in_array($this->type, $primitives)) {
			if (isset($this->value_type)) {
				throw new \Exception('' .
					'Media type validation error: Media type with type ' .
					$this->type . ' has value_type set to "' .
					$this->value_type . '" which is not allowed'
				);
			}

			if ($this->type == 'integer') {
				$integer_formats = [
					'int32',
					'int64'
				];

				if (isset($this->format) and !in_array($this->format, $integer_formats)) {
					throw new \Exception(''.
						'Media type validation error: Media type with type ' .
						$this->type . ' cannot have format "' .
						$this->format . '"'
					);
				}
			}

			if ($this->type == 'number') {
				$number_formats = [
					'float',
					'double'
				];

				if (isset($this->format) and !in_array($this->format, $number_formats)) {
					throw new \Exception(
						'Media type validation error: Media type with type ' .
						$this->type . ' cannot have format "' .
						$this->format . '"'
					);
				}
			}

			if ($this->type == 'string') {
				$string_formats = [
					'byte',
					'binary',
					'date',
					'date-time',
					'password',
					'email',
					'uuid'
				];

				if (isset($this->format) and !in_array($this->format, $string_formats)) {
					throw new \Exception(
						'Media type validation error: Media type with type ' .
						$this->type . ' cannot have format "' .
						$this->format . '"'
					);
				}
			}

			return;
		}

		throw new \Exception('Media type validation error: incorrect type "' . $this->type . '"');
	}

	/**
	 * Get schema
	 *
	 * @access public
	 * @return array $schema
	 */
	public function get_schema($object_reference = true) {
		$this->validate();
		$application = \Skeleton\Core\Application::get();

		if (is_callable([$this, 'get_schema_' . $this->type])) {
			return call_user_func_array([$this, 'get_schema_' . $this->type], [ $object_reference ]);
		}

		throw new \Exception('Cannot create schema for type ' . $this->type);
	}

	/**
	 * Get schema for type=object
	 *
	 * @access private
	 * @param boolean $object_reference
	 */
	private function get_schema_object($object_reference = true) {
		$schema = [];

		if ($object_reference) {
			$classname = new $this->value_type();
			$name = $classname->get_openapi_component_name();
			$schema['$ref'] = '#/components/schemas/' . $name;
			return $schema;
		}

		$schema['type'] = 'object';
		if ($this->description !== null) {
			$schema['description'] = $this->description;
		}
		$schema['properties'] = [];
		$required_properties = [];
		foreach ($this->properties as $key => $property) {
			$schema['properties'][$key] = $property->get_schema(true);
			if ($property->required) {
				$required_properties[] = $key;
			}
		}

		if (count($required_properties) > 0) {
			$schema['required'] = $required_properties;
		}

		if (count($schema['properties']) == 0) {
			unset($schema['properties']);
		}

		if ($this->additional_properties instanceof \Skeleton\Application\Api\Media\Type) {
			$schema['additionalProperties'] = $this->additional_properties->get_schema();
		}

		return $schema;
	}

	/**
	 * Get schema for type=array
	 *
	 * @access private
	 * @param boolean $object_reference
	 */
	private function get_schema_array($object_reference = true) {
		$schema = [];
		$schema['type'] = 'array';
		if ($this->description !== null) {
			$schema['description'] = $this->description;
		}
		if (isset($this->readonly) and $this->readonly !== false) {
			$schema['readOnly'] = $this->readonly;
		}
		if (isset($this->default)) {
			$schema['default'] = $this->default;
		}
		if (isset($this->writeonly) and $this->writeonly !== false) {
			$schema['writeOnly'] = $this->writeonly;
		}
		$schema['items'] = $this->value_type->get_schema();
		return $schema;
	}

	/**
	 * Get schema for type=boolean
	 *
	 * @access private
	 * @param boolean $object_reference
	 */
	private function get_schema_boolean($object_reference = true) {
		$schema = [];
		$schema['type'] = 'boolean';
		if ($this->description !== null) {
			$schema['description'] = $this->description;
		}
		if (isset($this->nullable) and $this->nullable !== false) {
			$schema['nullable'] = $this->nullable;
		}
		if (isset($this->deprecated) and $this->deprecated !== false) {
			$schema['deprecated'] = $this->deprecated;
		}
		if (isset($this->readonly) and $this->readonly !== false) {
			$schema['readOnly'] = $this->readonly;
		}
		if (isset($this->default)) {
			$schema['default'] = $this->default;
		}
		if (isset($this->writeonly) and $this->writeonly !== false) {
			$schema['writeOnly'] = $this->writeonly;
		}
		return $schema;
	}

	/**
	 * Get schema for type=number
	 *
	 * @access private
	 * @param boolean $object_reference
	 */
	private function get_schema_number($object_reference = true) {
		$schema = [];
		$schema['type'] = 'number';
		if ($this->description !== null) {
			$schema['description'] = $this->description;
		}
		if ($this->example !== null) {
			$schema['example'] = $this->example;
		}
		if (isset($this->format)) {
			$schema['format'] = $this->format;
		}
		if (isset($this->nullable) and $this->nullable !== false) {
			$schema['nullable'] = $this->nullable;
		}
		if (isset($this->deprecated) and $this->deprecated !== false) {
			$schema['deprecated'] = $this->deprecated;
		}
		if (isset($this->readonly) and $this->readonly !== false) {
			$schema['readOnly'] = $this->readonly;
		}
		if (isset($this->default)) {
			$schema['default'] = $this->default;
		}
		if (isset($this->writeonly) and $this->writeonly !== false) {
			$schema['writeOnly'] = $this->writeonly;
		}
		return $schema;
	}

	/**
	 * Get schema for type=integer
	 *
	 * @access private
	 * @param boolean $object_reference
	 */
	private function get_schema_integer($object_reference = true) {
		$schema = [];
		$schema['type'] = 'integer';
		if ($this->description !== null) {
			$schema['description'] = $this->description;
		}
		if ($this->example !== null) {
			$schema['example'] = $this->example;
		}
		if (isset($this->format)) {
			$schema['format'] = $this->format;
		}
		if (isset($this->nullable) and $this->nullable !== false) {
			$schema['nullable'] = $this->nullable;
		}
		if (isset($this->deprecated) and $this->deprecated !== false) {
			$schema['deprecated'] = $this->deprecated;
		}
		if (isset($this->readonly) and $this->readonly !== false) {
			$schema['readOnly'] = $this->readonly;
		}
		if (isset($this->default)) {
			$schema['default'] = $this->default;
		}
		if (isset($this->writeonly) and $this->writeonly !== false) {
			$schema['writeOnly'] = $this->writeonly;
		}
		return $schema;
	}

	/**
	 * Get schema for type=string
	 *
	 * @access private
	 * @param boolean $object_reference
	 */
	private function get_schema_string($object_reference = true) {
		$schema = [];
		$schema['type'] = 'string';
		if ($this->description !== null) {
			$schema['description'] = $this->description;
		}
		if ($this->example !== null) {
			$schema['example'] = $this->example;
		}
		if (isset($this->format)) {
			$schema['format'] = $this->format;
		}
		if (isset($this->nullable) and $this->nullable !== false) {
			$schema['nullable'] = $this->nullable;
		}
		if (isset($this->deprecated) and $this->deprecated !== false) {
			$schema['deprecated'] = $this->deprecated;
		}
		if (isset($this->readonly) and $this->readonly !== false) {
			$schema['readOnly'] = $this->readonly;
		}
		if (isset($this->default)) {
			$schema['default'] = $this->default;
		}
		if (isset($this->writeonly) and $this->writeonly !== false) {
			$schema['writeOnly'] = $this->writeonly;
		}
		if (isset($this->enum) and is_array($this->enum)) {
			$schema['enum'] = $this->enum;
		}
		return $schema;
	}

	/**
	 * create for reflection_type
	 *
	 * @access public
	 * @param \phpDocumentor\Reflection\Type $type
	 * @return self
	 */
	public static function create_for_reflection_type(\phpDocumentor\Reflection\Type $type) {
		$classname = get_class($type);
		$parts = explode('\\', $classname);
		$classname = end($parts);

		$media_type = new self();

		switch ($classname) {
			case 'String_':
				$media_type->type = 'string';
				break;
			case 'Integer':
				$media_type->type = 'integer';
				$media_type->format = 'int64';
				break;
			case 'Boolean':
				$media_type->type = 'boolean';
				break;
			case 'Float_':
				$media_type->type = 'number';
				$media_type->format = 'float';
				break;
			case 'Object_':
				// We will treat some objects as primitive types
				$classname = (string)$type->getFqsen();
				if (strtolower($classname) == "\date") {
					$media_type->type = 'string';
					$media_type->format = 'date';
				} elseif (strtolower($classname) == "\datetime") {
					$media_type->type = 'string';
					$media_type->format = 'date-time';
				} else {
					$media_type->type = 'object';
					$media_type->value_type = (string)$type->getFqsen();
				}
				break;
			case 'Array_':
				$media_type->type = 'array';
				$value_type = self::create_for_reflection_type($type->getValueType());
				$media_type->value_type = $value_type;
				break;
			default:
				throw new \Exception('Cannot create media type for type ' . $type);

		}
		return $media_type;
	}

	/**
	 * Create for mysql type
	 *
	 * @access public
	 * @param string $mysql_type
	 * @return self
	 */
	public static function create_for_mysql_type($mysql_type) {
		$media_type = new self();

		if (strpos($mysql_type, 'tinyint') === 0) {
			$media_type->type = 'integer';
			$media_type->format = 'int32';
			return $media_type;
		}

		if (strpos($mysql_type, 'int') === 0) {
			$media_type->type = 'integer';
			$media_type->format = 'int64';
			return $media_type;
		}

		if (strpos($mysql_type, 'varchar') === 0 || $mysql_type === 'text') {
			$media_type->type = 'string';
			return $media_type;
		}

		if ($mysql_type == 'datetime') {
			$media_type->type = 'string';
			$media_type->format = 'date-time';
			return $media_type;
		}

		if ($mysql_type == 'date') {
			$media_type->type = 'string';
			$media_type->format = 'date';
			return $media_type;
		}

		if (strpos($mysql_type, 'decimal') === 0) {
			$media_type->type = 'number';
			$media_type->format = 'float';
			return $media_type;
		}

		throw new \Exception('Cannot create media type for mysql type "' . $mysql_type . '"');
	}

}
