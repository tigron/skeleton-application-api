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
	 * Generate an integer
	 *
	 * @access public
	 * @return int $integer
	 */
	private function generate_integer() {
		return $this->body;
	}

	/**
	 * Generate an number
	 *
	 * @access public
	 * @return int $number
	 */
	private function generate_number() {
		return $this->body;
	}

	/**
	 * Generate a boolean
	 *
	 * @access public
	 * @return bool $boolean
	 */
	private function generate_boolean() {
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
	 * Generate mixed
	 *
	 * @access private
	 * @return mixed $value
	 */
	private function generate_mixed() {
		$criteria = $this->media_type->criteria;

		if (is_callable( [$this, 'generate_mixed_' . $criteria])) {
			return call_user_func_array( [$this, 'generate_mixed_' . $criteria], []);
		}
	}

	/**
	 * Generate mixed anyof
	 *
	 * @access private
	 */
	private function generate_mixed_anyof() {
		foreach ($this->media_type->media_types as $media_type) {
			$generator = new self($media_type);
			$generator->set_body($this->body);
			$errors = $generator->validate();
			if (count($errors) === 0) {
				return $generator->generate();
			}
		}
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
		$fields = [];
		foreach ($class->get_openapi_component_properties() as $field => $media_type) {
			$fields[] = $field;

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
			$property_generator->datapath = $this->datapath . '.' . $field;
			$class->$field = $property_generator->generate();
		}

		$media_type = $class->get_openapi_additional_properties();
		if ($media_type === null) {
			return $class;
		}

		foreach ($this->body as $field => $value) {
			if (in_array($field, $fields)) {
				// ignore fields that are already added
				continue;
			}
			$property_generator = new self($media_type);
			$property_generator->set_body( $this->body[$field] );
			$property_generator->datapath .= $this->datapath . '.' . $field;
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

		if (
			(
				isset($this->media_type->nullable)
				&& $this->media_type->nullable
				&& $this->body === null
			)
			||
			(
				!isset($this->media_type->nullable)
				&& $this->body === null
			)
		) {
			// nullable values are allowed, stop validating
			return [];
		}

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
	private function validate_mixed() {
		$criteria = $this->media_type->criteria;

		if (is_callable( [$this, 'validate_mixed_' . $criteria])) {
			return call_user_func_array( [$this, 'validate_mixed_' . $criteria], []);
		}
	}

	/**
	 * Validate mixed_anyof
	 *
	 * @access private
	 */
	private function validate_mixed_anyof() {
		$anyof = [];
		foreach ($this->media_type->media_types as $media_type) {
			if ($media_type->type === 'object') {
				$component = new $media_type->value_type;
				$anyof[] = $media_type->type . ' ' . $component->get_openapi_component_name();
			} else {
				$anyof[] = $media_type->type;
			}
			$generator = new self($media_type);
			$generator->set_body($this->body);
			$generator->datapath = $this->datapath;
			$errors = $generator->validate();

			if (count($errors) === 0) {
				$this->errors = [];
				return;
			}

			$this->errors = $errors;
		}
		if (count($this->errors) > 0) {
			$error = [
				'keyword' => 'type',
				'dataPath' => $this->datapath,
				'params' => [
					'type' => $this->media_type->type
				],
				'message' => "should be " . $this->media_type->type . " with anyof " . implode(', ', $anyof)
			];
			$this->errors = [ $error ];
		}
	}

	/**
	 * Validate an array
	 *
	 * @access private
	 */
	private function validate_array() {
		$errors = [];

		if (!is_array($this->body)) {
			$error = [
				'keyword' => 'type',
				'dataPath' => $this->datapath,
				'params' => [
					'type' => $this->media_type->type
				],
				'message' => "should be " . $this->media_type->type
			];
			$this->errors[] = $error;
			return;
		}

		if (array_values($this->body) !== $this->body) {
			$error = [
				'keyword' => 'type',
				'dataPath' => $this->datapath,
				'params' => [
					'type' => $this->media_type->type
				],
				'message' => "should be " . $this->media_type->type
			];
			$this->errors[] = $error;
			return;
		}

		foreach ($this->body as $key => $item) {
			$value_type = $this->media_type->value_type;
			$generator = new self($value_type);
			$generator->set_body($item);
			$generator->datapath = $this->datapath . '[' . $key . ']';
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
		$valid = true;
		if (!is_string($this->body)) {
			$valid = false;
		}

		if ($valid && is_array($this->media_type->enum)) {
			if (!in_array($this->body, $this->media_type->enum)) {
				$valid = false;
			}
		}

		if ($valid === false) {
			$error = [
				'keyword' => 'type',
				'dataPath' => $this->datapath,
				'params' => [
					'type' => $this->media_type->type
				],
				'message' => "should be " . $this->media_type->type
			];
			if (is_array($this->media_type->enum)) {
				$error['message'] .= ' with any of the following values: ' . implode(', ', $this->media_type->enum);
			}
			$this->errors[] = $error;
		}
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
	 * Validate number
	 *
	 * @access private
	 */
	private function validate_number() {
		if (is_numeric($this->body)) {
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
	 * Validate boolean
	 *
	 * @access private
	 */
	private function validate_boolean() {
		if (is_bool($this->body)) {
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
		$fields = [];

		foreach ($class->get_openapi_component_properties() as $field => $media_type) {
			$fields[] = $field;
			// If property is required but not provided: error
			if ($media_type->required and !array_key_exists($field, $this->body)) {
				$error = [
					'keyword' => 'required',
					'dataPath' => $this->datapath . '.' . $field,
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
			if (isset($media_type->nullable) and !$media_type->nullable and $this->body[$field] === null) {
				$error = [
					'keyword' => 'nullable',
					'dataPath' => $this->datapath . '.' . $field,
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

		$media_type = $class->get_openapi_additional_properties();
		if ($media_type === null) {
			return $this->errors;
		}

		foreach ($this->body as $field => $value) {
			if (in_array($field, $fields)) {
				// ignore fields that are already added
				continue;
			}
			$property_generator = new self($media_type);
			$property_generator->set_body( $this->body[$field] );
			$property_generator->datapath = $this->datapath . '.' . $field;
			$this->errors = array_merge($this->errors, $property_generator->validate());
		}
		return $this->errors;
	}

}
