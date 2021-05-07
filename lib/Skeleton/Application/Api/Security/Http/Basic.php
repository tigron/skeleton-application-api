<?php
/**
 * Security
 *
 * @author Gerry Demaret <gerry@tigron.be>
 * @author Christophe Gosiau <christophe@tigron.be>
 */

namespace Skeleton\Application\Api\Security\Http;

class Basic extends \Skeleton\Application\Api\Security\Http{

	/**
	 * Get the type
	 *
	 * @access public
	 * @return string $type
	 */
	public function get_name() {
		return 'http_basic';
	}

	/**
	 * Get schema
	 *
	 * @access public
	 * @return array $schema
	 */
	public function get_schema() {
		$schema = [
			'scheme' => 'basic',
			'type' => $this->get_type(),
		];

		return $schema;
	}


}
