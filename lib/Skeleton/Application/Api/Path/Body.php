<?php
/**
 * Body class
 *
 * @author Gerry Demaret <gerry@tigron.be>
 * @author Christophe Gosiau <christophe@tigron.be>
 */

namespace Skeleton\Application\Api\Path;

class Body {

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
	public $in = 'body';

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
		$schema['schema'] = $this->media_type->get_schema();
		return $schema;
	}
}
