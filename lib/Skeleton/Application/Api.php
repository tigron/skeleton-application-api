<?php
/**
 * Skeleton Core Application class
 *
 * @author Christophe Gosiau <christophe@tigron.be>
 * @author Gerry Demaret <gerry@tigron.be>
 */

namespace Skeleton\Application;

class Api extends \Skeleton\Core\Application {

	/**
	 * Module Path
	 *
	 * @var string $module_path
	 * @access public
	 */
	public $endpoint_path = null;

	/**
	 * Endpoint namespace
	 *
	 * @var string $endpoint_namespace
	 * @access public
	 */
	public $endpoint_namespace = null;

	/**
	 * Component Path
	 *
	 * @var string $component_path
	 * @access public
	 */
	public $component_path = null;

	/**
	 * Component namespace
	 *
	 * @var string $component_namespace
	 * @access public
	 */
	public $component_namespace = null;

	/**
	 * Security Path
	 *
	 * @var string $security_path
	 * @access public
	 */
	public $security_path = null;

	/**
	 * Security namespace
	 *
	 * @var string $security_namespace
	 * @access public
	 */
	public $security_namespace = null;

	/**
	 * Get details
	 *
	 * @access protected
	 */
	protected function get_details() {
		parent::get_details();

		$this->endpoint_path = $this->path . '/endpoint/';
		$this->component_path = $this->path . '/component/';
		$this->security_path = $this->path . '/security/';
		$this->exception_path = $this->path . '/exception/';
		$this->endpoint_namespace = "\\App\\" . ucfirst($this->name) . "\Endpoint\\";
		$this->component_namespace = "\\App\\" . ucfirst($this->name) . "\Component\\";
		$this->security_namespace = "\\App\\" . ucfirst($this->name) . "\Security\\";
		$this->exception_namespace = "\\App\\" . ucfirst($this->name) . "\Exception\\";

		$autoloader = new \Skeleton\Core\Autoloader();
		$autoloader->add_namespace($this->endpoint_namespace, $this->endpoint_path);
		$autoloader->add_namespace($this->component_namespace, $this->component_path);
		$autoloader->add_namespace($this->security_namespace, $this->security_path);
		$autoloader->add_namespace($this->exception_namespace, $this->exception_path);

		$autoloader->register();
	}

	/**
	 * Load the config
	 *
	 * @access private
	 */
	protected function load_config() {
		/**
		 * Set some defaults
		 */
		$this->config->csrf_enabled = false;
		$this->config->replay_enabled = false;
		$this->config->hostnames = [];
		$this->config->routes = [];
		$this->config->route_resolver = function($path) {
			return \Skeleton\Application\Api\Endpoint::resolve($path);
		};
		$this->config->version = '1.0.0';

		parent::load_config();
	}

	/**
	 * Run the application
	 *
	 * @access public
	 */
	public function run() {
		try {
			\Skeleton\Core\Web\Media::detect($this->request_relative_uri);
		} catch (\Skeleton\Core\Exception\Media\Not\Found $e) {
			\Skeleton\Core\Web\HTTP\Status::code_404('media');
		}

		$request = pathinfo($this->request_relative_uri);

		if ($request['dirname'] == '/' and $request['basename'] == 'openapi.json') {
			$generator = new \Skeleton\Application\Api\Openapi\Generator();
			foreach ($this->get_components() as $component) {
				$generator->add_component($component);
			}
			$generator->add_endpoints($this->get_endpoints());
			$generator->add_security($this->get_security());
			$generator->serve('json');

		}

		if ($request['dirname'] == '/' and $request['filename'] == '') {
			$template = \Skeleton\Core\Web\Template::get();
			$template->display('@skeleton-application-api/index.twig');
			return;
		}

		/**
		 * Find the module to load
		 *
		 * FIXME: this nested try/catch is not the prettiest of things
		 */
		$endpoint = null;

		try {
			// Attempt to find the module by matching defined routes
			$endpoint = $this->route($this->request_relative_uri);
		} catch (\Exception $e) {
			try {
				// Attempt to find a module by matching paths
				$endpoint = Api\Endpoint::resolve($this->request_relative_uri);
			} catch (\Exception $e) {
				if ($this->event_exists('module', 'not_found')) {
					$this->call_event_if_exists('module', 'not_found');
				} else {
					\Skeleton\Core\Web\HTTP\Status::code_404('module');
				}
			}
		}

		if (!is_a($endpoint, 'Skeleton\Application\Api\Endpoint')) {
			\Skeleton\Core\Web\HTTP\Status::code_404('module');
		}

		// All what will be outputted after this is JSON
		header('Content-Type: application/json');
		$endpoint->accept_request();
	}

