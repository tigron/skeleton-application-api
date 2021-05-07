<?php
/**
 * Security
 *
 * @author Gerry Demaret <gerry@tigron.be>
 * @author Christophe Gosiau <christophe@tigron.be>
 */

namespace Skeleton\Application\Api\Security;

abstract class Http extends \Skeleton\Application\Api\Security {

	/**
	 * Get the type
	 *
	 * @access public
	 * @return string $type
	 */
	public function get_type() {
		return 'http';
	}

	/**
	 * Handle the security
	 *
	 * @access public
	 */
	abstract public function handle();


}
