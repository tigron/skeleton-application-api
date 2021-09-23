<?php
/**
 * Component trait
 *
 * @author Gerry Demaret <gerry@tigron.be>
 * @author Christophe Gosiau <christophe@tigron.be>
 */

namespace Skeleton\Application\Api;

trait Component {

	/**
	 * Get the media type for this object
	 *
	 * @access public
	 * @return \Skeleton\Application\Api\Media\Type $media_type
	 */
	public function get_openapi_media_type(): \Skeleton\Application\Api\Media\Type {
		$media_type = new \Skeleton\Application\Api\Media\Type();
		$media_type->type = 'object';
		$media_type->value_type = get_class($this);
		foreach ($this->get_openapi_component_properties() as $key => $property) {
			$media_type->properties[$key] = $property;
		}
		return $media_type;
		
	}

	/**
	 * Get the name of the component
	 *
	 * @access public
	 * @return string $name
	 */
	public function get_openapi_component_name():string {
		$application = \Skeleton\Core\Application::get();
		$name = str_replace($application->component_namespace, '', '\\' . get_class($this));
		$name = str_replace('\\', '_', $name);
		return $name;
	}


	/**
	 * Get openapi component properties
	 *
	 * @access public
	 * @return array $properties
	 */
	public function get_openapi_component_properties():array {
		$db = self::trait_get_database();
		$table = self::trait_get_database_table();

		$properties = [];
		$definition = $db->get_table_definition($table);

		foreach ($definition as $field) {
			$properties[ $field['Field'] ] = Media\Type::create_for_mysql_type($field['Type']);
		}
		return $properties;
	}

	/**
	 * Get additional properties for the openapi object
	 *
	 * @access public
	 * @return array $additional_properties
	 */
	public function get_openapi_additional_properties() {
		return [];
	}

	/**
	 * Get the description of the object
	 *
	 * @access public
	 * @return string $description
	 */
	public function get_openapi_description():string {
		return 'test description';
	}

	/**
	 * Get an openapi example for the object
	 *
	 * @access public
	 * @return array $example
	 */
	public function get_openapi_example():array {
		return [];
	}

	/**
	 * Get component_info
	 *
	 * @access public
	 * @return array $info
	 */
	public function get_openapi_component_info():array {
		$info = [];

		foreach ($this->get_component_properties() as $field => $definition) {
			if (!isset($this->$field)) {
				$info[$field] = null;
			} else {
				$info[$field] = $this->$field;
			}
		}
		return $info;
	}

}
