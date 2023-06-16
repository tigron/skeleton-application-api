<?php
/**
 * Openapi body generator class
 * Converts the body into known components
 *
 * @author Christophe Gosiau <christophe@tigron.be>
 * @author Gerry Demaret <gerry@tigron.be>
 */

namespace Skeleton\Application\Api\Body;

class Generator {

	/**
	 * Media type
	 *
	 * @var \Skeleton\Application\Api\Media\Type $media_type
	 * @access private
	 */
	private $media_type;

	/**
	 * The body
	 *
	 * @var mixed $body
	 * @access private
	 */	
	private $body;

	/**
	 * The datapath
	 *
	 * @access public
	 * @var string $datapath
	 */
	public $datapath = '.body';

	/**
	 * Validation errors
	 *
	 * @access private
	 * @var array $errors
	 */
	private $errors = [];	

	/**
	 * Constructor
	 *
	 * @access public
	 * @param \Skeleton\Application\Api\Media\Type $media_type
	 */
	public function __construct(\Skeleton\Application\Api\Media\Type $media_type) {
		$this->media_type = $media_type;
	}

	/**
	 * Set the body
	 *
	 * @access public
	 * @param mixed $body
	 */
	public function set_body($body) {
		$this->body = $body;
	}

	/**
	 * Generate the body
	 *
	 * @access public
	 * @return mixed $body_object
	 */
	public function generate() {
		if ($this->media_type->nullable and $this->body === null) {
			return null;
		}

		$type = $this->media_type->type;
		if (is_callable( [$this, 'generate_' . $type])) {
			return call_user_func_array( [$this, 'generate_' . $type], []);
		}
	}	
	
	/**
	 * Generate a string
	 *
	 * @access public
	 * @return string $string
	 */
	private function generate_string() {
		return $this->body;
	}

	/**
	 * Generate an array
	 *
	 * @access private
	 * @return array $array
	 */
	private function generate_array() {
		$response = [];
		foreach ($this->body as $item) {
			$value_type = $this->media_type->value_type;
			$generator = new self($value_type);
			$generator->set_body($item);
			$response[] = $generator->generate();
		}
		return $response;
	}	

	/**
	 * Generate an object
	 *
	 * @access private
	 * @return Object $object
	 */
	private function generate_object() {
		$classname = $this->media_type->value_type;
		$class = new $classname();
		foreach ($class->get_openapi_component_properties() as $field => $media_type) {
			// If property is required but not provided: error
			if ($media_type->required and !array_key_exists($field, $this->body)) {
				continue;
			}
			// if property is not provided and not required: continue
			if (!array_key_exists($field, $this->body)) {
				continue;
			}
			// From here, the property is provided
			if (!$media_type->nullable and $this->body[$field] === null) {
				continue;
			}

			$property_generator = new self($media_type);
			$property_generator->set_body( $this->body[$field] );
			$property_generator->datapath .= '.' . $field;
			$class->$field = $property_generator->generate();
		}
		return $class;
	}

	/**
	 * Validate the body
	 *
	 * @access public
	 * @return array $errors
	 */
	public function validate() {
		$type = $this->media_type->type;
		if (is_callable( [$this, 'validate_' . $type])) {
			call_user_func_array( [$this, 'validate_' . $type], []);
		}	
		return $this->errors;	
	}

	/**
	 * Validate an array
	 *
	 * @access private
	 */
	private function validate_array() {
		$errors = [];
		foreach ($this->body as $item) {
			$value_type = $this->media_type->value_type;
			$generator = new self($value_type);
			$generator->set_body($item);
			$errors = array_merge($errors, $generator->validate());
		}
		$this->errors = array_merge($this->errors, $errors);
	}

	/**
	 * Validate a string
	 *
	 * @access private
	 */
	private function validate_string() {
		if (is_string($this->body)) {
			return;
		}	
		$error = [
			'keyword' => 'type',
			'dataPath' => $this->datapath,
			'params' => [
				'type' => $this->media_type->type
			],
			'message' => "should be " . $this->media_type->type
		];
		$this->errors[] = $error;		
	}

	/**
	 * Validate integer
	 *
	 * @access private
	 */
	private function validate_integer() {
		if (is_int($this->body)) {
			return;
		}	
		$error = [
			'keyword' => 'type',
			'dataPath' => $this->datapath,
			'params' => [
				'type' => $this->media_type->type
			],
			'message' => "should be " . $this->media_type->type
		];
		$this->errors[] = $error;		
	}	

	/**
	 * Validate object
	 *
	 * @access private
	 */
	private function validate_object() {
		$classname = $this->media_type->value_type;
		$class = new $classname();
		foreach ($class->get_openapi_component_properties() as $field => $media_type) {
			// If property is required but not provided: error
			if ($media_type->required and !array_key_exists($field, $this->body)) {
				$error = [
					'keyword' => 'required',
					'dataPath' => $this->datapath,
					'params' => [
						'missingProperty' => $field
					],
					'message' => "should have required property '" . $field . "'"
				];
				$this->errors[] = $error;
				continue;
			}
			// if property is not provided and not required: continue
			if (!array_key_exists($field, $this->body)) {
				continue;
			}
			// From here, the property is provided
			if (!$media_type->nullable and $this->body[$field] === null) {
				$error = [
					'keyword' => 'nullable',
					'dataPath' => $this->datapath,
					'params' => [
						'missingProperty' => $field
					],
					'message' => "should have required property '" . $field . "'"
				];
				$this->errors[] = $error;
				continue;
			}

			$property_generator = new self($media_type);
			$property_generator->set_body( $this->body[$field] );
			$property_generator->datapath = $this->datapath . '.' . $field;
			$this->errors = array_merge($this->errors, $property_generator->validate());
		}
	}	

}
