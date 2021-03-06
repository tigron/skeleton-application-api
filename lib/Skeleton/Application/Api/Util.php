<?php
namespace Skeleton\Application\Api;

/**
 * Util class
 *
 */
class Util {

	/**
	 * Recursive glob
	 *
	 * @access public
	 * @return array $files
	 */
	public static function rglob($pattern, $flags = 0) {
		$files = glob($pattern, $flags);
		foreach (glob(dirname($pattern).'/*', GLOB_ONLYDIR|GLOB_NOSORT) as $dir) {
			$files = array_merge($files, self::rglob($dir . '/' . basename($pattern), $flags));
		}
		return $files;
	}
}