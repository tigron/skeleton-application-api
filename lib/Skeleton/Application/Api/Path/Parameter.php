<?php
/**
 * Parameter class
 *
 * @author Gerry Demaret <gerry@tigron.be>
 * @author Christophe Gosiau <christophe@tigron.be>
 */

namespace Skeleton\Application\Api\Path;

class Parameter {

	/**
	 * Name
	 *
	 * @access public
	 * @var string $name
	 */
	public $name;

	/**
	 * Description
	 *
	 * @access public
	 * @var string $description
	 */
	public $description;

	/**
	 * Required
	 *
	 * @access public
	 * @var boolean $required
	 */
	public $required = false;

	/**
	 * In
	 *
	 * @access public
	 * @var string $in
	 */
	public $in = 'query';

	/**
	 * Type
	 *
	 * @access public
	 * @var string $type
	 */
	public $media_type = '';

	/**
	 * Get schema
	 *
	 * @access public
	 * @return array $schema
	 */
	public function get_schema() {
		$schema = [];
		// if the parameter is an array, add [] to the name
		if ($this->media_type->type == 'array') {
			$schema['name'] = $this->name . '[]';
		} else {
			$schema['name'] = $this->name;
		}
		$schema['required'] = $this->required;
		$schema['in'] = $this->in;
		$schema['schema'] = $this->media_type->get_schema();
		$schema['description'] = $this->description;
		return $schema;
	}
}
