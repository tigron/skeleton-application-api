<?php
/**
 * Component trait
 *
 * @author Gerry Demaret <gerry@tigron.be>
 * @author Christophe Gosiau <christophe@tigron.be>
 */

namespace Skeleton\Application\Api;
use Skeleton\Application\Api\Exception;

trait Component {

	/**
	 * Store local properties
	 *
	 * @access private
	 * @var array $properties
	 */
	private $properties = [];

	/**
	 * Get a detail
	 *
	 * @access public
	 * @param string $key
	 * @return mixed $value
	 */
	public function __get($key) {
		if (is_array($this->properties) && array_key_exists($key, $this->properties)) {
			return $this->properties[$key];
		}

		if (get_parent_class($this) !== false && is_callable([parent::class, '__get'])) {
			return parent::__get($key);
		}

		throw new \Exception('Unknown key requested: ' . $key);
	}

	/**
	 * Isset
	 *
	 * @access public
	 * @param string $key
	 * @return bool $isset
	 */
	public function __isset($key): bool {
		if (is_array($this->properties) && isset($this->properties[$key])) {
			return true;
		}

		if (get_parent_class($this) !== false && is_callable([parent::class, '__isset'])) {
			return parent::__isset($key);
		}

		return false;
	}

	/**
	 * Set a detail
	 *
	 * @access public
	 * @param string $key
	 * @param mixex $value
	 */
	public function __set($key, $value): void {
		$this->properties[$key] = $value;

		if (get_parent_class($this) !== false && is_callable([parent::class, '__set'])) {
			parent::__set($key, $value);
		}
	}

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

		$media_type->additional_properties = $this->get_openapi_additional_properties();

		return $media_type;
	}

	/**
	 * Get the the content type for this object
	 *
	 * @access public
	 * @return $string content_type
	 */
	public function get_openapi_content_type(): string {
		return 'application/json';
	}

	/**
	 * Get the name of the component
	 *
	 * @access public
	 * @return string $name
	 */
	public function get_openapi_component_name(): string {
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
	public function get_openapi_component_properties(): array {
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
	 * @return ?\Skeleton\Application\Api\Media\Type $additional_properties
	 */
	public function get_openapi_additional_properties(): ?\Skeleton\Application\Api\Media\Type {
		return null;
	}

	/**
	 * Get the description of the object
	 *
	 * @access public
	 * @return string $description
	 */
	public function get_openapi_description(): string {
		return 'test description';
	}

	/**
	 * Get an openapi example for the object
	 *
	 * @access public
	 * @return array $example
	 */
	public function get_openapi_example(): array {
		return [];
	}

	/**
	 * Get component_info
	 *
	 * @access public
	 * @return array $info
	 */
	public function get_openapi_component_info(): array {
		$info = [];

		foreach ($this->get_openapi_component_properties() as $field => $definition) {
			if (is_a($definition, 'Skeleton\Application\Api\Media\Type\Object\Text')) {
				$language_interface = \Skeleton\I18n\Config::$language_interface;
				$languages = $language_interface::get_all();
				$info[$field] = [];

				foreach ($languages as $language) {
					if (!$language->is_translatable() and !$language->is_base()) {
						continue;
					}

					$key = 'text_' . $language->name_short . '_' . $definition->field;
					$info[$field][$language->name_short] = $definition->object->$key;
				}

				continue;
			}

			if (!isset($this->$field)) {
				$info[$field] = null;
			} else {
				$info[$field] = $this->$field;
			}
		}

		return $info;
	}

	/**
	 * Output the component info in a specific format.
	 *
	 * @return void
	 * @throws \Exception
	 */
	public function output_openapi_component_info(): void {
		echo json_encode($this->get_openapi_component_info(), JSON_PRETTY_PRINT);
	}
}
