<?php
/**
 * Error Context
 *
 * @author Gerry Demaret <gerry@tigron.be>
 * @author Christophe Gosiau <christophe@tigron.be>
 * @author David Vandemaele <david@tigron.be>
 */

namespace Skeleton\Application\Api\Event;

class Error extends \Skeleton\Core\Application\Event\Error {

	/**
	 * Exception denied
	 *
	 * @access public
	 * @param \Exception $exception
	 * @return bool $proceed_error_handlers
	 */
	public function exception(\Throwable $exception): bool {
		$exception = new \Skeleton\Application\Api\Exception($exception->getMessage(), 500);
		$exception->output();
		return false;
	}
}
