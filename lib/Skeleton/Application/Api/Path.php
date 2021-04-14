<?php
/**
 * Path class
 *
 * @author Gerry Demaret <gerry@tigron.be>
 * @author Christophe Gosiau <christophe@tigron.be>
 */

namespace Skeleton\Application\Api;

class Path {

	/**
	 * Operation
	 *
	 * @access public
	 * @var string $operation
	 */
	public $operation;

	/**
	 * Name
	 *
	 * @access public
	 * @var string $name
	 */
	public $name;

	/**
	 * Parameters
	 *
	 * @access public
	 * @var Parameter[] $parameters
	 */
	public $parameters = [];

	/**
	 * Get parameter by name
	 *
	 * @access public
	 * @param string $name
	 */
	public function get_parameter_by_name($name) {
		foreach ($this->parameters as $parameter) {
			if ($parameter->name == $name) {
				return $parameter;
			}
		}
		throw new \Exception('No parameter found with name ' . $name);
	}	
}
