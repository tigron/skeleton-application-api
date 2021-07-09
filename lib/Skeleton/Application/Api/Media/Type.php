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
	 * The data type
	 *
	 * @access public
	 * @var string $type
	 */
	public $type = '';

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
	public $nullable = false;

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
					throw new \Exception('Media type validation error: Media type with type ' . $this->type . ' references value type ' . $this->value_type . ' but it does not exist');
				}
			}

			if (isset($this->format)) {
				throw new \Exception('Media type validation error: Media type with type ' . $this->type . ' has defined a format, which is not allowed');
			}

			return;
		}

		// Primitive data types
		$primitives = [ 'integer', 'number', 'string', 'boolean' ];
		if (in_array($this->type, $primitives)) {
			if (isset($this->value_type)) {
				throw new \Exception('Media type validation error: Media type with type ' . $this->type . ' has value_type set to "' . $this->value_type . '" which is not allowed');
			}

			if ($this->type == 'integer') {
				if (isset($this->format) and !in_array($this->format, ['int32', 'int64'])) {
					throw new \Exception('Media type validation error: Media type with type ' . $this->type . ' cannot have format "' . $this->format . '"');
				}
			}

			if ($this->type == 'number') {
				if (isset($this->format) and !in_array($this->format, ['float', 'double'])) {
					throw new \Exception('Media type validation error: Media type with type ' . $this->type . ' cannot have format "' . $this->format . '"');
				}
			}

			if ($this->type == 'string') {
				if (isset($this->format) and !in_array($this->format, ['byte', 'binary', 'date', 'date-time', 'password', 'email', 'uuid' ])) {
					throw new \Exception('Media type validation error: Media type with type ' . $this->type . ' cannot have format "' . $this->format . '"');
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
	public function get_schema() {
		$this->validate();
		$application = \Skeleton\Core\Application::get();

		$schema = [];
		if ($this->type != 'object' and $this->type != 'array') {
			$schema['type'] = $this->type;
			if (isset($this->format)) {
				$schema['format'] = $this->format;
			}
			if (isset($this->nullable) and $this->nullable !== false) {
				$schema['nullable'] = $this->nullable;
			}
		}

		if ($this->type == 'object') {
			$component = str_replace($application->component_namespace, '', $this->value_type);
			$schema['$ref'] = '#/components/schemas/' . $component;
		}

		if ($this->type == 'array') {
			$schema['type'] = 'array';
			$schema['items'] = $this->value_type->get_schema();
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

		if (strpos($mysql_type, 'varchar') === 0) {
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
