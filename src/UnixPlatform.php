<?php

declare(strict_types = 1);

namespace SOFe\Pathetique;

use InvalidArgumentException;
use function assert;
use function strlen;

final class UnixPlatform extends Platform {
	/** @var self|null */
	private static $unix = null;

	private function __construct() {
	}

	public static function getInstance() : self {
		return self::$unix = self::$unix ?? new self;
	}


	public function isWindows() : bool {
		return false;
	}

	public function getDirectorySeparator() : string {
		return "/";
	}

	public function isDirectorySeparator(string $char, bool $isVerbatim = false) : bool {
		assert(!$isVerbatim);
		return $char === "/";
	}

	public function validateComponent(string $name, bool $verbatim) : void {
		for($i = 0; $i < strlen($name); $i++) {
			if($name[$i] === "/") {
				throw new InvalidArgumentException("Forward slashes not allowed in Unix path components");
			}
			if($name[$i] === "\0") {
				throw new InvalidArgumentException("NUL bytes not allowed in Unix path components");
			}
		}
	}
}
