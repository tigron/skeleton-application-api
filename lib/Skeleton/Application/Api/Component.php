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
			$properties[ $field['Field'] ] = Media\Type::create_for_mysql_type($field['Type']);
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
