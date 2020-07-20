<?php

declare(strict_types = 1);

namespace SOFe\Pathetique;

use InvalidArgumentException;
use const PHP_OS;
use function assert;
use function strtoupper;
use function substr;

/**
 * Represents a family of filesystems that interprets paths differently.
 *
 * This is an enum class. Use `===` to compare objects of this class.
 */
final class Platform {
	/** @var self|null */
	private static $windows = null;
	/** @var self|null */
	private static $unix = null;
	/** @var self|null */
	private static $current = null;

	/** @var bool */
	private $isWindows;

	private function __construct() {
	}


	/**
	 * Returns whether this platform uses DOS paths.
	 */
	public function isWindows() : bool {
		return $this->isWindows;
	}


	/**
	 * Returns the default directory separator
	 */
	public function getDirectorySeparator() : string {
		return $this->isWindows ? "\\" : "/";
	}

	/**
	 * Checks whether the string contains exactly one character,
	 * which this platform interprets as a directory separator.
	 */
	public function isDirectorySeparator(string $char, bool $isVerbatim = false) : bool {
		if($this->isWindows) {
			if($isVerbatim) {
				return $char === "\\";
			} else {
				return $char === "/" || $char === "\\";
			}
		} else {
			assert(!$isVerbatim);
			return $char === "/";
		}
	}


	/**
	 * Validates a normal path component.
	 *
	 * @throws InvalidArgumentException if the component name contains invalid characters
	 */
	public function validateComponent(string $name, bool $verbatim) : void {
		// TODO unimplemented
	}


	/**
	 * Returns "Windows" or "Unix" based on the variant.
	 *
	 * The output is human-readable and is subject to change.
	 * Use `=== Platform::unix()` or `=== Platform::windows()` on the object directly * to compare.
	 */
	public function __toString() : string {
		return $this->isWindows ? "Windows" : "Unix";
	}

	/**
	 * Checks whether this object represents the running platform.
	 *
	 * @throws PlatformMismatchException if this object is not the running platform.
	 */
	public function check() : void {
		if($this !== self::current()) {
			throw new PlatformMismatchException(self::current(), $this);
		}
	}


	/**
	 * Returns the Platform object representing a Windows platform.
	 */
	public static function windows() : self {
		if(self::$windows === null) {
			self::$windows = new self;
			self::$windows->isWindows = true;
		}
		return self::$windows;
	}

	/**
	 * Returns the Platform object representing a Unix platform.
	 */
	public static function unix() : self {
		if(self::$unix === null) {
			self::$unix = new self;
			self::$unix->isWindows = false;
		}
		return self::$unix;
	}


	/**
	 * Returns the Platform object representing the current platform.
	 */
	public static function current() : self {
		if(self::$current === null) {
			if(strtoupper(substr(PHP_OS, 0, 3)) === "WIN") {
				self::$current = self::windows();
			} else {
				self::$current = self::unix();
			}
		}
		return self::$current;
	}
}
