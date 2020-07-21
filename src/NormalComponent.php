<?php

declare(strict_types = 1);

namespace SOFe\Pathetique;

/**
 * A normal path component.
 */
final class NormalComponent implements Component {
	/** @var string */
	private $name;

	public function __construct(string $name) {
		$this->name = $name;
	}

	public function getName() : string {
		return $this->name;
	}

	public function toString() : string {
		return $this->name;
	}

	public function toNormalizedString() : ?string {
		return $this->name;
	}

	public function __toString() : string {
		return $this->toString();
	}
}
