<?php
/**
 * Security
 *
 * @author Gerry Demaret <gerry@tigron.be>
 * @author Christophe Gosiau <christophe@tigron.be>
 */

namespace Skeleton\Application\Api;

abstract class Security {

	/**
	 * Name
	 *
	 * @var string $name
	 * @access protected
	 */
	protected $name;

	/**
	 * Type
	 *
	 * @var string $type
	 * @access protected
	 */
	protected $type;

	/**
	 * Get the name
	 *
	 * @access public
	 * @return string $name
	 */
	abstract public function get_name();

	/**
	 * Get the type
	 *
	 * @access public
	 * @return string $type
	 */
	abstract public function get_type();

	/**
	 * Get schema
	 *
	 * @access public
	 * @return array $schema
	 */
	abstract public function get_schema();

	/**
	 * Get reponses
	 *
	 * @access public
	 * @return array $responses
	 */
	public function get_responses() {
		$method = new \ReflectionMethod($this, 'handle');
		$docblock = $method->getDocComment();
		$factory  = \phpDocumentor\Reflection\DocBlockFactory::createInstance();
		$docblock = $factory->create($docblock);
		$exceptions = $docblock->getTagsByName('throws');
		$responses = [];
		foreach ($exceptions as $exception) {
			$classname = (string)$exception->getType()->getFqsen();
			if (!class_exists($classname)) {
				throw new \Exception('Incorrect exception specified in docblock for method ' . $this->get_name() . '/' . $method->name);
			}
			$class = new $classname();

			$response = new Path\Response();
			$response->code = $class->getCode();
			$response->description = $class->getMessage();
			$responses[] = $response;
		}
		return $responses;
	}

	/**
	 * Get reponses
	 *
	 * @access public
	 * @return array $responses
	 */
	public function get_headers() {
		$method = new \ReflectionMethod($this, 'handle');
		$docblock = $method->getDocComment();
		$factory  = \phpDocumentor\Reflection\DocBlockFactory::createInstance();
		$docblock = $factory->create($docblock);
		$doc_headers = $docblock->getTagsByName('header');
		$headers = [];
		foreach ($doc_headers as $doc_header) {
			$headers[] = $doc_header->getDescription()->getBodyTemplate();
		}
		return $headers;
	}

	/**
	 * Handle
	 *
	 * @access public
	 */
	abstract public function handle();


}
