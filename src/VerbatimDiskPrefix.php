<?php

declare(strict_types = 1);

namespace SOFe\Pathetique;

use function strlen;

final class VerbatimDiskPrefix extends Prefix {
	/** @var string */
	private $drive;

	public function __construct(string $drive) {
		$this->drive = $drive;
	}

	public function getDrive() : string {
		return $this->drive;
	}

	public function getFullPrefix() : string {
		return "\\\\?\\{$this->drive}:";
	}

	public function getLength() : int {
		return 4 + 1 + 1;
	}

	public function isVerbatim() : bool {
		return true;
	}
}