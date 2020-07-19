<?php

declare(strict_types = 1);

namespace SOFe\Pathetique;

interface Prefix {
	/**
	 * Returns a human-readable string for this prefix.
	 *
	 * This is different from `PrefixComponent->toString()`
	 * in that this method is only intended for human reading.
	 */
	public function toString() : string;

	public function __toString() : string;
}
