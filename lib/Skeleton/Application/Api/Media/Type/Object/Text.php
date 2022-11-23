<?php
/**
 * Type class
 *
 * @author Gerry Demaret <gerry@tigron.be>
 * @author Christophe Gosiau <christophe@tigron.be>
 */

namespace Skeleton\Application\Api\Media\Type\Object;

class Text extends \Skeleton\Application\Api\Media\Type {

	/**
	 * The object where the object text will be fetched from
	 *
	 * @access public
	 * @var $object
	 */
	public $object = null;

	/**
	 * The field
	 *
	 * @access public
	 * @var string $field
	 */
	public $field = null;

	public function __construct() {
		$this->type = 'object';
		$this->value_type = get_class($this);
		$this->description = 'Field translated in multiple languages';
	}

	/**
	 * Validate
	 *
	 * @access public
	 */
	public function validate() {

	}

	public function get_openapi_component_name() {
		return 'LocalizedString';
	}

	/**
	 * Get openapi_component_properties
	 *
	 * @access public
	 * @return array $properties
	 */
	public function get_openapi_component_properties():array {
		if (!class_exists('\Skeleton\I18n\Config')) {
			throw new \Exception('Cannot fet object-text field, skeleton-i18 ' .
			'is not installed');
		}
		$language = \Skeleton\I18n\Config::$language_interface;
		$languages = $language::get_all();

		$properties = [];
		foreach ($languages as $language) {
			if (!$language->is_translatable() and !$language->is_base()) {
				continue;
			}
			$media_type = new \Skeleton\Application\Api\Media\Type();
			$media_type->type = 'string';
			if ($language->is_base()) {
				$media_type->nullable = false;
			} else {
				$media_type->nullable = true;
			}
			$properties[$language->name_short] = $media_type;

		}

		return $properties;
	}

	/**
	 * Get schema
	 *
	 * @access public
	 * @return array $schema
	 */
	public function get_schema($object_reference = true) {
		foreach ($this->get_openapi_component_properties() as $key => $property) {
			$this->properties[$key] = $property;
		}
		return parent::get_schema(false);
	}
}
