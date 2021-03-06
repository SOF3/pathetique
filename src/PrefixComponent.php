<?php

declare(strict_types = 1);

namespace SOFe\Pathetique;

use function strtoupper;

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

	public function toNormalizedString() : string {
		if($this->prefix instanceof DiskPrefix || $this->prefix instanceof VerbatimDiskPrefix) {
			return strtoupper($this->toString());
		}

		return $this->toString();
	}

	public function __toString() : string {
		return $this->toString();
	}
}
