<?php
/**
 * Path class
 *
 * @author Gerry Demaret <gerry@tigron.be>
 * @author Christophe Gosiau <christophe@tigron.be>
 */

namespace Skeleton\Application\Api;

use \Skeleton\Application\Api\Path\Parameter;
use \Skeleton\Application\Api\Path\Response;

class Path {

	/**
	 * Operation
	 * HTTP request method
	 *
	 * @access public
	 * @var string $operation
	 */
	public $operation;

	/**
	 * Name
	 *
	 * @access public
	 * @var string $name
	 */
	public $name;

	/**
	 * Method
	 * The method that will be called in the endpoint
	 *
	 * @access public
	 * @var string $method_name
	 */
	public $method;

	/**
	 * Action
	 * The action that will be performed
	 *
	 * @access public
	 * @var string $method_name
	 */
	public $action = null;

	/**
	 * Summary
	 *
	 * @access public
	 * @var string $summary
	 */
	public $summary;

	/**
	 * Parameters
	 *
	 * @access public
	 * @var Parameter[] $parameters
	 */
	public $parameters = [];

	/**
	 * Responses
	 *
	 * @access public
	 * @var Response $responses
	 */
	public $response = null;

	/**
	 * Security
	 *
	 * @access public
	 * @var Security[] $security
	 */
	public $security = [];

	/**
	 * Endpoint
	 *
	 * @access public
	 * @var \Skeleton\Application\Api\Endpoint $endpoint
	 */
	public $endpoint;

	/**
	 * Get parameter by name
	 *
	 * @access public
	 * @param string $name
	 */
	public function get_parameter_by_name($name) {
		foreach ($this->parameters as $parameter) {
			if ($parameter->name == $name) {
				return $parameter;
			}
		}
		throw new \Exception('No parameter found with name ' . $name);
	}

	/**
	 * Get route
	 *
	 * @access public
	 * @return string $route
	 */
	public function get_route() {
		$params = [];
		if (isset($this->action) and !empty($this->action)) {
			$params['action'] = $this->action;
		}
		foreach ($this->parameters as $parameter) {
			if (!$parameter->required) {
				continue;
			}
			$params[$parameter->name] = '{' . $parameter->name . '}';
		}
		if (count($params) == 0) {
			return $this->endpoint->get_name();
		}
		$query = $this->endpoint->get_name() . '?';
		foreach ($params as $key => $value) {
			$query .= $key . '=' . $value . '&';
		}
		$query = rtrim($query, '&');
		$query = \Skeleton\Core\Util::rewrite_reverse($query);

		return $query;
	}

	/**
	 * Get schema
	 *
	 * @access public
	 * @return array $schema
	 */
	public function get_schema() {
		$schema = [];

		$route = $this->get_route();
		if (!isset($schema[$route])) {
			$schema[$route] = [];
		}
		$schema[$route][$this->operation] = [];
		$schema[$route][$this->operation]['tags'] = [ $this->endpoint->get_name() ];
		$schema[$route][$this->operation]['summary'] = $this->summary;

		if (count($this->parameters) > 0) {
			$schema[$route][$this->operation]['parameters'] = [];
			foreach ($this->parameters as $parameter) {
				$schema[$route][$this->operation]['parameters'][] = $parameter->get_schema();
			}
		}

		if ($this->response === null) {
			throw new \Exception('No response defined for path ' . $this->name . '/' . $this->operation);
		}

		$schema[$route][$this->operation]['responses'] = [];
		$schema[$route][$this->operation]['responses'][200]['description'] = $this->response->description;
		$schema[$route][$this->operation]['responses'][200]['content']['application/json'] = $this->response->get_schema();

		/**
		 * If headers are specified, we add them to every response
		 * This is a limitation of not using a Response object
		 */
		if (count($this->headers) > 0) {
			foreach ($this->headers as $key => $header) {
				$schema[$route][$this->operation]['responses'][200]['headers'][$header]['type'] = 'string';
			}
		}		

		if (count($this->security) > 0) {
			$schema[$route][$this->operation]['security'] = [];
			foreach ($this->security as $security) {
				$schema[$route][$this->operation]['security'][] = [ $security->get_name() => [] ];
			}
		}

		return $schema;
	}