	/**
	 * Search module
	 *
	 * @access public
	 * @param string $request_uri
	 */
	public function route($request_uri) {
		/**
		 * Remove leading slash
		 */
		if ($request_uri[0] == '/') {
			$request_uri = substr($request_uri, 1);
		}

		if (substr($request_uri, -1) == '/') {
			$request_uri = substr($request_uri, 0, strlen($request_uri)-1);
		}

		if (!isset($this->config->base_uri)) {
			$this->config->base_uri = '/';
		}

		if (strpos( '/' . $request_uri, $this->config->base_uri) === 0) {
			$request_uri = substr($request_uri, strlen($this->config->base_uri)-1);
		}
		$request_parts = explode('/', $request_uri);

		$routes = $this->config->routes;

		/**
		 * We need to find the route that matches the most the fixed parts
		 */
		$matched_module = null;
		$best_matches_fixed_parts = 0;
		$route = '';

		foreach ($routes as $module => $uris) {
			foreach ($uris as $uri) {
				if (isset($uri[0]) AND $uri[0] == '/') {
					$uri = substr($uri, 1);
				}
				$parts = explode('/', $uri);
				$matches_fixed_parts = 0;
				$match = true;

				foreach ($parts as $key => $value) {
					if (!isset($request_parts[$key])) {
						$match = false;
						continue;
					}

					if (isset($value[0]) AND $value[0] == '$') {
						if (strpos($value, '$action') === 0) {
							// Check if the given parameter defines an action
							$method = strtolower($_SERVER['REQUEST_METHOD']) . '_' . $request_parts[$key];

							if (is_callable([$module, $method])) {
								$matches_fixed_parts++;
								continue;
							}
						}

						preg_match_all('/(\[(.*?)\])/', $value, $matches);

						if (!isset($matches[2][0])) {
							/**
							 *  There are no possible values for the variable
							 *  The match is valid
							 */
							 continue;
						}

						$possible_values = explode(',', $matches[2][0]);

						$variable_matches = false;
						foreach ($possible_values as $possible_value) {
							if ($request_parts[$key] == $possible_value) {
								$variable_matches = true;
							}
						}

						if (!$variable_matches) {
							$match = false;
						}

						// This is a variable, we do not increase the fixed parts
						continue;
					}


					if ($value == $request_parts[$key]) {
						$matches_fixed_parts++;
						continue;
					}


					$match = false;
				}



				if ($match and count($parts) == count($request_parts)) {
					if ($matches_fixed_parts >= $best_matches_fixed_parts) {
						$best_matches_fixed_parts = $matches_fixed_parts;
						$route = $uri;
						$matched_module = $module;
					}
				}
			}
		}

		if ($matched_module === null) {
			throw new \Exception('No matching route found');
		}

		/**
		 * We now have the correct route
		 * Now fill in the GET-parameters
		 */
		$parts = explode('/', $route);

		foreach ($parts as $key => $value) {
			if (isset($value[0]) and $value[0] == '$') {
				$value = substr($value, 1);
				if (strpos($value, '[') !== false) {
					$value = substr($value, 0, strpos($value, '['));
				}
				$_GET[$value] = $request_parts[$key];
				$_REQUEST[$value] = $request_parts[$key];
			}
		}

		$request_relative_uri = str_replace($this->endpoint_namespace, '', $matched_module);

		$request_relative_uri = str_replace('_', '/', $request_relative_uri);
		return \Skeleton\Application\Api\Endpoint::resolve($request_relative_uri);
	}

	/**
	 * Get endpoints
	 *
	 * @access public
	 */
	public function get_endpoints() {
		$files = Api\Util::rglob($this->endpoint_path . '*.php');

		$endpoints = [];

		foreach ($files as $file) {
			$pathinfo = pathinfo($file);
			$endpoint = str_replace($this->endpoint_path, '', $pathinfo['dirname'] . DIRECTORY_SEPARATOR) . $pathinfo['filename'];
			$endpoint = str_replace(DIRECTORY_SEPARATOR, '\\', $endpoint);

			$classname = "\\App\\" . ucfirst($this->name) . "\Endpoint\\" . $endpoint;
			$class = new $classname();
			$endpoints[] = $class;
		}
		return $endpoints;
	}

	/**
	 * Get components
	 *
	 * @access public
	 */
	public function get_components() {
		$files = Api\Util::rglob($this->component_path . '*.php');

		$components = [];

		foreach ($files as $file) {
			$file = str_replace($this->component_path, '', $file);

			$pathinfo = pathinfo($file);
			$classname = '';
			if ($pathinfo['dirname'] !== '.') {
				$classname .= $pathinfo['dirname'] . '/';
			}
			$classname .= $pathinfo['filename'];
			$component = str_replace(DIRECTORY_SEPARATOR, '\\', $classname);

			$classname = "\\App\\" . ucfirst($this->name) . "\Component\\" . $component;
			$class = new $classname();
			$components[] = $class;
		}
		return $components;
	}

	/**
	 * Get security
	 *
	 * @access public
	 */
	public function get_security() {
		if (!file_exists($this->security_path)) {
			return [];
		}
		$files = Api\Util::rglob($this->security_path . '*.php');

		$securities = [];

		foreach ($files as $file) {
			$pathinfo = pathinfo($file);
			$security = $pathinfo['filename'];
			$security = str_replace(DIRECTORY_SEPARATOR, '\\', $security);

			$classname = "\\App\\" . ucfirst($this->name) . "\Security\\" . $security;
			$class = new $classname();
			$securities[] = $class;
		}
		return $securities;
	}

}
