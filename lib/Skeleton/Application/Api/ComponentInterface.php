<?php
/**
 * Component interface
 *
 * @author Gerry Demaret <gerry@tigron.be>
 * @author Christophe Gosiau <christophe@tigron.be>
 */
namespace Skeleton\Application\Api;

interface ComponentInterface {

	public function get_openapi_media_type(): \Skeleton\Application\Api\Media\Type;
	public function get_openapi_component_name(): string;
	public function get_openapi_component_properties(): array;
	public function get_openapi_additional_properties(): ?\Skeleton\Application\Api\Media\Type;
	public function get_openapi_description(): string;
	public function get_openapi_example(): array;
	public function get_openapi_component_info(): array;
}
