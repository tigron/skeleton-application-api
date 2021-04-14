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
	 * Get details
	 *
	 * @access protected
	 */
	protected function get_details() {
		parent::get_details();
		
		$this->endpoint_path = $this->path . '/endpoint/';

		$autoloader = new \Skeleton\Core\Autoloader();
		$autoloader->add_namespace("\\App\\" . $this->name . "\Endpoint\\", $this->endpoint_path);
		$autoloader->register();
	}	

	/**
	 * Load the config
	 *
	 * @access private
	 */
	protected function load_config() {
		parent::load_config();
	}

	/**
	 * Run the application
	 *
	 * @access public
	 */
	public function run() {
		$request = pathinfo($this->request_relative_uri);
		if (isset($request['extension']) and $request['extension'] == 'json') {
			$this->run_openapi();
		}
	}
	
	public function run_openapi() {
		$endpoints = $this->get_endpoints();
		foreach ($endpoints as $endpoint) {
			print_r($endpoint->get_paths());
		}
	}

	/**
	 * Get endpoints
	 *
	 * @access public
	 */
	public function get_endpoints() {
		$iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->endpoint_path));
		$files = array_filter(iterator_to_array($iterator), function($file) {
			return $file->isFile();
		});
		$endpoints = [];

		foreach ($files as $file) {
			if ($file->getExtension() != 'php') {
				continue;
			}
			$pathinfo = pathinfo($file->getPathname());
			$endpoint = str_replace($this->endpoint_path, '', $pathinfo['dirname'] . DIRECTORY_SEPARATOR) . $pathinfo['filename'];
			$endpoint = str_replace(DIRECTORY_SEPARATOR, '\\', $endpoint);

			$classname = "\\App\\" . ucfirst($this->name) . "\Endpoint\\" . $endpoint;
			$class = new $classname();
			$endpoints[] = $class;
		}
		return $endpoints;
	}	

}
