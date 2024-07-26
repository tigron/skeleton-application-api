<?php
/**
 * Endpoint class
 *
 * @author Gerry Demaret <gerry@tigron.be>
 * @author Christophe Gosiau <christophe@tigron.be>
 */

namespace Skeleton\Application\Api;

use \Skeleton\Application\Api\Path\Parameter;
use \Skeleton\Application\Api\Path\Response;

abstract class Endpoint extends \Skeleton\Core\Application\Module {

	/**
	 * Get name
	 *
	 * @access public
	 * @return string $name
	 */
	public function _get_name() {
		return $this->_get_basename();
	}

	/**
	 * Get name
	 *
	 * @access public
	 * @return string $name
	 */
	public function _get_basename() {
		$api = \Skeleton\Core\Application::get();
		$class = new \ReflectionClass($this);
		$filename = $class->getFileName();
		$filename = str_replace($api->endpoint_path, '', $filename);
		$filename = pathinfo($filename);
		if ($filename['dirname'] != '.') {
			return strtolower($filename['dirname'] . '/' . $filename['filename']);
		} else {
			return strtolower($filename['filename']);
		}
	}

	/**
	 * Get description
	 *
	 * @access public
	 * @return string $description
	 */
	public function _get_description() {
		$reflection = new \ReflectionClass($this);
		$docblock = $reflection->getDocComment();
		try {
			$factory  = \phpDocumentor\Reflection\DocBlockFactory::createInstance();
			$docblock = $factory->create($docblock);
			return $docblock->getSummary();
		} catch (\Exception $e) {
			return "";
		}
	}

	/**
	 * Get paths
	 *
	 * @access public
	 * @return \Skeleton\Application\Api\Path[] $paths
	 */
	public function _get_paths() {
		return \Skeleton\Application\Api\Path::get_by_endpoint($this);
	}

	/**
	 * Get body
	 *
	 * @access public
	 */
	public function get_body() {
		// Find the method
		$method = debug_backtrace()[1]['function'];
		// Get the path object for this method
		$paths = $this->_get_paths();
		$current_path = null;
		foreach ($paths as $path) {
			if ($path->method === $method) {
				$current_path = $path;
			}
		}

		if ($current_path === null) {
			throw new \Exception('Cannot find path for "' . $method . '"');
		}

		if (!isset($current_path->body)) {
			throw new \Skeleton\Application\Api\Exception\Bad\Request();
		}

		try {
			$info = json_decode(file_get_contents('php://input'), true, 512, JSON_THROW_ON_ERROR);
		} catch (\JsonException $exception) {
			throw new \Skeleton\Application\Api\Exception\Bad\Request();
		}

		$generator = new \Skeleton\Application\Api\Body\Generator($current_path->body->media_type);
		$generator->set_body($info);
		$errors = $generator->validate();

		if (count($errors) > 0) {
			$exception = new \Skeleton\Application\Api\Exception\Bad\Request();
			$exception->set_errors($errors);
			throw $exception;
		}

		return $generator->generate();
	}

	/**
	 * Accept request
	 *
	 * @access public
	 */
	public function accept_request(): void {
		// Bootstrap the application
		$application = \Skeleton\Core\Application::get();
		$application->call_event_if_exists('endpoint', 'bootstrap', [$this]);

		// Find the method name to call
		$method = strtolower($_SERVER['REQUEST_METHOD']);
		if (isset($_GET['action'])) {
			$method .= '_' . $_GET['action'];
		}

		if (!is_callable([$this, $method])) {
			\Skeleton\Core\Http\Status::code_404('endpoint');
		}

		$paths = $this->_get_paths();
		$requested_path = null;
		foreach ($paths as $path) {
			if ($path->method == $method) {
				$requested_path = $path;
			}
		}

		if ($requested_path === null) {
			\Skeleton\Core\Http\Status::code_404('endpoint');
		}


		$reflection_method = new \ReflectionMethod($this, $method);

		// Verify if security is ok
		foreach ($requested_path->security as $security) {
			try {
				$security->handle();
			} catch (Exception $e) {
				$e->output();
				return;
			}
		}

		// We know the correct path, now check if all required parameters are available
		$required_parameters = $reflection_method->getParameters();
		$parameters = [];
		foreach ($required_parameters as $required_parameter) {
			if (!isset($_GET[$required_parameter->getName()])) {
				echo $required_parameter->getName() . ' not given';
				exit;
			}
			$parameters[] = $_GET[$required_parameter->getName()];
		}
		try {
			$response = $reflection_method->invokeArgs($this, $parameters);
		} catch (Exception $e) {
			$e->output();
			return;
		}

		if (is_object($response)) {
			echo json_encode($response->get_openapi_component_info(), JSON_PRETTY_PRINT);
		} elseif (is_array($response)) {
			$output = [];
			foreach ($response as $value) {
				if (is_object($value)) {
					$output[] = $value->get_openapi_component_info();
				} else {
					$output[] = $value;
				}
			}
			echo json_encode($output, JSON_PRETTY_PRINT);
		} else {
			echo json_encode($response, JSON_PRETTY_PRINT);
		}
		$application->call_event_if_exists('endpoint', 'teardown', [$this]);
	}

	/**
	 * Resolve the requested path
	 *
	 * @access public
	 */
	public static function resolve(string $request_relative_uri): \Skeleton\Application\Api\Endpoint {
		$relative_uri_parts = array_values(array_filter(explode('/', $request_relative_uri)));
		$relative_uri_parts = array_map('ucfirst', $relative_uri_parts);
		$application = \Skeleton\Core\Application::get();
		$endpoint_namespace = $application->endpoint_namespace;

		$classnames = [];
		$classnames[] = $endpoint_namespace . implode('\\', $relative_uri_parts);

		if (count($relative_uri_parts) > 1) {
			array_pop($relative_uri_parts);
			$classnames[] = $endpoint_namespace . implode('\\', $relative_uri_parts);
		}

		foreach ($classnames as $classname) {
			if (class_exists($classname)) {
				return new $classname();
			}
		}

		throw new \Exception('No endpoint found for ' . $request_relative_uri);
	}
}
