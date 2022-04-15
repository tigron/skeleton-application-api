<?php
/**
 * Path class
 *
 * @author Gerry Demaret <gerry@tigron.be>
 * @author Christophe Gosiau <christophe@tigron.be>
 */

namespace Skeleton\Application\Api;

use \Skeleton\Application\Api\Path\Parameter;
use \Skeleton\Application\Api\Path\Body;
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
	 * Body
	 *
	 * @access public
	 * @var Body $body
	 */
	public $body = null;

	/**
	 * Responses
	 *
	 * @access public
	 * @var Response[] $responses
	 */
	public $responses = [];

	/**
	 * Security
	 *
	 * @access public
	 * @var Security[] $security
	 */
	public $security = [];

	/**
	 * Headers
	 *
	 * @access public
	 * @var string[] $headers
	 */
	public $headers = [];

	/**
	 * Endpoint
	 *
	 * @access public
	 * @var \Skeleton\Application\Api\Endpoint $endpoint
	 */
	public $endpoint;

	/**
	 * Deprecated
	 *
	 * @access public
	 * @var boolean $deprecated
	 */
	public $deprecated = false;

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
		/**
		 * 1. Create the complete raw url for this path
		 */
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
			return '/' . $this->endpoint->_get_name();
		}
		$query = $this->endpoint->_get_name() . '?';
		foreach ($params as $key => $value) {
			$query .= $key . '=' . $value . '&';
		}
		$query = '/' . rtrim($query, '&');

		/**
		 * 2. Check for existing routes, if so rewrite the raw url
		 * If a base_uri is set, it should not be included in every endpoint,
		 * instead, a server variable is set.
		 */
		$query = \Skeleton\Core\Util::rewrite_reverse($query);
		$application = \Skeleton\Core\Application::get();
		if (isset($application->config->base_uri)) {
			$query = '/' . ltrim($query, $application->config->base_uri);
		}

		/**
		 * 3. We don't want to show $_GET parameters in specifications. Let's
		 * clean them. Important: $_GET['action'] cannot be removed.
		 */
		$parsed = parse_url($query);
		if (isset($parsed['query'])) {
			parse_str($parsed['query'], $get_variables);
			foreach ($get_variables as $key => $value) {
				if ($key == 'action') {
					continue;
				}
				unset($get_variables[$key]);
				/**
				 * If a variable is removed, we have to change its properties:
				 * - it is still required
				 * - they should appear in 'query', not in 'path'
				 */
				$parameter = $this->get_parameter_by_name($key);
				$parameter->in = 'query';
			}
			$query = $parsed['path'] . '?';
			foreach ($get_variables as $key => $value) {
				$query .= $key . '=' . $value . '&';
			}
			$query = rtrim($query, '&');
			$query = rtrim($query, '?');
		}

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
		$schema[$route][$this->operation]['tags'] = [ $this->endpoint->_get_name() ];
		$schema[$route][$this->operation]['summary'] = $this->summary;
		$schema[$route][$this->operation]['deprecated'] = $this->deprecated;

		if (count($this->parameters) > 0) {
			$schema[$route][$this->operation]['parameters'] = [];
			foreach ($this->parameters as $parameter) {
				$schema[$route][$this->operation]['parameters'][] = $parameter->get_schema();
			}
		}

		if ($this->body !== null) {
			$schema[$route][$this->operation]['requestBody'] = [
				'required' => true,
				'content' => [
					'application/json' => $this->body->get_schema(),
				],
			];
		}

		if (count($this->responses) == 0) {
			throw new \Exception('No response defined for path ' . $this->name . '/' . $this->operation);
		}

		$schema[$route][$this->operation]['responses'] = [];
		foreach ($this->responses as $response) {
			$response_schema = $response->get_schema();
			$schema[$route][$this->operation]['responses'][$response->code]['description'] = $response->description;
			if ($response_schema !== null) {
				$schema[$route][$this->operation]['responses'][$response->code]['content']['application/json'] = $response_schema;
			}

			/**
			 * If headers are specified, we add them to every response
			 * This is a limitation of not using a Response object
			 */
			if (count($this->headers) > 0) {
				foreach ($this->headers as $key => $header) {
					$schema[$route][$this->operation]['responses'][$response->code]['headers'][$header]['schema']['type'] = 'string';
				}
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
					$factory->registerTagHandler('body', '\Skeleton\Application\Api\Docblock\Tag\Handler\Body');
					$docblock = $factory->create($docblock);
					$path->summary = $docblock->getSummary();
				} catch (\Webmozart\Assert\InvalidArgumentException $e) {
					// No docblock found, we ignore this method
					continue;
				}

				/**
				 * Check if deprecated
				 */
				$deprecated = $docblock->getTagsByName('deprecated');
				if (count($deprecated) > 0) {
					$path->deprecated = true;
				}

				/**
				 * Add parameters by docblock
				 */
				$params = $docblock->getTagsByName('param');
				foreach ($params as $param) {
					$parameter = new Parameter();
					$parameter->name = $param->getVariableName();
					$parameter->description = $param->getDescription()->getBodyTemplate();
					$parameter->media_type = \Skeleton\Application\Api\Media\Type::create_for_reflection_type($param->getType());
					$path->parameters[] = $parameter;
				}

				/**
				 * Add body by docblock
				 */
				$body = $docblock->getTagsByName('body');
				if (count($body) > 1) {
					throw new \Exception('More than 1 return value specified in docblock for body');
				} elseif (count($body) == 1) {
					$tag = array_shift($body);
					$parameter = new Body();
					$parameter->name = 'body';
					$parameter->description = $tag->getDescription()->getBodyTemplate();
					$parameter->media_type = \Skeleton\Application\Api\Media\Type::create_for_reflection_type($tag->getType());
					$path->body = $parameter;
				}

				/**
				 * Add return values by docblock
				 */
				$returns = $docblock->getTagsByName('return');

				if (count($returns) > 1) {
					throw new \Exception('More than 1 return value specified in docblock for method ' . $endpoint->_get_name() . '/' . $method->name);
				} elseif (count($returns) == 1) {
					$tag = array_shift($returns);
					$response = new Response();
					$response->code = 200;
					$response->description = $tag->getDescription()->getBodyTemplate();
					$media_type = \Skeleton\Application\Api\Media\Type::create_for_reflection_type($tag->getType());
					$response->content = $media_type;
					$path->responses[] = $response;
				}
				/**
				 * If an exception can be thrown, add it as a possible response
				 */
				$exceptions = $docblock->getTagsByName('throws');

				foreach ($exceptions as $exception) {
					$classname = (string)$exception->getType()->getFqsen();
					if (!class_exists($classname)) {
						throw new \Exception('Incorrect exception specified in docblock for method ' . $endpoint->_get_name() . '/' . $method->name);
					}
					$class = new $classname();
					$response = new Response();
					$response->code = $class->getCode();
					$response->description = $class->getMessage();
					$path->responses[] = $response;
				}

				/**
				 * Add headers by docblock
				 */
				$headers = $docblock->getTagsByName('header');
				foreach ($headers as $header) {
					$path->headers[] = $header->getDescription()->getBodyTemplate();
				}

				/**
				 * Add security by docblock
				 */
				$securities = $docblock->getTagsByName('security');
				foreach ($securities as $security) {
					$classname = $security->getDescription()->getBodyTemplate();
					if (!class_exists($classname)) {
						throw new \Exception('Incorrect security specified in docblock for method ' . $endpoint->_get_name() . '/' . $method->name);
					}
					$class = new $classname();
					if (!is_a($class, '\Skeleton\Application\Api\Security')) {
						throw new \Exception('Incorrect security specified in docblock for method ' . $endpoint->_get_name() . '/' . $method->name);
					}
					$path->security[] = $class;
					$path->responses = array_merge($path->responses, $class->get_responses());
					$path->headers = array_merge($path->headers, $class->get_headers());
				}

				/**
				 * Get the method parameters, make them required
				 */
				$params = $method->getParameters();
				foreach ($params as $param) {
					try {
						$parameter = $path->get_parameter_by_name($param->name);
					} catch (\Exception $e) {
						throw new \Exception('Required parameter ' . $param->name . ' for method ' . $endpoint->_get_name() . '/' . $method->name . ' is not mentioned in docblock');
					}
					$parameter->required = true;
					$parameter->in = 'path';
				}
				$paths[] = $path;
			}
		}

		return $paths;
	}

}
