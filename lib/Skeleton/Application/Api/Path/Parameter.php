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
}
