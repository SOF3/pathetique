<?php

declare(strict_types = 1);

namespace SOFe\Pathetique;

use function assert;
use function error_get_last;

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
}
