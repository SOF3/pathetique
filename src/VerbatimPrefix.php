<?php

declare(strict_types = 1);

namespace SOFe\Pathetique;

use function strlen;

final class VerbatimPrefix extends Prefix {
	/** @var string */
	private $name;

	public function __construct(string $name) {
		$this->name = $name;
	}

	public function getName() : string {
		return $this->name;
	}

	public function getFullPrefix() : string {
		return "\\\\?\\{$this->name}";
	}

	public function getLength() : int {
		return 4 + strlen($this->name);
	}

	public function isVerbatim() : bool {
		return true;
	}
}
