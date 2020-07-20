<?php

declare(strict_types = 1);

namespace SOFe\Pathetique;

/**
 * A Windows verbatim path prefix, e.g. `\\?\cat_pics\`.
 */
final class VerbatimPrefix implements Prefix {
	/** @var string */
	private $name;

	public function __construct(string $name) {
		$this->name = $name;
	}

	public function getName() : string {
		return $this->name;
	}

	public function isVerbatim() : bool {
		return true;
	}

	public function toString() : string {
		return "\\\\?\\" . $this->name;
	}

	public function __toString() : string {
		return $this->toString();
	}
}
