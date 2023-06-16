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

use \Skeleton\Application\Api\Path\Response;

class Exception extends \Exception {
	use \Skeleton\Application\Api\Component;

	/**
	 * Body
	 *
	 * @access public
	 * @var string $body
	 */
	public $body = null;

	/**
	 * Get the name of the component
	 *
	 * @access public
	 * @return string $name
	 */
	public function get_openapi_component_name():string {
		$application = \Skeleton\Core\Application::get();
		$name = str_replace($application->exception_namespace, '', '\\' . get_class($this));
		$name = 'Exception_' . str_replace('\\', '_', $name);
		return $name;
	}

	/**
	 * Get schema
	 *
	 * @access public
	 * @return array $schema
	 */
	public function get_response() {
		$response = new Response();
		$response->code = $this->getCode();
		$response->description = $this->getMessage();
		if (property_exists($this, 'body')) {
			$media_type = new \Skeleton\Application\Api\Media\Type();
			$media_type->type = 'string';
			$media_type->description = 'The error message';
			$response->content = $media_type;
		}
		return $response;
	}

	/**
	 * Output
	 *
	 * @access public
	 */
	public function output() {
		header($_SERVER["SERVER_PROTOCOL"] . ' ' . $this->getCode() . ' ' . $this->getMessage(), true);

		if (isset($this->body)) {
			echo json_encode($this->body, JSON_PRETTY_PRINT);
		}
		return;
	}

}
