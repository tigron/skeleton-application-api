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
	 * Get properties
	 *
	 * @access public
	 * @return array $properties
	 */
	public function get_component_properties() {
		$db = self::trait_get_database();
		$table = self::trait_get_database_table();

		$properties = [];
		$definition = $db->get_table_definition($table);

		foreach ($definition as $field) {
			$type = null;
			$format = null;
			if (strpos($field['Type'], 'int') === 0) {
				$type = 'integer';
				$format = 'int64';
			} elseif (strpos($field['Type'], 'varchar') === 0) {
				$type = 'string';
			} elseif ($field['Type'] == 'datetime') {
				$type = 'string';
				$format = 'date-time';
			} elseif ($field['Type'] == 'date') {
				$type = 'string';
				$format = 'date';
			} elseif (strpos($field['Type'], 'decimal') === 0) {
				$type = 'number';
				$format = 'double';
			}
			$properties[ $field['Field'] ] = [
				'type' => $type
			];
			if (isset($format)) {
				$properties[ $field['Field'] ]['format'] = $format;
			}
		}
		return $properties;
	}

	/**
	 * Get component_info
	 *
	 * @access public
	 * @return array $info
	 */
	public function get_component_info() {
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
