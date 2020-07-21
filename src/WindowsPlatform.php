<?php

declare(strict_types = 1);

namespace SOFe\Pathetique;

use InvalidArgumentException;
use function ord;
use function strlen;
use function strpos;

final class WindowsPlatform extends Platform {
	/** @var self|null */
	private static $unix = null;

	private function __construct() {
	}

	public static function getInstance() : self {
		return self::$unix = self::$unix ?? new self;
	}


	public function isWindows() : bool {
		return true;
	}

	public function getDirectorySeparator() : string {
		return "\\";
	}

	public function isDirectorySeparator(string $char, bool $isVerbatim = false) : bool {
		if($isVerbatim) {
			return $char === "\\";
		} else {
			return $char === "/" || $char === "\\";
		}
	}

	public function validateComponent(string $name, bool $verbatim) : void {
		for($i = 0; $i < strlen($name); $i++) {
			if(ord($name[$i]) < 32) {
				throw new InvalidArgumentException("Non-printable ASCII characters not allowed in Windows path components");
			}
			if(strpos("<>:\"/\\|?*", $name[$i]) !== false) {
				throw new InvalidArgumentException("The {$name[$i]} character is not allowed in Windows path components");
			}
		}

		if(!$verbatim) {
			if($name[strlen($name) - 1] === ".") {
				throw new InvalidArgumentException("Windows path components must not end with a dot");
			}
		}
	}
}
