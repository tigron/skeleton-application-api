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
	public function add_component($component) {
		if (!is_callable( [$component, 'get_component_properties'])) {
			throw new \Exception('Component with class "' . get_class($component) . '" has no method "get_component_properties"');
		}
		$properties = $component->get_component_properties();
		foreach ($properties as $key => $property) {
			if (!is_a($property, 'Skeleton\Application\Api\Media\Type')) {
				throw new \Exception('Property "' . $key . '" defined in component "' . get_class($component) . '" is not a media_type');
			}
		}
		$this->components[] = $component;
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
		$config = $application->config;

		$schema = [];
		$schema['openapi'] = '3.0.2';
		$schema['servers'] = [];
		$schema['servers'][] = [
			'url' => '/',
		];

		if (!isset($config->title)) {
			throw new \Exception('Please specify "title" configuration');
		}

		$schema['info'] = [
			'version' => $config->version,
			'title' => $config->title,
		];

		if (isset($config->terms)) {
			$schema['info']['termsOfService'] = $config->terms;
		}
		if (isset($config->description)) {
			$schema['info']['description'] = $config->description;
		}

		if (isset($config->contact)) {
			$contact = $config->contact;
			$schema['info']['contact'] = [];
			if (isset($contact['name'])) {
				$schema['info']['contact']['name'] = $contact['name'];
			}
			if (isset($contact['url'])) {
				$schema['info']['contact']['url'] = $contact['url'];
			}
			if (isset($contact['email'])) {
				$schema['info']['contact']['email'] = $contact['email'];
			}
		}
/*
			'title'=> 'Swagger Petstore',
			'license' => [
				'name' => 'MIT',
			],
		];*/

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
				$schema['components']['schemas'][$name]['properties'][$key] = $property->get_schema();
			}
		}
//		$schema['components']['schemas']['http_basic'] = [
//			'type' => 'string'
//		];
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