<?php
/**
 * Response class
 *
 * @author Gerry Demaret <gerry@tigron.be>
 * @author Christophe Gosiau <christophe@tigron.be>
 */

namespace Skeleton\Application\Api\Path;

class Response {

	/**
	 * Code
	 *
	 * @access public
	 * @var string $code
	 */
	public $code;

	/**
	 * Description
	 *
	 * @access public
	 * @var string $description
	 */
	public $description = '';

	/**
	 * String
	 *
	 * @access public
	 * @var string $conten_type
	 */
	public $content_type = 'application/json';

	/**
	 * Type
	 *
	 * @access public
	 * @var Media\Type $content
	 */
	public $content = null;

	/**
	 * Get schema
	 *
	 * @access public
	 * @return array $schema
	 */
	public function get_schema() {
		if ($this->content === null) {
			return null;
		}

		$schema = [];
		$schema['schema'] = $this->content->get_schema();
		return $schema;
	}
}
