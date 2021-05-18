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

abstract class Endpoint {

	/**
	 * Get name
	 *
	 * @access public
	 * @return string $name
	 */
	public function get_name() {
		$api = \Skeleton\Core\Application::get();
		$class = new \ReflectionClass($this);
		$filename = $class->getFileName();
		$filename = str_replace($api->endpoint_path, '', $filename);
		$filename = pathinfo($filename);
		return strtolower($filename['filename']);
	}

	/**
	 * Get paths
	 *
	 * @access public
	 * @return \Skeleton\Application\Api\Path[] $paths
	 */
	public function get_paths() {
		return \Skeleton\Application\Api\Path::get_by_endpoint($this);
	}

	/**
	 * Accept request
	 *
	 * @access public
	 */
	public function accept_request() {

		// Find the method name to call
		$method = strtolower($_SERVER['REQUEST_METHOD']);
		if (isset($_GET['action'])) {
			$method .= '_' . $_GET['action'];
		}

		if (!is_callable([$this, $method])) {
			\Skeleton\Core\Web\HTTP\Status::code_404('module');
		}

		$paths = $this->get_paths();
		$requested_path = null;
		foreach ($paths as $path) {
			if ($path->method == $method) {
				$requested_path = $path;
			}
		}

		if ($requested_path === null) {
			\Skeleton\Core\Web\HTTP\Status::code_404('module');
		}


		$reflection_method = new \ReflectionMethod($this, $method);
		// Verify if security is ok
		foreach ($requested_path->security as $security) {
			if (!$secutity->handle()) {

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
		$response = $reflection_method->invokeArgs($this, $parameters);
		echo json_encode($response->get_component_info(), JSON_PRETTY_PRINT);



	}

	/**
	 * Resolve the requested path
	 *
	 * @access public
	 * @param string $path
	 * @return \Skeleton\Application\Api\Path $path
	 */
	public static function resolve($request_relative_uri) {
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