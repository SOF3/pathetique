<?php

declare(strict_types = 1);

namespace SOFe\Pathetique;

use const DIRECTORY_SEPARATOR;
use const PHP_OS_FAMILY;
use function min;
use function strlen;
use function strpos;
use function substr;

/**
 * @internal This class contains internal utility functions.
 * This is not part of the public API.
 */
final class Utils {
	private function __construct() {
	}

	public static function startsWith(string $string, string $prefix, int $offset = 0) : bool {
		return strlen($string) >= $offset + strlen($prefix) && substr($string, $offset, strlen($prefix)) === $prefix;
	}

	public static function findSlash(string $string, int $offset, bool $includeSlash) : ?int {
		$slashFs = strpos($string, DIRECTORY_SEPARATOR, $offset);
		$slashBs = $includeSlash ? strpos($string, "/", $offset) : false;

		if($slashFs !== false && $slashBs !== false) {
			return min($slashFs, $slashBs);
		}
		if($slashFs !== false) {
			return $slashFs;
		}
		if($slashBs !== false) {
			return $slashBs;
		}
		return null;
	}

	public static function isWindows() : bool {
		return PHP_OS_FAMILY === "Windows";
	}

	public static function isSeparator(string $char) : bool {
		if($char === "/") {
			return true;
		}
		if(Utils::isWindows() && $char === "\\") {
			return true;
		}

		return false;
	}
}
