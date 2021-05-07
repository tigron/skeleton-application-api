<?php
/**
 * Openapi generator class
 *
 * @author Christophe Gosiau <christophe@tigron.be>
 * @author Gerry Demaret <gerry@tigron.be>
 */

namespace Skeleton\Application\Api\Openapi;

class Generator {

	/**
	 * components
	 *
	 * @access private
	 * @var array $components
	 */
	private $components = [];

	/**
	 * endpoints
	 *
	 * @access private
	 * @var array $endpoints
	 */
	private $endpoints = [];

	/**
	 * Security
	 *
	 * @access private
	 * @var array $security
	 */
	private $security = [];

	/**
	 * Add components
	 *
	 * @access public
	 * @param \Skeleton\Application\Api\Component[] $components
	 */
	public function add_components($components) {
		$this->components = $components;
	}

	/**
	 * Add endpoints
	 *
	 * @access public
	 * @param \Skeleton\Application\Api\Endpoint[] $endpoints
	 */
	public function add_endpoints($endpoints) {
		$this->endpoints = $endpoints;
	}

	/**
	 * Add security
	 *
	 * @access public
	 * @param \Skeleton\Application\Api\Security[] $security
	 */
	public function add_security($security) {
		$this->security = $security;
	}

	/**
	 * Build the schema
	 *
	 * @access private
	 * @return array $schema
	 */
	private function build() {
		// Get the curent API application
		$application = \Skeleton\Core\Application::get();

		$schema = [];
		$schema['openapi'] = '3.0.2';
		$schema['servers'] = [];
		$schema['servers'][] = [
			'url' => '/',
		];

		$schema['info'] = [
			'version' => '1.0.0',
			'title'=> 'Swagger Petstore',
			'license' => [
				'name' => 'MIT',
			],
		];

		$schema['paths'] = [];
		foreach ($this->endpoints as $endpoint) {
			$paths = $endpoint->get_paths();

			foreach ($paths as $path) {
				$schema['paths'] = array_merge_recursive($schema['paths'], $path->get_schema());
			}
		}

		$schema['components'] = [];
		$schema['components']['securitySchemes'] = [];
		foreach ($this->security as $security) {
			$schema['components']['securitySchemes'][$security->get_name()] = $security->get_schema();
		}
		$schema['components']['schemas'] = [];
		foreach ($this->components as $component) {
			$name = str_replace($application->component_namespace, '', '\\' . get_class($component));
			$name = strtolower($name);
			$schema['components']['schemas'][$name] = [
				'properties' => [],
				'type' => 'object',
			];
			$properties = $component->get_component_properties();
			foreach ($properties as $key => $property) {
				$schema['components']['schemas'][$name]['properties'][strtolower($key)] = $property;
			}
		}
		$schema['components']['schemas']['http_basic'] = [
			'type' => 'string'
		];
		return $schema;
	}

	/**
	 * Serve
	 *
	 * @access public
	 * @param string $format
	 */
	public function serve($format = 'json') {
		$schema = $this->build();
		header('Content-Type: application/json');
		echo json_encode($schema, JSON_PRETTY_PRINT);
		exit;
	}


}