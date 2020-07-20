<?php

declare(strict_types = 1);

namespace SOFe\Pathetique;

use function strtoupper;

/**
 * A Windows verbatim path disk prefix, e.g. `\\?\C:\`.
 */
final class VerbatimDiskPrefix implements Prefix {
	/** @var string */
	private $disk;

	public function __construct(string $disk) {
		$this->disk = $disk;
	}

	public function getDisk() : string {
		return strtoupper($this->disk);
	}

	public function toString() : string {
		return "\\\\?\\" . $this->disk . ":";
	}

	public function __toString() : string {
		return $this->toString();
	}
}
