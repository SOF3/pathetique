<?php

declare(strict_types = 1);

namespace SOFe\Pathetique;

use function ord;
use function strlen;
use function strtoupper;
use function substr;

abstract class Prefix {
	public const VERBATIM = 0;
	public const VERBATIM_UNC = 1;
	public const VERBATIM_DISK = 2;
	public const DEVICE_NS = 3;
	public const UNC = 4;
	public const DISK = 5;

	abstract public function getFullPrefix() : string;

	abstract public function getLength() : int;

	abstract public function isVerbatim() : bool;

	public static function parse(string $string) : ?Prefix {
		$start = 0;

		if(Utils::startsWith($string, "\\\\", $start)) {
			$start += 2;

			if(Utils::startsWith($string, "?\\", $start)) {
				$start += 2;

				if(Utils::startsWith($string, "UNC\\", $start)) {
					$start += 4;

					$slash = Utils::findSlash($string, $start, false);
					if($slash !== null) {
						$server = substr($string, $start, $slash - $start);
						$start = $slash + 1;
						$slash = Utils::findSlash($string, $start, false);
						$share = substr($string, $start, ($slash ?? strlen($string)) - $start);
					} else {
						$server = substr($string, $start);
						$share = "";
					}
					return new VerbatimUncPrefix($server, $share);
				}

				$index = Utils::findSlash($string, $start, false);

				if($index === $start + 2 && $string[$start + 1] === ":") {
					$c = ord($string[$start]);
					if(ord("A") <= $c && $c <= ord("Z") || ord("a") <= $c && $c <= ord("z")) {
						return new VerbatimDiskPrefix(strtoupper($string[$start]));
					}
				}

				return new VerbatimPrefix(substr($string, $start, ($index ?? strlen($string)) - $start));
			}

			if(Utils::startsWith($string, ".\\", $start)) {
				$start += 2;

				$index = Utils::findSlash($string, $start, false);
				return new DeviceNsPrefix(substr($string, $start, ($index ?? strlen($string)) - $start));
			}

			$slash = Utils::findSlash($string, $start, true);
			if($slash !== null) {
				$server = substr($string, $start, $slash - $start);
				$start = $slash + 1;
				$slash = Utils::findSlash($string, $start, true);
				$share = substr($string, $start, ($slash ?? strlen($string)) - $start);
				return new UncPrefix($server, $share);
			}
		}

		if(strlen($string) >= 2 && $string[1] === ":") {
			$c = ord($string[0]);
			if(ord("A") <= $c && $c <= ord("Z") || ord("a") <= $c && $c <= ord("z")) {
				return new DiskPrefix(strtoupper($string[0]));
			}
		}

		return null;
	}
}
