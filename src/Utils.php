<?php

declare(strict_types = 1);

namespace SOFe\Pathetique;

use InvalidArgumentException;
use function assert;
use function error_get_last;
use function strlen;
use function substr;

/**
 * @internal This class is internal and semver-exempt.
 * Do NOT use outside this library.
 */
final class Utils {
	/**
	 * Throws an IOException with the corresponding error message
	 * if `$value` is false.
	 *
	 * Call IO methods with `@` inside the param list,
	 * e.g. `Utils::throwIo(@realpath($path))`.
	 *
	 * @template T
	 *
	 * @param mixed|bool $value
	 * @phpstan-param T|bool $value
	 *
	 * @return mixed
	 * @phpstan-return T
	 *
	 * @throws IOException
	 */
	public static function throwIo($value) {
		if($value === false) {
			$error = error_get_last();
			$message = $error["message"] ?? "unknown IO error";
			throw new IOException($message);
		}
		assert($value !== true);
		/** @phpstan-var T $value */
		return $value;
	}

	/**
	 * Returns whether $string starts with $prefix.
	 *
	 * @param string $string the full string
	 * @param string $prefix the prefix string
	 * @param int $offset the offset of string to start matching from
	 */
	public static function startsWith(string $string, string $prefix, int $offset = 0) : bool {
		return substr($string, $offset, strlen($prefix)) === $prefix;
	}

	public static function parseWindowsPrefix(string $string, int &$index) : ?Prefix {
		$index = 0;

		if(Utils::startsWith($string, "\\\\", $index)) {
			$index += 2;
			if(Utils::startsWith($string, "?\\", $index)) {
				$index += 2;
				if(Utils::startsWith($string, "UNC\\", $index)) {
					$index += 4;
					// TODO unimplemented
				}
				// TODO unimplemented
			}
			// TODO unimplemented
		}

		// TODO unimplemented
	}

	/**
	 * @throws InvalidArgumentException
	 */
	public static function parseComponent(string $string, bool $verbatim) : Component {
		// TODO unimplemented
	}
}
