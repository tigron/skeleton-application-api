<?php
/**
 * Endpoint Context
 *
 * @author Gerry Demaret <gerry@tigron.be>
 * @author Christophe Gosiau <christophe@tigron.be>
 * @author David Vandemaele <david@tigron.be>
 */

namespace Skeleton\Application\Api\Event;

class Endpoint extends \Skeleton\Core\Application\Event {

	/**
	 * Access denied
	 *
	 * @access public
	 * @param \Skeleton\Core\Application\Web\Module
	 */
	public function access_denied(\Skeleton\Application\Api\Endpoint $endpoint): void {
		\Skeleton\Core\Http\Status::code_403('access denied');
	}

	/**
	 * Media not found
	 *
	 * @access public
	 */
	public function not_found(): void {
		\Skeleton\Core\Http\Status::code_404('module');
	}

}
