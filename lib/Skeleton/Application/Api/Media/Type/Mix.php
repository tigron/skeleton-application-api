<?php
/**
 * Type class
 *
 * @author Gerry Demaret <gerry@tigron.be>
 * @author Christophe Gosiau <christophe@tigron.be>
 */

namespace Skeleton\Application\Api\Media\Type;

class Mix extends \Skeleton\Application\Api\Media\Type {

	/**
	 * Valuetype
	 * The object in the array or the classname in case of type=object
	 *
	 * @access public
	 * @var string $value_type
	 */
	public $media_types = [];

	/**
	 * Criteria
	 *
	 * @access public
	 * @var string $criteria
	 */
	public $criteria = 'anyOf';

	/**
	 * Constructor
	 *
	 * @access public
	 */
	public function __construct() {
		$this->type = 'mixed';
	}

	/**
	 * Validate
	 *
	 * @access public
	 */
	public function validate() {
	}

	/**
	 * Add media type
	 *
	 * @access public
	 * @param \Skeleton\Application\Api\Media\Type
	 */
	public function add_media_type(\Skeleton\Application\Api\Media\Type $media_type) {
		$this->media_types[] = $media_type;
	}

	/**
	 * Get schema
	 *
	 * @access public
	 * @return array $schema
	 */
	public function get_schema($object_reference = true) {
		$schema = [];
		$schema[$this->criteria] = [];
		foreach ($this->media_types as $media_type) {
			$schema[$this->criteria][] = $media_type->get_schema();
		}
		return $schema;
	}
}
