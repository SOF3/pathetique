<?php

declare(strict_types = 1);

namespace SOFe\Pathetique;

use InvalidArgumentException;
use function assert;
use function error_get_last;
use function min;
use function ord;
use function strlen;
use function strpos;
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

	/**
	 * Similar to `strpos` but matches *any* character in $charset
	 *
	 * @return int|null null if not find
	 */
	public static function strposMulti(string $string, string $charset, int $offset = 0) : ?int {
		for($i = $offset; $i < strlen($string); $i++) {
			if(strpos($charset, $i) !== false) {
				return $i;
			}
		}

		return null;
	}

	/**
	 * Parses a Windows prefix.
	 *
	 * When the function returns with a non-null value, `$index` is the length of the prefix,
	 * plus one if there is a directory separator after the prefix.
	 */
	public static function parseWindowsPrefix(string $string, int &$index) : ?Prefix {
		$index = 0;

		if(Utils::startsWith($string, "\\\\", $index)) {
			$index += 2;
			// \\

			if(Utils::startsWith($string, "?\\", $index)) {
				$index += 2;
				// \\?\

				if(Utils::startsWith($string, "UNC\\", $index)) {
					$index += 4;
					// \\?\UNC\server\share

					self::parseUnc($string, "\\", $index, $server, $share);
					return new VerbatimUncPrefix($server, $share);
				}

				// \\?\path

				if(strlen($string) - $index >= 3 && substr($string, $index + 1, 2) === ":\\") {
					$ascii = ord($string[$index]);
					if(ord("A") <= $ascii && $ascii <= ord("Z") || ord("a") <= $ascii && $ascii <= ord("z")) {
						// \\?\X:\

						$drive = $string[$index];
						$index += 3;
						return new VerbatimDiskPrefix($drive);
					}
				}

				$pos = strpos($string, "\\", $index);
				if($pos === false) {
					$path = substr($string, $index, $pos - $index);
					$index = strlen($string);
				} else {
					$path = substr($string, $index, $pos - $index);
					$index = $pos + 1;
				}
				return new VerbatimPrefix($path);
			}

			if(Utils::startsWith($string, ".\\", $index)) {
				$index += 2;
				// \\.\path
				$pos = strpos($string, "\\", $index);
				if($pos === false) {
					$path = substr($string, $index, $pos - $index);
					$index = strlen($string);
				} else {
					$path = substr($string, $index, $pos - $index);
					$index = $pos + 1;
				}
				return new DeviceNsPrefix($path);
			}

			self::parseUnc($string, "\\/", $index, $server, $share);
			return new UncPrefix($server, $share);
		}

		if(strlen($string) >= 2 && $string[1] === ":") {
			if(strlen($string) === 2 || strpos("\\/", $string[2]) !== false) {
				$ascii = ord($string[0]);
				if(ord("A") <= $ascii && $ascii <= ord("Z") || ord("a") <= $ascii && $ascii <= ord("z")) {
					$index = min(strlen($string), 3);
					return new DiskPrefix($string[0]);
				}
			}
			throw new InvalidArgumentException("The : character is not allowed in Windows path components");
		}

		return null;
	}

	/**
	 * @param string $string
	 * @param string $charset
	 * @param int $index
	 * @param string $server
	 * @param string $share
	 */
	private static function parseUnc(string $string, string $charset, int &$index, &$server, &$share) : void {
		$pos = self::strposMulti($string, $charset, $index);
		if($pos === null) {
			$server = substr($string, $index);
			$share = "";
			$index = strlen($string);
		} else {
			$server = substr($string, $index, $pos - $index);
			$index = $pos + 1;
			$pos = self::strposMulti($string, $charset, $index);
			if($pos === null) {
				$share = substr($string, $index);
				$index = strlen($string);
			} else {
				$share = substr($string, $index, $pos - $index);
				$index = $pos + 1;
			}
		}
	}

	/**
	 * @throws InvalidArgumentException
	 */
	public static function parseComponent(string $string, bool $verbatim, bool $last) : ?Component {
		if(!$verbatim) {
			if($string === ".") {
				return new CurrentDirectoryComponent;
			}

			if($string === "..") {
				return new ParentDirectoryComponent;
			}
		}

		if($string === "") {
			return $last ? new TrailingSeparatorComponent : null;
		}

		return new NormalComponent($string);
	}
}
