<?php

declare(strict_types = 1);

namespace SOFe\Pathetique;

/**
 * The prefix component in absolute paths.
 */
final class PrefixComponent implements Component {
	/** @var Prefix */
	private $prefix;

	public function __construct(Prefix $prefix) {
		$this->prefix = $prefix;
	}

	public function getPrefix() : Prefix {
		return $this->prefix;
	}

	public function toString() : string {
		return $this->prefix->toString();
	}

	public function __toString() : string {
		return $this->toString();
	}
}
