<?php
/**
 * Security
 *
 * @author Gerry Demaret <gerry@tigron.be>
 * @author Christophe Gosiau <christophe@tigron.be>
 */

namespace Skeleton\Application\Api;

abstract class Security {

	/**
	 * Name
	 *
	 * @var string $name
	 * @access protected
	 */
	protected $name;

	/**
	 * Type
	 *
	 * @var string $type
	 * @access protected
	 */
	protected $type;

	/**
	 * Get the name
	 *
	 * @access public
	 * @return string $name
	 */
	abstract public function get_name();

	/**
	 * Get the type
	 *
	 * @access public
	 * @return string $type
	 */
	abstract public function get_type();

	/**
	 * Get schema
	 *
	 * @access public
	 * @return array $schema
	 */
	abstract public function get_schema();


}
