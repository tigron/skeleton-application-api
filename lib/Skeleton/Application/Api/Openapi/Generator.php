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
		if (!($component instanceof \Skeleton\Application\Api\ComponentInterface)) {
			throw new \Exception('Component with class "' . get_class($component) . '" does not implements the Component interface');
		}
		$properties = $component->get_openapi_component_properties();
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
	 * @param \Skeleton\Application\Api\Endpoint $endpoint
	 */
	public function add_endpoint(\Skeleton\Application\Api\Endpoint $endpoint) {
		$this->endpoints[] = $endpoint;
	}

	/**
	 * Add security
	 *
	 * @access public
	 * @param \Skeleton\Application\Api\Security $security
	 */
	public function add_security(\Skeleton\Application\Api\Security $security) {
		$this->security[] = $security;
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
		if (isset($config->base_uri)) {
			$schema['servers'][] = [
				'url' => $config->base_uri,
			];
		}

		if (!isset($config->title)) {
			throw new \Exception('Please specify "title" configuration');
		}

		/**
		 * Generate the 'info'
		 */
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

		/**
		 * Generate the 'tags'
		 */
		$schema['tags'] = [];
		foreach ($this->endpoints as $endpoint) {
			$schema['tags'][] = [
				'name' => $endpoint->_get_name(),
				'description' => $endpoint->_get_description(),
			];
		}

		/**
		 * Generate the 'paths'
		 */
		$schema['paths'] = [];

		foreach ($this->endpoints as $endpoint) {
			$paths = $endpoint->_get_paths();

			foreach ($paths as $path) {
				$schema['paths'] = array_merge_recursive($schema['paths'], $path->get_schema());
			}
		}

		/**
		 * Generate the 'components'
		 */
		$schema['components'] = [];
		if (count($this->security) > 0) {
			$schema['components']['securitySchemes'] = [];
			foreach ($this->security as $security) {
				$schema['components']['securitySchemes'][$security->get_name()] = $security->get_schema();
			}
		}
		$schema['components']['schemas'] = [];

		foreach ($this->components as $component) {
			$name = $component->get_openapi_component_name();
			$media_type = $component->get_openapi_media_type();

			$schema['components']['schemas'][$name] = $media_type->get_schema(false);
		}

		/**
		 * Sort schemas
		 */
		ksort($schema['components']['schemas']);

		/**
		 * Return the final schema
		 */
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

		if ($format == 'json') {
			header('Content-Type: application/json');
			echo json_encode($schema, JSON_PRETTY_PRINT);
			exit;
		}

		if ($format == 'yaml') {
			header('Content-Type: application/x-yaml');
			$yaml = \yaml_emit($schema);
			echo $yaml;
			exit;
		}
	}

}
