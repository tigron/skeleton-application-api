<?php
/**
 * Exception class
 *
 * This exception class can be outputed via API
 *
 * @author Gerry Demaret <gerry@tigron.be>
 * @author Christophe Gosiau <christophe@tigron.be>
 */

namespace Skeleton\Application\Api\Exception\Bad;

class Request extends \Skeleton\Application\Api\Exception {

	/**
	 * The HTTP code
	 *
	 * @access protected
	 * @var int $code
	 */
	protected $code = 400;

	/**
	 * The message
	 *
	 * @access protected
	 * @var string $message
	 */
	protected $message = 'Bad request';

	/**
	 * Set errors
	 *
	 * @access public
	 * @param array $errors
	 */
	public function set_errors($errors) {
		$this->body = $errors;
	}	

}
