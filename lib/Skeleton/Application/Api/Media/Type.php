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
	 * The name of the type
	 *
	 * @access public
	 * @var string $name
	 */
	public $name = '';

	/**
	 * Valuetype
	 * The object in the array
	 *
	 * @access public
	 * @var string $value_type
	 */
	public $value_type = '';


	/**
	 * Get schema
	 *
	 * @access public
	 * @return array $schema
	 */
	public function get_schema() {
		$application = \Skeleton\Core\Application::get();

		$schema = [];
		if ($this->name != 'object' and $this->name != 'array') {
			$schema['type'] = $this->name;
		}

		if ($this->name == 'object') {
			if (!class_exists($this->value_type)) {
				throw new \Exception('Object ' . $this->value_type . ' is referenced in docblock but class does not exist');
			}
			$component = str_replace($application->component_namespace, '', $this->value_type);
			$schema['$ref'] = '#/components/schemas/' . strtolower($component);
		}

		if ($this->name == 'array') {
			if (!class_exists($this->value_type)) {
				throw new \Exception('Object ' . $this->value_type . ' is referenced in docblock but class does not exist');
			}
			$component = str_replace($application->component_namespace, '', $this->value_type);

			$schema['type'] = 'array';
			$schema['items'] = [
				'$ref' => '#/components/schemas/' . strtolower($component)
			];
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
			case 'String_': $media_type->name = 'string'; break;
			case 'Integer': $media_type->name = 'integer'; break;
			case 'Boolean': $media_type->name = 'boolean'; break;
			case 'Object_': $media_type->name = 'object'; $media_type->value_type = (string)$type->getFqsen(); break;
			case 'Array_': $media_type->name = 'array'; $media_type->value_type = (string)$type->getValueType(); break;

		}
		return $media_type;
	}

}