	/**
	 * Get by Endpoint
	 *
	 * @access public
	 * @param \Skeleton\Application\Api\Endpoint $endpoint
	 */
	public static function get_by_endpoint(\Skeleton\Application\Api\Endpoint $endpoint) {
		$reflection = new \ReflectionClass($endpoint);
		$methods = $reflection->getMethods(\ReflectionMethod::IS_PUBLIC);

		// Only check for methods in child class
		foreach ($methods as $key => $method) {
			if ($method->class == get_parent_class($endpoint)) {
				unset($methods[$key]);
			}
		}
		$operations = [ 'get', 'post', 'put', 'patch', 'delete', 'head', 'options', 'trace' ];

		$paths = [];

		foreach ($methods as $method) {
			foreach ($operations as $operation) {
				if (strpos($method->name, $operation) !== 0) {
					continue;
				}
				$path = new self();
				$path->endpoint = $endpoint;
				$path->operation = $operation;
				$path->method = $method->name;
				$name = substr($method->name, strlen($operation));
				$name = ltrim($name, '_');
				$path->action = $name;

				/**
				 * Get the docblock of the method
				 */
				try {
					$docblock = $method->getDocComment();
					$factory  = \phpDocumentor\Reflection\DocBlockFactory::createInstance();
					$docblock = $factory->create($docblock);
					$path->summary = $docblock->getSummary();
				} catch (\Webmozart\Assert\InvalidArgumentException $e) {
					// No docblock found, we ignore this method
					continue;
				}

				/**
				 * Add parameters by docblock
				 */
				$params = $docblock->getTagsByName('param');
				foreach ($params as $param) {
					$parameter = new Parameter();
					$parameter->name = $param->getVariableName();
					$parameter->description = $param->getDescription()->getBodyTemplate();
					$parameter->type = \Skeleton\Application\Api\Media\Type::create_for_reflection_type($param->getType());
					$path->parameters[] = $parameter;
				}

				/**
				 * Add return values by docblock
				 */
				$returns = $docblock->getTagsByName('return');

				if (count($returns) > 1) {
					throw new \Exception('More than 1 return value specified in docblock for method ' . $endpoint->get_name() . '/' . $method->name);
				} elseif (count($returns) == 1) {
					$tag = array_shift($returns);
					$response = new Response();
					$response->code = 200;
					$response->description = $tag->getDescription()->getBodyTemplate();
					$media_type = \Skeleton\Application\Api\Media\Type::create_for_reflection_type($tag->getType());
					$response->content = $media_type;
					$path->response = $response;
				}

				/**
				 * Add headers by docblock
				 */
				$headers = $docblock->getTagsByName('header');
				foreach ($headers as $header) {
					$path->headers[] = $header->getDescription()->getBodyTemplate();
				}

				/**
				 * Add headers by docblock
				 */
				$securities = $docblock->getTagsByName('security');
				foreach ($securities as $security) {
					$classname = $security->getDescription()->getBodyTemplate();
					if (!class_exists($classname)) {
						throw new \Exception('Incorrect security specified in docblock for method ' . $endpoint->get_name() . '/' . $method->name);
					}
					$class = new $classname();
					if (!is_a($class, '\Skeleton\Application\Api\Security')) {
						throw new \Exception('Incorrect security specified in docblock for method ' . $endpoint->get_name() . '/' . $method->name);
					}
					$path->security[] = $class;
				}

				/**
				 * Get the method parameters, make them required
				 */
				$params = $method->getParameters();
				foreach ($params as $param) {
					try {
						$parameter = $path->get_parameter_by_name($param->name);
					} catch (\Exception $e) {
						throw new \Exception('Required parameter ' . $param->name . ' for method ' . $endpoint->get_name() . '/' . $method->name . ' is not mentioned in docblock');
					}
					$parameter->required = true;
				}

				$paths[] = $path;
			}
		}
		return $paths;
	}

}
