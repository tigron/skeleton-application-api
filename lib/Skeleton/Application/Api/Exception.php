<?php
/**
 * Exception class
 *
 * This exception class can be outputed via API
 *
 * @author Gerry Demaret <gerry@tigron.be>
 * @author Christophe Gosiau <christophe@tigron.be>
 */

namespace Skeleton\Application\Api;

class Exception extends \Exception {

	/**
	 * Output
	 *
	 * @access public
	 */
	public function output() {
		header($_SERVER["SERVER_PROTOCOL"] . ' ' . $this->getCode() . ' ' . $this->getMessage(), true);
		echo json_encode($this->getMessage());
		return;
	}

}
