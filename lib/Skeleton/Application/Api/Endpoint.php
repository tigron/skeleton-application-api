<?php
/**
 * Endpoint class
 *
 * @author Gerry Demaret <gerry@tigron.be>
 * @author Christophe Gosiau <christophe@tigron.be>
 */

namespace Skeleton\Application\Api;

use \Skeleton\Application\Api\Path\Parameter;

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
	 */
	public function get_paths() {
		$reflection = new \ReflectionClass($this);
		$methods = $reflection->getMethods(\ReflectionMethod::IS_PUBLIC);

		// Only check for methods in child class		
		foreach ($methods as $key => $method) {
			if ($method->class == get_parent_class($this)) {
				unset($methods[$key]);
			}
		}
		$operations = [ 'get', 'post', 'put', 'patch', 'delete', 'head', 'options', 'trace' ];		
		foreach ($methods as $method) {
			foreach ($operations as $operation) {
				if (strpos($method->name, $operation) !== 0) {
					continue;
				}
				$path = new Path();
				$path->operation = $operation;
				$name = substr($method->name, strlen($operation));
				$name = ltrim($name, '_');
				$name = '/' . $this->get_name() . '/' . $name;
				$name = rtrim($name, '/');
				$path->name = $name;

				/**
				 * Get the docblock of the method
				 */
				try {
					$docblock = $method->getDocComment();
					$factory  = \phpDocumentor\Reflection\DocBlockFactory::createInstance();
					$docblock = $factory->create($docblock);
					$path->summary = $docblock->getSummary();

					/**
					 *  Add parameters by docblock
					 */
					$params = $docblock->getTagsByName('param');
					foreach ($params as $param) {
						$parameter = new Parameter();
						$parameter->name = $param->getVariableName();
						$parameter->description = $param->getDescription()->getBodyTemplate();
						$parameter->type = (string)$param->getType();
						$path->parameters[] = $parameter;			
					}
				} catch (\Webmozart\Assert\InvalidArgumentException $e) {
					// No docblock found		
				}

				/**
				 * Get the method parameters, make them required
				 */
				$params = $method->getParameters();
				foreach ($params as $param) {
					try {
						$parameter = $path->get_parameter_by_name($param->name);
					} catch (\Exception $e) {
						throw new \Exception('Required parameter ' . $param->name . ' for method ' . $method->name . ' is not mentioned in docblock');
					}
					$parameter->required = true;
				}
				print_r($path);
			}			
		}

	}	

}
