<?php

declare(strict_types = 1);

namespace SOFe\Pathetique;

use function strtoupper;

/**
 * A normal Windows path prefix with a disk drive, e.g. `C:\`.
 */
final class DiskPrefix implements Prefix {
	/** @var string */
	private $drive;

	public function __construct(string $drive) {
		$this->drive = $drive;
	}

	public function getDrive() : string {
		return strtoupper($this->drive);
	}

	public function toString() : string {
		return $this->drive . ":";
	}

	public function __toString() : string {
		return $this->toString();
	}
}
